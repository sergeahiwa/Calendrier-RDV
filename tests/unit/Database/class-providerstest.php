<?php
/**
 * Fichier de tests unitaires pour la classe Providers.
 *
 * @package    CalendrierRdv\Tests\Unit\Database
 * @subpackage Database
 */

namespace CalendrierRdv\Tests\Unit\Database;

use CalendrierRdv\Core\Database\Providers;
use WP_REST_Request;
use WP_REST_Response;
use PHPUnit\Framework\TestCase;

/**
 * Classe de tests pour les fonctionnalités de la base de données des prestataires.
 */
class Providers_Test extends TestCase {
	/**
	 * Instance de WPDB.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Nom de la table des prestataires.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Configuration initiale pour chaque test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'rdv_providers';

		// Créer la table de test.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$this->table_name} (
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

		// Ajouter un prestataire de test.
		$this->wpdb->insert(
			$this->table_name,
			array(
				'name'    => 'Test Provider',
				'email'   => 'test@example.com',
				'phone'   => '0123456789',
				'address' => '123 Test Street',
				'active'  => 1,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);
	}

	/**
	 * Nettoyage après chaque test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
	}

	/**
	 * Teste la récupération des prestataires.
	 *
	 * @return void
	 */
	public function testGetProviders(): void {
		// Vider la table avant le test.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		// Créer un prestataire de test.
		$test_provider = array(
			'name'    => 'Test Provider',
			'email'   => 'test@example.com',
			'phone'   => '1234567890',
			'address' => '123 Test Street',
			'active'  => 1,
		);

		$result = $this->wpdb->insert( $this->table_name, $test_provider );
		$this->assertNotFalse( $result, 'Échec de l_insertion du prestataire de test.' );

		// Créer une requête mock.
		$request = $this->createMock( WP_REST_Request::class );

		// Appeler la méthode.
		$response = Providers::getProviders( $request );

		// Vérifier la réponse.
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertGreaterThan( 0, count( $data ), 'Aucun prestataire trouvé dans la base de données.' );

		// Vérifier les données du prestataire.
		$provider = $data[0];
		$this->assertArrayHasKey( 'name', $provider, 'La clé "name" est manquante.' );
		$this->assertArrayHasKey( 'email', $provider, 'La clé "email" est manquante.' );
		$this->assertEquals( 'Test Provider', $provider['name'] );
		$this->assertEquals( 'test@example.com', $provider['email'] );
	}

	/**
	 * Teste la création d'un prestataire.
	 *
	 * @return void
	 */
	public function testCreateProvider(): void {
		// Vider la table avant le test.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		// Préparer les données du prestataire.
		$provider_data = array(
			'name'    => 'New Provider',
			'email'   => 'new@example.com',
			'phone'   => '0987654321',
			'address' => '456 New Street',
			'active'  => true,
		);

		// Créer une requête mock.
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_json_params' )->willReturn( $provider_data );

		// Simuler les capacités utilisateur.
		global $current_user_can_return;
		$current_user_can_return = true;

		// Appeler la méthode.
		$response = Providers::createProvider( $request );

		// Vérifier la réponse.
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 201, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertIsArray( $response_data, 'La réponse doit être un tableau.' );
		$this->assertArrayHasKey( 'data', $response_data, 'La clé "data" est manquante dans la réponse.' );
		$this->assertIsArray( $response_data['data'], 'La valeur de la clé "data" doit être un tableau.' );
		$this->assertArrayHasKey( 'id', $response_data['data'], 'La clé "id" est manquante dans les données.' );

		// Vérifier que le prestataire a été créé.
		$created_provider = $this->wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$response_data['data']['id']
			)
		);

		$this->assertNotNull( $created_provider, 'Le prestataire n_a pas été créé dans la base de données.' );
		$this->assertEquals( 'New Provider', $created_provider->name );
		$this->assertEquals( 'new@example.com', $created_provider->email );
	}

	/**
	 * Teste la mise à jour d'un prestataire.
	 *
	 * @return void
	 */
	public function testUpdateProvider(): void {
		// Vider la table avant le test.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		// Créer un prestataire de test.
		$test_provider = array(
			'name'       => 'Test Provider',
			'email'      => 'test@example.com',
			'phone'      => '1234567890',
			'address'    => '123 Test Street',
			'active'     => 1,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$result = $this->wpdb->insert( $this->table_name, $test_provider );
		$this->assertNotFalse( $result, 'Échec de l_insertion du prestataire de test.' );
		$provider_id = $this->wpdb->insert_id;
		$this->assertGreaterThan( 0, $provider_id, 'ID du prestataire invalide.' );

		// Préparer les données mises à jour.
		$update_data = array(
			'name'    => 'Updated Provider',
			'email'   => 'updated@example.com',
			'phone'   => '9876543210',
			'address' => '789 Updated Street',
			'active'  => false,
		);

		// Créer une requête mock.
		$request = $this->createMock( WP_REST_Request::class );
		$request->method( 'get_json_params' )->willReturn( $update_data );

		// Simuler les capacités utilisateur.
		global $current_user_can_return;
		$current_user_can_return = true;

		// Appeler la méthode.
		$response = Providers::updateProvider( $request, $provider_id );

		// Vérifier la réponse.
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertIsArray( $response_data, 'La réponse doit être un tableau.' );
		$this->assertArrayHasKey( 'message', $response_data, 'La clé "message" est manquante.' );
		$this->assertArrayHasKey( 'data', $response_data, 'La clé "data" est manquante.' );

		// Vérifier que le prestataire a été mis à jour dans la base de données.
		$updated_provider = $this->wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$provider_id
			)
		);

		$this->assertNotNull( $updated_provider, 'Le prestataire n_a pas été trouvé dans la base de données.' );
		$this->assertEquals( 'Updated Provider', $updated_provider->name, 'Le nom n_a pas été mis à jour.' );
		$this->assertEquals( 'updated@example.com', $updated_provider->email, 'L_email n_a pas été mis à jour.' );
		$this->assertEquals( '9876543210', $updated_provider->phone, 'Le téléphone n_a pas été mis à jour.' );
		$this->assertEquals( '789 Updated Street', $updated_provider->address, 'L_adresse n_a pas été mise à jour.' );
		$this->assertEquals( 0, (int) $updated_provider->active, 'Le statut actif n_a pas été mis à jour.' );
	}

	/**
	 * Teste la suppression d'un prestataire.
	 *
	 * @return void
	 */
	public function testDeleteProvider(): void {
		// Vider la table avant le test.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		// Créer un prestataire de test.
		$test_provider = array(
			'name'       => 'Test Provider',
			'email'      => 'test@example.com',
			'phone'      => '1234567890',
			'address'    => '123 Test Street',
			'active'     => 1,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$result = $this->wpdb->insert( $this->table_name, $test_provider );
		$this->assertNotFalse( $result, 'Échec de l_insertion du prestataire de test.' );
		$provider_id = $this->wpdb->insert_id;
		$this->assertGreaterThan( 0, $provider_id, 'ID du prestataire invalide.' );

		// Vérifier que le prestataire existe avant la suppression.
		$existing_provider = $this->wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$provider_id
			)
		);
		$this->assertNotNull( $existing_provider, 'Le prestataire devrait exister avant la suppression.' );

		// Créer une requête mock.
		$request = $this->createMock( WP_REST_Request::class );

		// Simuler les capacités utilisateur.
		global $current_user_can_return;
		$current_user_can_return = true;

		// Appeler la méthode.
		$response = Providers::deleteProvider( $request, $provider_id );

		// Vérifier la réponse.
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertIsArray( $response_data, 'La réponse doit être un tableau.' );
		$this->assertArrayHasKey( 'message', $response_data, 'La clé "message" est manquante.' );

		// Vérifier que le prestataire a été supprimé de la base de données.
		$deleted_provider = $this->wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$provider_id
			)
		);

		$this->assertNull( $deleted_provider, 'Le prestataire n_a pas été supprimé de la base de données.' );
	}
}
