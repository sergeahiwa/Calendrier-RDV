<?php
/**
 * Tests pour l'API REST du plugin Calendrier RDV
 */
class RestApiTest extends WP_UnitTestCase {
    /**
     * @var WP_REST_Server
     */
    protected $server;

    /**
     * Configuration des tests
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialiser l'API REST
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init');
        
        // Créer un utilisateur avec les droits nécessaires
        $this->user_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'testadmin',
            'user_email' => 'admin@example.com'
        ]);
        
        // Se connecter en tant qu'administrateur
        wp_set_current_user($this->user_id);
    }
    
    /**
     * Teste la récupération des créneaux disponibles
     */
    public function test_get_available_slots() {
        // Créer des données de test
        $prestataire_id = $this->create_test_prestataire();
        $service_id = $this->create_test_service();
        
        // Créer une requête
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/creneaux');
        $request->set_query_params([
            'prestataire_id' => $prestataire_id,
            'service_id' => $service_id,
            'date' => date('Y-m-d', strtotime('+1 day'))
        ]);
        
        // Exécuter la requête
        $response = $this->server->dispatch($request);
        $data = $response->get_data();
        
        // Vérifications
        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('creneaux', $data);
        $this->assertIsArray($data['creneaux']);
    }
    
    /**
     * Teste la création d'un rendez-vous
     */
    public function test_create_booking() {
        // Créer des données de test
        $prestataire_id = $this->create_test_prestataire();
        $service_id = $this->create_test_service();
        
        // Données de la requête
        $booking_data = [
            'prestataire_id' => $prestataire_id,
            'service_id' => $service_id,
            'date_rdv' => date('Y-m-d', strtotime('+1 day')),
            'heure_debut' => '14:00',
            'client_nom' => 'Test User',
            'client_email' => 'test@example.com',
            'client_telephone' => '0123456789',
            'notes' => 'Test de création de rendez-vous'
        ];
        
        // Créer une requête
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/rendez-vous');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($booking_data));
        
        // Exécuter la requête
        $response = $this->server->dispatch($request);
        $data = $response->get_data();
        
        // Vérifications
        $this->assertEquals(201, $response->get_status());
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('reference', $data);
        $this->assertEquals($booking_data['client_nom'], $data['client_nom']);
    }
    
    /**
     * Teste la validation des données de création
     */
    public function test_booking_validation() {
        // Données invalides (email manquant)
        $invalid_data = [
            'prestataire_id' => 1,
            'service_id' => 1,
            'date_rdv' => date('Y-m-d'),
            'heure_debut' => '14:00',
            'client_nom' => 'Test User',
            'client_telephone' => '0123456789'
        ];
        
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/rendez-vous');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($invalid_data));
        
        $response = $this->server->dispatch($request);
        
        // Doit retourner une erreur 400
        $this->assertEquals(400, $response->get_status());
        
        $data = $response->get_data();
        $this->assertArrayHasKey('code', $data);
        $this->assertEquals('missing_parameter', $data['code']);
    }
    
    /**
     * Crée un prestataire de test
     */
    private function create_test_prestataire() {
        return $this->factory->post->create([
            'post_type' => 'prestataire',
            'post_title' => 'Dr. Test',
            'post_status' => 'publish'
        ]);
    }
    
    /**
     * Crée un service de test
     */
    private function create_test_service() {
        return $this->factory->post->create([
            'post_type' => 'service',
            'post_title' => 'Consultation',
            'post_status' => 'publish'
        ]);
    }
}
