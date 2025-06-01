<?php
/**
 * Tests unitaires pour le chargeur d'intégrations
 *
 * @package CalendrierRdv\Tests\Unit
 */

class IntegrationLoaderTest extends WP_UnitTestCase {
    
    /**
     * Teste le chargement des intégrations
     */
    public function test_load_integrations() {
        // Vérifier que la fonction existe
        $this->assertTrue(function_exists('calendrier_rdv_load_integrations'));
        
        // Exécuter la fonction
        calendrier_rdv_load_integrations();
        
        // Vérifier qu'aucune erreur n'est survenue
        $this->assertTrue(true);
    }
    
    /**
     * Teste la détection de Divi
     */
    public function test_divi_detection() {
        // Tester sans Divi
        $this->assertFalse(calendrier_rdv_divi_is_active());
        
        // Simuler Divi comme thème actif
        switch_theme('divi');
        
        // La détection devrait toujours échouer car la classe du constructeur n'existe pas
        $this->assertFalse(calendrier_rdv_divi_is_active());
        
        // Remettre le thème par défaut
        switch_theme('twentytwentyone');
    }
}
