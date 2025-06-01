<?php
/**
 * Class ServiceManagerTest
 *
 * @package CalendrierRdv\Tests\Unit
 */

namespace CalendrierRdv\Tests\Unit;

use CalendrierRdv\Core\Managers\ServiceManager;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class ServiceManagerTest extends TestCase {
    private $serviceManager;
    private $serviceId;

    public function setUp(): void {
        parent::setUp();
        $this->serviceManager = new ServiceManager();
        WP_Mock::setUp();
        
        // Créer un service de test
        $this->serviceId = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Consultation initiale',
            'post_status' => 'publish'
        ]);
        
        // Ajouter des métadonnées
        update_post_meta($this->serviceId, '_service_duration', 60);
        update_post_meta($this->serviceId, '_service_price', 80);
        update_post_meta($this->serviceId, '_service_color', '#3498db');
    }

    public function testGetService() {
        // Tester la récupération d'un service
        $service = $this->serviceManager->getService($this->serviceId);
        
        $this->assertIsArray($service);
        $this->assertEquals('Consultation initiale', $service['title']);
        $this->assertEquals(60, $service['duration']);
        $this->assertEquals(80, $service['price']);
        $this->assertEquals('#3498db', $service['color']);
    }

    public function testGetAllServices() {
        // Tester la récupération de tous les services
        $services = $this->serviceManager->getAllServices();
        
        $this->assertIsArray($services);
        $this->assertNotEmpty($services);
        $this->assertEquals('Consultation initiale', $services[0]['title']);
    }

    public function testCreateService() {
        // Données pour un nouveau service
        $serviceData = [
            'title' => 'Massage relaxant',
            'description' => 'Séance de massage relaxant de 60 minutes',
            'duration' => 60,
            'price' => 70,
            'color' => '#9b59b6'
        ];
        
        // Créer le service
        $newServiceId = $this->serviceManager->createService($serviceData);
        
        // Vérifier la création
        $this->assertIsInt($newServiceId);
        $this->assertGreaterThan(0, $newServiceId);
        
        // Vérifier les métadonnées
        $this->assertEquals('Massage relaxant', get_the_title($newServiceId));
        $this->assertEquals(60, get_post_meta($newServiceId, '_service_duration', true));
        $this->assertEquals(70, get_post_meta($newServiceId, '_service_price', true));
        
        // Nettoyer
        wp_delete_post($newServiceId, true);
    }

    public function testUpdateService() {
        // Données de mise à jour
        $updateData = [
            'title' => 'Consultation initiale (mise à jour)',
            'duration' => 90,
            'price' => 100,
            'color' => '#2ecc71'
        ];
        
        // Mettre à jour le service
        $result = $this->serviceManager->updateService($this->serviceId, $updateData);
        $this->assertTrue($result);
        
        // Vérifier les mises à jour
        $updatedService = $this->serviceManager->getService($this->serviceId);
        $this->assertEquals('Consultation initiale (mise à jour)', $updatedService['title']);
        $this->assertEquals(90, $updatedService['duration']);
        $this->assertEquals(100, $updatedService['price']);
        $this->assertEquals('#2ecc71', $updatedService['color']);
    }

    public function testDeleteService() {
        // Créer un service à supprimer
        $serviceToDeleteId = $this->factory->post->create([
            'post_type' => 'cal_rdv_service',
            'post_title' => 'Service à supprimer',
            'post_status' => 'publish'
        ]);
        
        // Supprimer le service
        $result = $this->serviceManager->deleteService($serviceToDeleteId);
        $this->assertTrue($result);
        
        // Vérifier que le service n'existe plus
        $deletedService = get_post($serviceToDeleteId);
        $this->assertNull($deletedService);
    }

    public function testGetServiceProviders() {
        // Créer un prestataire et l'associer au service
        $providerId = $this->factory->user->create([
            'role' => 'cal_rdv_provider',
            'user_login' => 'provider_test',
            'user_email' => 'provider_test@example.com'
        ]);
        
        update_user_meta($providerId, '_provider_services', [$this->serviceId]);
        
        // Tester la récupération des prestataires pour ce service
        $providers = $this->serviceManager->getServiceProviders($this->serviceId);
        
        $this->assertIsArray($providers);
        $this->assertNotEmpty($providers);
        $this->assertEquals($providerId, $providers[0]['id']);
        
        // Nettoyer
        wp_delete_user($providerId, true);
    }

    public function tearDown(): void {
        // Nettoyer
        wp_delete_post($this->serviceId, true);
        WP_Mock::tearDown();
        parent::tearDown();
    }
}
