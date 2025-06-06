<?php

/**
 * @group l10n
 * @group i18n
 *
 * @covers ::get_user_locale
 */
class Tests_L10n_GetUserLocale extends WP_UnitTestCase {
	protected $user_id;

	/**
	 * ID of the administrator user with de_DE local.
	 *
	 * @var int
	 */
	public static $administrator_de_de;

	/**
	 * ID of the user with es_ES local.
	 *
	 * @var int
	 */
	public static $user_es_es;

	/**
	 * Set up the shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$administrator_de_de = $factory->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'de_DE',
			)
		);

		self::$user_es_es = self::factory()->user->create(
			array(
				'locale' => 'es_ES',
			)
		);
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$administrator_de_de );
	}

	public function test_user_locale_property() {
		set_current_screen( 'dashboard' );
		$this->assertSame( 'de_DE', get_user_locale() );
		$this->assertSame( get_user_by( 'id', self::$administrator_de_de )->locale, get_user_locale() );
	}

	public function test_update_user_locale() {
		set_current_screen( 'dashboard' );
		update_user_meta( self::$administrator_de_de, 'locale', 'fr_FR' );
		$this->assertSame( 'fr_FR', get_user_locale() );
	}

	public function test_returns_site_locale_if_empty() {
		set_current_screen( 'dashboard' );
		update_user_meta( self::$administrator_de_de, 'locale', '' );
		$this->assertSame( get_locale(), get_user_locale() );
	}

	public function test_returns_site_locale_if_no_user() {
		wp_set_current_user( 0 );
		$this->assertSame( get_locale(), get_user_locale() );
	}

	public function test_returns_correct_user_locale() {
		set_current_screen( 'dashboard' );
		$this->assertSame( 'de_DE', get_user_locale() );
	}

	public function test_returns_correct_user_locale_on_frontend() {
		$this->assertSame( 'de_DE', get_user_locale() );
	}

	public function test_site_locale_is_not_affected() {
		set_current_screen( 'dashboard' );
		$this->assertSame( 'en_US', get_locale() );
	}

	public function test_site_locale_is_not_affected_on_frontend() {
		$this->assertSame( 'en_US', get_locale() );
	}

	/**
	 * @group ms-required
	 */
	public function test_user_locale_is_same_across_network() {
		$user_locale = get_user_locale();

		switch_to_blog( self::factory()->blog->create() );
		$user_locale_2 = get_user_locale();
		restore_current_blog();

		$this->assertSame( 'de_DE', $user_locale );
		$this->assertSame( $user_locale, $user_locale_2 );
	}

	public function test_user_id_argument_with_id() {
		$user_id = self::$user_es_es;

		$user_locale1 = get_user_locale( $user_id );

		delete_user_meta( $user_id, 'locale' );

		$user_locale2 = get_user_locale( $user_id );

		$this->assertSame( 'es_ES', $user_locale1 );
		$this->assertSame( get_locale(), $user_locale2 );
	}

	public function test_user_id_argument_with_wp_user_object() {
		$user_id = self::$user_es_es;

		$user = get_user_by( 'id', $user_id );

		$user_locale1 = get_user_locale( $user );

		delete_user_meta( $user_id, 'locale' );

		$user_locale2 = get_user_locale( $user );

		$this->assertSame( 'es_ES', $user_locale1 );
		$this->assertSame( get_locale(), $user_locale2 );
	}

	public function test_user_id_argument_with_nonexistent_user() {
		global $wpdb;

		$user_id = $wpdb->get_var( "SELECT MAX(ID) FROM $wpdb->users" ) + 1;

		$user_locale = get_user_locale( $user_id );

		$this->assertSame( get_locale(), $user_locale );
	}

	public function test_user_id_argument_with_invalid_type() {
		$user_locale = get_user_locale( 'string' );
		$this->assertSame( get_locale(), $user_locale );
	}
}
