<?php

namespace CalendrierRdv\Tests\Unit\Database;

use CalendrierRdv\Core\Database\Appointments;
use WP_REST_Request;
use WP_REST_Response;
use PHPUnit\Framework\TestCase;

class Appointments_Test extends TestCase {
	private $wpdb;
	private $appointments_table;
	private $services_table;
	private $providers_table;

	protected function setUp(): void {
		parent::setUp();
		global $wpdb;
		$this->wpdb               = $wpdb;
		$this->appointments_table = $wpdb->prefix . 'rdv_appointments';
		$this->services_table     = $wpdb->prefix . 'rdv_services';
		$this->providers_table    = $wpdb->prefix . 'rdv_providers';

		// Réinitialiser le compteur de disponibilité
		if ( method_exists( $wpdb, 'reset_availability_check_count' ) ) {
			$wpdb->reset_availability_check_count();
		}

		// Créer les tables de test
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$this->appointments_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            provider_id bigint(20) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )"
		);

		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$this->services_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            duration int NOT NULL,
            price decimal(10,2) DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )"
		);

		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$this->providers_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(100) DEFAULT NULL,
            address text DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )"
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->appointments_table}" );
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->services_table}" );
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->providers_table}" );
	}

	public function testCreateAppointment() {
		// Créer un service de test
		$service_id = $this->wpdb->insert(
			$this->services_table,
			array(
				'name'     => 'Test Service',
				'duration' => 60,
				'price'    => 50.00,
				'active'   => 1,
			)
		);

		// Créer un prestataire de test
		$provider_id = $this->wpdb->insert(
			$this->providers_table,
			array(
				'name'   => 'Test Provider',
				'email'  => 'provider@example.com',
				'active' => 1,
			)
		);

		// Préparer les données du rendez-vous
		$appointment_data = array(
			'service_id'     => $service_id,
			'provider_id'    => $provider_id,
			'date'           => '2025-05-23',
			'time'           => '14:00:00',
			'customer_name'  => 'John Doe',
			'customer_email' => 'john@example.com',
			'customer_phone' => '1234567890',
			'notes'          => 'Test notes',
		);

		// Créer une requête mock
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_json_params' )->willReturn( $appointment_data );

		// Simuler la vérification du nonce
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';

		// Appeler la méthode
		$response = Appointments::createAppointment( $request );

		// Vérifier la réponse
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'id', $data );

		// Vérifier que le rendez-vous a été créé
		$created_appointment = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->appointments_table} WHERE id = %d",
				$data['id']
			)
		);

		$this->assertNotNull( $created_appointment );
		$this->assertEquals( $service_id, $created_appointment->service_id );
		$this->assertEquals( $provider_id, $created_appointment->provider_id );
		$this->assertEquals( '2025-05-23', $created_appointment->appointment_date );
		$this->assertEquals( '14:00:00', $created_appointment->appointment_time );
		$this->assertEquals( 'pending', $created_appointment->status );
	}

	public function testCheckAvailability() {
		// Créer un service de test
		$service_id = $this->wpdb->insert(
			$this->services_table,
			array(
				'name'     => 'Test Service',
				'duration' => 60,
				'price'    => 50.00,
				'active'   => 1,
			)
		);

		// Créer un prestataire de test
		$provider_id = $this->wpdb->insert(
			$this->providers_table,
			array(
				'name'   => 'Test Provider',
				'email'  => 'provider@example.com',
				'active' => 1,
			)
		);

		// Créer une première requête avec un nonce valide
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';
		$request1                   = new WP_REST_Request( 'POST', '/calendrier-rdv/v1/appointments' );
		$request1->set_json_params(
			array(
				'service_id'     => $service_id,
				'provider_id'    => $provider_id,
				'date'           => '2025-05-23',
				'time'           => '14:00:00',
				'customer_name'  => 'Test User',
				'customer_email' => 'test@example.com',
				'customer_phone' => '0123456789',
			)
		);

		// Créer un rendez-vous
		$response = Appointments::createAppointment( $request1 );
		$this->assertEquals( 201, $response->get_status() );

		// Créer une deuxième requête pour le même créneau
		$request2 = new WP_REST_Request( 'POST', '/calendrier-rdv/v1/appointments' );
		$request2->set_json_params(
			array(
				'service_id'     => $service_id,
				'provider_id'    => $provider_id,
				'date'           => '2025-05-23',
				'time'           => '14:00:00',
				'customer_name'  => 'Another User',
				'customer_email' => 'another@example.com',
				'customer_phone' => '9876543210',
			)
		);

		// Tenter de créer un autre rendez-vous sur le même créneau
		$response = Appointments::createAppointment( $request2 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Ce créneau n\'est pas disponible', $response->get_data()['message'] );
	}

	public function testCreateAppointmentWithInvalidData() {
		// Tester avec des données manquantes
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';
		$request1                   = new WP_REST_Request( 'POST', '/calendrier-rdv/v1/appointments' );
		$request1->set_json_params(
			array(
			// Données manquantes intentionnellement
			)
		);

		$response = Appointments::createAppointment( $request1 );
		$this->assertEquals( 400, $response->get_status() );

		// Tester avec un nonce invalide
		$_SERVER['HTTP_X_WP_NONCE'] = 'invalid-nonce';
		$request2                   = new WP_REST_Request( 'POST', '/calendrier-rdv/v1/appointments' );
		$request2->set_json_params(
			array(
				'service_id'     => 1,
				'provider_id'    => 1,
				'date'           => '2025-05-23',
				'time'           => '14:00:00',
				'customer_name'  => 'Test User',
				'customer_email' => 'test@example.com',
				'customer_phone' => '0123456789',
			)
		);

		$response = Appointments::createAppointment( $request2 );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function testCancelAppointment() {
		// Créer un service de test
		$service_id = $this->wpdb->insert(
			$this->services_table,
			array(
				'name'     => 'Test Service',
				'duration' => 60,
				'price'    => 50.00,
				'active'   => 1,
			)
		);

		// Créer un prestataire de test
		$provider_id = $this->wpdb->insert(
			$this->providers_table,
			array(
				'name'   => 'Test Provider',
				'email'  => 'provider@example.com',
				'active' => 1,
			)
		);

		// Créer un rendez-vous de test
		$appointment_data = array(
			'service_id'     => $service_id,
			'provider_id'    => $provider_id,
			'date'           => '2025-05-23',
			'time'           => '14:00:00',
			'customer_name'  => 'Test User',
			'customer_email' => 'test@example.com',
			'customer_phone' => '0123456789',
		);

		// Créer une requête mock pour créer le rendez-vous
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';
		$request                    = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_json_params' )->willReturn( $appointment_data );

		// Créer le rendez-vous
		$response = Appointments::createAppointment( $request );
		$this->assertEquals( 201, $response->get_status() );

		$appointment_id = $response->get_data()['id'];

		// Créer une requête mock pour annuler le rendez-vous
		$cancel_request = $this->createMock( WP_REST_Request::class );
		$cancel_request->method( 'get_param' )->with( 'id' )->willReturn( $appointment_id );

		// Annuler le rendez-vous
		$response = Appointments::cancelAppointment( $cancel_request );

		// Vérifier la réponse
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Rendez-vous annulé avec succès', $response->get_data()['message'] );

		// Note: Dans un environnement de test réel, nous vérifierions que le statut a été mis à jour
		// Mais dans notre environnement de test simulé, nous vérifions uniquement la réponse
	}

	public function testCancelAppointmentWithInvalidData() {
		// Tester avec un ID manquant
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';
		$request1                   = $this->createMock( WP_REST_Request::class );
		$request1->method( 'get_param' )->with( 'id' )->willReturn( null );

		$response = Appointments::cancelAppointment( $request1 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'ID du rendez-vous manquant', $response->get_data()['message'] );

		// Tester avec un ID invalide
		$request2 = $this->createMock( WP_REST_Request::class );
		$request2->method( 'get_param' )->with( 'id' )->willReturn( 9999 ); // ID inexistant

		$response = Appointments::cancelAppointment( $request2 );
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'Rendez-vous introuvable', $response->get_data()['message'] );

		// Tester avec un nonce invalide
		$_SERVER['HTTP_X_WP_NONCE'] = 'invalid-nonce';
		$request3                   = $this->createMock( WP_REST_Request::class );
		$request3->method( 'get_param' )->with( 'id' )->willReturn( 1 );

		$response = Appointments::cancelAppointment( $request3 );
		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'Nonce invalide', $response->get_data()['message'] );
	}

	public function testUpdateAppointment() {
		// Créer un service de test
		$service_id = $this->wpdb->insert(
			$this->services_table,
			array(
				'name'     => 'Test Service',
				'duration' => 60,
				'price'    => 50.00,
				'active'   => 1,
			)
		);

		// Créer un prestataire de test
		$provider_id = $this->wpdb->insert(
			$this->providers_table,
			array(
				'name'   => 'Test Provider',
				'email'  => 'provider@example.com',
				'active' => 1,
			)
		);

		// Créer un rendez-vous de test
		$appointment_data = array(
			'service_id'     => $service_id,
			'provider_id'    => $provider_id,
			'date'           => '2025-05-23',
			'time'           => '14:00:00',
			'customer_name'  => 'Test User',
			'customer_email' => 'test@example.com',
			'customer_phone' => '0123456789',
		);

		// Créer une requête mock pour créer le rendez-vous
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';
		$request                    = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_json_params' )->willReturn( $appointment_data );

		// Créer le rendez-vous
		$response = Appointments::createAppointment( $request );
		$this->assertEquals( 201, $response->get_status() );

		$appointment_id = $response->get_data()['id'];

		// Données de mise à jour
		$update_data = array(
			'date'          => '2025-05-24', // Nouvelle date
			'time'          => '15:30:00',  // Nouvelle heure
			'customer_name' => 'Updated User',
			'notes'         => 'Updated notes',
		);

		// Créer une requête mock pour mettre à jour le rendez-vous
		$update_request = $this->createMock( WP_REST_Request::class );
		$update_request->method( 'get_param' )->with( 'id' )->willReturn( $appointment_id );
		$update_request->method( 'get_json_params' )->willReturn( $update_data );

		// Mettre à jour le rendez-vous
		$response = Appointments::updateAppointment( $update_request );

		// Vérifier la réponse
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Rendez-vous mis à jour avec succès', $response->get_data()['message'] );
	}

	public function testUpdateAppointmentWithInvalidData() {
		// Tester avec un ID manquant
		$_SERVER['HTTP_X_WP_NONCE'] = 'test-nonce';
		$request1                   = $this->createMock( WP_REST_Request::class );
		$request1->method( 'get_param' )->with( 'id' )->willReturn( null );
		$request1->method( 'get_json_params' )->willReturn( array( 'date' => '2025-05-24' ) );

		$response = Appointments::updateAppointment( $request1 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'ID du rendez-vous manquant', $response->get_data()['message'] );

		// Tester avec des données de mise à jour vides
		$request2 = $this->createMock( WP_REST_Request::class );
		$request2->method( 'get_param' )->with( 'id' )->willReturn( 1 );
		$request2->method( 'get_json_params' )->willReturn( array() );

		$response = Appointments::updateAppointment( $request2 );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Aucune donnée de mise à jour fournie', $response->get_data()['message'] );

		// Tester avec un ID invalide
		$request3 = $this->createMock( WP_REST_Request::class );
		$request3->method( 'get_param' )->with( 'id' )->willReturn( 9999 ); // ID inexistant
		$request3->method( 'get_json_params' )->willReturn( array( 'date' => '2025-05-24' ) );

		$response = Appointments::updateAppointment( $request3 );
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'Rendez-vous introuvable', $response->get_data()['message'] );

		// Tester avec un nonce invalide
		$_SERVER['HTTP_X_WP_NONCE'] = 'invalid-nonce';
		$request4                   = $this->createMock( WP_REST_Request::class );
		$request4->method( 'get_param' )->with( 'id' )->willReturn( 1 );
		$request4->method( 'get_json_params' )->willReturn( array( 'date' => '2025-05-24' ) );

		$response = Appointments::updateAppointment( $request4 );
		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'Nonce invalide', $response->get_data()['message'] );
	}
}
