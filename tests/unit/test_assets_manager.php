<?php
/**
 * Tests unitaires pour la classe AssetsManager
 */

class AssetsManagerTest extends WP_UnitTestCase {
    private $assetsManager;
    
    public function setUp() {
        parent::setUp();
        $this->assetsManager = \CalendrierRdv\Includes\AssetsManager::getInstance();
    }
    
    public function tearDown() {
        parent::tearDown();
        $this->assetsManager->unregisterAssets();
    }
    
    public function testRegisterAssets() {
        $this->assetsManager->registerAssets();
        
        // Vérifier que les styles sont enregistrés
        $this->assertTrue(wp_style_is('calendrier-rdv-frontend', 'registered'));
        $this->assertTrue(wp_style_is('calendrier-rdv-builder', 'registered'));
        $this->assertTrue(wp_style_is('calendrier-rdv-divi', 'registered'));
        
        // Vérifier que les scripts sont enregistrés
        $this->assertTrue(wp_script_is('calendrier-rdv-main', 'registered'));
        $this->assertTrue(wp_script_is('calendrier-rdv-divi', 'registered'));
    }
    
    public function testUnregisterAssets() {
        $this->assetsManager->registerAssets();
        $this->assetsManager->unregisterAssets();
        
        // Vérifier que les assets sont désenregistrés
        $this->assertFalse(wp_style_is('calendrier-rdv-frontend', 'registered'));
        $this->assertFalse(wp_style_is('calendrier-rdv-builder', 'registered'));
        $this->assertFalse(wp_style_is('calendrier-rdv-divi', 'registered'));
        $this->assertFalse(wp_script_is('calendrier-rdv-main', 'registered'));
        $this->assertFalse(wp_script_is('calendrier-rdv-divi', 'registered'));
    }
    
    public function testGetCacheKey() {
        $this->assertEquals('calendrier_rdv_services', $this->assetsManager->getCacheKey('services'));
        $this->assertEquals('calendrier_rdv_providers', $this->assetsManager->getCacheKey('providers'));
        $this->assertEquals('calendrier_rdv_appointments', $this->assetsManager->getCacheKey('appointments'));
        $this->assertEquals('', $this->assetsManager->getCacheKey('invalid'));
    }
    
    public function testCacheConfiguration() {
        $this->assertTrue($this->assetsManager->isCacheEnabled());
        $this->assertEquals(3600, $this->assetsManager->getCacheDuration());
    }
}
