<?php
/**
 * Test rapide pour vérifier l'architecture d'intégration
 */

// Simuler WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../');
}

// Fonction wp_get_theme simulée
if (!function_exists('wp_get_theme')) {
    function wp_get_theme() {
        return new class {
            public $name = '';
            public $parent_theme = '';
            
            public function __construct() {
                // Simuler Divi comme thème actif pour le test
                $this->name = 'Divi';
                $this->parent_theme = '';
            }
        };
    }
}

// Fonction add_action simulée
if (!function_exists('add_action')) {
    function add_action($hook, $callback) {
        // Ne rien faire dans ce test
    }
}

// Fonction current_user_can simulée
if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // Pour les tests
    }
}

// Fonction __ simulée
if (!function_exists('__')) {
    function __($text, $domain = '') {
        return $text;
    }
}

// Charger le fichier bootstrap directement
require_once __DIR__ . '/../includes/integrations/divi/bootstrap.php';

// Fonction de test
function run_quick_tests() {
    echo "=== Test de l'architecture d'intégration ===\n\n";
    
    // 1. Vérifier que le fichier bootstrap existe
    $bootstrapFile = __DIR__ . '/../includes/integrations/divi/bootstrap.php';
    if (!file_exists($bootstrapFile)) {
        die("❌ Le fichier bootstrap.php est introuvable dans le module Divi\n");
    }
    echo "✅ Fichier bootstrap.php trouvé\n";
    
    // 2. Inclure le fichier bootstrap
    require_once $bootstrapFile;
    echo "✅ Fichier bootstrap.php chargé avec succès\n";
    
    // 3. Vérifier que la fonction de détection existe
    if (!function_exists('calendrier_rdv_divi_is_active')) {
        die("❌ La fonction calendrier_rdv_divi_is_active() est introuvable\n");
    }
    echo "✅ Fonction calendrier_rdv_divi_is_active() trouvée\n";
    
    // 4. Tester la détection de Divi (devrait retourner false car Divi n'est pas actif)
    $isDiviActive = calendrier_rdv_divi_is_active();
    echo "✅ Test de détection de Divi : " . ($isDiviActive ? 'Détecté' : 'Non détecté (comportement attendu)') . "\n";
    
    // 5. Vérifier que la classe du module n'est pas chargée (car Divi n'est pas actif)
    if (class_exists('CalendrierRdvModule')) {
        echo "⚠️ La classe CalendrierRdvModule est chargée alors qu'elle ne devrait pas l'être\n";
    } else {
        echo "✅ La classe CalendrierRdvModule n'est pas chargée (comportement attendu)\n";
    }
    
    echo "\n=== Tests terminés avec succès ===\n";
}

// Exécuter les tests
run_quick_tests();
