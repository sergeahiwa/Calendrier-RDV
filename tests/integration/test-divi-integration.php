<?php
/**
 * Tests d'intégration pour le module Divi
 *
 * @package CalendrierRdv\Tests\Integration
 */

class DiviIntegrationTest extends WP_UnitTestCase {
    
    /**
     * Teste le chargement du module Divi
     */
    public function test_divi_module_loading() {
        // Simuler que Divi est actif
        switch_theme('divi');
        
        // Créer une classe factice pour le constructeur Divi
        if (!class_exists('ET_Builder_Module')) {
            eval('class ET_Builder_Module {}');
        }
        
        // Inclure le fichier bootstrap
        require_once CALENDRIER_RDV_DIR . 'includes/integrations/divi/bootstrap.php';
        
        // Vérifier que la fonction de détection retourne true
        $this->assertTrue(calendrier_rdv_divi_is_active());
        
        // Vérifier que la classe du module existe
        $this->assertTrue(class_exists('CalendrierRdvModule'));
        
        // Nettoyer
        switch_theme('twentytwentyone');
    }
    
    /**
     * Teste la notification d'erreur lorsque Divi n'est pas disponible
     */
    public function test_divi_missing_notice() {
        // S'assurer que Divi n'est pas actif
        switch_theme('twentytwentyone');
        
        // Capturer la sortie de la fonction de notification
        ob_start();
        calendrier_rdv_divi_admin_notice();
        $output = ob_get_clean();
        
        // Vérifier que le message d'erreur est affiché
        $this->assertStringContainsString('Le module Divi pour Calendrier RDV', $output);
    }
}
