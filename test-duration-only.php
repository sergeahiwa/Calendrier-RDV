<?php
/**
 * Script de test simplifié pour vérifier la durée dans les rendez-vous formatés
 */

// Inclure l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Simuler l'environnement WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Définir les constantes WordPress nécessaires
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// Fonctions WordPress essentielles
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        $args = func_get_args();
        array_shift($args); // Remove $tag
        return $args[0]; // Return first argument (the value)
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_timezone_string')) {
    function wp_timezone_string() {
        return 'UTC';
    }
}

if (!function_exists('wp_timezone')) {
    function wp_timezone() {
        return new DateTimeZone(wp_timezone_string());
    }
}

// Simuler la connexion à la base de données
global $wpdb;
$wpdb = (object) [
    'prefix' => 'wp_',
    'base_prefix' => 'wp_',
    'posts' => 'wp_posts',
    'postmeta' => 'wp_postmeta',
    'terms' => 'wp_terms',
    'term_taxonomy' => 'wp_term_taxonomy',
    'term_relationships' => 'wp_term_relationships',
    'users' => 'wp_users',
    'usermeta' => 'wp_usermeta',
    'comments' => 'wp_comments',
    'commentmeta' => 'wp_commentmeta',
    'options' => 'wp_options',
];

// Fonction de débogage
function debug($data, $title = '') {
    echo "<h3>$title</h3>";
    echo '<pre>' . print_r($data, true) . '</pre>';
}

// Inclure le fichier de la classe Appointments
require_once __DIR__ . '/src/Core/Database/Appointments.php';

// Créer un mock de la base de données
class MockWPDB {
    public $prefix = 'wp_';
    
    public function get_results($query, $output = OBJECT) {
        // Simuler un résultat de base de données
        return [
            (object) [
                'id' => 1,
                'appointment_date' => '2025-06-01',
                'appointment_time' => '10:00:00',
                'customer_name' => 'Test Client',
                'status' => 'confirmed',
                'service_id' => 1,
                'provider_id' => 1,
                'duration' => 45,
                'service_name' => 'Test Service',
                'provider_name' => 'Test Provider',
                'provider_email' => 'provider@test.com',
                'duration_minutes' => 45
            ]
        ];
    }
    
    public function prepare($query, ...$args) {
        return $query;
    }
    
    public function get_var($query = null, $x = 0, $y = 0) {
        return 1; // Simuler un ID
    }
}

// Remplacer la variable globale $wpdb par notre mock
global $wpdb;
$wpdb = new MockWPDB();

// Fonction pour tester get_formatted_appointments_for_calendar
function test_get_formatted_appointments() {
    // Appeler la méthode à tester
    $appointments = CalendrierRdv\Core\Database\Appointments::get_formatted_appointments_for_calendar('2025-06-01', '2025-06-02');
    
    // Afficher les résultats
    debug($appointments, 'Résultats de get_formatted_appointments_for_calendar');
    
    // Vérifier que la durée est correctement incluse
    if (isset($appointments[0]['duration']) && $appointments[0]['duration'] === 45) {
        echo "<p style='color: green;'>✅ Test réussi : La durée est correctement incluse au niveau racine.</p>";
    } else {
        echo "<p style='color: red;'>❌ Test échoué : La durée n'est pas correctement incluse au niveau racine.</p>";
    }
    
    if (isset($appointments[0]['extendedProps']['duration']) && $appointments[0]['extendedProps']['duration'] === 45) {
        echo "<p style='color: green;'>✅ Test réussi : La durée est correctement incluse dans les propriétés étendues.</p>";
    } else {
        echo "<p style='color: red;'>❌ Test échoué : La durée n'est pas correctement incluse dans les propriétés étendues.</p>";
    }
}

// Exécuter le test
echo "<h1>Test de la fonction get_formatted_appointments_for_calendar</h1>";
test_get_formatted_appointments();

// Afficher le code source de la méthode pour référence
echo "<h2>Code source de la méthode :</h2>";
$source = file_get_contents(__DIR__ . '/src/Core/Database/Appointments.php');
$source = htmlspecialchars($source);
$source = substr($source, 0, 1000) . '...';
echo "<pre>$source</pre>";
