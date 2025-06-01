<?php
/**
 * Fichier d'initialisation du module d'administration
 *
 * @package     CalendrierRdv\Includes\Admin
 * @since       1.0.0
 * @author      Votre Nom <votre.email@example.com>
 */

// Si ce fichier est appelé directement, on sort immédiatement.
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier si la classe Admin existe déjà avant de la définir
if (!class_exists('CalendrierRdv\Includes\Admin\Admin')) {
    /**
     * Initialise le module d'administration du plugin
     *
     * Cette fonction est appelée lors de l'initialisation du plugin pour charger
     * les fonctionnalités d'administration.
     *
     * @since 1.0.0
     */
    function cal_rdv_init_admin() {
        // Inclure le fichier de la classe principale d'administration
        require_once plugin_dir_path(__FILE__) . 'Admin.php';
        
        // Initialiser l'administration
        $admin = new \CalendrierRdv\Includes\Admin\Admin();
        
        // Retourner l'instance pour une utilisation ultérieure si nécessaire
        return $admin;
    }
    
    // Initialiser l'administration lors du chargement du plugin
    add_action('plugins_loaded', 'CalendrierRdv\\Includes\\Admin\\cal_rdv_init_admin');
}
