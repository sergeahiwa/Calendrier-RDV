<?php
// Configuration de test pour WordPress
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Configuration de dÃ©bogage
define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Calendrier RDV Tests' );

// Configuration des chemins
if ( ! defined( 'WP_TESTS_DIR' ) ) {
    define( 'WP_TESTS_DIR', dirname( __FILE__ ) . '/wordpress-tests-lib' );
}

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
    define( 'WP_TESTS_CONFIG_FILE_PATH', __FILE__ );
}

// Charger les tests
require_once WP_TESTS_DIR . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function() {
    // Charger le plugin
    require dirname( dirname( __FILE__ ) ) . '/calendrier-rdv.php';
} );

// DÃ©marrer les tests
require WP_TESTS_DIR . '/includes/bootstrap.php';
