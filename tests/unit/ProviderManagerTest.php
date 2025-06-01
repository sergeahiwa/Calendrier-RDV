<?php
/**
 * Class ProviderManagerTest
 *
 * @package CalendrierRdv\Tests\Unit
 */

namespace CalendrierRdv\Tests\Unit;

use CalendrierRdv\Core\Managers\ProviderManager;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class ProviderManagerTest extends TestCase {
    private $providerManager;
    private $providerId;

    public function setUp(): void {
        parent::setUp();
        $this->providerManager = new ProviderManager();
        WP_Mock::setUp();
        
        // Créer un prestataire de test
        $this->providerId = $this->factory->user->create([
            'role' => 'cal_rdv_provider',
            'user_login' => 'testprovider',
            'user_email' => 'provider@example.com',
            'first_name' => 'Test',
            'last_name' => 'Provider'
        ]);
        
        // Ajouter des métadonnées
        update_user_meta($this->providerId, '_provider_services', [1, 2, 3]);
        update_user_meta($this->providerId, '_provider_schedule', [
            'monday' => ['start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00'],
            'thursday' => ['start' => '09:00', 'end' => '17:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
            'saturday' => ['start' => '10:00', 'end' => '14:00'],
            'sunday' => ['start' => '', 'end' => '']
        ]);
    }

    public function testGetProvider() {
        // Tester la récupération d'un prestataire
        $provider = $this->providerManager->getProvider($this->providerId);
        
        $this->assertIsArray($provider);
        $this->assertEquals('Test', $provider['first_name']);
        $this->assertEquals('Provider', $provider['last_name']);
        $this->assertArrayHasKey('services', $provider);
        $this->assertArrayHasKey('schedule', $provider);
    }

    public function testGetAvailableProviders() {
        // Tester la récupération des prestataires disponibles
        $providers = $this->providerManager->getAvailableProviders('2025-06-15', '10:00:00', '11:00:00', 1);
        
        $this->assertIsArray($providers);
        $this->assertNotEmpty($providers);
        $this->assertEquals($this->providerId, $providers[0]['id']);
    }

    public function testIsProviderAvailable() {
        // Tester la disponibilité d'un prestataire
        $isAvailable = $this->providerManager->isProviderAvailable(
            $this->providerId,
            '2025-06-16', // Un lundi
            '10:00:00',
            '11:00:00',
            1 // service_id
        );
        
        $this->assertTrue($isAvailable);
        
        // Tester en dehors des heures d'ouverture
        $isAvailable = $this->providerManager->isProviderAvailable(
            $this->providerId,
            '2025-06-16',
            '08:00:00', // Trop tôt
            '09:00:00',
            1
        );
        
        $this->assertFalse($isAvailable);
    }

    public function testUpdateProviderServices() {
        // Mettre à jour les services du prestataire
        $newServices = [2, 4, 6];
        $result = $this->providerManager->updateProviderServices($this->providerId, $newServices);
        
        $this->assertTrue($result);
        
        // Vérifier la mise à jour
        $updatedServices = get_user_meta($this->providerId, '_provider_services', true);
        $this->assertEquals($newServices, $updatedServices);
    }

    public function testGetProviderSchedule() {
        // Tester la récupération de l'emploi du temps
        $schedule = $this->providerManager->getProviderSchedule($this->providerId);
        
        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('monday', $schedule);
        $this->assertEquals('09:00', $schedule['monday']['start']);
    }

    public function testUpdateProviderSchedule() {
        // Mettre à jour l'emploi du temps
        $newSchedule = [
            'monday' => ['start' => '10:00', 'end' => '18:00'],
            'tuesday' => ['start' => '10:00', 'end' => '18:00'],
            'wednesday' => ['start' => '10:00', 'end' => '18:00'],
            'thursday' => ['start' => '10:00', 'end' => '18:00'],
            'friday' => ['start' => '10:00', 'end' => '16:00'],
            'saturday' => ['start' => '09:00', 'end' => '13:00'],
            'sunday' => ['start' => '', 'end' => '']
        ];
        
        $result = $this->providerManager->updateProviderSchedule($this->providerId, $newSchedule);
        $this->assertTrue($result);
        
        // Vérifier la mise à jour
        $updatedSchedule = get_user_meta($this->providerId, '_provider_schedule', true);
        $this->assertEquals('10:00', $updatedSchedule['monday']['start']);
        $this->assertEquals('16:00', $updatedSchedule['friday']['end']);
    }

    public function tearDown(): void {
        // Nettoyer
        wp_delete_user($this->providerId, true);
        WP_Mock::tearDown();
        parent::tearDown();
    }
}
