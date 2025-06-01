<?php

namespace CalendrierRdv\Tests\Integration;

use CalendrierRdv\Tests\Integration\TestCase;
use WP_REST_Request;

class Appointments_Test extends TestCase
{
    protected $service_id;
    protected $provider_id;
    protected $appointment_id;
    protected $customer_id;
    protected $factory;

    protected function setUp(): void 
    {
        parent::setUp();
        
        // Créer des données de test
        $this->provider_id = create_test_prestataire([
            'meta_input' => [
                '_disponibilites' => 'monday,tuesday,wednesday,thursday,friday',
                '_horaires' => [
                    'monday' => ['start' => '09:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '17:00']
                ]
            ]
        ]);
        
        $this->service_id = create_test_service([
            'meta_input' => [
                '_prestataires' => [$this->provider_id],
                '_duree' => 60,
                '_prix' => 50.00
            ]
        ]);
        
        $this->customer_id = wp_insert_user([
            'user_login' => 'testclient',
            'user_email' => 'client@test.com',
            'user_pass' => 'password',
            'role' => 'subscriber'
        ]);
        
        wp_set_current_user($this->customer_id);
    }

    /**
     * Teste la création d'un rendez-vous
     */
    public function test_create_appointment()
    {
        $start_time = strtotime('next monday 10:00');
        
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/appointments');
        $request->set_param('service_id', $this->service_id);
        $request->set_param('provider_id', $this->provider_id);
        $request->set_param('start_time', $start_time);
        $request->set_param('customer_note', 'Test de rendez-vous');
        
        $response = rest_do_request($request);
        $data = $response->get_data();
        
        $this->assertEquals(201, $response->get_status());
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($this->service_id, $data['service_id']);
        $this->assertEquals($this->provider_id, $data['provider_id']);
        $this->assertEquals($this->customer_id, $data['customer_id']);
        
        $this->appointment_id = $data['id'];
    }

    /**
     * Teste la récupération des créneaux disponibles
     */
    public function test_get_available_slots()
    {
        $start_date = strtotime('next monday');
        $end_date = strtotime('next monday +1 day');
        
        $request = new WP_REST_Request('GET', '/calendrier-rdv/v1/availability/slots');
        $request->set_query_params([
            'service_id' => $this->service_id,
            'provider_id' => $this->provider_id,
            'start_date' => date('Y-m-d', $start_date),
            'end_date' => date('Y-m-d', $end_date)
        ]);
        
        $response = rest_do_request($request);
        $data = $response->get_data();
        
        $this->assertEquals(200, $response->get_status());
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    /**
     * Teste que la durée est correctement incluse dans les propriétés étendues
     */
    public function test_appointment_includes_duration_in_extended_props()
    {
        // Créer un rendez-vous de test
        $start_time = strtotime('next monday 10:00');
        
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/appointments');
        $request->set_param('service_id', $this->service_id);
        $request->set_param('provider_id', $this->provider_id);
        $request->set_param('start_time', $start_time);
        $request->set_param('duration', 45); // Durée personnalisée
        $request->set_param('customer_note', 'Test de durée');
        
        $response = rest_do_request($request);
        $data = $response->get_data();
        $this->appointment_id = $data['id'];

        // Récupérer les rendez-vous formatés pour le calendrier
        $appointments = \CalendrierRdv\Core\Database\Appointments::get_formatted_appointments_for_calendar(
            date('Y-m-d', $start_time),
            date('Y-m-d', strtotime('+1 day', $start_time))
        );

        // Vérifier que le rendez-vous a été trouvé
        $this->assertNotEmpty($appointments, 'Aucun rendez-vous trouvé dans la plage de dates');
        
        // Trouver notre rendez-vous
        $test_appointment = null;
        foreach ($appointments as $appt) {
            if ($appt['id'] === $this->appointment_id) {
                $test_appointment = $appt;
                break;
            }
        }

        $this->assertNotNull($test_appointment, 'Le rendez-vous de test n\'a pas été trouvé');
        
        // Vérifier que la durée est correctement définie
        $this->assertArrayHasKey('duration', $test_appointment, 'La durée n\'est pas définie au niveau racine');
        $this->assertEquals(45, $test_appointment['duration'], 'La durée au niveau racine est incorrecte');
        
        // Vérifier les propriétés étendues
        $this->assertArrayHasKey('extendedProps', $test_appointment, 'Les propriétés étendues sont manquantes');
        $this->assertArrayHasKey('duration', $test_appointment['extendedProps'], 'La durée dans les propriétés étendues est manquante');
        $this->assertEquals(45, $test_appointment['extendedProps']['duration'], 'La durée dans les propriétés étendues est incorrecte');
        
        // Nettoyer
        wp_delete_post($this->appointment_id, true);
    }

    /**
     * Teste l'annulation d'un rendez-vous
     */
    public function test_cancel_appointment()
    {
        // Créer un rendez-vous
        $start_time = strtotime('next tuesday 11:00');
        
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/appointments');
        $request->set_param('service_id', $this->service_id);
        $request->set_param('provider_id', $this->provider_id);
        $request->set_param('start_time', $start_time);
        
        $response = rest_do_request($request);
        $appointment = $response->get_data();
        
        // Annuler le rendez-vous
        $request = new WP_REST_Request('PUT', '/calendrier-rdv/v1/appointments/' . $appointment['id'] . '/cancel');
        $response = rest_do_request($request);
        
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('cancelled', $data['status']);
    }

    protected function tearDown(): void 
    {
        if (isset($this->appointment_id)) {
            wp_delete_post($this->appointment_id, true);
        }
        if (isset($this->service_id)) {
            wp_delete_post($this->service_id, true);
        }
        if (isset($this->provider_id)) {
            wp_delete_post($this->provider_id, true);
        }
        if (isset($this->customer_id)) {
            wp_delete_user($this->customer_id);
        }
        
        parent::tearDown();
    }
}
