<?php
/**
 * Tests for the Test_WP_Customize_Control class.
 *
 * @package WordPress
 *
 * @todo This is missing dedicated tests for all but one of the methods.
 *
 * @group customize
 */
class Test_WP_Customize_Control extends WP_UnitTestCase {

	/**
	 * Manager.
	 *
	 * @var WP_Customize_Manager
	 */
	public $wp_customize;

	/**
	 * Shared user ID for the tests.

	 * @var int
	 */
	public static $user_id = 0;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$user_id );
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		$this->wp_customize      = $GLOBALS['wp_customize'];
	}

	/**
	 * Test WP_Customize_Control::check_capabilities().
	 *
	 * @see WP_Customize_Control::check_capabilities()
	 */
	public function test_check_capabilities() {
		do_action( 'customize_register', $this->wp_customize );
		$control = new WP_Customize_Control(
			$this->wp_customize,
			'blogname',
			array(
				'settings' => array( 'blogname' ),
			)
		);
		$this->assertTrue( $control->check_capabilities() );

		$control = new WP_Customize_Control(
			$this->wp_customize,
			'blogname',
			array(
				'settings' => array( 'blogname', 'non_existing' ),
			)
		);
		$this->assertFalse( $control->check_capabilities() );

		$this->wp_customize->add_setting(
			'top_secret_message',
			array(
				'capability' => 'top_secret_clearance',
			)
		);
		$control = new WP_Customize_Control(
			$this->wp_customize,
			'blogname',
			array(
				'settings' => array( 'blogname', 'top_secret_clearance' ),
			)
		);
		$this->assertFalse( $control->check_capabilities() );

		$control = new WP_Customize_Control(
			$this->wp_customize,
			'no_setting',
			array(
				'settings' => array(),
			)
		);
		$this->assertTrue( $control->check_capabilities() );

		$control = new WP_Customize_Control(
			$this->wp_customize,
			'no_setting',
			array(
				'settings'   => array(),
				'capability' => 'top_secret_clearance',
			)
		);
		$this->assertFalse( $control->check_capabilities() );

		$control = new WP_Customize_Control(
			$this->wp_customize,
			'no_setting',
			array(
				'settings'   => array(),
				'capability' => 'edit_theme_options',
			)
		);
		$this->assertTrue( $control->check_capabilities() );
	}

	/**
	 * @ticket 38164
	 */
	public function test_dropdown_pages() {
		do_action( 'customize_register', $this->wp_customize );

		$this->assertInstanceOf( 'WP_Customize_Nav_Menus', $this->wp_customize->nav_menus );
		$nav_menus_created_posts_setting = $this->wp_customize->get_setting( 'nav_menus_created_posts' );
		$this->assertInstanceOf( 'WP_Customize_Filter_Setting', $nav_menus_created_posts_setting );
		$page_on_front_control = $this->wp_customize->get_control( 'page_on_front' );

		// Ensure the add-new-toggle is absent if allow_addition param is not set.
		$page_on_front_control->allow_addition = false;
		ob_start();
		$page_on_front_control->maybe_render();
		$content = ob_get_clean();
		$this->assertStringNotContainsString( 'add-new-toggle', $content );

		// Ensure the add-new-toggle is absent if allow_addition param is set.
		$page_on_front_control->allow_addition = true;
		ob_start();
		$page_on_front_control->maybe_render();
		$content = ob_get_clean();
		$this->assertStringContainsString( 'add-new-toggle', $content );

		// Ensure that dropdown-pages delect is rendered even if there are no pages published (yet).
		foreach ( get_pages() as $page ) {
			wp_delete_post( $page->ID );
		}
		$page_on_front_control->allow_addition = true;
		ob_start();
		$page_on_front_control->maybe_render();
		$content = ob_get_clean();
		$this->assertStringContainsString( '<option value="0">', $content, 'Dropdown-pages renders select even without any pages published.' );

		// Ensure that auto-draft pages are included if they are among the nav_menus_created_posts.
		$auto_draft_page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'auto-draft',
				'post_title'  => 'Auto Draft Page',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'auto-draft',
				'post_title'  => 'Orphan Auto Draft Page',
			)
		);
		$auto_draft_post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'auto-draft',
				'post_title'  => 'Auto Draft Post',
			)
		);
		$this->wp_customize->set_post_value( $nav_menus_created_posts_setting->id, array( $auto_draft_page_id, $auto_draft_post_id ) );
		$nav_menus_created_posts_setting->preview();
		ob_start();
		$page_on_front_control->maybe_render();
		$content = ob_get_clean();
		$this->assertStringContainsString( sprintf( '<option value="%d">Auto Draft Page</option>', $auto_draft_page_id ), $content );
		$this->assertStringNotContainsString( 'Auto Draft Post', $content );
		$this->assertStringNotContainsString( 'Orphan Auto Draft Page', $content );
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		$this->wp_customize = null;
		unset( $GLOBALS['wp_customize'] );
		parent::tear_down();
	}
}
