<?php
// tests/RestBookingTest.php

use PHPUnit\Framework\TestCase;

class Calendrier_RDV_REST_Booking_Test extends WP_UnitTestCase {

    protected static $admin_id;

    public static function wpSetUpBeforeClass($factory) {
        self::$admin_id = $factory->user->create([
            'role' => 'administrator',
            'user_login' => 'admin_test',
            'user_pass' => 'password',
        ]);
    }

    public function setUp(): void {
        parent::setUp();
        wp_set_current_user(self::$admin_id);
    }

    public function test_booking_endpoint_valid_request() {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/book');
        $request->set_body_params([
            'nom'         => 'Jean Dupont',
            'email'       => 'jean@example.com',
            'prestation'  => 'Consultation',
            'date_rdv'    => '2025-06-01',
            'heure_rdv'   => '10:00',
            'prestataire' => 1,
        ]);

        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function test_booking_endpoint_invalid_email() {
        $request = new WP_REST_Request('POST', '/calendrier-rdv/v1/book');
        $request->set_body_params([
            'nom'         => 'Jean Dupont',
            'email'       => 'email_invalide',
            'prestation'  => 'Consultation',
            'date_rdv'    => '2025-06-01',
            'heure_rdv'   => '10:00',
            'prestataire' => 1,
        ]);

        $response = rest_get_server()->dispatch($request);
        $this->assertEquals(400, $response->get_status());
    }
}
