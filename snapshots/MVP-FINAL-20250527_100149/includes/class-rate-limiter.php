<?php
/**
 * Gestion de la limitation de taux (Rate Limiting)
 * 
 * @package CalendrierRdv
 * @version 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

class CalRdv_Rate_Limiter {
    /**
     * @var string Préfixe pour les options
     */
    private $prefix = 'calrdv_rate_limit_';

    /**
     * @var array Configuration par défaut
     */
    private $default_config = [
        'max_attempts' => 5,           // Nombre maximum de tentatives
        'time_window' => HOUR_IN_SECONDS, // Fenêtre de temps en secondes (1 heure par défaut)
        'ban_time' => DAY_IN_SECONDS,    // Durée du bannissement en secondes (24 heures)
    ];


    /**
     * Vérifie si une adresse IP est autorisée à effectuer une action
     *
     * @param string $action Action à vérifier (ex: 'add_appointment')
     * @param string $ip Adresse IP (optionnelle, utilise l'IP actuelle par défaut)
     * @return array|true Retourne true si autorisé, sinon un tableau d'erreur
     */
    public function is_allowed($action, $ip = null) {
        global $wpdb;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true; // Désactiver en mode débogage
        }

        $ip = $this->get_client_ip($ip);
        $now = current_time('timestamp');
        $config = $this->get_config($action);
        
        // Vérifier si l'IP est bannie
        $ban_key = $this->prefix . 'ban_' . md5($action . $ip);
        $ban_until = get_transient($ban_key);
        
        if ($ban_until !== false && $ban_until > $now) {
            return [
                'allowed' => false,
                'message' => __('Trop de tentatives. Veuillez réessayer plus tard.', 'calendrier-rdv'),
                'retry_after' => $ban_until - $now,
                'code' => 'rate_limit_exceeded'
            ];
        }
        
        // Récupérer l'historique des tentatives
        $history_key = $this->prefix . 'history_' . md5($action . $ip);
        $history = get_transient($history_key);
        
        if ($history === false) {
            $history = [];
        }
        
        // Supprimer les tentatives trop anciennes
        $history = array_filter($history, function($timestamp) use ($now, $config) {
            return ($now - $timestamp) < $config['time_window'];
        });
        
        // Vérifier si le nombre maximum de tentatives est atteint
        if (count($history) >= $config['max_attempts']) {
            // Bannir temporairement
            set_transient($ban_key, $now + $config['ban_time'], $config['ban_time']);
            
            // Journaliser la tentative de bannissement
            if (function_exists('calrdv_log_security')) {
                calrdv_log_security('rate_limit_ban', [
                    'ip' => $ip,
                    'action' => $action,
                    'attempts' => count($history),
                    'ban_until' => $now + $config['ban_time']
                ]);
            }
            
            return [
                'allowed' => false,
                'message' => sprintf(
                    __('Trop de tentatives. Veuillez réessayer après %s.', 'calendrier-rdv'),
                    human_time_diff($now, $now + $config['ban_time'])
                ),
                'retry_after' => $config['ban_time'],
                'code' => 'too_many_attempts'
            ];
        }
        
        // Ajouter la tentative actuelle
        $history[] = $now;
        set_transient($history_key, $history, $config['time_window']);
        
        return true;
    }
    
    /**
     * Réinitialise le compteur pour une IP et une action
     * 
     * @param string $action
     * @param string $ip
     * @return bool
     */
    public function reset($action, $ip = null) {
        $ip = $this->get_client_ip($ip);
        $history_key = $this->prefix . 'history_' . md5($action . $ip);
        $ban_key = $this->prefix . 'ban_' . md5($action . $ip);
        
        delete_transient($history_key);
        delete_transient($ban_key);
        
        return true;
    }
    
    /**
     * Récupère l'adresse IP du client
     * 
     * @param string $ip IP spécifique (pour les tests)
     * @return string
     */
    private function get_client_ip($ip = null) {
        if ($ip !== null) {
            return $ip;
        }
        
        $ip = '';
        
        // En cas de proxy
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        // Pour les chaînes d'IP (cas de proxy)
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }
        
        // Validation de l'adresse IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }
        
        return $ip;
    }
    
    /**
     * Récupère la configuration pour une action
     * 
     * @param string $action
     * @return array
     */
    private function get_config($action) {
        $config = $this->default_config;
        
        // Personnalisation par action
        switch ($action) {
            case 'add_rdv_event':
                $config['max_attempts'] = 10; // 10 tentatives par heure pour l'ajout de RDV
                break;
            case 'login':
                $config['max_attempts'] = 5;  // 5 tentatives de connexion
                $config['time_window'] = 15 * MINUTE_IN_SECONDS; // Fenêtre de 15 minutes
                break;
        }
        
        return apply_filters('calrdv_rate_limit_config', $config, $action);
    }
}

// Initialisation du rate limiter
function calrdv_get_rate_limiter() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new CalRdv_Rate_Limiter();
    }
    
    return $instance;
}
