<?php

namespace CalendrierRdv\Tests\Integration;

use CalendrierRdv\Tests\Integration\TestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * @group integration
 * @group rest-api
 */
class Rest_Providers_Test extends TestCase {
    protected $provider_id;

    protected function setUp(): void {
        parent::setUp();
        
        // Créer un prestataire de test avec des disponibilités
        $this->provider_id = create_test_prestataire([
            'meta_input' => [
                '_disponibilites' => 'monday,tuesday,wednesday,thursday,friday',
                '_duree_rdv' => 30,
                '_pauses' => [
                    'monday' => ['12:00-13:00'],
                    'tuesday' => ['12:00-13:00']
                ]
            ]
        ]);
    }

    protected function tearDown(): void {
        if (isset($this->provider_id)) {
            wp_delete_post($this->provider_id, true);
        }
        parent::tearDown();
    }

    /**
     * Teste la récupération de tous les prestataires
     */
    public function test_get_all_providers() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/providers');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        
        // Vérifier les propriétés d'un prestataire
        $provider = $data[0];
        $this->assertArrayHasKey('id', $provider);
        $this->assertArrayHasKey('name', $provider);
        $this->assertArrayHasKey('availability', $provider);
        $this->assertArrayHasKey('pause_times', $provider);
    }

    /**
     * Teste la récupération d'un prestataire spécifique
     */
    public function test_get_single_provider() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/providers/' . $this->provider_id);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertEquals($this->provider_id, $data['id']);
        $this->assertEquals('Dr. Test', $data['name']);
    }

    /**
     * Teste la recherche de prestataires par nom
     */
    public function test_search_providers() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/providers');
        $request->set_query_params(['search' => 'Dr. Test']);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * Teste la récupération des disponibilités d'un prestataire
     */
    public function test_get_provider_availability() {
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/providers/' . $this->provider_id . '/availability');
        $request->set_query_params([
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+2 weeks'))
        ]);
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('availability', $data);
        
        // Vérifier que les pauses sont correctement appliquées
        foreach ($data['availability'] as $date => $slots) {
            $day_of_week = date('l', strtotime($date));
            if (in_array(strtolower($day_of_week), ['monday', 'tuesday'])) {
                $this->assertFalse(in_array('12:00', array_column($slots, 'start')));
            }
        }
    }
}
