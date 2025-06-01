<?php
/**
 * Plugin Name: Calendrier RDV - Module Divi
 * Plugin URI: https://votresite.com/plugins/calendrier-rdv
 * Description: Module Divi pour l'intégration du calendrier de rendez-vous
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://votresite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: calendrier-rdv-divi
 * Domain Path: /languages
 */

// Sécurité : Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Vérifier si Divi est installé et activé
function calendrier_rdv_divi_requirements_check() {
    $theme = wp_get_theme();
    
    if ('Divi' !== $theme->name && 'Divi' !== $theme->parent_theme) {
        add_action('admin_notices', 'calendrier_rdv_divi_theme_not_found_notice');
        return false;
    }
    
    if (!class_exists('ET_Builder_Module')) {
        add_action('admin_notices', 'calendrier_rdv_divi_builder_not_found_notice');
        return false;
    }
    
    return true;
}

// Afficher une notification si le thème Divi n'est pas trouvé
function calendrier_rdv_divi_theme_not_found_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Le module Calendrier RDV nécessite le thème Divi pour fonctionner correctement.', 'calendrier-rdv-divi'); ?></p>
    </div>
    <?php
}

// Afficher une notification si le Divi Builder n'est pas trouvé
function calendrier_rdv_divi_builder_not_found_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Le module Calendrier RDV nécessite le Divi Builder pour fonctionner correctement.', 'calendrier-rdv-divi'); ?></p>
    </div>
    <?php
}

// Charger les fichiers du module
function calendrier_rdv_divi_load_module() {
    // Vérifier les prérequis
    if (!calendrier_rdv_divi_requirements_check()) {
        return;
    }
    
    // Charger le module principal
    require_once plugin_dir_path(__FILE__) . 'CalendrierRdvModule.php';
    
    // Enregistrer les styles et scripts
    add_action('wp_enqueue_scripts', 'calendrier_rdv_divi_enqueue_scripts');
}
add_action('et_builder_ready', 'calendrier_rdv_divi_load_module');

// Enregistrer les scripts et styles
function calendrier_rdv_divi_enqueue_scripts() {
    // Styles
    wp_enqueue_style(
        'calendrier-rdv-divi',
        plugin_dir_url(__FILE__) . 'css/divi-module.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . 'css/divi-module.css')
    );
    
    // Scripts
    wp_enqueue_script(
        'calendrier-rdv-divi',
        plugin_dir_url(__FILE__) . 'js/divi-module.js',
        ['jquery', 'jquery-ui-datepicker'],
        filemtime(plugin_dir_path(__FILE__) . 'js/divi-module.js'),
        true
    );
    
    // Localisation des variables pour JavaScript
    wp_localize_script('calendrier-rdv-divi', 'calendrierRdvDiviVars', [
        'restUrl' => esc_url_raw(rest_url('calendrier-rdv/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'i18n' => [
            'loading' => __('Chargement...', 'calendrier-rdv-divi'),
            'error' => __('Une erreur est survenue', 'calendrier-rdv-divi'),
        ]
    ]);
    
    // Charger les styles du datepicker jQuery UI
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}

// Ajouter un lien vers les paramètres dans la page des plugins
function calendrier_rdv_divi_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=calendrier-rdv-settings') . '">' . __('Paramètres', 'calendrier-rdv-divi') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'calendrier_rdv_divi_add_settings_link');

// Activation du plugin
function calendrier_rdv_divi_activate() {
    // Code d'activation si nécessaire
}
register_activation_hook(__FILE__, 'calendrier_rdv_divi_activate');

// Désactivation du plugin
function calendrier_rdv_divi_deactivate() {
    // Code de désactivation si nécessaire
}
register_deactivation_hook(__FILE__, 'calendrier_rdv_divi_deactivate');

// Désinstallation du plugin
function calendrier_rdv_divi_uninstall() {
    // Code de désinstallation si nécessaire
}
register_uninstall_hook(__FILE__, 'calendrier_rdv_divi_uninstall');
