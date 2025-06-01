<?php
/**
 * Fichier de constantes partagées
 *
 * @package CalendrierRdv\Common
 */

// Sécurité : empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Version du plugin
define( 'CAL_RDV_VERSION', '1.2.0' );

// Chemins
define( 'CAL_RDV_PLUGIN_DIR', plugin_dir_path( dirname( __DIR__ ) ) );
define( 'CAL_RDV_PLUGIN_URL', plugin_dir_url( dirname( __DIR__ ) ) );
define( 'CAL_RDV_PLUGIN_BASENAME', plugin_basename( dirname( __DIR__ ) . '/calendrier-rdv.php' ) );

// Chemins des répertoires
define( 'CAL_RDV_INCLUDES_DIR', CAL_RDV_PLUGIN_DIR . 'includes/' );
define( 'CAL_RDV_ADMIN_DIR', CAL_RDV_PLUGIN_DIR . 'admin/' );
define( 'CAL_RDV_PUBLIC_DIR', CAL_RDV_PLUGIN_DIR . 'public/' );
define( 'CAL_RDV_TEMPLATES_DIR', CAL_RDV_PLUGIN_DIR . 'templates/' );

// URLs des assets
define( 'CAL_RDV_CSS_URL', CAL_RDV_PLUGIN_URL . 'assets/css/' );
define( 'CAL_RDV_JS_URL', CAL_RDV_PLUGIN_URL . 'assets/js/' );
define( 'CAL_RDV_IMAGES_URL', CAL_RDV_PLUGIN_URL . 'assets/images/' );

// Noms des options
define( 'CAL_RDV_OPTION_SETTINGS', 'calendrier_rdv_settings' );
define( 'CAL_RDV_OPTION_VERSION', 'calendrier_rdv_version' );

// Rôles et capacités
define( 'CAL_RDV_ADMIN_CAPABILITY', 'manage_options' );
define( 'CAL_RDV_PROVIDER_CAPABILITY', 'edit_calendrier_rdv_appointments' );

// Statuts des rendez-vous
define( 'CAL_RDV_STATUS_PENDING', 'pending' );
define( 'CAL_RDV_STATUS_CONFIRMED', 'confirmed' );
define( 'CAL_RDV_STATUS_CANCELLED', 'cancelled' );
define( 'CAL_RDV_STATUS_COMPLETED', 'completed' );

// Statuts de paiement
define( 'CAL_RDV_PAYMENT_PENDING', 'pending' );
define( 'CAL_RDV_PAYMENT_PAID', 'paid' );
define( 'CAL_RDV_PAYMENT_REFUNDED', 'refunded' );
define( 'CAL_RDV_PAYMENT_CANCELLED', 'cancelled' );

// Statuts de la liste d'attente
define( 'CAL_RDV_WAITLIST_WAITING', 'waiting' );
define( 'CAL_RDV_WAITLIST_NOTIFIED', 'notified' );
define( 'CAL_RDV_WAITLIST_BOOKED', 'booked' );
define( 'CAL_RDV_WAITLIST_CANCELLED', 'cancelled' );

// Constantes de temps (en secondes)
define( 'CAL_RDV_MINUTE_IN_SECONDS', 60 );
define( 'CAL_RDV_HOUR_IN_SECONDS', 3600 );
define( 'CAL_RDV_DAY_IN_SECONDS', 86400 );
define( 'CAL_RDV_WEEK_IN_SECONDS', 604800 );

// Options par défaut
if ( ! defined( 'CAL_RDV_REMOVE_ALL_DATA' ) ) {
	define( 'CAL_RDV_REMOVE_ALL_DATA', false );
}

// Débogage
if ( ! defined( 'CAL_RDV_DEBUG' ) ) {
	define( 'CAL_RDV_DEBUG', WP_DEBUG );
}

// Logging
if ( ! defined( 'CAL_RDV_LOG_ENABLED' ) ) {
	define( 'CAL_RDV_LOG_ENABLED', CAL_RDV_DEBUG );
}

define( 'CAL_RDV_LOG_DIR', WP_CONTENT_DIR . '/calendrier-rdv-logs/' );

// Support Divi 5
define( 'CAL_RDV_DIVI_SUPPORT', true );
