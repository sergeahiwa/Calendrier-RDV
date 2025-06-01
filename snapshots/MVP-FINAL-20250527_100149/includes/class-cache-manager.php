<?php
/**
 * Gestionnaire de cache pour le plugin Calendrier RDV
 */
class CalRdv_Cache_Manager {
    /**
     * Préfixe pour les clés de cache
     */
    const CACHE_PREFIX = 'calrdv_cache_';
    
    /**
     * Durée de vie par défaut (1 heure)
     */
    const DEFAULT_EXPIRATION = HOUR_IN_SECONDS;
    
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Statut d'activation du cache
     */
    private $enabled = true;
    
    /**
     * Constructeur privé (singleton)
     */
    private function __construct() {
        // Vérifier si le cache est désactivé par constante
        if (defined('CAL_RDV_DISABLE_CACHE') && CAL_RDV_DISABLE_CACHE) {
            $this->enabled = false;
        }
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
     * Active ou désactive le cache
     */
    public function set_enabled($enabled) {
        $this->enabled = (bool) $enabled;
        return $this;
    }
    
    /**
     * Vérifie si le cache est activé
     */
    public function is_enabled() {
        return $this->enabled;
    }
    
    /**
     * Génère une clé de cache unique
     */
    private function get_cache_key($key) {
        return self::CACHE_PREFIX . md5(serialize($key));
    }
    
    /**
     * Récupère une donnée en cache
     */
    public function get($key, $group = 'default') {
        if (!$this->enabled) {
            return false;
        }
        
        $cache_key = $this->get_cache_key([$group, $key]);
        $value = wp_cache_get($cache_key, $group);
        
        // Vérifier l'expiration
        if (is_array($value) && isset($value['expires']) && $value['expires'] < time()) {
            wp_cache_delete($cache_key, $group);
            return false;
        }
        
        return is_array($value) && isset($value['data']) ? $value['data'] : false;
    }
    
    /**
     * Stocke une donnée en cache
     */
    public function set($key, $data, $expiration = null, $group = 'default') {
        if (!$this->enabled) {
            return false;
        }
        
        if (is_null($expiration)) {
            $expiration = self::DEFAULT_EXPIRATION;
        }
        
        $cache_key = $this->get_cache_key([$group, $key]);
        $value = [
            'data' => $data,
            'expires' => time() + $expiration,
            'created' => time()
        ];
        
        return wp_cache_set($cache_key, $value, $group, $expiration);
    }
    
    /**
     * Supprime une entrée du cache
     */
    public function delete($key, $group = 'default') {
        $cache_key = $this->get_cache_key([$group, $key]);
        return wp_cache_delete($cache_key, $group);
    }
    
    /**
     * Vide tout le cache ou un groupe spécifique
     */
    public function flush($group = null) {
        if ($group) {
            wp_cache_flush_group($group);
        } else {
            wp_cache_flush();
        }
        return true;
    }
    
    /**
     * Incrémente une valeur numérique en cache
     */
    public function increment($key, $offset = 1, $group = 'default') {
        $value = $this->get($key, $group);
        $value = is_numeric($value) ? $value + $offset : $offset;
        $this->set($key, $value, null, $group);
        return $value;
    }
    
    /**
     * Décrémente une valeur numérique en cache
     */
    public function decrement($key, $offset = 1, $group = 'default') {
        return $this->increment($key, -$offset, $group);
    }
    
    /**
     * Récupère ou génère une donnée avec callback
     */
    public function remember($key, $callback, $expiration = null, $group = 'default') {
        $cached = $this->get($key, $group);
        
        if (false !== $cached) {
            return $cached;
        }
        
        $value = is_callable($callback) ? call_user_func($callback) : $callback;
        $this->set($key, $value, $expiration, $group);
        
        return $value;
    }
}

// Initialiser le gestionnaire de cache
add_action('plugins_loaded', function() {
    // Créer une instance pour une utilisation globale
    $GLOBALS['calrdv_cache'] = CalRdv_Cache_Manager::get_instance();
    
    // Désactiver le cache en mode débogage
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $GLOBALS['calrdv_cache']->set_enabled(!(defined('CAL_RDV_DEBUG') && CAL_RDV_DEBUG));
    }
});

// Nettoyer le cache lors de la désactivation du plugin
register_deactivation_hook(__FILE__, function() {
    if (isset($GLOBALS['calrdv_cache'])) {
        $GLOBALS['calrdv_cache']->flush();
    }
});
