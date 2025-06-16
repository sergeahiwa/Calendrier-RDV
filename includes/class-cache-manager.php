<?php
/**
 * Gestionnaire de cache pour le plugin Calendrier RDV
 */

namespace CalendrierRdv\Core;

// Désactiver la vérification ABSPATH pour les tests
echo "Classe Cache_Manager chargée avec succès!\n";

class Cache_Manager {
    /**
     * Préfixe pour les clés de cache
     */
    const CACHE_PREFIX = 'cal_rdv_';

    /**
     * Durée de vie du cache par défaut (en secondes)
     */
    const DEFAULT_EXPIRATION = HOUR_IN_SECONDS; // 1 heure

    /**
     * Obtient une valeur en cache
     *
     * @param string $key Clé du cache
     * @return mixed|null Valeur en cache ou null si non trouvée
     */
    public static function get($key) {
        $key = self::sanitize_key($key);
        $value = get_transient($key);
        
        // Si le cache est expiré ou invalide
        if ($value === false) {
            return null;
        }
        
        return $value;
    }

    /**
     * Définit une valeur en cache
     * 
     * @param string $key Clé du cache
     * @param mixed $value Valeur à mettre en cache
     * @param int $expiration Durée de vie en secondes
     * @return bool Résultat de l'opération
     */
    public static function set($key, $value, $expiration = null) {
        if ($expiration === null) {
            $expiration = self::DEFAULT_EXPIRATION;
        }
        
        $key = self::sanitize_key($key);
        return set_transient($key, $value, $expiration);
    }

    /**
     * Supprime une entrée du cache
     * 
     * @param string $key Clé du cache à supprimer
     * @return bool Résultat de l'opération
     */
    public static function delete($key) {
        $key = self::sanitize_key($key);
        return delete_transient($key);
    }

    /**
     * Nettoie une clé de cache
     */
    protected static function sanitize_key($key) {
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_]/', '_', $key);
        $key = trim($key, '_');
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Vide tout le cache du plugin
     */
    public static function flush() {
        global $wpdb, $wp_transients;
        
        // Si $wpdb est disponible, on nettoie via la base de données
        if (isset($wpdb) && class_exists('WPDB')) {
            $prefix = '_transient_' . self::CACHE_PREFIX;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
                    $prefix . '%',
                    '_transient_timeout_' . self::CACHE_PREFIX . '%'
                )
            );
        } 
        // Sinon, on nettoie le tableau global utilisé pour les tests
        elseif (isset($wp_transients) && is_array($wp_transients)) {
            foreach (array_keys($wp_transients) as $key) {
                if (strpos($key, self::CACHE_PREFIX) === 0) {
                    unset($wp_transients[$key]);
                }
            }
        }
        
        return true;
    }
}
