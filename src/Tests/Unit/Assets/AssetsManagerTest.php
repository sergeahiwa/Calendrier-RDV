<?php

namespace CalendrierRdv\Tests\Unit\Assets;

use CalendrierRdv\Core\Assets\AssetsManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la classe AssetsManager
 */
class AssetsManagerTest extends TestCase {
    private $assetsManager;

    protected function setUp(): void {
        parent::setUp();
        $this->assetsManager = AssetsManager::getInstance();
    }

    public function testRegisterStyles(): void {
        $this->assetsManager->registerStyles();
        
        $this->assertTrue(
            wp_style_is('calendrier-rdv-frontend', 'registered'),
            'Le style frontend doit être enregistré'
        );
        
        $this->assertTrue(
            wp_style_is('calendrier-rdv-builder', 'registered'),
            'Le style builder doit être enregistré'
        );
    }

    public function testRegisterScripts(): void {
        $this->assetsManager->registerScripts();
        
        $this->assertTrue(
            wp_script_is('calendrier-rdv-main', 'registered'),
            'Le script principal doit être enregistré'
        );
    }

    public function testLocalizeScript(): void {
        $this->assetsManager->registerScripts();
        
        $config = get_option('calendrierRdvConfig');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('ajax_url', $config);
        $this->assertArrayHasKey('nonce', $config);
        $this->assertArrayHasKey('i18n', $config);
    }

    public function testUnregisterAssets(): void {
        $this->assetsManager->registerAssets();
        $this->assetsManager->unregisterAssets();
        
        $this->assertFalse(
            wp_style_is('calendrier-rdv-frontend', 'enqueued'),
            'Le style frontend doit être désenregistré'
        );
        
        $this->assertFalse(
            wp_style_is('calendrier-rdv-builder', 'enqueued'),
            'Le style builder doit être désenregistré'
        );
        
        $this->assertFalse(
            wp_script_is('calendrier-rdv-main', 'enqueued'),
            'Le script principal doit être désenregistré'
        );
    }
}
