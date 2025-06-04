<?php

echo "Démarrage des tests...\n";

// Définir les constantes WordPress nécessaires
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 60 * 60);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
}

// Simuler les fonctions WordPress
$wp_transients = [];

function get_transient($key) {
    global $wp_transients;
    echo "Récupération de la clé: $key\n";
    return $wp_transients[$key] ?? false;
}

function set_transient($key, $value, $expiration) {
    global $wp_transients;
    echo "Mise en cache de la clé: $key\n";
    $wp_transients[$key] = $value;
    return true;
}

function delete_transient($key) {
    global $wp_transients;
    echo "Suppression de la clé: $key\n";
    unset($wp_transients[$key]);
    return true;
}

// Inclure la classe Cache_Manager
echo "Chargement de la classe Cache_Manager...\n";
$cache_manager_path = __DIR__ . '/includes/class-cache-manager.php';
if (!file_exists($cache_manager_path)) {
    die("Erreur: Le fichier class-cache-manager.php est introuvable à l'emplacement: $cache_manager_path\n");
}

echo "Contenu du dossier includes:\n";
foreach (scandir(__DIR__ . '/includes') as $file) {
    echo "- $file\n";}

// Vérifier si la classe existe avant de l'inclure
if (!class_exists('CalendrierRdv\\Core\\Cache_Manager')) {
    echo "La classe Cache_Manager n'est pas encore chargée.\n";
    require_once $cache_manager_path;
    
    if (!class_exists('CalendrierRdv\\Core\\Cache_Manager')) {
        die("Erreur: La classe Cache_Manager n'a pas pu être chargée.\n");
    }
    echo "La classe Cache_Manager a été chargée avec succès.\n";
} else {
    echo "La classe Cache_Manager est déjà chargée.\n";
}

// Fonction de test
function run_test($name, $callback) {
    echo "\n=== Début du test: $name ===\n";
    echo "Mémoire utilisée: " . memory_get_usage() . " octets\n";
    try {
        $result = $callback();
        echo "✅ Réussi\n";
        return $result;
    } catch (Exception $e) {
        echo "❌ Échec: " . $e->getMessage() . "\n";
        return false;
    }
    echo "\n";
}

// Test 1: Mise en cache et récupération
run_test('Mise en cache et récupération', function() {
    $key = 'test_key';
    $value = ['test' => 'value'];
    
    // Test de récupération d'une valeur non existante
    $result = CalendrierRdv\Core\Cache_Manager::get($key);
    if ($result !== null) {
        throw new Exception("La valeur devrait être nulle");
    }
    
    // Test de mise en cache
    CalendrierRdv\Core\Cache_Manager::set($key, $value, 60);
    
    // Test de récupération
    $cached = CalendrierRdv\Core\Cache_Manager::get($key);
    if ($cached !== $value) {
        throw new Exception("La valeur mise en cache ne correspond pas");
    }
    
    return true;
});

// Test 2: Suppression du cache
run_test('Suppression du cache', function() {
    $key = 'test_key_delete';
    $value = 'value_to_delete';
    
    // Mise en cache
    CalendrierRdv\Core\Cache_Manager::set($key, $value);
    
    // Vérification
    if (CalendrierRdv\Core\Cache_Manager::get($key) !== $value) {
        throw new Exception("La valeur devrait être en cache");
    }
    
    // Suppression
    CalendrierRdv\Core\Cache_Manager::delete($key);
    
    // Vérification
    if (CalendrierRdv\Core\Cache_Manager::get($key) !== null) {
        throw new Exception("La valeur devrait être supprimée du cache");
    }
    
    return true;
});

// Test 3: Nettoyage complet du cache
run_test('Nettoyage complet du cache', function() {
    // Données de test
    $test_data = [
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3'
    ];
    
    // Mise en cache
    foreach ($test_data as $key => $value) {
        CalendrierRdv\Core\Cache_Manager::set($key, $value);
    }
    
    // Vérification
    foreach ($test_data as $key => $value) {
        if (CalendrierRdv\Core\Cache_Manager::get($key) !== $value) {
            throw new Exception("La valeur pour $key ne correspond pas");
        }
    }
    
    // Nettoyage
    CalendrierRdv\Core\Cache_Manager::flush();
    
    // Vérification
    foreach (array_keys($test_data) as $key) {
        if (CalendrierRdv\Core\Cache_Manager::get($key) !== null) {
            throw new Exception("La valeur pour $key devrait être supprimée");
        }
    }
    
    return true;
});

echo "\nTous les tests sont terminés !\n";
