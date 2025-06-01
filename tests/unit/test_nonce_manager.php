<?php
/**
 * Fichier de tests unitaires pour la classe NonceManager.
 *
 * @package    CalendrierRdv
 * @subpackage Tests\Unit
 */

/**
 * Classe de tests pour NonceManager.
 */
class NonceManagerTest extends WP_UnitTestCase {
	/**
	 * Instance de NonceManager.
	 *
	 * @var \CalendrierRdv\Includes\NonceManager
	 */
	private $nonce_manager;

	/**
	 * Configuration initiale pour chaque test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->nonce_manager = \CalendrierRdv\Includes\NonceManager::getInstance();
	}

	/**
	 * Teste la création d'un nonce.
	 *
	 * @return void
	 */
	public function testCreateNonce(): void {
		$nonce = $this->nonce_manager->createNonce( 'create_appointment' );
		$this->assertNotEmpty( $nonce );
		$this->assertStringContainsString( 'calendrier_rdv_create_appointment', $nonce );
	}

	/**
	 * Teste la vérification d'un nonce.
	 *
	 * @return void
	 */
	public function testVerifyNonce(): void {
		$action = 'create_appointment';
		$nonce  = $this->nonce_manager->createNonce( $action );

		$this->assertTrue( $this->nonce_manager->verifyNonce( $nonce, $action ) );
		$this->assertFalse( $this->nonce_manager->verifyNonce( 'invalid_nonce', $action ) );
	}

	/**
	 * Teste la fonction checkNonce qui lève une exception si le nonce est invalide.
	 *
	 * @return void
	 */
	public function testCheckNonce(): void {
		$action = 'create_appointment';
		$nonce  = $this->nonce_manager->createNonce( $action );

		// Test avec un nonce valide.
		try {
			$this->nonce_manager->checkNonce( $nonce, $action );
			$this->assertTrue( true, 'La vérification du nonce valide ne devrait pas lever d_exception.' );
		} catch ( Exception $e ) {
			$this->fail( 'Nonce valide a échoué la vérification: ' . $e->getMessage() );
		}

		// Test avec un nonce invalide.
		try {
			$this->nonce_manager->checkNonce( 'invalid_nonce', $action );
			$this->fail( 'Nonce invalide n_a pas échoué la vérification.' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Nonce invalide. Veuillez rafraîchir la page et réessayer.', $e->getMessage() );
		}
	}

	/**
	 * Teste la récupération du champ de formulaire nonce.
	 *
	 * @return void
	 */
	public function testGetNonceField(): void {
		$field = $this->nonce_manager->getNonceField( 'create_appointment' );
		$this->assertStringContainsString( 'type="hidden"', $field );
		$this->assertStringContainsString( 'name="calendrier_rdv_create_appointment"', $field );
	}

	/**
	 * Teste la récupération du script nonce.
	 *
	 * @return void
	 */
	public function testGetNonceScript(): void {
		$script = $this->nonce_manager->getNonceScript( 'create_appointment' );
		$this->assertStringContainsString( 'window.calendrierRdvNonce', $script );
		$this->assertStringContainsString( 'calendrier_rdv_create_appointment', $script );
	}
}
