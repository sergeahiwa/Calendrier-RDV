<?php
/**
 * Class AppointmentApiTest
 *
 * @package CalendrierRdv\Tests\Integration\Api
 */

namespace CalendrierRdv\Tests\Integration\Api;

use WP_UnitTestCase;
use WP_REST_Request;
use WP_REST_Server;

class AppointmentApiTest extends WP_UnitTestCase {
    protected $server;
    protected $namespace = '/calendrier-rdv/v1';
    protected $admin_id;
    protected $appointment_id;

    public function setUp(): void {
        parent::setUp();
        
        // Initialiser l'API REST
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init');
        
        // Créer un utilisateur administrateur pour les tests
        $this->admin_id = $this->factory->user->create([
            'role' => 'administrator',
            'user_login' => 'testadmin',
            'user_pass' => 'testpass'
        ]);
        
        // Créer un rendez-vous de test
        $this->appointment_id = $this->factory->post->create([
            'post_type' => 'cal_rdv_appointment',
            'post_status' => 'publish',
            'post_title' => 'Test Appointment'
        ]);
        
        // Ajouter des métadonnées au rendez-vous
        update_post_meta($this->appointment_id, '_appointment_customer_name', 'Test Customer');
        update_post_meta($this->appointment_id, '_appointment_customer_email', 'test@example.com');
        update_post_meta($this->appointment_id, '_appointment_start', '2025-06-15 14:00:00');
        update_post_meta($this->appointment_id, '_appointment_end', '2025-06-15 15:00:00');
        update_post_meta($this->appointment_id, '_appointment_status', 'pending');
    }

    public function testGetAppointments() {
        // Se connecter en tant qu'administrateur
        wp_set_current_user($this->admin_id);
        
        // Créer une requête GET pour récupérer les rendez-vous
        $request = new WP_REST_Request('GET', $this->namespace . '/appointments');
        $request->set_query_params([
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-30'
        ]);
        
        // Exécuter la requête
        $response = $this->server->dispatch($request);
        $data = $response->get_data();
        
        // Vérifier la réponse
        $this->assertEquals(200, $response->get_status());
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertEquals('Test Customer', $data[0]['customer_name']);
    }

    public function testCreateAppointment() {
        // Données du nouveau rendez-vous
        $appointment_data = [
            'customer_name' => 'New Customer',
            'customer_email' => 'new@example.com',
            'start_date' => '2025-06-20',
            'start_time' => '10:00',
            'duration' => 60,
            'service_id' => 1,
            'provider_id' => 1,
            'notes' => 'Test notes'
        ];
        
        // Créer une requête POST
        $request = new WP_REST_Request('POST', $this->namespace . '/appointments');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($appointment_data));
        
        // Exécuter la requête
        $response = $this->server->dispatch($request);
        $data = $response->get_data();
        
        // Vérifier la réponse
        $this->assertEquals(201, $response->get_status());
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('New Customer', $data['customer_name']);
        $this->assertEquals('pending', $data['status']);
    }

    public function testUpdateAppointmentStatus() {
        // Se connecter en tant qu'administrateur
        wp_set_current_user($this->admin_id);
        
        // Données de mise à jour
        $update_data = [
            'status' => 'confirmed'
        ];
        
        // Créer une requête PUT
        $request = new WP_REST_Request('PUT', $this->namespace . '/appointments/' . $this->appointment_id . '/status');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(json_encode($update_data));
        
        // Exécuter la requête
        $response = $this->server->dispatch($request);
        $data = $response->get_data();
        
        // Vérifier la réponse
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('confirmed', $data['status']);
    }

    public function testDeleteAppointment() {
        // Se connecter en tant qu'administrateur
        wp_set_current_user($this->admin_id);
        
        // Créer une requête DELETE
        $request = new WP_REST_Request('DELETE', $this->namespace . '/appointments/' . $this->appointment_id);
        
        // Exécuter la requête
        $response = $this->server->dispatch($request);
        
        // Vérifier la réponse
        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('trash', get_post_status($this->appointment_id));
    }

    public function tearDown(): void {
        // Nettoyer les données de test
        wp_delete_post($this->appointment_id, true);
        wp_delete_user($this->admin_id);
        
        parent::tearDown();
    }
}
