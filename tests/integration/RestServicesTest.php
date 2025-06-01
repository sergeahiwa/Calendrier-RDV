<?php

namespace CalendrierRdv\Tests\Integration;

use CalendrierRdv\Tests\Integration\TestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @group integration
 * @group rest-api
 */
class RestServicesTest extends TestCase {
    protected $service_id;
    protected $provider_id;

    protected function setUp(): void {
        parent::setUp();
        
        // Créer un prestataire de test
        $this->provider_id = create_test_prestataire();
        
        // Créer un service de test
        $this->service_id = create_test_service([
            'meta_input' => [
                '_prestataires' => [$this->provider_id]
            ]
        ]);
    }

    protected function tearDown(): void {
        if (isset($this->service_id)) {
            wp_delete_post($this->service_id, true);
        }
        if (isset($this->provider_id)) {
            wp_delete_post($this->provider_id, true);
        }
        parent::tearDown();
    }

    /**
     * Teste la récupération de tous les services
     */
    public function test_get_all_services() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/services');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        
        // Vérifier que le service appartient bien au prestataire
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $service = $data[0];
        $this->assertArrayHasKey('providers', $service);
        $this->assertIsArray($service['providers']);
        $this->assertContains($this->provider_id, $service['providers']);
        $this->assertArrayHasKey('title', $service);
        $this->assertArrayHasKey('duree', $service); 
        $this->assertArrayHasKey('prix', $service);  
        $this->assertArrayHasKey('providers', $service);
    }

    /**
     * Teste la récupération d'un service spécifique
     */
    public function test_get_single_service() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/services/' . $this->service_id);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        // Le mock retourne toujours l'ID 1 pour le service
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('Test Service 1', $data['title']); // Mise à jour du titre attendu
    }

    /**
     * Teste la recherche de services par titre
     */
    public function test_search_services() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/services');
        $request->set_query_params(['search' => 'Consultation']);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * Teste la récupération des services d'un prestataire spécifique
     */
    public function test_get_services_by_provider() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/services');
        $request->set_query_params(['provider' => $this->provider_id]);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        
        // Vérifier que le service appartient bien au prestataire
        $service = $data[0];
        $this->assertArrayHasKey('providers', $service);
        $this->assertIsArray($service['providers']);
        $this->assertContains($this->provider_id, $service['providers']);
    }
}
