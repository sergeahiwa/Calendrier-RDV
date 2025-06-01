<?php
/**
 * Plugin Name: Calendrier RDV
 * Plugin URI: https://votresite.com/plugins/calendrier-rdv
 * Description: Un plugin de gestion de rendez-vous avec calendrier interactif.
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://votresite.com
 * Text Domain: calendrier-rdv
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package CalendrierRDV
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin.
define('CALENDRIER_RDV_VERSION', '1.0.0');
define('CALENDRIER_RDV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CALENDRIER_RDV_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclure le fichier principal du plugin.
require_once CALENDRIER_RDV_PLUGIN_DIR . 'src/Plugin.php';

// Initialiser le plugin.
function calendrier_rdv_init() {
    // Vérifier les dépendances.
    if (!class_exists('CalendrierRDV\Plugin')) {
        add_action('admin_notices', 'calendrier_rdv_missing_files_notice');
        return;
    }
    
    // Initialiser le plugin.
    CalendrierRDV\Plugin::get_instance();
}
add_action('plugins_loaded', 'calendrier_rdv_init');

// Afficher un message d'erreur si les fichiers du plugin sont manquants.
function calendrier_rdv_missing_files_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('Erreur : Les fichiers du plugin Calendrier RDV sont manquants. Veuillez réinstaller le plugin.', 'calendrier-rdv'); ?></p>
    </div>
    <?php
}

// Activation du plugin.
register_activation_hook(__FILE__, 'calendrier_rdv_activate');
function calendrier_rdv_activate() {
    // Créer les tables de la base de données si nécessaire.
    require_once CALENDRIER_RDV_PLUGIN_DIR . 'src/Installer.php';
    $installer = new CalendrierRDV\Installer();
    $installer->install();
    
    // Mettre à jour la version du plugin.
    update_option('calendrier_rdv_version', CALENDRIER_RDV_VERSION);
    
    // Planifier les tâches récurrentes si nécessaire.
    if (!wp_next_scheduled('calendrier_rdv_daily_tasks')) {
        wp_schedule_event(time(), 'daily', 'calendrier_rdv_daily_tasks');
    }
}

// Désactivation du plugin.
register_deactivation_hook(__FILE__, 'calendrier_rdv_deactivate');
function calendrier_rdv_deactivate() {
    // Nettoyer les tâches planifiées.
    wp_clear_scheduled_hook('calendrier_rdv_daily_tasks');
}

// Désinstallation du plugin.
register_uninstall_hook(__FILE__, 'calendrier_rdv_uninstall');
function calendrier_rdv_uninstall() {
    // Supprimer les options de la base de données si nécessaire.
    delete_option('calendrier_rdv_version');
    
    // Supprimer les tables de la base de données si nécessaire.
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'rdv_appointments',
        $wpdb->prefix . 'rdv_services',
        $wpdb->prefix . 'rdv_providers',
        $wpdb->prefix . 'rdv_availability',
        $wpdb->prefix . 'rdv_holidays',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}
