<?php
// tests/RestSlotsTest.php

use PHPUnit\Framework\TestCase;

class Calendrier_RDV_REST_Slots_Test extends WP_UnitTestCase {

    public static function wpSetUpBeforeClass( $factory ) {
        // Crée un prestataire et quelques RDV pour le test
        $presta_id = $factory->post->create([
            'post_type'  => 'prestataire',
            'post_title' => 'Prestataire Test',
            'post_status'=> 'publish',
        ]);
        // Insère quelques créneaux en base (via direct SQL ou la fonction métier)
        global $wpdb;
        $table = $wpdb->prefix . 'calrdv_reservations';
        $wpdb->insert($table, [
            'nom'         => 'Test User',
            'email'       => 'test@example.com',
            'prestation'  => 'Consultation',
            'date_rdv'    => '2025-06-10',
            'heure_rdv'   => '14:00',
            'prestataire' => $presta_id,
            'statut'      => 'confirmé',
            'rappel_envoye'=> 0
        ], ['%s','%s','%s','%s','%s','%d','%s','%d']);
    }

    public function test_slots_endpoint_returns_array() {
        $request  = new WP_REST_Request('GET', '/calendrier-rdv/v1/slots');
        $response = rest_get_server()->dispatch( $request );
        $this->assertEquals(200, $response->get_status());

        $data = $response->get_data();
        $this->assertIsArray($data, 'Le endpoint doit retourner un tableau d’événements');

        // Vérifie la présence des clés attendues sur le premier élément
        if (!empty($data)) {
            $event = $data[0];
            $this->assertArrayHasKey('nom', $event);
            $this->assertArrayHasKey('date_rdv', $event);
            $this->assertArrayHasKey('heure_rdv', $event);
        }
    }
}
