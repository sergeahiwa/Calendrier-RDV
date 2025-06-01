<?php
/**
 * Tests fonctionnels pour les endpoints REST
 */

class RestEndpointsTest extends WP_UnitTestCase {
    private $nonceManager;
    
    public function setUp() {
        parent::setUp();
        $this->nonceManager = \CalendrierRdv\Includes\NonceManager::getInstance();
    }
    
    public function testGetServicesEndpoint() {
        // Créer un service de test
        $service = $this->factory()->post->create_and_get([
            'post_type' => 'service',
            'post_title' => 'Test Service',
            'post_content' => 'Service de test',
            'post_status' => 'publish'
        ]);
        
        // Obtenir le nonce
        $nonce = $this->nonceManager->createNonce('get_services');
        
        // Faire la requête REST
        $response = wp_remote_get(
            add_query_arg([
                'nonce' => $nonce
            ], rest_url('calendrier-rdv/v1/services'))
        );
        
        // Vérifier la réponse
        $this->assertEquals(200, wp_remote_retrieve_response_code($response));
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->assertIsArray($body);
        $this->assertGreaterThan(0, count($body));
        $this->assertArrayHasKey('id', $body[0]);
        $this->assertArrayHasKey('name', $body[0]);
        $this->assertArrayHasKey('description', $body[0]);
    }
    
    public function testGetProvidersEndpoint() {
        // Créer un prestataire de test
        $provider = $this->factory()->post->create_and_get([
            'post_type' => 'provider',
            'post_title' => 'Test Provider',
            'post_content' => 'Provider de test',
            'post_status' => 'publish'
        ]);
        
        // Obtenir le nonce
        $nonce = $this->nonceManager->createNonce('get_providers');
        
        // Faire la requête REST
        $response = wp_remote_get(
            add_query_arg([
                'nonce' => $nonce
            ], rest_url('calendrier-rdv/v1/providers'))
        );
        
        // Vérifier la réponse
        $this->assertEquals(200, wp_remote_retrieve_response_code($response));
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->assertIsArray($body);
        $this->assertGreaterThan(0, count($body));
        $this->assertArrayHasKey('id', $body[0]);
        $this->assertArrayHasKey('name', $body[0]);
        $this->assertArrayHasKey('email', $body[0]);
    }
    
    public function testCreateAppointmentEndpoint() {
        // Créer un service et un prestataire de test
        $service = $this->factory()->post->create_and_get([
            'post_type' => 'service',
            'post_status' => 'publish'
        ]);
        
        $provider = $this->factory()->post->create_and_get([
            'post_type' => 'provider',
            'post_status' => 'publish'
        ]);
        
        // Obtenir le nonce
        $nonce = $this->nonceManager->createNonce('create_appointment');
        
        // Préparer les données du rendez-vous
        $data = [
            'service_id' => $service->ID,
            'provider_id' => $provider->ID,
            'appointment_date' => date('Y-m-d'),
            'appointment_time' => date('H:i'),
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '1234567890',
            'notes' => 'Test notes'
        ];
        
        // Faire la requête REST
        $response = wp_remote_post(
            rest_url('calendrier-rdv/v1/appointments'),
            [
                'headers' => [
                    'X-WP-Nonce' => $nonce
                ],
                'body' => $data
            ]
        );
        
        // Vérifier la réponse
        $this->assertEquals(200, wp_remote_retrieve_response_code($response));
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('status', $body);
        $this->assertEquals('pending', $body['status']);
    }
}
