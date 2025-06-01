<?php
/**
 * Gestion de la file d'attente des emails en échec
 * 
 * @package CalendrierRdv
 * @version 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

class CalRdv_Email_Queue {
    /**
     * Nom de la table dans la base de données
     * 
     * @var string
     */
    private $table_name;
    
    /**
     * Instance de la classe
     * 
     * @var CalRdv_Email_Queue|null
     */
    private static $instance = null;
    
    /**
     * Constructeur privé (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'rdv_email_failures';
    }
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return CalRdv_Email_Queue
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ajoute un email en échec à la file d'attente
     * 
     * @param array $data {
     *     Données de l'email en échec
     *     
     *     @type string   $recipient_email   Adresse email du destinataire
     *     @type string   $recipient_name    Nom du destinataire
     *     @type string   $subject           Objet de l'email
     *     @type string   $error_code        Code d'erreur
     *     @type string   $error_message     Message d'erreur détaillé
     *     @type array    $email_data        Données de l'email (sérialisées)
     *     @type int      $max_retries       Nombre maximum de tentatives (défaut: 3)
     *     @type string   $scheduled_at      Date de programmation (format MySQL)
     * }
     * @return int|false ID de l'entrée insérée ou false en cas d'échec
     */
    public function add_failed_email($data) {
        global $wpdb;
        
        // Valeurs par défaut
        $defaults = [
            'recipient_name' => '',
            'max_retries' => 3,
            'status' => 'pending',
            'scheduled_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
        ];
        
        // Fusionner avec les données fournies
        $data = wp_parse_args($data, $defaults);
        
        // Nettoyer et valider les données
        $data = $this->sanitize_email_data($data);
        
        // Insérer dans la base de données
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('Erreur lors de l\'ajout de l\'email en échec : ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Tente de renvoyer les emails en échec
     * 
     * @param int $limit Nombre maximum d'emails à traiter (par défaut: 10)
     * @return array Résultats des tentatives
     */
    public function process_queue($limit = 10) {
        global $wpdb;
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];
        
        // Récupérer les emails à traiter
        $emails = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status IN ('pending', 'retrying') 
                AND (next_retry IS NULL OR next_retry <= %s) 
                ORDER BY created_at ASC 
                LIMIT %d",
                current_time('mysql'),
                $limit
            ),
            ARRAY_A
        );
        
        if (empty($emails)) {
            return $results;
        }
        
        // Traiter chaque email
        foreach ($emails as $email) {
            $result = $this->retry_email($email);
            $results['details'][] = $result;
            
            if ($result['success']) {
                $results['success']++;
            } elseif ($result['status'] === 'failed' && $result['retry_count'] >= $email['max_retries']) {
                $results['failed']++;
            } else {
                $results['skipped']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Tente de renvoyer un email en échec
     * 
     * @param array $email Données de l'email
     * @return array Résultat de la tentative
     */
    private function retry_email($email) {
        global $wpdb;
        
        $email_id = $email['id'];
        $retry_count = (int) $email['retry_count'] + 1;
        $max_retries = (int) $email['max_retries'];
        
        // Mettre à jour le statut
        $update_data = [
            'status' => 'retrying',
            'retry_count' => $retry_count,
            'last_attempt' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $email_id],
            ['%s', '%d', '%s', '%s'],
            ['%d']
        );
        
        // Désérialiser les données de l'email
        $email_data = maybe_unserialize($email['email_data']);
        
        // Tenter d'envoyer l'email
        $result = calrdv_envoyer_email_confirmation(
            $email['recipient_email'],
            $email['recipient_name'],
            $email_data
        );
        
        // Traiter le résultat
        if ($result['success']) {
            // Succès
            $status = 'sent';
            $message = 'Email envoyé avec succès après ' . $retry_count . ' tentative(s)';
        } elseif ($retry_count >= $max_retries) {
            // Échec définitif
            $status = 'failed';
            $message = 'Échec après ' . $retry_count . ' tentatives : ' . ($result['message'] ?? 'Erreur inconnue');
        } else {
            // Nouvel échec, on réessaiera plus tard
            $status = 'pending';
            $next_retry = date('Y-m-d H:i:s', time() + ($this->get_retry_delay($retry_count) * 60));
            $message = 'Nouvel échec, prochaine tentative à ' . $next_retry;
        }
        
        // Mettre à jour le statut
        $update_data = [
            'status' => $status,
            'error_message' => $result['message'] ?? null,
            'error_code' => $result['code'] ?? 'unknown_error',
            'updated_at' => current_time('mysql')
        ];
        
        if ($status === 'pending') {
            $update_data['next_retry'] = $next_retry;
        } else {
            $update_data['next_retry'] = null;
        }
        
        $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $email_id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        return [
            'id' => $email_id,
            'email' => $email['recipient_email'],
            'status' => $status,
            'retry_count' => $retry_count,
            'message' => $message,
            'success' => ($status === 'sent')
        ];
    }
    
    /**
     * Calcule le délai avant une nouvelle tentative (en minutes)
     * 
     * @param int $attempt_num Numéro de la tentative (commence à 1)
     * @return int Délai en minutes
     */
    private function get_retry_delay($attempt_num) {
        // Stratégie de backoff exponentiel avec facteur aléatoire
        $base_delay = pow(2, $attempt_num - 1) * 5; // 5, 10, 20, 40, 80, ... minutes
        $jitter = rand(1, 10); // Ajouter un peu d'aléatoire
        
        // Ne pas dépasser 1440 minutes (24h)
        return min($base_delay + $jitter, 1440);
    }
    
    /**
     * Nettoie et valide les données d'email
     * 
     * @param array $data Données à nettoyer
     * @return array Données nettoyées
     */
    private function sanitize_email_data($data) {
        // Nettoyer les champs texte
        $data['recipient_email'] = sanitize_email($data['recipient_email']);
        $data['recipient_name'] = sanitize_text_field($data['recipient_name']);
        $data['subject'] = sanitize_text_field($data['subject']);
        $data['error_code'] = sanitize_text_field($data['error_code']);
        $data['error_message'] = sanitize_textarea_field($data['error_message']);
        
        // S'assurer que les données de l'email sont sérialisées
        if (is_array($data['email_data'])) {
            $data['email_data'] = maybe_serialize($data['email_data']);
        }
        
        // Valider les valeurs numériques
        $data['max_retries'] = max(1, (int) $data['max_retries']);
        
        // Formater les dates
        foreach (['scheduled_at', 'last_attempt', 'next_retry'] as $date_field) {
            if (!empty($data[$date_field]) && !is_string($data[$date_field])) {
                $data[$date_field] = date('Y-m-d H:i:s', strtotime($data[$date_field]));
            }
        }
        
        return $data;
    }
    
    /**
     * Supprime les anciens échecs (nettoyage)
     * 
     * @param int $days_old Nombre de jours à conserver (défaut: 30)
     * @return int|false Nombre de lignes supprimées ou false en cas d'erreur
     */
    public function cleanup_old_failures($days_old = 30) {
        global $wpdb;
        
        $date = date('Y-m-d H:i:s', strtotime("-$days_old days"));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE (status = 'sent' OR status = 'failed') 
                AND created_at < %s",
                $date
            )
        );
    }
}

/**
 * Fonction utilitaire pour accéder facilement à la file d'attente
 * 
 * @return CalRdv_Email_Queue
 */
function calrdv_email_queue() {
    return CalRdv_Email_Queue::get_instance();
}
