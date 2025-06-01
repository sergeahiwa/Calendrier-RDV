<?php
/**
 * Plugin Name: Calendrier RDV
 * Plugin URI: https://example.com/plugins/calendrier-rdv
 * Description: Un système de prise de rendez-vous complet pour WordPress
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://example.com
 * Text Domain: calendrier-rdv
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://example.com/plugins/calendrier-rdv
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit('Accès direct non autorisé');
}

// Définition des constantes
define('RDV_PLUGIN_VERSION', '1.0.0');
define('RDV_PLUGIN_FILE', __FILE__);
define('RDV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RDV_PLUGIN_URL', plugin_dir_url(__FILE__));

// Chargement des dépendances
require_once RDV_PLUGIN_DIR . 'includes/class-rdv-calendar.php';

// Initialisation du plugin
function rdv_init() {
    return RDV_Calendar::get_instance();
}

// Démarrer le plugin
add_action('plugins_loaded', 'rdv_init');

// Activation du plugin
register_activation_hook(__FILE__, ['RDV_Calendar', 'activate']);

// Désactivation du plugin
register_deactivation_hook(__FILE__, ['RDV_Calendar', 'deactivate']);

// Chargement des fichiers d'internationalisation
add_action('init', function() {
    load_plugin_textdomain(
        'calendrier-rdv',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
});
