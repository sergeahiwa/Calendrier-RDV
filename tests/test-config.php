<?php
/**
 * Configuration de test pour PHPUnit
 */

// Configuration de la base de données
define('DB_NAME', 'wordpress_test');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', '127.0.0.1');

// Configuration de base de WordPress
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Calendrier RDV Tests');
define('WP_PHP_BINARY', 'php');

// Configuration de débogage
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SAVEQUERIES', true);

// Configuration de l'environnement de test
define('WP_TESTS_MULTISITE', false);

// Clés de sécurité (à remplacer par des valeurs aléatoires pour les environnements de production)
define('AUTH_KEY',         'test-key-1234567890');
define('SECURE_AUTH_KEY',  'test-secure-key-1234567890');
define('LOGGED_IN_KEY',    'test-logged-in-key-1234567890');
define('NONCE_KEY',        'test-nonce-key-1234567890');
define('AUTH_SALT',        'test-auth-salt-1234567890');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt-1234567890');
define('LOGGED_IN_SALT',   'test-logged-in-salt-1234567890');
define('NONCE_SALT',       'test-nonce-salt-1234567890');

// Préfixe des tables pour les tests
$table_prefix = 'wptests_';

// Définir le chemin d'accès à WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/wordpress/');
}

// Définir le chemin vers les tests
if (!defined('WP_TESTS_DIR')) {
    define('WP_TESTS_DIR', dirname(__FILE__) . '/wordpress-tests-lib');
}

// Définir le chemin vers le noyau de WordPress
if (!defined('WP_TESTS_CONFIG_PATH')) {
    define('WP_TESTS_CONFIG_PATH', __FILE__);
}

// Charger le fichier de configuration de WordPress
require_once ABSPATH . 'wp-settings.php';
