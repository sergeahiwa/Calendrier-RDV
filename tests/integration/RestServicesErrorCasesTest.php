<?php

namespace CalendrierRdv\Tests\Integration;

use CalendrierRdv\Tests\Integration\TestCase;
use WP_REST_Request;

class RestServicesErrorCasesTest extends TestCase
{
    protected $service_id;
    protected $provider_id;

    protected function setUp(): void 
    {
        parent::setUp();
        $this->provider_id = create_test_prestataire();
        $this->service_id = create_test_service([
            'meta_input' => [
                '_prestataires' => [$this->provider_id],
                '_duree' => 60,
                '_prix' => 50.00
            ]
        ]);
    }

    /**
     * Teste la création d'un service avec des données manquantes
     */
    public function test_create_service_with_missing_data()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => '', // volontairement vide
            'duree' => 60,
            'prix' => 50.00
            // 'providers' manquant volontairement
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(400, $response->get_status(), 'Le statut de la réponse devrait être 400');
        $data = $response->get_data();
        $this->assertIsArray($data, 'La réponse devrait être un tableau');
        $this->assertArrayHasKey('error', $data, 'La réponse devrait contenir une clé "error"');
        $this->assertArrayHasKey('code', $data, 'La réponse devrait contenir une clé "code"');
        $this->assertEquals('missing_required_params', $data['code'], 'Le code d\'erreur devrait être "missing_required_params"');
    }
    
    /**
     * Teste la création d'un service avec un titre vide
     */
    public function test_create_service_with_empty_title()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => '',
            'duree' => 60,
            'prix' => 50.00,
            'providers' => [$this->provider_id]
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_title', $data['code']);
    }
    
    /**
     * Teste la création d'un service avec une durée invalide
     */
    public function test_create_service_with_invalid_duration()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => 'Service de test',
            'duree' => 0, // Durée invalide
            'prix' => 50.00,
            'providers' => [$this->provider_id]
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_duration', $data['code']);
    }
    
    /**
     * Teste la création d'un service avec un prix invalide
     */
    public function test_create_service_with_invalid_price()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => 'Service de test',
            'duree' => 60,
            'prix' => -10, // Prix invalide
            'providers' => [$this->provider_id]
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_price', $data['code']);
    }
    
    /**
     * Teste la création d'un service avec un prestataire inexistant
     */
    public function test_create_service_with_nonexistent_provider()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => 'Service de test',
            'duree' => 60,
            'prix' => 50.00,
            'providers' => [999999] // ID de prestataire inexistant
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_provider', $data['code']);
    }

    /**
     * Teste la création d'un service avec un titre vide
     */
    public function test_create_service_with_empty_title()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => '', // Titre vide
            'duree' => 60,
            'prix' => 50.00,
            'providers' => [$this->provider_id]
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('missing_title', $data['code']);
    }

    /**
     * Teste la création d'un service avec une durée invalide
     */
    public function test_create_service_with_invalid_duration()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => 'Service Test',
            'duree' => -10, // Durée négative invalide
            'prix' => 50.00,
            'providers' => [$this->provider_id]
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_duration', $data['code']);
    }

    /**
     * Teste la création d'un service avec un prix invalide
     */
    public function test_create_service_with_invalid_price()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => 'Service Test',
            'duree' => 60,
            'prix' => -10.00, // Prix négatif invalide
            'providers' => [$this->provider_id]
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_price', $data['code']);
    }

    /**
     * Teste la création d'un service avec un prestataire inexistant
     */
    public function test_create_service_with_nonexistent_provider()
    {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/services');
        $body = [
            'title' => 'Service Test',
            'duree' => 60,
            'prix' => 50.00,
            'providers' => [999999] // ID de prestataire inexistant
        ];
        $request->set_body(json_encode($body));
        $request->set_header('Content-Type', 'application/json');

        $response = \rest_do_request_simulation($request);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('invalid_provider', $data['code']);
    }
}
