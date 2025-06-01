<?php
/**
 * Bootstrap pour les tests PHPUnit
 */

// Activer l'affichage des erreurs pendant les tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__FILE__) . '/error.log');

// Définir les constantes de test
define('WP_TESTS_CONFIG_FILE_PATH', dirname(__FILE__) . '/test-config.php');
define('WP_TESTS_DIR', dirname(__FILE__) . '/wordpress-tests-lib');
define('WP_ROOT_DIR', dirname(__FILE__) . '/wordpress');

// Vérifier si les tests WordPress sont installés
if (!file_exists(WP_TESTS_DIR . '/includes/functions.php')) {
    die("Les tests WordPress ne sont pas installés. Exécutez 'bash tests/install-wp-tests.sh' pour les installer.\n");
}

// Charger les fonctions de test
require_once WP_TESTS_DIR . '/includes/functions.php';

// Fonction pour charger le plugin
tests_add_filter('muplugins_loaded', function() {
    // Charger WordPress
    require_once dirname(dirname(__FILE__)) . '/calendrier-rdv.php';
    
    // Initialiser le plugin si nécessaire
    if (function_exists('calendrier_rdv_init')) {
        calendrier_rdv_init();
    }
});

// Démarrer l'environnement de test
require WP_TESTS_DIR . '/includes/bootstrap.php';

// Fonctions utilitaires pour les tests
if (!function_exists('create_test_prestataire')) {
    function create_test_prestataire($args = []) {
        $defaults = [
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'post_status' => 'publish',
            'meta_input' => [
                '_disponibilites' => 'all',
                '_duree_rdv' => 30,
                '_pauses' => []
            ]
        ];
        
        return wp_insert_post(wp_parse_args($args, $defaults));
    }
}

if (!function_exists('create_test_service')) {
    function create_test_service($args = []) {
        $defaults = [
            'post_type' => 'service',
            'post_title' => 'Consultation',
            'post_status' => 'publish',
            'meta_input' => [
                '_duree' => 30,
                '_prix' => 50,
                '_prestataires' => []
            ]
        ];
        
        return wp_insert_post(wp_parse_args($args, $defaults));
    }
}

if (!function_exists('create_test_booking')) {
    function create_test_booking($args = []) {
        $defaults = [
            'post_type' => 'rdv_booking',
            'post_status' => 'publish',
            'post_title' => 'Réservation #' . uniqid(),
            'meta_input' => [
                '_prestataire_id' => 1,
                '_service_id' => 1,
                '_client_nom' => 'Test User',
                '_client_email' => 'test@example.com',
                '_client_telephone' => '0123456789',
                '_date_rdv' => date('Y-m-d', strtotime('+1 day')),
                '_heure_debut' => '14:00',
                '_statut' => 'confirme',
                '_reference' => 'TEST-' . strtoupper(uniqid())
            ]
        ];
        
        return wp_insert_post(wp_parse_args($args, $defaults));
    }
}
