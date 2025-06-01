<?php
/**
 * Optimiseur de requêtes pour le plugin Calendrier RDV
 */
class CalRdv_Query_Optimizer {
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Instance du gestionnaire de cache
     */
    private $cache;
    
    /**
     * Constructeur privé (singleton)
     */
    private function __construct() {
        $this->cache = CalRdv_Cache_Manager::get_instance();
    }
    
    /**
     * Récupère l'instance unique
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Exécute une requête avec mise en cache
     */
    public function get_results($query, $args = array(), $cache_key = '', $expiration = HOUR_IN_SECONDS) {
        global $wpdb;
        
        // Générer une clé de cache si non fournie
        if (empty($cache_key)) {
            $cache_key = md5($query . serialize($args));
        }
        
        // Essayer de récupérer depuis le cache
        $cached = $this->cache->get($cache_key, 'query_results');
        if (false !== $cached) {
            return $cached;
        }
        
        // Exécuter la requête
        $results = $wpdb->get_results($wpdb->prepare($query, $args), ARRAY_A);
        
        // Mettre en cache les résultats
        $this->cache->set($cache_key, $results, $expiration, 'query_results');
        
        return $results;
    }
    
    /**
     * Récupère une seule ligne avec mise en cache
     */
    public function get_row($query, $args = array(), $cache_key = '', $expiration = HOUR_IN_SECONDS) {
        // Ajouter LIMIT 1 si non spécifié
        if (!preg_match('/\bLIMIT\s+\d+\s*$/i', $query)) {
            $query .= ' LIMIT 1';
        }
        
        $results = $this->get_results($query, $args, $cache_key, $expiration);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Récupère une valeur unique avec mise en cache
     */
    public function get_var($query, $args = array(), $cache_key = '', $expiration = HOUR_IN_SECONDS) {
        global $wpdb;
        
        // Générer une clé de cache si non fournie
        if (empty($cache_key)) {
            $cache_key = 'var_' . md5($query . serialize($args));
        }
        
        // Essayer de récupérer depuis le cache
        $cached = $this->cache->get($cache_key, 'query_vars');
        if (false !== $cached) {
            return $cached;
        }
        
        // Exécuter la requête
        $value = $wpdb->get_var($wpdb->prepare($query, $args));
        
        // Mettre en cache le résultat
        $this->cache->set($cache_key, $value, $expiration, 'query_vars');
        
        return $value;
    }
    
    /**
     * Invalide le cache pour une requête spécifique
     */
    public function invalidate_cache($cache_key, $group = '') {
        return $this->cache->delete($cache_key, $group ?: 'query_results');
    }
    
    /**
     * Optimise une requête pour la recherche de créneaux disponibles
     */
    public function optimize_slot_query($date, $provider_id = 0, $service_id = 0) {
        global $wpdb;
        
        $cache_key = sprintf('slots_%s_%d_%d', $date, $provider_id, $service_id);
        
        return $this->cache->remember($cache_key, function() use ($date, $provider_id, $service_id, $wpdb) {
            $query = "
                SELECT s.*, 
                       p.display_name as provider_name,
                       sr.name as service_name,
                       sr.duration
                FROM {$wpdb->prefix}rdv_slots s
                LEFT JOIN {$wpdb->users} p ON s.provider_id = p.ID
                LEFT JOIN {$wpdb->prefix}rdv_services sr ON s.service_id = sr.id
                WHERE s.slot_date = %s
                AND s.status = 'available'
            ";
            
            $args = array($date);
            
            if ($provider_id > 0) {
                $query .= " AND s.provider_id = %d";
                $args[] = $provider_id;
            }
            
            if ($service_id > 0) {
                $query .= " AND s.service_id = %d";
                $args[] = $service_id;
            }
            
            $query .= " ORDER BY s.start_time ASC";
            
            return $wpdb->get_results(
                $wpdb->prepare($query, $args),
                ARRAY_A
            );
        }, 15 * MINUTE_IN_SECONDS, 'available_slots');
    }
    
    /**
     * Optimise la récupération des rendez-vous à venir
     */
    public function get_upcoming_appointments($days = 7, $limit = 50) {
        $cache_key = sprintf('upcoming_%d_%d', $days, $limit);
        
        return $this->cache->remember($cache_key, function() use ($days, $limit) {
            global $wpdb;
            
            $query = "
                SELECT a.*, 
                       p.display_name as provider_name,
                       sr.name as service_name,
                       u.display_name as client_name,
                       u.user_email as client_email
                FROM {$wpdb->prefix}rdv_appointments a
                LEFT JOIN {$wpdb->users} p ON a.provider_id = p.ID
                LEFT JOIN {$wpdb->prefix}rdv_services sr ON a.service_id = sr.id
                LEFT JOIN {$wpdb->users} u ON a.client_id = u.ID
                WHERE a.start_date >= CURDATE()
                AND a.start_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
                AND a.status NOT IN ('cancelled', 'rejected')
                ORDER BY a.start_date ASC, a.start_time ASC
                LIMIT %d
            ";
            
            return $wpdb->get_results(
                $wpdb->prepare($query, [$days, $limit]),
                ARRAY_A
            );
        }, 30 * MINUTE_IN_SECONDS, 'appointments');
    }
}

// Initialiser l'optimiseur de requêtes
add_action('plugins_loaded', function() {
    // Vérifier que le gestionnaire de cache est chargé
    if (!class_exists('CalRdv_Cache_Manager')) {
        require_once __DIR__ . '/class-cache-manager.php';
    }
    
    // Créer une instance pour une utilisation globale
    $GLOBALS['calrdv_query_optimizer'] = CalRdv_Query_Optimizer::get_instance();
});
