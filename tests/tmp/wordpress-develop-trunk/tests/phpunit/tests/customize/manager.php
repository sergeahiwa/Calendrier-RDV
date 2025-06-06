<?php
/**
 * WP_Customize_Manager tests.
 *
 * @package WordPress
 */

/**
 * Tests for the WP_Customize_Manager class.
 *
 * @group customize
 */
class Tests_WP_Customize_Manager extends WP_UnitTestCase {

	/**
	 * Customize manager instance re-instantiated with each test.
	 *
	 * @var WP_Customize_Manager
	 */
	public $manager;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_user_id;

	/**
	 * Second admin user ID.
	 *
	 * @var int
	 */
	protected static $other_admin_user_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	protected static $subscriber_user_id;

	/**
	 * Whether any attachments have been created in the current test run.
	 *
	 * @var bool
	 */
	private $attachments_created = false;

	/**
	 * Set up before class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$subscriber_user_id  = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$admin_user_id       = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$other_admin_user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test.
	 */
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$this->manager = $this->instantiate();
	}

	/**
	 * Tear down test.
	 */
	public function tear_down() {
		if ( true === $this->attachments_created ) {
			$this->remove_added_uploads();
			$this->attachments_created = false;
		}

		$this->manager = null;
		unset( $GLOBALS['wp_customize'] );
		$_REQUEST = array();
		parent::tear_down();
	}

	/**
	 * Get a core theme that is not the same as the current theme.
	 *
	 * @throws Exception If an inactive core Twenty* theme cannot be found.
	 * @return string Theme slug (stylesheet).
	 */
	private function get_inactive_core_theme() {
		$stylesheet = get_stylesheet();
		foreach ( wp_get_themes() as $theme ) {
			if ( $theme->stylesheet !== $stylesheet && 0 === strpos( $theme->stylesheet, 'twenty' ) ) {
				return $theme->stylesheet;
			}
		}
		throw new Exception( 'Unable to find inactive twenty* theme.' );
	}

	/**
	 * Instantiate class, set global $wp_customize, and return instance.
	 *
	 * @return WP_Customize_Manager
	 */
	private function instantiate() {
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		return $GLOBALS['wp_customize'];
	}

	/**
	 * Test WP_Customize_Manager::__construct().
	 *
	 * @covers WP_Customize_Manager::__construct
	 */
	public function test_constructor() {
		$uuid              = wp_generate_uuid4();
		$theme             = 'twentyfifteen';
		$messenger_channel = 'preview-123';
		$wp_customize      = new WP_Customize_Manager(
			array(
				'changeset_uuid'    => $uuid,
				'theme'             => $theme,
				'messenger_channel' => $messenger_channel,
			)
		);
		$this->assertSame( $uuid, $wp_customize->changeset_uuid() );
		$this->assertSame( $theme, $wp_customize->get_stylesheet() );
		$this->assertSame( $messenger_channel, $wp_customize->get_messenger_channel() );
		$this->assertFalse( $wp_customize->autosaved() );
		$this->assertTrue( $wp_customize->branching() );

		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => null,
			)
		);
		$this->assertTrue( wp_is_uuid( $wp_customize->changeset_uuid(), 4 ) );

		$theme                                   = 'twentyfourteen';
		$messenger_channel                       = 'preview-456';
		$_REQUEST['theme']                       = $theme;
		$_REQUEST['customize_messenger_channel'] = $messenger_channel;
		$wp_customize                            = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$this->assertSame( $theme, $wp_customize->get_stylesheet() );
		$this->assertSame( $messenger_channel, $wp_customize->get_messenger_channel() );

		$theme                       = 'twentyfourteen';
		$_REQUEST['customize_theme'] = $theme;
		$wp_customize                = new WP_Customize_Manager();
		$this->assertSame( $theme, $wp_customize->get_stylesheet() );
		$this->assertTrue( wp_is_uuid( $wp_customize->changeset_uuid(), 4 ) );
	}

	/**
	 * Test constructor when deferring UUID.
	 *
	 * @ticket 39896
	 * @covers WP_Customize_Manager::establish_loaded_changeset
	 * @covers WP_Customize_Manager::__construct
	 */
	public function test_constructor_deferred_changeset_uuid() {
		wp_set_current_user( self::$admin_user_id );
		$other_admin_user_id = self::$other_admin_user_id;

		$data = array(
			'blogname' => array(
				'value' => 'Test',
			),
		);

		$uuid1 = wp_generate_uuid4();
		self::factory()->post->create(
			array(
				'post_type'     => 'customize_changeset',
				'post_name'     => $uuid1,
				'post_status'   => 'draft',
				'post_content'  => wp_json_encode( $data ),
				'post_author'   => get_current_user_id(),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
			)
		);

		/*
		 * Create a changeset for another user that is newer to ensure that it is the one that gets returned,
		 * as in non-branching mode there should only be one pending changeset at a time.
		 */
		$uuid2   = wp_generate_uuid4();
		$post_id = self::factory()->post->create(
			array(
				'post_type'     => 'customize_changeset',
				'post_name'     => $uuid2,
				'post_status'   => 'draft',
				'post_content'  => wp_json_encode( $data ),
				'post_author'   => $other_admin_user_id,
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);

		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => false, // Cause UUID to be deferred.
				'branching'      => false, // To cause drafted changeset to be autoloaded.
			)
		);
		$this->assertSame( $uuid2, $wp_customize->changeset_uuid() );
		$this->assertSame( $post_id, $wp_customize->changeset_post_id() );

		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => false, // Cause UUID to be deferred.
				'branching'      => true,  // To cause no drafted changeset to be autoloaded.
			)
		);
		$this->assertNotContains( $wp_customize->changeset_uuid(), array( $uuid1, $uuid2 ) );
		$this->assertEmpty( $wp_customize->changeset_post_id() );

		// Make sure existing changeset is not autoloaded in the case of previewing a theme switch.
		switch_theme( 'twentyseventeen' );
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => false, // Cause UUID to be deferred.
				'branching'      => false,
				'theme'          => 'twentyfifteen',
			)
		);
		$this->assertEmpty( $wp_customize->changeset_post_id() );
	}

	/**
	 * Test WP_Customize_Manager::setup_theme() for admin screen.
	 *
	 * @covers WP_Customize_Manager::setup_theme
	 */
	public function test_setup_theme_in_customize_admin() {
		global $pagenow, $wp_customize;
		$pagenow = 'customize.php';
		set_current_screen( 'customize' );

		// Unauthorized.
		$exception    = null;
		$wp_customize = new WP_Customize_Manager();
		wp_set_current_user( self::$subscriber_user_id );
		try {
			$wp_customize->setup_theme();
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertStringContainsString( 'you are not allowed to customize this site', $exception->getMessage() );

		// Bad changeset.
		$exception = null;
		wp_set_current_user( self::$admin_user_id );
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => 'bad' ) );
		try {
			$wp_customize->setup_theme();
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertStringContainsString( 'Invalid changeset UUID', $exception->getMessage() );

		update_option( 'fresh_site', '0' );
		$wp_customize = new WP_Customize_Manager();
		$wp_customize->setup_theme();
		$this->assertFalse( has_action( 'after_setup_theme', array( $wp_customize, 'import_theme_starter_content' ) ) );

		// Make sure that starter content import gets queued on a fresh site.
		update_option( 'fresh_site', '1' );
		$wp_customize->setup_theme();
		$this->assertSame( 100, has_action( 'after_setup_theme', array( $wp_customize, 'import_theme_starter_content' ) ) );
	}

	/**
	 * Test that clearing a fresh site is a no-op if the site is already fresh.
	 *
	 * @see _delete_option_fresh_site()
	 * @ticket 41039
	 */
	public function test_fresh_site_flag_clearing() {
		global $wp_customize;

		// Make sure fresh site flag is cleared when publishing a changeset.
		update_option( 'fresh_site', '1' );
		do_action( 'customize_save_after', $wp_customize );
		$this->assertSame( '0', get_option( 'fresh_site' ) );

		// Simulate a new, uncached request.
		wp_cache_delete( 'alloptions', 'options' );
		wp_load_alloptions();

		// Make sure no DB write is done when publishing and a site is already non-fresh.
		$query_count = get_num_queries();
		do_action( 'customize_save_after', $wp_customize );
		$this->assertSame( $query_count, get_num_queries() );
	}

	/**
	 * Test WP_Customize_Manager::setup_theme() for frontend.
	 *
	 * @covers WP_Customize_Manager::setup_theme
	 */
	public function test_setup_theme_in_frontend() {
		global $wp_customize, $pagenow, $show_admin_bar;
		$pagenow = 'front';
		set_current_screen( 'front' );

		wp_set_current_user( 0 );
		$exception    = null;
		$wp_customize = new WP_Customize_Manager();
		wp_set_current_user( self::$subscriber_user_id );
		try {
			$wp_customize->setup_theme();
		} catch ( Exception $e ) {
			$exception = $e;
		}
		$this->assertInstanceOf( 'WPDieException', $exception );
		$this->assertStringContainsString( 'Non-existent changeset UUID', $exception->getMessage() );

		wp_set_current_user( self::$admin_user_id );
		$wp_customize = new WP_Customize_Manager( array( 'messenger_channel' => 'preview-1' ) );
		$wp_customize->setup_theme();
		$this->assertFalse( $show_admin_bar );

		show_admin_bar( true );
		wp_set_current_user( self::$admin_user_id );
		$wp_customize = new WP_Customize_Manager( array( 'messenger_channel' => null ) );
		$wp_customize->setup_theme();
		$this->assertTrue( $show_admin_bar );
	}

	/**
	 * Test WP_Customize_Manager::settings_previewed().
	 *
	 * @ticket 39221
	 * @covers WP_Customize_Manager::settings_previewed
	 */
	public function test_settings_previewed() {
		$wp_customize = new WP_Customize_Manager( array( 'settings_previewed' => false ) );
		$this->assertFalse( $wp_customize->settings_previewed() );

		$wp_customize = new WP_Customize_Manager();
		$this->assertTrue( $wp_customize->settings_previewed() );
	}

	/**
	 * Test WP_Customize_Manager::autosaved().
	 *
	 * @ticket 39896
	 * @covers WP_Customize_Manager::autosaved
	 */
	public function test_autosaved() {
		$wp_customize = new WP_Customize_Manager();
		$this->assertFalse( $wp_customize->autosaved() );

		$wp_customize = new WP_Customize_Manager( array( 'autosaved' => false ) );
		$this->assertFalse( $wp_customize->autosaved() );

		$wp_customize = new WP_Customize_Manager( array( 'autosaved' => true ) );
		$this->assertTrue( $wp_customize->autosaved() );
	}

	/**
	 * Test WP_Customize_Manager::branching().
	 *
	 * @ticket 39896
	 * @covers WP_Customize_Manager::branching
	 */
	public function test_branching() {
		$wp_customize = new WP_Customize_Manager();
		$this->assertTrue( $wp_customize->branching(), 'Branching should default to true since it is original behavior in 4.7.' );

		$wp_customize = new WP_Customize_Manager( array( 'branching' => false ) );
		$this->assertFalse( $wp_customize->branching() );
		add_filter( 'customize_changeset_branching', '__return_true' );
		$this->assertTrue( $wp_customize->branching() );
		remove_filter( 'customize_changeset_branching', '__return_true' );

		$wp_customize = new WP_Customize_Manager( array( 'branching' => true ) );
		$this->assertTrue( $wp_customize->branching() );
		add_filter( 'customize_changeset_branching', '__return_false' );
		$this->assertFalse( $wp_customize->branching() );
	}

	/**
	 * Test WP_Customize_Manager::changeset_uuid().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::changeset_uuid
	 */
	public function test_changeset_uuid() {
		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$this->assertSame( $uuid, $wp_customize->changeset_uuid() );
	}

	/**
	 * Test WP_Customize_Manager::wp_loaded().
	 *
	 * Ensure that post values are previewed even without being in preview.
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::wp_loaded
	 */
	public function test_wp_loaded() {
		wp_set_current_user( self::$admin_user_id );
		$wp_customize = new WP_Customize_Manager();
		$title        = 'Hello World';
		$wp_customize->set_post_value( 'blogname', $title );
		$this->assertNotEquals( $title, get_option( 'blogname' ) );
		$wp_customize->wp_loaded();
		$this->assertFalse( $wp_customize->is_preview() );
		$this->assertSame( $title, $wp_customize->get_setting( 'blogname' )->value() );
		$this->assertSame( $title, get_option( 'blogname' ) );
	}

	/**
	 * Test WP_Customize_Manager::find_changeset_post_id().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::find_changeset_post_id
	 */
	public function test_find_changeset_post_id() {
		$uuid    = wp_generate_uuid4();
		$post_id = self::factory()->post->create(
			array(
				'post_name'    => $uuid,
				'post_type'    => 'customize_changeset',
				'post_status'  => 'auto-draft',
				'post_content' => '{}',
			)
		);

		$wp_customize = new WP_Customize_Manager();
		$this->assertNull( $wp_customize->find_changeset_post_id( wp_generate_uuid4() ) );
		$this->assertSame( $post_id, $wp_customize->find_changeset_post_id( $uuid ) );

		// Verify that the found post ID was cached under the given UUID, not the manager's UUID.
		$this->assertNotEquals( $post_id, $wp_customize->find_changeset_post_id( $wp_customize->changeset_uuid() ) );
	}

	/**
	 * Test WP_Customize_Manager::changeset_post_id().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::changeset_post_id
	 */
	public function test_changeset_post_id() {
		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$this->assertNull( $wp_customize->changeset_post_id() );

		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$post_id      = self::factory()->post->create(
			array(
				'post_name'    => $uuid,
				'post_type'    => 'customize_changeset',
				'post_status'  => 'auto-draft',
				'post_content' => '{}',
			)
		);
		$this->assertSame( $post_id, $wp_customize->changeset_post_id() );
	}

	/**
	 * Test WP_Customize_Manager::changeset_data().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::changeset_data
	 */
	public function test_changeset_data() {
		wp_set_current_user( self::$admin_user_id );
		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$this->assertSame( array(), $wp_customize->changeset_data() );

		$uuid = wp_generate_uuid4();
		$data = array(
			'blogname'        => array( 'value' => 'Hello World' ),
			'blogdescription' => array( 'value' => 'Greet the world' ),
		);
		self::factory()->post->create(
			array(
				'post_name'    => $uuid,
				'post_type'    => 'customize_changeset',
				'post_status'  => 'draft',
				'post_content' => wp_json_encode( $data ),
			)
		);
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$this->assertSame( $data, $wp_customize->changeset_data() );

		// Autosave.
		$wp_customize->set_post_value( 'blogname', 'Hola Mundo' );
		$wp_customize->register_controls(); // That is, settings, so blogname setting is registered.
		$r = $wp_customize->save_changeset_post(
			array(
				'autosave' => true,
			)
		);
		$this->assertNotWPError( $r );

		// No change to data if not requesting autosave.
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
				'autosaved'      => false,
			)
		);
		$wp_customize->register_controls(); // That is, settings.
		$this->assertFalse( $wp_customize->autosaved() );
		$this->assertSame( $data, $wp_customize->changeset_data() );

		// No change to data if not requesting autosave.
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
				'autosaved'      => true,
			)
		);
		$this->assertTrue( $wp_customize->autosaved() );
		$this->assertNotEquals( $data, $wp_customize->changeset_data() );
		$this->assertSame(
			array_merge(
				wp_list_pluck( $data, 'value' ),
				array( 'blogname' => 'Hola Mundo' )
			),
			wp_list_pluck( $wp_customize->changeset_data(), 'value' )
		);

		// If there is no user, don't fetch the most recent autosave. See #42450.
		wp_set_current_user( 0 );
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
				'autosaved'      => true,
			)
		);
		$this->assertSame( $data, $wp_customize->changeset_data() );
	}

	/**
	 * Test WP_Customize_Manager::import_theme_starter_content().
	 *
	 * @covers WP_Customize_Manager::import_theme_starter_content
	 * @covers WP_Customize_Manager::_save_starter_content_changeset
	 * @requires function imagejpeg
	 */
	public function test_import_theme_starter_content() {
		wp_set_current_user( self::$admin_user_id );
		register_nav_menu( 'top', 'Top' );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'custom-header' );
		add_theme_support( 'custom-background' );

		// For existing attachment, copy into uploads.
		$canola_image_file    = DIR_TESTDATA . '/images/canola.jpg';
		$canola_image_upload  = wp_upload_bits( wp_basename( $canola_image_file ), null, file_get_contents( $canola_image_file ) );
		$existing_canola_file = $canola_image_upload['file'];

		$existing_canola_attachment_id = self::factory()->attachment->create_object(
			$existing_canola_file,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
				'post_name'      => 'canola',
			)
		);

		$this->attachments_created = true;

		$existing_published_home_page_id   = self::factory()->post->create(
			array(
				'post_name'   => 'home',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$existing_auto_draft_about_page_id = self::factory()->post->create(
			array(
				'post_name'   => 'about',
				'post_type'   => 'page',
				'post_status' => 'auto-draft',
			)
		);

		global $wp_customize;
		$wp_customize           = new WP_Customize_Manager();
		$starter_content_config = array(
			'widgets'     => array(
				'sidebar-1' => array(
					'text_business_info',
					'meta_custom' => array(
						'meta',
						array(
							'title' => 'Pre-hydrated meta widget.',
						),
					),
				),
			),
			'nav_menus'   => array(
				'top' => array(
					'name'  => 'Menu Name',
					'items' => array(
						'link_home',
						'page_about',
						'page_blog',
						'link_email',
						'link_facebook',
						'link_custom' => array(
							'title' => 'Custom',
							'url'   => 'https://custom.example.com/',
						),
					),
				),
			),
			'posts'       => array(
				'home',
				'about'       => array(
					'template' => 'sample-page-template.php',
				),
				'blog',
				'custom'      => array(
					'post_type'  => 'post',
					'post_title' => 'Custom',
					'thumbnail'  => '{{waffles}}',
				),
				'unknown_cpt' => array(
					'post_type'  => 'unknown_cpt',
					'post_title' => 'Unknown CPT',
				),
			),
			'attachments' => array(
				'waffles' => array(
					'post_title'   => 'Waffles',
					'post_content' => 'Waffles Attachment Description',
					'post_excerpt' => 'Waffles Attachment Caption',
					'file'         => DIR_TESTDATA . '/images/waffles.jpg',
				),
				'canola'  => array(
					'post_title'   => 'Canola',
					'post_content' => 'Canola Attachment Description',
					'post_excerpt' => 'Canola Attachment Caption',
					'file'         => $existing_canola_file,
				),
			),
			'options'     => array(
				'blogname'        => 'Starter Content Title',
				'blogdescription' => 'Starter Content Tagline',
				'show_on_front'   => 'page',
				'page_on_front'   => '{{home}}',
				'page_for_posts'  => '{{blog}}',
			),
			'theme_mods'  => array(
				'custom_logo'      => '{{canola}}',
				'header_image'     => '{{waffles}}',
				'background_image' => '{{waffles}}',
			),
		);

		update_option( 'posts_per_page', 1 ); // To check #39022.
		add_theme_support( 'starter-content', $starter_content_config );
		$this->assertEmpty( $wp_customize->unsanitized_post_values() );
		$wp_customize->import_theme_starter_content();
		$changeset_values     = $wp_customize->unsanitized_post_values();
		$expected_setting_ids = array(
			'blogname',
			'blogdescription',
			'custom_logo',
			'header_image_data',
			'background_image',
			'widget_text[2]',
			'widget_meta[2]',
			'sidebars_widgets[sidebar-1]',
			'nav_menus_created_posts',
			'nav_menu[-1]',
			'nav_menu_item[-1]',
			'nav_menu_item[-2]',
			'nav_menu_item[-3]',
			'nav_menu_item[-4]',
			'nav_menu_item[-5]',
			'nav_menu_item[-6]',
			'nav_menu_locations[top]',
			'show_on_front',
			'page_on_front',
			'page_for_posts',
		);
		$this->assertSameSets( $expected_setting_ids, array_keys( $changeset_values ) );

		foreach ( array( 'widget_text[2]', 'widget_meta[2]' ) as $setting_id ) {
			$this->assertIsArray( $changeset_values[ $setting_id ] );
			$instance_data = $wp_customize->widgets->sanitize_widget_instance( $changeset_values[ $setting_id ] );
			$this->assertIsArray( $instance_data );
			$this->assertArrayHasKey( 'title', $instance_data );
		}

		$this->assertSame( array( 'text-2', 'meta-2' ), $changeset_values['sidebars_widgets[sidebar-1]'] );

		$posts_by_name = array();
		$this->assertCount( 7, $changeset_values['nav_menus_created_posts'] );
		$this->assertContains( $existing_published_home_page_id, $changeset_values['nav_menus_created_posts'], 'Expected reuse of non-auto-draft posts.' );
		$this->assertContains( $existing_canola_attachment_id, $changeset_values['nav_menus_created_posts'], 'Expected reuse of non-auto-draft attachment.' );
		$this->assertNotContains( $existing_auto_draft_about_page_id, $changeset_values['nav_menus_created_posts'], 'Expected non-reuse of auto-draft posts.' );
		foreach ( $changeset_values['nav_menus_created_posts'] as $post_id ) {
			$post = get_post( $post_id );
			if ( $post->ID === $existing_published_home_page_id ) {
				$this->assertSame( 'publish', $post->post_status );
			} elseif ( $post->ID === $existing_canola_attachment_id ) {
				$this->assertSame( 'inherit', $post->post_status );
			} else {
				$this->assertSame( 'auto-draft', $post->post_status );
				$this->assertEmpty( $post->post_name );
			}
			$post_name = $post->post_name;
			if ( empty( $post_name ) ) {
				$post_name = get_post_meta( $post->ID, '_customize_draft_post_name', true );
			}
			$posts_by_name[ $post_name ] = $post->ID;
		}
		$this->assertSame( array( 'waffles', 'canola', 'home', 'about', 'blog', 'custom', 'unknown-cpt' ), array_keys( $posts_by_name ) );
		$this->assertSame( 'Custom', get_post( $posts_by_name['custom'] )->post_title );
		$this->assertSame( 'sample-page-template.php', get_page_template_slug( $posts_by_name['about'] ) );
		$this->assertSame( '', get_page_template_slug( $posts_by_name['blog'] ) );
		$this->assertSame( $posts_by_name['waffles'], get_post_thumbnail_id( $posts_by_name['custom'] ) );
		$this->assertSame( 0, get_post_thumbnail_id( $posts_by_name['blog'] ) );
		$attachment_metadata = wp_get_attachment_metadata( $posts_by_name['waffles'] );
		$this->assertSame( 'Waffles', get_post( $posts_by_name['waffles'] )->post_title );
		$this->assertSame( 'waffles', get_post_meta( $posts_by_name['waffles'], '_customize_draft_post_name', true ) );
		$this->assertArrayHasKey( 'file', $attachment_metadata );
		$this->assertStringContainsString( 'waffles', $attachment_metadata['file'] );

		$this->assertSame( 'page', $changeset_values['show_on_front'] );
		$this->assertSame( $posts_by_name['home'], $changeset_values['page_on_front'] );
		$this->assertSame( $posts_by_name['blog'], $changeset_values['page_for_posts'] );

		$this->assertSame( -1, $changeset_values['nav_menu_locations[top]'] );
		$this->assertSame( 0, $changeset_values['nav_menu_item[-1]']['object_id'] );
		$this->assertSame( 'custom', $changeset_values['nav_menu_item[-1]']['type'] );
		$this->assertSame( home_url( '/' ), $changeset_values['nav_menu_item[-1]']['url'] );

		$this->assertEmpty( $wp_customize->changeset_data() );
		$this->assertNull( $wp_customize->changeset_post_id() );
		$this->assertSame( 1000, has_action( 'customize_register', array( $wp_customize, '_save_starter_content_changeset' ) ) );
		do_action( 'customize_register', $wp_customize ); // This will trigger the changeset save.
		$this->assertIsInt( $wp_customize->changeset_post_id() );
		$this->assertNotEmpty( $wp_customize->changeset_data() );
		foreach ( $wp_customize->changeset_data() as $setting_id => $setting_params ) {
			$this->assertArrayHasKey( 'starter_content', $setting_params );
			$this->assertTrue( $setting_params['starter_content'] );
		}

		// Ensure that re-importing doesn't cause auto-drafts to balloon.
		$wp_customize->import_theme_starter_content();
		$changeset_data = $wp_customize->changeset_data();
		// Auto-drafts should not get re-created and amended with each import.
		$this->assertSameSets( array_values( $posts_by_name ), $changeset_data['nav_menus_created_posts']['value'] );

		// Test that saving non-starter content on top of the changeset clears the starter_content flag.
		$wp_customize->save_changeset_post(
			array(
				'data' => array(
					'blogname' => array( 'value' => 'Starter Content Modified' ),
				),
			)
		);
		$changeset_data = $wp_customize->changeset_data();
		$this->assertArrayNotHasKey( 'starter_content', $changeset_data['blogname'] );
		$this->assertArrayHasKey( 'starter_content', $changeset_data['blogdescription'] );

		/*
		 * Test that adding blogname starter content is ignored now that it is modified,
		 * but updating a non-modified starter content site description passes.
		 */
		$previous_blogname        = $changeset_data['blogname']['value'];
		$previous_blogdescription = $changeset_data['blogdescription']['value'];
		$wp_customize->import_theme_starter_content(
			array(
				'options' => array(
					'blogname'        => 'Newer Starter Content Title',
					'blogdescription' => 'Newer Starter Content Description',
				),
			)
		);
		$changeset_data = $wp_customize->changeset_data();
		$this->assertSame( $previous_blogname, $changeset_data['blogname']['value'] );
		$this->assertArrayNotHasKey( 'starter_content', $changeset_data['blogname'] );
		$this->assertNotEquals( $previous_blogdescription, $changeset_data['blogdescription']['value'] );
		$this->assertArrayHasKey( 'starter_content', $changeset_data['blogdescription'] );

		// Publish.
		$this->assertEmpty( get_custom_logo() );
		$this->assertEmpty( get_header_image() );
		$this->assertEmpty( get_background_image() );
		$this->assertEmpty( get_theme_mod( 'custom_logo' ) );
		$this->assertEmpty( get_theme_mod( 'header_image' ) );
		$this->assertEmpty( get_theme_mod( 'background_image' ) );
		$this->assertSame( 'auto-draft', get_post( $posts_by_name['about'] )->post_status );
		$this->assertSame( 'auto-draft', get_post( $posts_by_name['waffles'] )->post_status );
		$this->assertNotEquals( $changeset_data['blogname']['value'], get_option( 'blogname' ) );
		$r = $wp_customize->save_changeset_post( array( 'status' => 'publish' ) );
		$this->assertIsArray( $r );
		$this->assertSame( 'publish', get_post( $posts_by_name['about'] )->post_status );
		$this->assertSame( 'inherit', get_post( $posts_by_name['waffles'] )->post_status );
		$this->assertSame( $changeset_data['blogname']['value'], get_option( 'blogname' ) );
		$this->assertNotEmpty( get_theme_mod( 'custom_logo' ) );
		$this->assertNotEmpty( get_theme_mod( 'header_image' ) );
		$this->assertNotEmpty( get_theme_mod( 'background_image' ) );
		$this->assertNotEmpty( get_custom_logo() );
		$this->assertNotEmpty( get_header_image() );
		$this->assertNotEmpty( get_background_image() );
		$this->assertStringContainsString( 'canola', get_custom_logo() );
		$this->assertStringContainsString( 'waffles', get_header_image() );
		$this->assertStringContainsString( 'waffles', get_background_image() );
		$this->assertSame( 'waffles', get_post( $posts_by_name['waffles'] )->post_name );
		$this->assertEmpty( get_post_meta( $posts_by_name['waffles'], '_customize_draft_post_name', true ) );
	}

	/**
	 * Test WP_Customize_Manager::import_theme_starter_content() with nested arrays.
	 *
	 * @ticket 45484
	 * @covers WP_Customize_Manager::import_theme_starter_content
	 */
	public function test_import_theme_starter_content_with_nested_arrays() {
		wp_set_current_user( self::$admin_user_id );

		$existing_published_home_page_id = self::factory()->post->create(
			array(
				'post_name'   => 'home',
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		global $wp_customize;
		$wp_customize           = new WP_Customize_Manager();
		$starter_content_config = array(
			'posts'      => array(
				'home',
			),
			'options'    => array(
				'array_option'        => array(
					0,
					1,
					'home_page_id' => '{{home}}',
				),
				'nested_array_option' => array(
					0,
					1,
					array(
						2,
						'home_page_id' => '{{home}}',
					),
				),
			),
			'theme_mods' => array(
				'array_theme_mod'        => array(
					0,
					1,
					'home_page_id' => '{{home}}',
				),
				'nested_array_theme_mod' => array(
					0,
					1,
					array(
						2,
						'home_page_id' => '{{home}}',
					),
				),
			),
		);

		add_theme_support( 'starter-content', $starter_content_config );
		$this->assertEmpty( $wp_customize->unsanitized_post_values() );
		$wp_customize->import_theme_starter_content();
		$changeset_values     = $wp_customize->unsanitized_post_values();
		$expected_setting_ids = array(
			'array_option',
			'array_theme_mod',
			'nav_menus_created_posts',
			'nested_array_option',
			'nested_array_theme_mod',
		);
		$this->assertSameSets( $expected_setting_ids, array_keys( $changeset_values ) );

		$this->assertSame( $existing_published_home_page_id, $changeset_values['array_option']['home_page_id'] );
		$this->assertSame( $existing_published_home_page_id, $changeset_values['nested_array_option'][2]['home_page_id'] );
		$this->assertSame( $existing_published_home_page_id, $changeset_values['array_theme_mod']['home_page_id'] );
		$this->assertSame( $existing_published_home_page_id, $changeset_values['nested_array_theme_mod'][2]['home_page_id'] );
	}

	/**
	 * Test WP_Customize_Manager::customize_preview_init().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::customize_preview_init
	 */
	public function test_customize_preview_init() {

		// Test authorized admin user.
		wp_set_current_user( self::$admin_user_id );
		$did_action_customize_preview_init = did_action( 'customize_preview_init' );
		$wp_customize                      = new WP_Customize_Manager();
		$wp_customize->customize_preview_init();
		$this->assertSame( $did_action_customize_preview_init + 1, did_action( 'customize_preview_init' ) );

		$this->assertSame( 10, has_filter( 'wp_robots', 'wp_robots_no_robots' ) );
		$this->assertSame( 10, has_action( 'wp_head', array( $wp_customize, 'remove_frameless_preview_messenger_channel' ) ) );
		$this->assertSame( 10, has_filter( 'wp_headers', array( $wp_customize, 'filter_iframe_security_headers' ) ) );
		$this->assertSame( 10, has_filter( 'wp_redirect', array( $wp_customize, 'add_state_query_params' ) ) );
		$this->assertTrue( wp_script_is( 'customize-preview', 'enqueued' ) );
		$this->assertSame( 10, has_action( 'wp_head', array( $wp_customize, 'customize_preview_loading_style' ) ) );
		$this->assertSame( 20, has_action( 'wp_footer', array( $wp_customize, 'customize_preview_settings' ) ) );

		// Test unauthorized user outside preview (no messenger_channel).
		wp_set_current_user( self::$subscriber_user_id );
		$wp_customize = new WP_Customize_Manager();
		$wp_customize->register_controls();
		$this->assertNotEmpty( $wp_customize->controls() );
		$wp_customize->customize_preview_init();
		$this->assertEmpty( $wp_customize->controls() );

		// Test unauthorized user inside preview (with messenger_channel).
		wp_set_current_user( self::$subscriber_user_id );
		$wp_customize = new WP_Customize_Manager( array( 'messenger_channel' => 'preview-0' ) );
		$exception    = null;
		try {
			$wp_customize->customize_preview_init();
		} catch ( WPDieException $e ) {
			$exception = $e;
		}
		$this->assertNotNull( $exception );
		$this->assertStringContainsString( 'Unauthorized', $exception->getMessage() );
	}

	/**
	 * Test WP_Customize_Manager::filter_iframe_security_headers().
	 *
	 * @ticket 30937
	 * @ticket 40020
	 * @covers WP_Customize_Manager::filter_iframe_security_headers
	 */
	public function test_filter_iframe_security_headers() {
		$wp_customize = new WP_Customize_Manager();
		$headers      = $wp_customize->filter_iframe_security_headers( array() );
		$this->assertArrayHasKey( 'X-Frame-Options', $headers );
		$this->assertArrayHasKey( 'Content-Security-Policy', $headers );
		$this->assertSame( 'SAMEORIGIN', $headers['X-Frame-Options'] );
		$this->assertSame( "frame-ancestors 'self'", $headers['Content-Security-Policy'] );
	}

	/**
	 * Test WP_Customize_Manager::add_state_query_params().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::add_state_query_params
	 */
	public function test_add_state_query_params() {
		$preview_theme = $this->get_inactive_core_theme();

		$uuid              = wp_generate_uuid4();
		$messenger_channel = 'preview-0';
		$wp_customize      = new WP_Customize_Manager(
			array(
				'changeset_uuid'    => $uuid,
				'messenger_channel' => $messenger_channel,
			)
		);
		$url               = $wp_customize->add_state_query_params( home_url( '/' ) );
		$parsed_url        = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		$this->assertArrayHasKey( 'customize_messenger_channel', $query_params );
		$this->assertArrayHasKey( 'customize_changeset_uuid', $query_params );
		$this->assertArrayNotHasKey( 'customize_theme', $query_params );
		$this->assertSame( $uuid, $query_params['customize_changeset_uuid'] );
		$this->assertSame( $messenger_channel, $query_params['customize_messenger_channel'] );

		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid'    => $uuid,
				'messenger_channel' => null,
				'theme'             => $preview_theme,
			)
		);
		$url          = $wp_customize->add_state_query_params( home_url( '/' ) );
		$parsed_url   = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		$this->assertArrayNotHasKey( 'customize_messenger_channel', $query_params );
		$this->assertArrayHasKey( 'customize_changeset_uuid', $query_params );
		$this->assertArrayHasKey( 'customize_theme', $query_params );
		$this->assertSame( $uuid, $query_params['customize_changeset_uuid'] );
		$this->assertSame( $preview_theme, $query_params['customize_theme'] );

		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid'    => $uuid,
				'messenger_channel' => null,
				'theme'             => $preview_theme,
			)
		);
		$url          = $wp_customize->add_state_query_params( 'http://not-allowed.example.com/?q=1' );
		$parsed_url   = wp_parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );
		$this->assertArrayNotHasKey( 'customize_messenger_channel', $query_params );
		$this->assertArrayNotHasKey( 'customize_changeset_uuid', $query_params );
		$this->assertArrayNotHasKey( 'customize_theme', $query_params );
	}

	/**
	 * Test WP_Customize_Manager::save_changeset_post().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_save_changeset_post_without_theme_activation() {
		global $wp_customize;
		wp_set_current_user( self::$admin_user_id );

		$did_action = array(
			'customize_save_validation_before' => did_action( 'customize_save_validation_before' ),
			'customize_save'                   => did_action( 'customize_save' ),
			'customize_save_after'             => did_action( 'customize_save_after' ),
		);
		$uuid       = wp_generate_uuid4();

		$manager      = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		$wp_customize = $manager;
		$manager->register_controls();
		$manager->set_post_value( 'blogname', 'Changeset Title' );
		$manager->set_post_value( 'blogdescription', 'Changeset Tagline' );

		$pre_saved_data = array(
			'blogname'        => array(
				'value' => 'Overridden Changeset Title',
			),
			'blogdescription' => array(
				'custom' => 'something',
			),
		);
		$date           = ( gmdate( 'Y' ) + 1 ) . '-12-01 00:00:00';
		$r              = $manager->save_changeset_post(
			array(
				'status'   => 'auto-draft',
				'title'    => 'Auto Draft',
				'date_gmt' => $date,
				'data'     => $pre_saved_data,
			)
		);
		$this->assertIsArray( $r );

		$this->assertSame( $did_action['customize_save_validation_before'] + 1, did_action( 'customize_save_validation_before' ) );

		$post_id = $manager->find_changeset_post_id( $uuid );
		$this->assertNotNull( $post_id );
		$saved_data = json_decode( get_post( $post_id )->post_content, true );
		$this->assertSame( $manager->unsanitized_post_values(), wp_list_pluck( $saved_data, 'value' ) );
		$this->assertSame( $pre_saved_data['blogname']['value'], $saved_data['blogname']['value'] );
		$this->assertSame( $pre_saved_data['blogdescription']['custom'], $saved_data['blogdescription']['custom'] );
		foreach ( $saved_data as $setting_id => $setting_params ) {
			$this->assertArrayHasKey( 'type', $setting_params );
			$this->assertSame( 'option', $setting_params['type'] );
			$this->assertArrayHasKey( 'user_id', $setting_params );
			$this->assertSame( self::$admin_user_id, $setting_params['user_id'] );
		}
		$this->assertSame( 'Auto Draft', get_post( $post_id )->post_title );
		$this->assertSame( 'auto-draft', get_post( $post_id )->post_status );
		$this->assertSame( $date, get_post( $post_id )->post_date_gmt );
		$this->assertNotEquals( 'Changeset Title', get_option( 'blogname' ) );
		$this->assertArrayHasKey( 'setting_validities', $r );

		// Test saving with invalid settings, ensuring transaction blocked.
		$previous_saved_data = $saved_data;
		$manager->add_setting(
			'foo_unauthorized',
			array(
				'capability' => 'do_not_allow',
			)
		);
		$manager->add_setting(
			'baz_illegal',
			array(
				'validate_callback' => array( $this, 'return_illegal_error' ),
			)
		);
		$r = $manager->save_changeset_post(
			array(
				'status' => 'auto-draft',
				'data'   => array(
					'blogname'         => array(
						'value' => 'OK',
					),
					'foo_unauthorized' => array(
						'value' => 'No',
					),
					'bar_unknown'      => array(
						'value' => 'No',
					),
					'baz_illegal'      => array(
						'value' => 'No',
					),
				),
			)
		);
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'transaction_fail', $r->get_error_code() );
		$this->assertIsArray( $r->get_error_data() );
		$this->assertArrayHasKey( 'setting_validities', $r->get_error_data() );
		$error_data = $r->get_error_data();
		$this->assertArrayHasKey( 'blogname', $error_data['setting_validities'] );
		$this->assertTrue( $error_data['setting_validities']['blogname'] );
		$this->assertArrayHasKey( 'foo_unauthorized', $error_data['setting_validities'] );
		$this->assertInstanceOf( 'WP_Error', $error_data['setting_validities']['foo_unauthorized'] );
		$this->assertSame( 'unauthorized', $error_data['setting_validities']['foo_unauthorized']->get_error_code() );
		$this->assertArrayHasKey( 'bar_unknown', $error_data['setting_validities'] );
		$this->assertInstanceOf( 'WP_Error', $error_data['setting_validities']['bar_unknown'] );
		$this->assertSame( 'unrecognized', $error_data['setting_validities']['bar_unknown']->get_error_code() );
		$this->assertArrayHasKey( 'baz_illegal', $error_data['setting_validities'] );
		$this->assertInstanceOf( 'WP_Error', $error_data['setting_validities']['baz_illegal'] );
		$this->assertSame( 'illegal', $error_data['setting_validities']['baz_illegal']->get_error_code() );

		// Since transactional, ensure no changes have been made.
		$this->assertSame( $previous_saved_data, json_decode( get_post( $post_id )->post_content, true ) );

		// Attempt a non-transactional/incremental update.
		$manager      = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		$wp_customize = $manager;
		$manager->register_controls(); // That is, register settings.
		$r = $manager->save_changeset_post(
			array(
				'status' => null,
				'data'   => array(
					'blogname'    => array(
						'value' => 'Non-Transactional \o/ <script>unsanitized</script>',
					),
					'bar_unknown' => array(
						'value' => 'No',
					),
				),
			)
		);
		$this->assertIsArray( $r );
		$this->assertArrayHasKey( 'setting_validities', $r );
		$this->assertTrue( $r['setting_validities']['blogname'] );
		$this->assertInstanceOf( 'WP_Error', $r['setting_validities']['bar_unknown'] );
		$saved_data = json_decode( get_post( $post_id )->post_content, true );
		$this->assertNotEquals( $previous_saved_data, $saved_data );
		$this->assertSame( 'Non-Transactional \o/ <script>unsanitized</script>', $saved_data['blogname']['value'] );

		// Ensure the filter applies.
		$customize_changeset_save_data_call_count = $this->customize_changeset_save_data_call_count;
		add_filter( 'customize_changeset_save_data', array( $this, 'filter_customize_changeset_save_data' ), 10, 2 );
		$manager->save_changeset_post(
			array(
				'status' => null,
				'data'   => array(
					'blogname' => array(
						'value' => 'Filtered',
					),
				),
			)
		);
		$this->assertSame( $customize_changeset_save_data_call_count + 1, $this->customize_changeset_save_data_call_count );

		// Publish the changeset: actions will be doubled since also trashed.
		$expected_actions = array(
			'wp_trash_post'                 => 1,
			'clean_post_cache'              => 2,
			'transition_post_status'        => 2,
			'publish_to_trash'              => 1,
			'trash_customize_changeset'     => 1,
			'edit_post'                     => 2,
			'save_post_customize_changeset' => 2,
			'save_post'                     => 2,
			'wp_insert_post'                => 2,
			'wp_after_insert_post'          => 2,
			'trashed_post'                  => 1,
		);
		$action_counts    = array();
		foreach ( array_keys( $expected_actions ) as $action_name ) {
			$action_counts[ $action_name ] = did_action( $action_name );
		}

		$manager      = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$wp_customize = $manager;
		do_action( 'customize_register', $wp_customize );
		$manager->add_setting(
			'scratchpad',
			array(
				'type'       => 'option',
				'capability' => 'exist',
			)
		);
		$manager->get_setting( 'blogname' )->capability = 'exist';
		$original_capabilities                          = wp_list_pluck( $manager->settings(), 'capability' );
		wp_set_current_user( self::$subscriber_user_id );
		$r = $manager->save_changeset_post(
			array(
				'status' => 'publish',
				'data'   => array(
					'blogname'   => array(
						'value' => 'Do it live \o/',
					),
					'scratchpad' => array(
						'value' => '<script>console.info( "HELLO" )</script>',
					),
				),
			)
		);
		$this->assertIsArray( $r );
		$this->assertSame( 'Do it live \o/', get_option( 'blogname' ) );
		$this->assertSame( 'trash', get_post_status( $post_id ) ); // Auto-trashed.
		$this->assertSame( $original_capabilities, wp_list_pluck( $manager->settings(), 'capability' ) );
		$this->assertStringContainsString( '<script>', get_post( $post_id )->post_content );
		$this->assertSame( $manager->changeset_uuid(), get_post( $post_id )->post_name, 'Expected that the "__trashed" suffix to not be added.' );
		wp_set_current_user( self::$admin_user_id );
		$this->assertSame( 'publish', get_post_meta( $post_id, '_wp_trash_meta_status', true ) );
		$this->assertIsNumeric( get_post_meta( $post_id, '_wp_trash_meta_time', true ) );

		foreach ( array_keys( $expected_actions ) as $action_name ) {
			$this->assertSame( $expected_actions[ $action_name ] + $action_counts[ $action_name ], did_action( $action_name ), "Action: $action_name" );
		}

		// Test revisions.
		add_post_type_support( 'customize_changeset', 'revisions' );
		$uuid         = wp_generate_uuid4();
		$manager      = new WP_Customize_Manager( array( 'changeset_uuid' => $uuid ) );
		$wp_customize = $manager;
		do_action( 'customize_register', $manager );

		$manager->set_post_value( 'blogname', 'Hello Surface' );
		$manager->save_changeset_post( array( 'status' => 'auto-draft' ) );

		$manager->set_post_value( 'blogname', 'Hello World' );
		$manager->save_changeset_post( array( 'status' => 'draft' ) );
		$this->assertTrue( wp_revisions_enabled( get_post( $manager->changeset_post_id() ) ) );

		$manager->set_post_value( 'blogname', 'Hello Solar System' );
		$manager->save_changeset_post( array( 'status' => 'draft' ) );

		$manager->set_post_value( 'blogname', 'Hello Galaxy' );
		$manager->save_changeset_post( array( 'status' => 'draft' ) );
		$this->assertCount( 3, wp_get_post_revisions( $manager->changeset_post_id() ) );
	}

	/**
	 * Test saving changeset post without Kses or other content_save_pre filters mutating content.
	 *
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_save_changeset_post_without_kses_corrupting_json() {
		global $wp_customize;
		$lesser_admin_user_id = self::$other_admin_user_id;

		$uuid         = wp_generate_uuid4();
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);

		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap_to_disallow_unfiltered_html' ), 10, 2 );
		kses_init();
		add_filter( 'content_save_pre', 'capital_P_dangit' );
		add_post_type_support( 'customize_changeset', 'revisions' );

		$options = array(
			'custom_html_1' => '<script>document.write(" Wordpress 1")</script>',
			'custom_html_2' => '<script>document.write(" Wordpress 2")</script>',
			'custom_html_3' => '<script>document.write(" Wordpress 3")</script>',
		);

		// Populate setting as user who can bypass content_save_pre filter.
		wp_set_current_user( self::$admin_user_id );
		$wp_customize = $this->get_manager_for_testing_json_corruption_protection( $uuid );
		$wp_customize->set_post_value( 'custom_html_1', $options['custom_html_1'] );
		$wp_customize->save_changeset_post(
			array(
				'status' => 'draft',
			)
		);

		// Populate setting as user who cannot bypass content_save_pre filter.
		wp_set_current_user( $lesser_admin_user_id );
		$wp_customize = $this->get_manager_for_testing_json_corruption_protection( $uuid );
		$wp_customize->set_post_value( 'custom_html_2', $options['custom_html_2'] );
		$wp_customize->save_changeset_post(
			array(
				'autosave' => true,
			)
		);

		/*
		 * Ensure that the unsanitized value (the "POST data") is preserved in the autosave revision.
		 * The value is sent through the sanitize function when it is read from the changeset.
		 */
		$autosave_revision = wp_get_post_autosave( $wp_customize->changeset_post_id(), get_current_user_id() );
		$saved_data        = json_decode( $autosave_revision->post_content, true );
		$this->assertSame( $options['custom_html_1'], $saved_data['custom_html_1']['value'] );
		$this->assertSame( $options['custom_html_2'], $saved_data['custom_html_2']['value'] );

		// Update post to discard autosave.
		$wp_customize->save_changeset_post(
			array(
				'status' => 'draft',
			)
		);

		/*
		 * Ensure that the unsanitized value (the "POST data") is preserved in the post content.
		 * The value is sent through the sanitize function when it is read from the changeset.
		 */
		$wp_customize = $this->get_manager_for_testing_json_corruption_protection( $uuid );
		$saved_data   = json_decode( get_post( $wp_customize->changeset_post_id() )->post_content, true );
		$this->assertSame( $options['custom_html_1'], $saved_data['custom_html_1']['value'] );
		$this->assertSame( $options['custom_html_2'], $saved_data['custom_html_2']['value'] );

		/*
		 * Ensure that the unsanitized value (the "POST data") is preserved in the revisions' content.
		 * The value is sent through the sanitize function when it is read from the changeset.
		 */
		$revisions  = wp_get_post_revisions( $wp_customize->changeset_post_id() );
		$revision   = array_shift( $revisions );
		$saved_data = json_decode( $revision->post_content, true );
		$this->assertSame( $options['custom_html_1'], $saved_data['custom_html_1']['value'] );
		$this->assertSame( $options['custom_html_2'], $saved_data['custom_html_2']['value'] );

		/*
		 * Now when publishing the changeset, the unsanitized values will be read from the changeset
		 * and sanitized according to the capabilities of the users who originally updated each
		 * setting in the changeset to begin with.
		 */
		wp_set_current_user( $lesser_admin_user_id );
		$wp_customize = $this->get_manager_for_testing_json_corruption_protection( $uuid );
		$wp_customize->set_post_value( 'custom_html_3', $options['custom_html_3'] );
		$wp_customize->save_changeset_post(
			array(
				'status' => 'publish',
			)
		);

		// User saved as one who can bypass content_save_pre filter.
		$this->assertStringContainsString( '<script>', get_option( 'custom_html_1' ) );
		$this->assertStringContainsString( 'Wordpress', get_option( 'custom_html_1' ) ); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText

		// User saved as one who cannot bypass content_save_pre filter.
		$this->assertStringNotContainsString( '<script>', get_option( 'custom_html_2' ) );
		$this->assertStringContainsString( 'WordPress', get_option( 'custom_html_2' ) );

		// User saved as one who also cannot bypass content_save_pre filter.
		$this->assertStringNotContainsString( '<script>', get_option( 'custom_html_3' ) );
		$this->assertStringContainsString( 'WordPress', get_option( 'custom_html_3' ) );
	}

	/**
	 * Get a manager for testing JSON corruption protection.
	 *
	 * @param string $uuid UUID.
	 * @return WP_Customize_Manager Manager.
	 */
	private function get_manager_for_testing_json_corruption_protection( $uuid ) {
		global $wp_customize;
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		for ( $i = 0; $i < 5; $i++ ) {
			$wp_customize->add_setting(
				sprintf( 'custom_html_%d', $i ),
				array(
					'type'              => 'option',
					'sanitize_callback' => array( $this, 'apply_content_save_pre_filters_if_not_main_admin_user' ),
				)
			);
		}
		return $wp_customize;
	}

	/**
	 * Sanitize content with Kses if the current user is not the main admin.
	 *
	 * @since 5.4.1
	 *
	 * @param string $content Content to sanitize.
	 * @return string Sanitized content.
	 */
	public function apply_content_save_pre_filters_if_not_main_admin_user( $content ) {
		if ( get_current_user_id() !== self::$admin_user_id ) {
			$content = apply_filters( 'content_save_pre', $content );
		}
		return $content;
	}

	/**
	 * Filter map_meta_cap to disallow unfiltered_html.
	 *
	 * @since 5.4.1
	 *
	 * @param array  $caps User's capabilities.
	 * @param string $cap  Requested cap.
	 * @return array Caps.
	 */
	public function filter_map_meta_cap_to_disallow_unfiltered_html( $caps, $cap ) {
		if ( 'unfiltered_html' === $cap ) {
			$caps = array( 'do_not_allow' );
		}
		return $caps;
	}

	/**
	 * Call count for customize_changeset_save_data filter.
	 *
	 * @var int
	 */
	protected $customize_changeset_save_data_call_count = 0;

	/**
	 * Filter customize_changeset_save_data.
	 *
	 * @param array $data    Data.
	 * @param array $context Context.
	 * @return array Data.
	 */
	public function filter_customize_changeset_save_data( $data, $context ) {
		$this->customize_changeset_save_data_call_count += 1;
		$this->assertIsArray( $data );
		$this->assertIsArray( $context );
		$this->assertArrayHasKey( 'uuid', $context );
		$this->assertArrayHasKey( 'title', $context );
		$this->assertArrayHasKey( 'status', $context );
		$this->assertArrayHasKey( 'date_gmt', $context );
		$this->assertArrayHasKey( 'post_id', $context );
		$this->assertArrayHasKey( 'previous_data', $context );
		$this->assertArrayHasKey( 'manager', $context );
		return $data;
	}

	/**
	 * Return illegal error.
	 *
	 * @return WP_Error Error.
	 */
	public function return_illegal_error() {
		return new WP_Error( 'illegal' );
	}

	/**
	 * Test WP_Customize_Manager::save_changeset_post().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::save_changeset_post
	 * @covers WP_Customize_Manager::update_stashed_theme_mod_settings
	 */
	public function test_save_changeset_post_with_theme_activation() {
		global $wp_customize;
		wp_set_current_user( self::$admin_user_id );

		$preview_theme      = $this->get_inactive_core_theme();
		$stashed_theme_mods = array(
			$preview_theme => array(
				'background_color' => array(
					'value' => '#123456',
				),
			),
		);
		update_option( 'customize_stashed_theme_mods', $stashed_theme_mods );
		$uuid         = wp_generate_uuid4();
		$manager      = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
				'theme'          => $preview_theme,
			)
		);
		$wp_customize = $manager;
		do_action( 'customize_register', $manager );

		$manager->set_post_value( 'blogname', 'Hello Preview Theme' );
		$post_values = $manager->unsanitized_post_values();
		$manager->save_changeset_post( array( 'status' => 'publish' ) ); // Activate.

		$this->assertSame( '#123456', $post_values['background_color'] );
		$this->assertSame( $preview_theme, get_stylesheet() );
		$this->assertSame( 'Hello Preview Theme', get_option( 'blogname' ) );
	}

	/**
	 * Test saving changesets with varying users and capabilities.
	 *
	 * @ticket 38705
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_save_changeset_post_with_varying_users() {
		global $wp_customize;

		add_theme_support( 'custom-background' );
		wp_set_current_user( self::$admin_user_id );
		$other_admin_user_id = self::$other_admin_user_id;

		$uuid         = wp_generate_uuid4();
		$wp_customize = $this->create_test_manager( $uuid );
		$r            = $wp_customize->save_changeset_post(
			array(
				'status' => 'auto-draft',
				'data'   => array(
					'blogname'         => array(
						'value' => 'Admin 1 Title',
					),
					'scratchpad'       => array(
						'value' => 'Admin 1 Scratch',
					),
					'background_color' => array(
						'value' => '#000000',
					),
				),
			)
		);
		$this->assertIsArray( $r );
		$this->assertSame(
			array_fill_keys( array( 'blogname', 'scratchpad', 'background_color' ), true ),
			$r['setting_validities']
		);
		$post_id = $wp_customize->find_changeset_post_id( $uuid );
		$data    = json_decode( get_post( $post_id )->post_content, true );
		$this->assertSame( self::$admin_user_id, $data['blogname']['user_id'] );
		$this->assertSame( self::$admin_user_id, $data['scratchpad']['user_id'] );
		$this->assertSame( self::$admin_user_id, $data[ $this->manager->get_stylesheet() . '::background_color' ]['user_id'] );

		// Attempt to save just one setting under a different user.
		wp_set_current_user( $other_admin_user_id );
		$wp_customize = $this->create_test_manager( $uuid );
		$r            = $wp_customize->save_changeset_post(
			array(
				'status' => 'auto-draft',
				'data'   => array(
					'blogname'         => array(
						'value' => 'Admin 2 Title',
					),
					'background_color' => array(
						'value' => '#FFFFFF',
					),
				),
			)
		);
		$this->assertIsArray( $r );
		$this->assertSame(
			array_fill_keys( array( 'blogname', 'background_color' ), true ),
			$r['setting_validities']
		);
		$data = json_decode( get_post( $post_id )->post_content, true );
		$this->assertSame( 'Admin 2 Title', $data['blogname']['value'] );
		$this->assertSame( $other_admin_user_id, $data['blogname']['user_id'] );
		$this->assertSame( 'Admin 1 Scratch', $data['scratchpad']['value'] );
		$this->assertSame( self::$admin_user_id, $data['scratchpad']['user_id'] );
		$this->assertSame( '#FFFFFF', $data[ $this->manager->get_stylesheet() . '::background_color' ]['value'] );
		$this->assertSame( $other_admin_user_id, $data[ $this->manager->get_stylesheet() . '::background_color' ]['user_id'] );

		// Attempt to save now as under-privileged user.
		$wp_customize = $this->create_test_manager( $uuid );
		$r            = $wp_customize->save_changeset_post(
			array(
				'status'  => 'auto-draft',
				'data'    => array(
					'blogname'   => array(
						'value' => 'Admin 2 Title', // Identical to what is already in the changeset so will be skipped.
					),
					'scratchpad' => array(
						'value' => 'Subscriber Scratch',
					),
				),
				'user_id' => self::$subscriber_user_id,
			)
		);
		$this->assertIsArray( $r );
		$this->assertSame(
			array_fill_keys( array( 'blogname', 'scratchpad' ), true ),
			$r['setting_validities']
		);
		$data = json_decode( get_post( $post_id )->post_content, true );
		$this->assertSame( $other_admin_user_id, $data['blogname']['user_id'], 'Expected setting to be untouched.' );
		$this->assertSame( self::$subscriber_user_id, $data['scratchpad']['user_id'] );
		$this->assertSame( $other_admin_user_id, $data[ $this->manager->get_stylesheet() . '::background_color' ]['user_id'] );

		// Manually update the changeset so that the user_id context is not included.
		$data                             = json_decode( get_post( $post_id )->post_content, true );
		$data['blogdescription']['value'] = 'Programmatically-supplied Tagline';
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $post_id,
					'post_content' => wp_json_encode( $data ),
				)
			)
		);

		// Ensure the modifying user set as the current user when each is saved, simulating WP Cron envronment.
		wp_set_current_user( 0 );
		$save_counts = array();
		foreach ( array_keys( $data ) as $setting_id ) {
			$setting_id                 = preg_replace( '/^.+::/', '', $setting_id );
			$save_counts[ $setting_id ] = did_action( sprintf( 'customize_save_%s', $setting_id ) );
		}
		$this->filtered_setting_current_user_ids = array();
		foreach ( $wp_customize->settings() as $setting ) {
			add_filter( sprintf( 'customize_sanitize_%s', $setting->id ), array( $this, 'filter_customize_setting_to_log_current_user' ), 10, 2 );
		}
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		foreach ( array_keys( $data ) as $setting_id ) {
			$setting_id = preg_replace( '/^.+::/', '', $setting_id );
			$this->assertSame( $save_counts[ $setting_id ] + 1, did_action( sprintf( 'customize_save_%s', $setting_id ) ), $setting_id );
		}
		$this->assertSameSets( array( 'blogname', 'blogdescription', 'background_color', 'scratchpad' ), array_keys( $this->filtered_setting_current_user_ids ) );
		$this->assertSame( $other_admin_user_id, $this->filtered_setting_current_user_ids['blogname'] );
		$this->assertSame( 0, $this->filtered_setting_current_user_ids['blogdescription'] );
		$this->assertSame( self::$subscriber_user_id, $this->filtered_setting_current_user_ids['scratchpad'] );
		$this->assertSame( $other_admin_user_id, $this->filtered_setting_current_user_ids['background_color'] );
		$this->assertSame( 'Subscriber Scratch', get_option( 'scratchpad' ) );
	}

	/**
	 * Create test manager.
	 *
	 * @param string $uuid Changeset UUID.
	 * @return WP_Customize_Manager Manager.
	 */
	protected function create_test_manager( $uuid ) {
		$manager = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		do_action( 'customize_register', $manager );
		$manager->add_setting(
			'blogfounded',
			array(
				'type' => 'option',
			)
		);
		$manager->add_setting(
			'blogterminated',
			array(
				'type'       => 'option',
				'capability' => 'do_not_allow',
			)
		);
		$manager->add_setting(
			'scratchpad',
			array(
				'type'       => 'option',
				'capability' => 'exist',
			)
		);
		return $manager;
	}

	/**
	 * Test that updating an auto-draft changeset bumps its post_date to keep it from getting garbage collected by wp_delete_auto_drafts().
	 *
	 * @ticket 31089
	 * @see wp_delete_auto_drafts()
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_save_changeset_post_dumping_auto_draft_date() {
		global $wp_customize;
		wp_set_current_user( self::$admin_user_id );

		$uuid              = wp_generate_uuid4();
		$changeset_post_id = wp_insert_post(
			array(
				'post_type'    => 'customize_changeset',
				'post_content' => '{}',
				'post_name'    => $uuid,
				'post_status'  => 'auto-draft',
				'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			)
		);

		$post               = get_post( $changeset_post_id );
		$original_post_date = $post->post_date;

		$wp_customize = $this->create_test_manager( $uuid );
		$wp_customize->save_changeset_post(
			array(
				'status' => 'auto-draft',
				'data'   => array(
					'blogname' => array(
						'value' => 'Admin 1 Title',
					),
				),
			)
		);

		$post = get_post( $changeset_post_id );
		$this->assertNotEquals( $post->post_date, $original_post_date );
	}

	/**
	 * Test writing changesets when user supplies unchanged values.
	 *
	 * @ticket 38865
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_save_changeset_post_with_unchanged_values() {
		global $wp_customize;

		add_theme_support( 'custom-background' );
		wp_set_current_user( self::$admin_user_id );
		$other_admin_user_id = self::$other_admin_user_id;

		$uuid         = wp_generate_uuid4();
		$wp_customize = $this->create_test_manager( $uuid );
		$wp_customize->save_changeset_post(
			array(
				'status' => 'auto-draft',
				'data'   => array(
					'blogname'        => array(
						'value' => 'Admin 1 Title',
					),
					'blogdescription' => array(
						'value' => 'Admin 1 Tagline',
					),
					'blogfounded'     => array(
						'value' => '2016',
					),
					'scratchpad'      => array(
						'value' => 'Admin 1 Scratch',
					),
				),
			)
		);

		// Make sure that setting properties of unknown and unauthorized settings are rejected.
		$data = get_post( $wp_customize->changeset_post_id() )->post_content;
		$r    = $wp_customize->save_changeset_post(
			array(
				'data' => array(
					'unknownsetting' => array(
						'custom' => 'prop',
					),
					'blogterminated' => array(
						'custom' => 'prop',
					),
				),
			)
		);
		$this->assertInstanceOf( 'WP_Error', $r['setting_validities']['unknownsetting'] );
		$this->assertSame( 'unrecognized', $r['setting_validities']['unknownsetting']->get_error_code() );
		$this->assertInstanceOf( 'WP_Error', $r['setting_validities']['blogterminated'] );
		$this->assertSame( 'unauthorized', $r['setting_validities']['blogterminated']->get_error_code() );
		$this->assertSame( $data, get_post( $wp_customize->changeset_post_id() )->post_content );

		// Test submitting data with changed and unchanged settings, creating a new instance so that the post_values are cleared.
		wp_set_current_user( $other_admin_user_id );
		$wp_customize = $this->create_test_manager( $uuid );
		$r            = $wp_customize->save_changeset_post(
			array(
				'status' => 'auto-draft',
				'data'   => array(
					'blogname'        => array(
						'value' => 'Admin 1 Title', // Unchanged value.
					),
					'blogdescription' => array(
						'value' => 'Admin 1 Tagline Changed', // Changed value.
					),
					'blogfounded'     => array(
						'extra' => 'blogfounded_param', // New param.
					),
					'scratchpad'      => array(
						'value' => 'Admin 1 Scratch', // Unchanged value.
						'extra' => 'background_scratchpad2', // New param.
					),
				),
			)
		);

		// Note that blogfounded is not included among setting_validities because no value was supplied and it is not unrecognized/unauthorized.
		$this->assertSame( array_fill_keys( array( 'blogname', 'blogdescription', 'scratchpad' ), true ), $r['setting_validities'], 'Expected blogname even though unchanged.' );

		$data = json_decode( get_post( $wp_customize->changeset_post_id() )->post_content, true );

		$this->assertSame( self::$admin_user_id, $data['blogname']['user_id'], 'Expected unchanged user_id since value was unchanged.' );
		$this->assertSame( $other_admin_user_id, $data['blogdescription']['user_id'] );
		$this->assertSame( $other_admin_user_id, $data['blogfounded']['user_id'] );
		$this->assertSame( $other_admin_user_id, $data['scratchpad']['user_id'] );
	}

	/**
	 * Test writing changesets when user supplies unchanged values.
	 *
	 * @ticket 39896
	 * @covers WP_Customize_Manager::save_changeset_post
	 * @covers WP_Customize_Manager::grant_edit_post_capability_for_changeset
	 */
	public function test_save_changeset_post_with_autosave() {
		wp_set_current_user( self::$admin_user_id );
		$uuid              = wp_generate_uuid4();
		$changeset_post_id = wp_insert_post(
			array(
				'post_type'    => 'customize_changeset',
				'post_content' => wp_json_encode(
					array(
						'blogname' => array(
							'value' => 'Auto-draft Title',
						),
					)
				),
				'post_author'  => self::$admin_user_id,
				'post_name'    => $uuid,
				'post_status'  => 'auto-draft',
			)
		);

		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		$wp_customize->register_controls(); // And settings too.

		// Autosave of an auto-draft overwrites original.
		$wp_customize->save_changeset_post(
			array(
				'data'     => array(
					'blogname' => array(
						'value' => 'Autosaved Auto-draft Title',
					),
				),
				'autosave' => true,
			)
		);
		$this->assertFalse( wp_get_post_autosave( $changeset_post_id, get_current_user_id() ) );
		$this->assertStringContainsString( 'Autosaved Auto-draft Title', get_post( $changeset_post_id )->post_content );

		// Update status to draft for subsequent tests.
		$wp_customize->save_changeset_post(
			array(
				'data'     => array(
					'blogname' => array(
						'value' => 'Draft Title',
					),
				),
				'status'   => 'draft',
				'autosave' => false,
			)
		);
		$this->assertStringContainsString( 'Draft Title', get_post( $changeset_post_id )->post_content );

		// Fail: illegal_autosave_with_date_gmt.
		$r = $wp_customize->save_changeset_post(
			array(
				'autosave' => true,
				'date_gmt' => ( gmdate( 'Y' ) + 1 ) . '-12-01 00:00:00',
			)
		);
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'illegal_autosave_with_date_gmt', $r->get_error_code() );

		// Fail: illegal_autosave_with_status.
		$r = $wp_customize->save_changeset_post(
			array(
				'autosave' => true,
				'status'   => 'pending',
			)
		);
		$this->assertSame( 'illegal_autosave_with_status', $r->get_error_code() );

		// Fail: illegal_autosave_with_non_current_user.
		$r = $wp_customize->save_changeset_post(
			array(
				'autosave' => true,
				'user_id'  => self::$other_admin_user_id,
			)
		);
		$this->assertSame( 'illegal_autosave_with_non_current_user', $r->get_error_code() );

		// Try autosave.
		$this->assertFalse( wp_get_post_autosave( $changeset_post_id, get_current_user_id() ) );
		$r = $wp_customize->save_changeset_post(
			array(
				'data'     => array(
					'blogname' => array(
						'value' => 'Autosave Title',
					),
				),
				'autosave' => true,
			)
		);
		$this->assertIsArray( $r );

		// Verify that autosave happened.
		$autosave_revision = wp_get_post_autosave( $changeset_post_id, get_current_user_id() );
		$this->assertInstanceOf( 'WP_Post', $autosave_revision );
		$this->assertStringContainsString( 'Draft Title', get_post( $changeset_post_id )->post_content );
		$this->assertStringContainsString( 'Autosave Title', $autosave_revision->post_content );
	}

	/**
	 * Test passing `null` for a setting ID to remove it from the changeset.
	 *
	 * @ticket 41621
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_remove_setting_from_changeset_post() {
		$uuid = wp_generate_uuid4();

		$manager = $this->create_test_manager( $uuid );
		$manager->save_changeset_post(
			array(
				'data' => array(
					'scratchpad' => array(
						'value' => 'foo',
					),
				),
			)
		);

		// Create a new manager so post values are unset.
		$manager = $this->create_test_manager( $uuid );

		$this->assertArrayHasKey( 'scratchpad', $manager->changeset_data() );

		$manager->save_changeset_post(
			array(
				'data' => array(
					'scratchpad' => null,
				),
			)
		);

		$this->assertArrayNotHasKey( 'scratchpad', $manager->changeset_data() );
	}

	/**
	 * Test writing changesets and publishing with users who can unfiltered_html and those who cannot.
	 *
	 * @ticket 38705
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_save_changeset_post_with_varying_unfiltered_html_cap() {
		global $wp_customize;
		grant_super_admin( self::$admin_user_id );
		$this->assertTrue( user_can( self::$admin_user_id, 'unfiltered_html' ) );
		$this->assertFalse( user_can( self::$subscriber_user_id, 'unfiltered_html' ) );
		wp_set_current_user( 0 );
		add_action( 'customize_register', array( $this, 'register_scratchpad_setting' ) );

		// Attempt scratchpad with user who has unfiltered_html.
		update_option( 'scratchpad', '' );
		$wp_customize = new WP_Customize_Manager();
		do_action( 'customize_register', $wp_customize );
		$wp_customize->set_post_value( 'scratchpad', 'Unfiltered<script>evil</script>' );
		$wp_customize->save_changeset_post(
			array(
				'status'  => 'auto-draft',
				'user_id' => self::$admin_user_id,
			)
		);
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $wp_customize->changeset_uuid() ) );
		do_action( 'customize_register', $wp_customize );
		$wp_customize->save_changeset_post( array( 'status' => 'publish' ) );
		$this->assertSame( 'Unfiltered<script>evil</script>', get_option( 'scratchpad' ) );

		// Attempt scratchpad with user who doesn't have unfiltered_html.
		update_option( 'scratchpad', '' );
		$wp_customize = new WP_Customize_Manager();
		do_action( 'customize_register', $wp_customize );
		$wp_customize->set_post_value( 'scratchpad', 'Unfiltered<script>evil</script>' );
		$wp_customize->save_changeset_post(
			array(
				'status'  => 'auto-draft',
				'user_id' => self::$subscriber_user_id,
			)
		);
		$wp_customize = new WP_Customize_Manager( array( 'changeset_uuid' => $wp_customize->changeset_uuid() ) );
		do_action( 'customize_register', $wp_customize );
		$wp_customize->save_changeset_post( array( 'status' => 'publish' ) );
		$this->assertSame( 'Unfilteredevil', get_option( 'scratchpad' ) );

		// Attempt publishing scratchpad as anonymous user when changeset was set by privileged user.
		update_option( 'scratchpad', '' );
		$wp_customize = new WP_Customize_Manager();
		do_action( 'customize_register', $wp_customize );
		$wp_customize->set_post_value( 'scratchpad', 'Unfiltered<script>evil</script>' );
		$wp_customize->save_changeset_post(
			array(
				'status'  => 'auto-draft',
				'user_id' => self::$admin_user_id,
			)
		);
		$changeset_post_id = $wp_customize->changeset_post_id();
		wp_set_current_user( 0 );
		$wp_customize = null;
		unset( $GLOBALS['wp_actions']['customize_register'] );
		$this->assertSame( 'Unfilteredevil', apply_filters( 'content_save_pre', 'Unfiltered<script>evil</script>' ) );
		wp_publish_post( $changeset_post_id ); // @todo If wp_update_post() is used here, then kses will corrupt the post_content.
		$this->assertSame( 'Unfiltered<script>evil</script>', get_option( 'scratchpad' ) );
	}

	/**
	 * Test saving settings by publishing a changeset outside of Customizer entirely.
	 *
	 * Widgets get their settings registered and previewed early in the admin,
	 * so this ensures that the previewing is bypassed when in the context of
	 * publishing
	 *
	 * @ticket 39221
	 * @covers ::_wp_customize_publish_changeset
	 * @see WP_Customize_Widgets::schedule_customize_register()
	 * @see WP_Customize_Widgets::customize_register()
	 */
	public function test_wp_customize_publish_changeset() {
		global $wp_customize;
		$wp_customize = null;

		// Set the admin current screen to cause WP_Customize_Widgets::schedule_customize_register() to do early setting registration.
		set_current_screen( 'edit' );
		$this->assertTrue( is_admin() );

		$old_sidebars_widgets = get_option( 'sidebars_widgets' );
		$new_sidebars_widgets = $old_sidebars_widgets;
		$this->assertGreaterThan( 2, count( $new_sidebars_widgets['sidebar-1'] ) );
		$new_sidebar_1 = array_reverse( $new_sidebars_widgets['sidebar-1'] );

		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'customize_changeset',
				'post_status'  => 'draft',
				'post_name'    => wp_generate_uuid4(),
				'post_content' => wp_json_encode(
					array(
						'sidebars_widgets[sidebar-1]' => array(
							'value' => $new_sidebar_1,
						),
					)
				),
			)
		);

		// Save the updated sidebar widgets into the options table by publishing the changeset.
		wp_publish_post( $post_id );

		// Make sure previewing filters were never added, since WP_Customize_Manager should be constructed with settings_previewed=false.
		$this->assertFalse( has_filter( 'option_sidebars_widgets' ) );
		$this->assertFalse( has_filter( 'default_option_sidebars_widgets' ) );

		// Ensure that the value has actually been written to the DB.
		$updated_sidebars_widgets = get_option( 'sidebars_widgets' );
		$this->assertSame( $new_sidebar_1, $updated_sidebars_widgets['sidebar-1'] );
	}

	/**
	 * Ensure that saving a changeset with a publish status but future date will change the status to future, to align with behavior in wp_insert_post().
	 *
	 * @ticket 41336
	 * @covers WP_Customize_Manager::save_changeset_post
	 */
	public function test_publish_changeset_with_future_status_when_future_date() {
		$wp_customize = $this->create_test_manager( wp_generate_uuid4() );

		$wp_customize->save_changeset_post(
			array(
				'date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
				'status'   => 'publish',
				'title'    => 'Foo',
			)
		);

		$this->assertSame( 'future', get_post_status( $wp_customize->changeset_post_id() ) );
	}

	/**
	 * Ensure that save_changeset_post method bails updating an underlying changeset which is invalid.
	 *
	 * @ticket 41252
	 * @covers WP_Customize_Manager::save_changeset_post
	 * @covers WP_Customize_Manager::get_changeset_post_data
	 */
	public function test_save_changeset_post_for_bad_changeset() {
		$uuid    = wp_generate_uuid4();
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'customize_changeset',
				'post_content' => 'INVALID_JSON',
				'post_name'    => $uuid,
				'post_status'  => 'auto-draft',
				'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			)
		);
		$manager = $this->create_test_manager( $uuid );
		$args    = array(
			'data' => array(
				'blogname' => array(
					'value' => 'Test',
				),
			),
		);

		$r = $manager->save_changeset_post( $args );
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'json_parse_error', $r->get_error_code() );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'null',
			)
		);
		$r = $manager->save_changeset_post( $args );
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'expected_array', $r->get_error_code() );
	}

	/**
	 * Test that trash_changeset_post() trashes a changeset post with its name and content preserved.
	 *
	 * @covers WP_Customize_Manager::trash_changeset_post
	 */
	public function test_trash_changeset_post_preserves_properties() {
		$args = array(
			'post_type'    => 'customize_changeset',
			'post_content' => wp_json_encode(
				array(
					'blogname' => array(
						'value' => 'Test',
					),
				)
			),
			'post_name'    => wp_generate_uuid4(),
			'post_status'  => 'draft',
		);

		$post_id = wp_insert_post( $args );

		$manager = $this->create_test_manager( $args['post_name'] );
		$manager->trash_changeset_post( $post_id );

		$post = get_post( $post_id );

		$this->assertSame( 'trash', get_post_status( $post_id ) );
		$this->assertSame( $args['post_name'], $post->post_name );
		$this->assertSame( $args['post_content'], $post->post_content );
	}

	/**
	 * Test that trash_changeset_post() passes the correct number of arguments to post trash hooks.
	 *
	 * @ticket 60183
	 * @covers WP_Customize_Manager::trash_changeset_post
	 */
	public function test_trash_changeset_post_passes_all_arguments_to_trash_hooks() {
		$args = array(
			'post_type'    => 'customize_changeset',
			'post_content' => wp_json_encode(
				array(
					'blogname' => array(
						'value' => 'Test',
					),
				)
			),
			'post_name'    => wp_generate_uuid4(),
			'post_status'  => 'draft',
		);

		$post_id = wp_insert_post( $args );

		$manager = $this->create_test_manager( $args['post_name'] );

		$pre_trash_post = new MockAction();
		$wp_trash_post  = new MockAction();
		$trashed_post   = new MockAction();

		add_action( 'pre_trash_post', array( $pre_trash_post, 'action' ), 10, 3 );
		add_action( 'wp_trash_post', array( $wp_trash_post, 'action' ), 10, 2 );
		add_action( 'trashed_post', array( $trashed_post, 'action' ), 10, 2 );

		$manager->trash_changeset_post( $post_id );

		$this->assertCount( 3, $pre_trash_post->get_args()[0] );
		$this->assertCount( 2, $wp_trash_post->get_args()[0] );
		$this->assertCount( 2, $trashed_post->get_args()[0] );
	}

	/**
	 * Register scratchpad setting.
	 *
	 * @param WP_Customize_Manager $wp_customize Manager.
	 */
	public function register_scratchpad_setting( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_setting(
			'scratchpad',
			array(
				'type'              => 'option',
				'capability'        => 'exist',
				'sanitize_callback' => array( $this, 'filter_sanitize_scratchpad' ),
			)
		);
	}

	/**
	 * Sanitize scratchpad as if it is post_content so kses filters apply.
	 *
	 * @param string $value Value.
	 * @return string Value.
	 */
	public function filter_sanitize_scratchpad( $value ) {
		return apply_filters( 'content_save_pre', $value );
	}

	/**
	 * Current user when settings are filtered.
	 *
	 * @var array
	 */
	protected $filtered_setting_current_user_ids = array();

	/**
	 * Filter setting to capture the current user when the filter applies.
	 *
	 * @param mixed                $value   Setting value.
	 * @param WP_Customize_Setting $setting Setting.
	 * @return mixed Value.
	 */
	public function filter_customize_setting_to_log_current_user( $value, $setting ) {
		$this->filtered_setting_current_user_ids[ $setting->id ] = get_current_user_id();
		return $value;
	}

	/**
	 * Test WP_Customize_Manager::is_cross_domain().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::is_cross_domain
	 */
	public function test_is_cross_domain() {
		$wp_customize = new WP_Customize_Manager();

		update_option( 'home', 'http://example.com' );
		update_option( 'siteurl', 'http://example.com' );
		$this->assertFalse( $wp_customize->is_cross_domain() );

		update_option( 'home', 'http://example.com' );
		update_option( 'siteurl', 'https://admin.example.com' );
		$this->assertTrue( $wp_customize->is_cross_domain() );
	}

	/**
	 * Test WP_Customize_Manager::get_allowed_urls().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::get_allowed_urls
	 */
	public function test_get_allowed_urls() {
		$wp_customize = new WP_Customize_Manager();
		$this->assertFalse( is_ssl() );
		$this->assertFalse( $wp_customize->is_cross_domain() );
		$allowed = $wp_customize->get_allowed_urls();
		$this->assertSame( $allowed, array( home_url( '/', 'http' ) ) );

		add_filter( 'customize_allowed_urls', array( $this, 'filter_customize_allowed_urls' ) );
		$allowed = $wp_customize->get_allowed_urls();
		$this->assertSameSets( $allowed, array( 'http://headless.example.com/', home_url( '/', 'http' ) ) );
	}

	/**
	 * Callback for customize_allowed_urls filter.
	 *
	 * @param array $urls URLs.
	 * @return array URLs.
	 */
	public function filter_customize_allowed_urls( $urls ) {
		$urls[] = 'http://headless.example.com/';
		return $urls;
	}

	/**
	 * Test WP_Customize_Manager::doing_ajax().
	 *
	 * @group ajax
	 */
	public function test_doing_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );

		$manager = $this->manager;
		$this->assertTrue( $manager->doing_ajax() );

		$_REQUEST['action'] = 'customize_save';
		$this->assertTrue( $manager->doing_ajax( 'customize_save' ) );
		$this->assertFalse( $manager->doing_ajax( 'update-widget' ) );
	}

	/**
	 * Test ! WP_Customize_Manager::doing_ajax().
	 */
	public function test_not_doing_ajax() {
		add_filter( 'wp_doing_ajax', '__return_false' );

		$manager = $this->manager;
		$this->assertFalse( $manager->doing_ajax() );
	}

	/**
	 * Test WP_Customize_Manager::unsanitized_post_values().
	 *
	 * @ticket 30988
	 */
	public function test_unsanitized_post_values_from_input() {
		wp_set_current_user( self::$admin_user_id );
		$manager = $this->manager;

		$customized          = array(
			'foo'       => 'bar',
			'baz[quux]' => 123,
		);
		$_POST['customized'] = wp_slash( wp_json_encode( $customized ) );
		$post_values         = $manager->unsanitized_post_values();
		$this->assertSame( $customized, $post_values );
		$this->assertEmpty( $manager->unsanitized_post_values( array( 'exclude_post_data' => true ) ) );

		$manager->set_post_value( 'foo', 'BAR' );
		$post_values = $manager->unsanitized_post_values();
		$this->assertSame( 'BAR', $post_values['foo'] );
		$this->assertEmpty( $manager->unsanitized_post_values( array( 'exclude_post_data' => true ) ) );

		// If user is unprivileged, the post data is ignored.
		wp_set_current_user( 0 );
		$this->assertEmpty( $manager->unsanitized_post_values() );
	}

	/**
	 * Test WP_Customize_Manager::unsanitized_post_values().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::unsanitized_post_values
	 */
	public function test_unsanitized_post_values_with_changeset_and_stashed_theme_mods() {
		wp_set_current_user( self::$admin_user_id );

		$preview_theme                          = $this->get_inactive_core_theme();
		$stashed_theme_mods                     = array(
			$preview_theme => array(
				'background_color' => array(
					'value' => '#000000',
				),
			),
		);
		$stashed_theme_mods[ get_stylesheet() ] = array(
			'background_color' => array(
				'value' => '#FFFFFF',
			),
		);
		update_option( 'customize_stashed_theme_mods', $stashed_theme_mods );

		$post_values         = array(
			'blogdescription' => 'Post Input Tagline',
		);
		$_POST['customized'] = wp_slash( wp_json_encode( $post_values ) );

		$uuid           = wp_generate_uuid4();
		$changeset_data = array(
			'blogname'        => array(
				'value' => 'Changeset Title',
			),
			'blogdescription' => array(
				'value' => 'Changeset Tagline',
			),
		);
		self::factory()->post->create(
			array(
				'post_type'    => 'customize_changeset',
				'post_status'  => 'auto-draft',
				'post_name'    => $uuid,
				'post_content' => wp_json_encode( $changeset_data ),
			)
		);

		$manager = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		$this->assertTrue( $manager->is_theme_active() );

		$this->assertArrayNotHasKey( 'background_color', $manager->unsanitized_post_values() );

		$this->assertSame(
			array(
				'blogname'        => 'Changeset Title',
				'blogdescription' => 'Post Input Tagline',
			),
			$manager->unsanitized_post_values()
		);
		$this->assertSame(
			array(
				'blogdescription' => 'Post Input Tagline',
			),
			$manager->unsanitized_post_values( array( 'exclude_changeset' => true ) )
		);

		$manager->set_post_value( 'blogdescription', 'Post Override Tagline' );
		$this->assertSame(
			array(
				'blogname'        => 'Changeset Title',
				'blogdescription' => 'Post Override Tagline',
			),
			$manager->unsanitized_post_values()
		);

		$this->assertSame(
			array(
				'blogname'        => 'Changeset Title',
				'blogdescription' => 'Changeset Tagline',
			),
			$manager->unsanitized_post_values( array( 'exclude_post_data' => true ) )
		);

		$this->assertEmpty(
			$manager->unsanitized_post_values(
				array(
					'exclude_post_data' => true,
					'exclude_changeset' => true,
				)
			)
		);

		// Test unstashing theme mods.
		$manager = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
				'theme'          => $preview_theme,
			)
		);
		$this->assertFalse( $manager->is_theme_active() );
		$values = $manager->unsanitized_post_values(
			array(
				'exclude_post_data' => true,
				'exclude_changeset' => true,
			)
		);
		$this->assertNotEmpty( $values );
		$this->assertArrayHasKey( 'background_color', $values );
		$this->assertSame( '#000000', $values['background_color'] );

		$values = $manager->unsanitized_post_values(
			array(
				'exclude_post_data' => false,
				'exclude_changeset' => false,
			)
		);
		$this->assertArrayHasKey( 'background_color', $values );
		$this->assertArrayHasKey( 'blogname', $values );
		$this->assertArrayHasKey( 'blogdescription', $values );
	}

	/**
	 * Test the WP_Customize_Manager::post_value() method.
	 *
	 * @ticket 30988
	 */
	public function test_post_value() {
		wp_set_current_user( self::$admin_user_id );
		$posted_settings     = array(
			'foo' => 'OOF',
		);
		$_POST['customized'] = wp_slash( wp_json_encode( $posted_settings ) );

		$manager = $this->manager;

		$manager->add_setting( 'foo', array( 'default' => 'foo_default' ) );
		$foo_setting = $manager->get_setting( 'foo' );
		$this->assertSame( 'foo_default', $manager->get_setting( 'foo' )->value(), 'Expected non-previewed setting to return default when value() method called.' );
		$this->assertSame( $posted_settings['foo'], $manager->post_value( $foo_setting, 'post_value_foo_default' ), 'Expected post_value($foo_setting) to return value supplied in $_POST[customized][foo]' );

		$manager->add_setting( 'bar', array( 'default' => 'bar_default' ) );
		$bar_setting = $manager->get_setting( 'bar' );
		$this->assertSame( 'post_value_bar_default', $manager->post_value( $bar_setting, 'post_value_bar_default' ), 'Expected post_value($bar_setting, $default) to return $default since no value supplied in $_POST[customized][bar]' );
	}

	/**
	 * Test the WP_Customize_Manager::post_value() method for a setting value that fails validation.
	 *
	 * @ticket 34893
	 */
	public function test_invalid_post_value() {
		wp_set_current_user( self::$admin_user_id );
		$default_value = 'foo_default';
		$setting       = $this->manager->add_setting(
			'foo',
			array(
				'validate_callback' => array( $this, 'filter_customize_validate_foo' ),
				'sanitize_callback' => array( $this, 'filter_customize_sanitize_foo' ),
			)
		);
		$this->assertSame( $default_value, $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( $default_value, $setting->post_value( $default_value ) );

		$post_value = 'bar';
		$this->manager->set_post_value( 'foo', $post_value );
		$this->assertSame( strtoupper( $post_value ), $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( strtoupper( $post_value ), $setting->post_value( $default_value ) );

		$this->manager->set_post_value( 'foo', 'return_wp_error_in_sanitize' );
		$this->assertSame( $default_value, $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( $default_value, $setting->post_value( $default_value ) );

		$this->manager->set_post_value( 'foo', 'return_null_in_sanitize' );
		$this->assertSame( $default_value, $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( $default_value, $setting->post_value( $default_value ) );

		$post_value = '<script>evil</script>';
		$this->manager->set_post_value( 'foo', $post_value );
		$this->assertSame( $default_value, $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( $default_value, $setting->post_value( $default_value ) );
	}

	/**
	 * Filter customize_validate callback.
	 *
	 * @param mixed $value Value.
	 * @return string|WP_Error
	 */
	public function filter_customize_sanitize_foo( $value ) {
		if ( 'return_null_in_sanitize' === $value ) {
			$value = null;
		} elseif ( is_string( $value ) ) {
			$value = strtoupper( $value );
			if ( false !== stripos( $value, 'return_wp_error_in_sanitize' ) ) {
				$value = new WP_Error( 'invalid_value_in_sanitize', __( 'Invalid value.' ), array( 'source' => 'filter_customize_sanitize_foo' ) );
			}
		}
		return $value;
	}

	/**
	 * Filter customize_validate callback.
	 *
	 * @param WP_Error $validity Validity.
	 * @param mixed    $value    Value.
	 * @return WP_Error
	 */
	public function filter_customize_validate_foo( $validity, $value ) {
		if ( false !== stripos( $value, '<script' ) ) {
			$validity->add( 'invalid_value_in_validate', __( 'Invalid value.' ), array( 'source' => 'filter_customize_validate_foo' ) );
		}
		return $validity;
	}

	/**
	 * Test the WP_Customize_Manager::post_value() method to make sure that the validation and sanitization are done in the right order.
	 *
	 * @ticket 37247
	 */
	public function test_post_value_validation_sanitization_order() {
		wp_set_current_user( self::$admin_user_id );
		$default_value = '0';
		$setting       = $this->manager->add_setting(
			'numeric',
			array(
				'validate_callback' => array( $this, 'filter_customize_validate_numeric' ),
				'sanitize_callback' => array( $this, 'filter_customize_sanitize_numeric' ),
			)
		);
		$this->assertSame( $default_value, $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( $default_value, $setting->post_value( $default_value ) );

		$post_value = 42;
		$this->manager->set_post_value( 'numeric', (string) $post_value );
		$this->assertSame( $post_value, $this->manager->post_value( $setting, $default_value ) );
		$this->assertSame( $post_value, $setting->post_value( $default_value ) );
	}

	/**
	 * Filter customize_validate callback for a numeric value.
	 *
	 * @param mixed $value Value.
	 * @return string|WP_Error
	 */
	public function filter_customize_sanitize_numeric( $value ) {
		return absint( $value );
	}

	/**
	 * Filter customize_validate callback for a numeric value.
	 *
	 * @param WP_Error $validity Validity.
	 * @param mixed    $value    Value.
	 * @return WP_Error
	 */
	public function filter_customize_validate_numeric( $validity, $value ) {
		if ( ! is_string( $value ) || ! is_numeric( $value ) ) {
			$validity->add( 'invalid_value_in_validate', __( 'Invalid value.' ), array( 'source' => 'filter_customize_validate_numeric' ) );
		}
		return $validity;
	}

	/**
	 * Test WP_Customize_Manager::validate_setting_values().
	 *
	 * @see WP_Customize_Manager::validate_setting_values()
	 */
	public function test_validate_setting_values() {
		wp_set_current_user( self::$admin_user_id );
		$setting = $this->manager->add_setting(
			'foo',
			array(
				'validate_callback' => array( $this, 'filter_customize_validate_foo' ),
				'sanitize_callback' => array( $this, 'filter_customize_sanitize_foo' ),
			)
		);

		$post_value = 'bar';
		$this->manager->set_post_value( 'foo', $post_value );
		$validities = $this->manager->validate_setting_values( $this->manager->unsanitized_post_values() );
		$this->assertCount( 1, $validities );
		$this->assertSame( array( 'foo' => true ), $validities );

		$this->manager->set_post_value( 'foo', 'return_wp_error_in_sanitize' );
		$invalid_settings = $this->manager->validate_setting_values( $this->manager->unsanitized_post_values() );
		$this->assertCount( 1, $invalid_settings );
		$this->assertArrayHasKey( $setting->id, $invalid_settings );
		$this->assertInstanceOf( 'WP_Error', $invalid_settings[ $setting->id ] );
		$error = $invalid_settings[ $setting->id ];
		$this->assertSame( 'invalid_value_in_sanitize', $error->get_error_code() );
		$this->assertSame( array( 'source' => 'filter_customize_sanitize_foo' ), $error->get_error_data() );

		$this->manager->set_post_value( 'foo', 'return_null_in_sanitize' );
		$invalid_settings = $this->manager->validate_setting_values( $this->manager->unsanitized_post_values() );
		$this->assertCount( 1, $invalid_settings );
		$this->assertArrayHasKey( $setting->id, $invalid_settings );
		$this->assertInstanceOf( 'WP_Error', $invalid_settings[ $setting->id ] );
		$this->assertNull( $invalid_settings[ $setting->id ]->get_error_data() );

		$post_value = '<script>evil</script>';
		$this->manager->set_post_value( 'foo', $post_value );
		$invalid_settings = $this->manager->validate_setting_values( $this->manager->unsanitized_post_values() );
		$this->assertCount( 1, $invalid_settings );
		$this->assertArrayHasKey( $setting->id, $invalid_settings );
		$this->assertInstanceOf( 'WP_Error', $invalid_settings[ $setting->id ] );
		$error = $invalid_settings[ $setting->id ];
		$this->assertSame( 'invalid_value_in_validate', $error->get_error_code() );
		$this->assertSame( array( 'source' => 'filter_customize_validate_foo' ), $error->get_error_data() );
	}

	/**
	 * Test WP_Customize_Manager::validate_setting_values().
	 *
	 * @ticket 37638
	 * @covers WP_Customize_Manager::validate_setting_values
	 */
	public function test_late_validate_setting_values() {
		$setting = new Test_Setting_Without_Applying_Validate_Filter( $this->manager, 'required' );
		$this->manager->add_setting( $setting );

		$this->assertInstanceOf( 'WP_Error', $setting->validate( '' ) );
		$setting_validities = $this->manager->validate_setting_values( array( $setting->id => '' ) );
		$this->assertInstanceOf( 'WP_Error', $setting_validities[ $setting->id ] );

		$this->assertTrue( $setting->validate( 'ok' ) );
		$setting_validities = $this->manager->validate_setting_values( array( $setting->id => 'ok' ) );
		$this->assertTrue( $setting_validities[ $setting->id ] );

		add_filter( "customize_validate_{$setting->id}", array( $this, 'late_validate_length' ), 10, 3 );
		$this->assertTrue( $setting->validate( 'bad' ) );
		$setting_validities = $this->manager->validate_setting_values( array( $setting->id => 'bad' ) );
		$validity           = $setting_validities[ $setting->id ];
		$this->assertInstanceOf( 'WP_Error', $validity );
		$this->assertSame( 'minlength', $validity->get_error_code() );
	}

	/**
	 * Test WP_Customize_Manager::validate_setting_values().
	 *
	 * @ticket 30937
	 * @covers WP_Customize_Manager::validate_setting_values
	 */
	public function test_validate_setting_values_args() {
		wp_set_current_user( self::$admin_user_id );
		$this->manager->register_controls();

		$validities = $this->manager->validate_setting_values( array( 'unknown' => 'X' ) );
		$this->assertEmpty( $validities );

		$validities = $this->manager->validate_setting_values( array( 'unknown' => 'X' ), array( 'validate_existence' => false ) );
		$this->assertEmpty( $validities );

		$validities = $this->manager->validate_setting_values( array( 'unknown' => 'X' ), array( 'validate_existence' => true ) );
		$this->assertNotEmpty( $validities );
		$this->assertArrayHasKey( 'unknown', $validities );
		$error = $validities['unknown'];
		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertSame( 'unrecognized', $error->get_error_code() );

		$this->manager->get_setting( 'blogname' )->capability = 'do_not_allow';
		$validities = $this->manager->validate_setting_values( array( 'blogname' => 'X' ), array( 'validate_capability' => false ) );
		$this->assertArrayHasKey( 'blogname', $validities );
		$this->assertTrue( $validities['blogname'] );
		$validities = $this->manager->validate_setting_values( array( 'blogname' => 'X' ), array( 'validate_capability' => true ) );
		$this->assertArrayHasKey( 'blogname', $validities );
		$error = $validities['blogname'];
		$this->assertInstanceOf( 'WP_Error', $error );
		$this->assertSame( 'unauthorized', $error->get_error_code() );
	}

	/**
	 * Add a length constraint to a setting.
	 *
	 * Adds minimum-length error code if the length is less than 10.
	 *
	 * @param WP_Error             $validity Validity.
	 * @param mixed                $value    Value.
	 * @param WP_Customize_Setting $setting  Setting.
	 * @return WP_Error Validity.
	 */
	public function late_validate_length( $validity, $value, $setting ) {
		$this->assertInstanceOf( 'WP_Customize_Setting', $setting );
		if ( strlen( $value ) < 10 ) {
			$validity->add( 'minlength', '' );
		}
		return $validity;
	}

	/**
	 * Test the WP_Customize_Manager::validate_setting_values() method to make sure that the validation and sanitization are done in the right order.
	 *
	 * @ticket 37247
	 */
	public function test_validate_setting_values_validation_sanitization_order() {
		wp_set_current_user( self::$admin_user_id );
		$setting    = $this->manager->add_setting(
			'numeric',
			array(
				'validate_callback' => array( $this, 'filter_customize_validate_numeric' ),
				'sanitize_callback' => array( $this, 'filter_customize_sanitize_numeric' ),
			)
		);
		$post_value = '42';
		$this->manager->set_post_value( 'numeric', $post_value );
		$validities = $this->manager->validate_setting_values( $this->manager->unsanitized_post_values() );
		$this->assertCount( 1, $validities );
		$this->assertSame( array( 'numeric' => true ), $validities );
	}

	/**
	 * Test WP_Customize_Manager::prepare_setting_validity_for_js().
	 *
	 * @see WP_Customize_Manager::prepare_setting_validity_for_js()
	 */
	public function test_prepare_setting_validity_for_js() {
		$this->assertTrue( $this->manager->prepare_setting_validity_for_js( true ) );
		$error = new WP_Error();
		$error->add( 'bad_letter', 'Bad letter', 'A' );
		$error->add( 'bad_letter', 'Bad letra', 123 );
		$error->add( 'bad_number', 'Bad number', array( 'number' => 123 ) );
		$validity = $this->manager->prepare_setting_validity_for_js( $error );
		$this->assertIsArray( $validity );
		foreach ( $error->errors as $code => $messages ) {
			$this->assertArrayHasKey( $code, $validity );
			$this->assertIsArray( $validity[ $code ] );
			$this->assertSame( implode( ' ', $messages ), $validity[ $code ]['message'] );
			$this->assertArrayHasKey( 'data', $validity[ $code ] );
			$this->assertSame( $validity[ $code ]['data'], $error->get_error_data( $code ) );
		}
		$this->assertArrayHasKey( 'number', $validity['bad_number']['data'] );
		$this->assertSame( 123, $validity['bad_number']['data']['number'] );
	}

	/**
	 * Test WP_Customize_Manager::set_post_value().
	 *
	 * @see WP_Customize_Manager::set_post_value()
	 */
	public function test_set_post_value() {
		wp_set_current_user( self::$admin_user_id );
		$this->manager->add_setting(
			'foo',
			array(
				'sanitize_callback' => array( $this, 'sanitize_foo_for_test_set_post_value' ),
			)
		);
		$setting = $this->manager->get_setting( 'foo' );

		$this->assertEmpty( $this->captured_customize_post_value_set_actions );
		add_action( 'customize_post_value_set', array( $this, 'capture_customize_post_value_set_actions' ), 10, 3 );
		add_action( 'customize_post_value_set_foo', array( $this, 'capture_customize_post_value_set_actions' ), 10, 2 );
		$this->manager->set_post_value( $setting->id, '123abc' );
		$this->assertCount( 2, $this->captured_customize_post_value_set_actions );
		$this->assertSame( 'customize_post_value_set_foo', $this->captured_customize_post_value_set_actions[0]['action'] );
		$this->assertSame( 'customize_post_value_set', $this->captured_customize_post_value_set_actions[1]['action'] );
		$this->assertSame( array( '123abc', $this->manager ), $this->captured_customize_post_value_set_actions[0]['args'] );
		$this->assertSame( array( $setting->id, '123abc', $this->manager ), $this->captured_customize_post_value_set_actions[1]['args'] );

		$unsanitized = $this->manager->unsanitized_post_values();
		$this->assertArrayHasKey( $setting->id, $unsanitized );

		$this->assertSame( '123abc', $unsanitized[ $setting->id ] );
		$this->assertSame( 123, $setting->post_value() );
	}

	/**
	 * Sanitize a value for Tests_WP_Customize_Manager::test_set_post_value().
	 *
	 * @see Tests_WP_Customize_Manager::test_set_post_value()
	 *
	 * @param mixed $value Value.
	 * @return int Value.
	 */
	public function sanitize_foo_for_test_set_post_value( $value ) {
		return (int) $value;
	}

	/**
	 * Store data coming from customize_post_value_set action calls.
	 *
	 * @see Tests_WP_Customize_Manager::capture_customize_post_value_set_actions()
	 * @var array
	 */
	protected $captured_customize_post_value_set_actions = array();

	/**
	 * Capture the actions fired when calling WP_Customize_Manager::set_post_value().
	 *
	 * @see Tests_WP_Customize_Manager::test_set_post_value()
	 *
	 * @param mixed ...$args Optional arguments passed to the action.
	 */
	public function capture_customize_post_value_set_actions( ...$args ) {
		$action = current_action();
		$this->captured_customize_post_value_set_actions[] = compact( 'action', 'args' );
	}

	/**
	 * Test the WP_Customize_Manager::add_dynamic_settings() method.
	 *
	 * @ticket 30936
	 */
	public function test_add_dynamic_settings() {
		$manager     = $this->manager;
		$setting_ids = array( 'foo', 'bar' );
		$manager->add_setting( 'foo', array( 'default' => 'foo_default' ) );
		$this->assertEmpty( $manager->get_setting( 'bar' ), 'Expected there to not be a bar setting up front.' );
		$manager->add_dynamic_settings( $setting_ids );
		$this->assertEmpty( $manager->get_setting( 'bar' ), 'Expected the bar setting to remain absent since filters not added.' );

		$this->action_customize_register_for_dynamic_settings();
		$manager->add_dynamic_settings( $setting_ids );
		$this->assertNotEmpty( $manager->get_setting( 'bar' ), 'Expected bar setting to be created since filters were added.' );
		$this->assertSame( 'foo_default', $manager->get_setting( 'foo' )->default, 'Expected static foo setting to not get overridden by dynamic setting.' );
		$this->assertSame( 'dynamic_bar_default', $manager->get_setting( 'bar' )->default, 'Expected dynamic setting bar to have default providd by filter.' );
	}

	/**
	 * Test WP_Customize_Manager::has_published_pages().
	 *
	 * @ticket 38013
	 * @covers WP_Customize_Manager::has_published_pages
	 */
	public function test_has_published_pages() {
		foreach ( get_pages() as $page ) {
			wp_delete_post( $page->ID, true );
		}
		$this->assertFalse( $this->manager->has_published_pages() );

		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'private',
			)
		);
		$this->assertFalse( $this->manager->has_published_pages() );

		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$this->assertTrue( $this->manager->has_published_pages() );
	}

	/**
	 * Ensure that page stubs created via nav menus will cause has_published_pages to return true.
	 *
	 * @ticket 38013
	 * @covers WP_Customize_Manager::has_published_pages
	 */
	public function test_has_published_pages_when_nav_menus_created_posts() {
		foreach ( get_pages() as $page ) {
			wp_delete_post( $page->ID, true );
		}
		$this->assertFalse( $this->manager->has_published_pages() );

		wp_set_current_user( self::$admin_user_id );
		$this->manager->nav_menus->customize_register();
		$setting_id = 'nav_menus_created_posts';
		$setting    = $this->manager->get_setting( $setting_id );
		$this->assertInstanceOf( 'WP_Customize_Filter_Setting', $setting );
		$auto_draft_page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'auto-draft',
			)
		);
		$this->manager->set_post_value( $setting_id, array( $auto_draft_page ) );
		$setting->preview();
		$this->assertTrue( $this->manager->has_published_pages() );
	}

	/**
	 * Test the WP_Customize_Manager::register_dynamic_settings() method.
	 *
	 * This is similar to test_add_dynamic_settings, except the settings are passed via $_POST['customized'].
	 *
	 * @ticket 30936
	 */
	public function test_register_dynamic_settings() {
		wp_set_current_user( self::$admin_user_id );
		$posted_settings     = array(
			'foo' => 'OOF',
			'bar' => 'RAB',
		);
		$_POST['customized'] = wp_slash( wp_json_encode( $posted_settings ) );

		add_action( 'customize_register', array( $this, 'action_customize_register_for_dynamic_settings' ) );

		$manager = $this->manager;
		$manager->add_setting( 'foo', array( 'default' => 'foo_default' ) );

		$this->assertEmpty( $manager->get_setting( 'bar' ), 'Expected dynamic setting "bar" to not be registered.' );
		do_action( 'customize_register', $manager );
		$this->assertNotEmpty( $manager->get_setting( 'bar' ), 'Expected dynamic setting "bar" to be automatically registered after customize_register action.' );
		$this->assertEmpty( $manager->get_setting( 'baz' ), 'Expected unrecognized dynamic setting "baz" to remain unregistered.' );
	}

	/**
	 * In lieu of closures, callback for customize_register action added in test_register_dynamic_settings().
	 */
	public function action_customize_register_for_dynamic_settings() {
		add_filter( 'customize_dynamic_setting_args', array( $this, 'filter_customize_dynamic_setting_args_for_test_dynamic_settings' ), 10, 2 );
		add_filter( 'customize_dynamic_setting_class', array( $this, 'filter_customize_dynamic_setting_class_for_test_dynamic_settings' ), 10, 3 );
	}

	/**
	 * In lieu of closures, callback for customize_dynamic_setting_args filter added for test_register_dynamic_settings().
	 *
	 * @param array  $setting_args Setting args.
	 * @param string $setting_id   Setting ID.
	 * @return array
	 */
	public function filter_customize_dynamic_setting_args_for_test_dynamic_settings( $setting_args, $setting_id ) {
		$this->assertIsString( $setting_id );
		if ( in_array( $setting_id, array( 'foo', 'bar' ), true ) ) {
			$setting_args = array( 'default' => "dynamic_{$setting_id}_default" );
		}
		return $setting_args;
	}

	/**
	 * In lieu of closures, callback for customize_dynamic_setting_class filter added for test_register_dynamic_settings().
	 *
	 * @param string $setting_class Setting class.
	 * @param string $setting_id    Setting ID.
	 * @param array  $setting_args  Setting args.
	 * @return string
	 */
	public function filter_customize_dynamic_setting_class_for_test_dynamic_settings( $setting_class, $setting_id, $setting_args ) {
		$this->assertSame( 'WP_Customize_Setting', $setting_class );
		$this->assertIsString( $setting_id );
		$this->assertIsArray( $setting_args );
		return $setting_class;
	}

	/**
	 * Test get_document_title_template() method.
	 *
	 * @see WP_Customize_Manager::get_document_title_template()
	 */
	public function test_get_document_title_template() {
		$tpl = $this->manager->get_document_title_template();
		$this->assertStringContainsString( '%s', $tpl );
	}

	/**
	 * Test get_preview_url()/set_preview_url methods.
	 *
	 * @see WP_Customize_Manager::get_preview_url()
	 * @see WP_Customize_Manager::set_preview_url()
	 */
	public function test_preview_url() {
		$this->assertSame( home_url( '/' ), $this->manager->get_preview_url() );
		$preview_url = home_url( '/foo/bar/baz/' );
		$this->manager->set_preview_url( $preview_url );
		$this->assertSame( $preview_url, $this->manager->get_preview_url() );
		$this->manager->set_preview_url( 'http://illegalsite.example.com/food/' );
		$this->assertSame( home_url( '/' ), $this->manager->get_preview_url() );
	}

	/**
	 * Test get_return_url()/set_return_url() methods.
	 *
	 * @see WP_Customize_Manager::get_return_url()
	 * @see WP_Customize_Manager::set_return_url()
	 */
	public function test_return_url() {
		wp_set_current_user( self::$subscriber_user_id );
		$this->assertSame( home_url( '/' ), $this->manager->get_return_url() );

		wp_set_current_user( self::$admin_user_id );
		$this->assertTrue( current_user_can( 'edit_theme_options' ) );
		$this->assertSame( home_url( '/' ), $this->manager->get_return_url() );

		$preview_url = home_url( '/foo/' );
		$this->manager->set_preview_url( $preview_url );
		$this->assertSame( $preview_url, $this->manager->get_return_url() );

		$_SERVER['HTTP_REFERER'] = wp_slash( admin_url( 'customize.php' ) );
		$this->assertSame( $preview_url, $this->manager->get_return_url() );

		// See #35355.
		$_SERVER['HTTP_REFERER'] = wp_slash( admin_url( 'wp-login.php' ) );
		$this->assertSame( $preview_url, $this->manager->get_return_url() );

		$url                     = home_url( '/referred/' );
		$_SERVER['HTTP_REFERER'] = wp_slash( $url );
		$this->assertSame( $url, $this->manager->get_return_url() );

		$url                     = 'http://badreferer.example.com/';
		$_SERVER['HTTP_REFERER'] = wp_slash( $url );
		$this->assertNotEquals( $url, $this->manager->get_return_url() );
		$this->assertSame( $preview_url, $this->manager->get_return_url() );

		$this->manager->set_return_url( admin_url( 'edit.php?trashed=1' ) );
		$this->assertSame( admin_url( 'edit.php' ), $this->manager->get_return_url() );
	}

	/**
	 * @ticket 46686
	 */
	public function test_return_url_with_deactivated_theme() {
		$this->manager->set_return_url( admin_url( 'themes.php?page=mytheme_documentation' ) );
		$this->assertSame( admin_url( 'themes.php' ), $this->manager->get_return_url() );
	}

	/**
	 * Test get_autofocus()/set_autofocus() methods.
	 *
	 * @see WP_Customize_Manager::get_autofocus()
	 * @see WP_Customize_Manager::set_autofocus()
	 */
	public function test_autofocus() {
		$this->assertEmpty( $this->manager->get_autofocus() );

		$this->manager->set_autofocus( array( 'unrecognized' => 'food' ) );
		$this->assertEmpty( $this->manager->get_autofocus() );

		$autofocus = array( 'control' => 'blogname' );
		$this->manager->set_autofocus( $autofocus );
		$this->assertSame( $autofocus, $this->manager->get_autofocus() );

		$autofocus = array( 'section' => 'colors' );
		$this->manager->set_autofocus( $autofocus );
		$this->assertSame( $autofocus, $this->manager->get_autofocus() );

		$autofocus = array( 'panel' => 'widgets' );
		$this->manager->set_autofocus( $autofocus );
		$this->assertSame( $autofocus, $this->manager->get_autofocus() );

		$autofocus = array( 'control' => array( 'blogname', 'blogdescription' ) );
		$this->manager->set_autofocus( $autofocus );
		$this->assertEmpty( $this->manager->get_autofocus() );
	}

	/**
	 * Test get_nonces() method.
	 *
	 * @see WP_Customize_Manager::get_nonces()
	 */
	public function test_nonces() {
		$nonces = $this->manager->get_nonces();
		$this->assertIsArray( $nonces );
		$this->assertArrayHasKey( 'save', $nonces );
		$this->assertArrayHasKey( 'preview', $nonces );

		add_filter( 'customize_refresh_nonces', array( $this, 'filter_customize_refresh_nonces' ), 10, 2 );
		$nonces = $this->manager->get_nonces();
		$this->assertArrayHasKey( 'foo', $nonces );
		$this->assertSame( wp_create_nonce( 'foo' ), $nonces['foo'] );
	}

	/**
	 * Filter for customize_refresh_nonces.
	 *
	 * @param array                $nonces  Nonces.
	 * @param WP_Customize_Manager $manager Manager.
	 * @return array Nonces.
	 */
	public function filter_customize_refresh_nonces( $nonces, $manager ) {
		$this->assertInstanceOf( 'WP_Customize_Manager', $manager );
		$nonces['foo'] = wp_create_nonce( 'foo' );
		return $nonces;
	}

	/**
	 * Test customize_pane_settings() method.
	 *
	 * @see WP_Customize_Manager::customize_pane_settings()
	 */
	public function test_customize_pane_settings() {
		wp_set_current_user( self::$admin_user_id );
		$this->manager->register_controls();
		$this->manager->prepare_controls();
		$autofocus = array( 'control' => 'blogname' );
		$this->manager->set_autofocus( $autofocus );

		ob_start();
		$this->manager->customize_pane_settings();
		$content = ob_get_clean();

		$this->assertStringContainsString( 'var _wpCustomizeSettings =', $content );
		$this->assertStringContainsString( '"blogname"', $content );
		$this->assertStringContainsString( '"type":"option"', $content );
		$this->assertStringContainsString( '_wpCustomizeSettings.controls', $content );
		$this->assertStringContainsString( '_wpCustomizeSettings.settings', $content );
		$this->assertStringContainsString( '</script>', $content );

		$this->assertNotEmpty( preg_match( '#var _wpCustomizeSettings\s*=\s*({.*?});\s*\n#', $content, $matches ) );
		$json = $matches[1];
		$data = json_decode( $json, true );
		$this->assertNotEmpty( $data );

		$this->assertSameSets( array( 'theme', 'url', 'browser', 'panels', 'sections', 'nonce', 'autofocus', 'documentTitleTmpl', 'previewableDevices', 'changeset', 'timeouts', 'dateFormat', 'timeFormat', 'initialClientTimestamp', 'initialServerDate', 'initialServerTimestamp', 'l10n' ), array_keys( $data ) );
		$this->assertSame( $autofocus, $data['autofocus'] );
		$this->assertArrayHasKey( 'save', $data['nonce'] );
		$this->assertArrayHasKey( 'preview', $data['nonce'] );

		$this->assertSameSets(
			array(
				'branching',
				'autosaved',
				'hasAutosaveRevision',
				'latestAutoDraftUuid',
				'status',
				'uuid',
				'currentUserCanPublish',
				'publishDate',
				'statusChoices',
				'lockUser',
			),
			array_keys( $data['changeset'] )
		);
	}

	/**
	 * Test remove_frameless_preview_messenger_channel.
	 *
	 * @ticket 38867
	 * @covers WP_Customize_Manager::remove_frameless_preview_messenger_channel
	 */
	public function test_remove_frameless_preview_messenger_channel() {
		wp_set_current_user( self::$admin_user_id );
		$manager = new WP_Customize_Manager( array( 'messenger_channel' => null ) );
		ob_start();
		$manager->remove_frameless_preview_messenger_channel();
		$output = ob_get_clean();
		$this->assertEmpty( $output );

		$manager = new WP_Customize_Manager( array( 'messenger_channel' => 'preview-0' ) );
		ob_start();
		$manager->remove_frameless_preview_messenger_channel();
		$processor = new WP_HTML_Tag_Processor( ob_get_clean() );
		$this->assertTrue( $processor->next_tag( 'script' ), 'Failed to find expected SCRIPT element in output.' );
	}

	/**
	 * Test customize_preview_settings() method.
	 *
	 * @see WP_Customize_Manager::customize_preview_settings()
	 */
	public function test_customize_preview_settings() {
		wp_set_current_user( self::$admin_user_id );
		$this->manager->register_controls();
		$this->manager->prepare_controls();
		$this->manager->set_post_value( 'foo', 'bar' );
		$_POST['customize_messenger_channel'] = 'preview-0';

		ob_start();
		$this->manager->customize_preview_settings();
		$content = ob_get_clean();

		$this->assertSame( 1, preg_match( '/var _wpCustomizeSettings = ({.+});/', $content, $matches ) );
		$settings = json_decode( $matches[1], true );

		$this->assertArrayHasKey( 'theme', $settings );
		$this->assertArrayHasKey( 'url', $settings );
		$this->assertArrayHasKey( 'channel', $settings );
		$this->assertArrayHasKey( 'activePanels', $settings );
		$this->assertArrayHasKey( 'activeSections', $settings );
		$this->assertArrayHasKey( 'activeControls', $settings );
		$this->assertArrayHasKey( 'settingValidities', $settings );
		$this->assertArrayHasKey( 'nonce', $settings );
		$this->assertArrayHasKey( '_dirty', $settings );
		$this->assertArrayHasKey( 'timeouts', $settings );
		$this->assertArrayHasKey( 'changeset', $settings );

		$this->assertArrayHasKey( 'preview', $settings['nonce'] );
	}

	/**
	 * @ticket 33552
	 */
	public function test_customize_loaded_components_filter() {
		$manager = new WP_Customize_Manager();
		$this->assertInstanceOf( 'WP_Customize_Widgets', $manager->widgets );
		$this->assertInstanceOf( 'WP_Customize_Nav_Menus', $manager->nav_menus );

		add_filter( 'customize_loaded_components', array( $this, 'return_array_containing_widgets' ), 10, 2 );
		$manager = new WP_Customize_Manager();
		$this->assertInstanceOf( 'WP_Customize_Widgets', $manager->widgets );
		$this->assertEmpty( $manager->nav_menus );
		remove_all_filters( 'customize_loaded_components' );

		add_filter( 'customize_loaded_components', array( $this, 'return_array_containing_nav_menus' ), 10, 2 );
		$manager = new WP_Customize_Manager();
		$this->assertInstanceOf( 'WP_Customize_Nav_Menus', $manager->nav_menus );
		$this->assertEmpty( $manager->widgets );
		remove_all_filters( 'customize_loaded_components' );

		add_filter( 'customize_loaded_components', '__return_empty_array' );
		$manager = new WP_Customize_Manager();
		$this->assertEmpty( $manager->widgets );
		$this->assertEmpty( $manager->nav_menus );
		remove_all_filters( 'customize_loaded_components' );
	}

	/**
	 * @see Tests_WP_Customize_Manager::test_customize_loaded_components_filter()
	 *
	 * @param array                $components         Components.
	 * @param WP_Customize_Manager $customize_manager  Manager.
	 *
	 * @return array Components.
	 */
	public function return_array_containing_widgets( $components, $customize_manager ) {
		$this->assertIsArray( $components );
		$this->assertContains( 'widgets', $components );
		$this->assertContains( 'nav_menus', $components );
		$this->assertIsArray( $components );
		$this->assertInstanceOf( 'WP_Customize_Manager', $customize_manager );
		return array( 'widgets' );
	}

	/**
	 * @see Tests_WP_Customize_Manager::test_customize_loaded_components_filter()
	 *
	 * @param array                $components         Components.
	 * @param WP_Customize_Manager $customize_manager  Manager.
	 *
	 * @return array Components.
	 */
	public function return_array_containing_nav_menus( $components, $customize_manager ) {
		$this->assertIsArray( $components );
		$this->assertContains( 'widgets', $components );
		$this->assertContains( 'nav_menus', $components );
		$this->assertIsArray( $components );
		$this->assertInstanceOf( 'WP_Customize_Manager', $customize_manager );
		return array( 'nav_menus' );
	}

	/**
	 * @ticket 30225
	 * @ticket 34594
	 */
	public function test_prepare_controls_stable_sorting() {
		$manager = new WP_Customize_Manager();
		$manager->register_controls();
		$section_id = 'foo-section';
		wp_set_current_user( self::$admin_user_id );
		$manager->add_section(
			$section_id,
			array(
				'title'    => 'Section',
				'priority' => 1,
			)
		);

		$added_control_ids = array();
		$count             = 9;
		for ( $i = 0; $i < $count; $i += 1 ) {
			$id                  = 'sort-test-' . $i;
			$added_control_ids[] = $id;
			$manager->add_setting( $id );
			$control = new WP_Customize_Control(
				$manager,
				$id,
				array(
					'section'  => $section_id,
					'priority' => 1,
					'setting'  => $id,
				)
			);
			$manager->add_control( $control );
		}

		$manager->prepare_controls();

		$sorted_control_ids = wp_list_pluck( $manager->get_section( $section_id )->controls, 'id' );
		$this->assertSame( $added_control_ids, $sorted_control_ids );
	}

	/**
	 * @ticket 34596
	 */
	public function test_add_section_return_instance() {
		$manager = new WP_Customize_Manager();
		wp_set_current_user( self::$admin_user_id );

		$section_id     = 'foo-section';
		$result_section = $manager->add_section(
			$section_id,
			array(
				'title'    => 'Section',
				'priority' => 1,
			)
		);

		$this->assertInstanceOf( 'WP_Customize_Section', $result_section );
		$this->assertSame( $section_id, $result_section->id );

		$section        = new WP_Customize_Section(
			$manager,
			$section_id,
			array(
				'title'    => 'Section 2',
				'priority' => 2,
			)
		);
		$result_section = $manager->add_section( $section );

		$this->assertInstanceOf( 'WP_Customize_Section', $result_section );
		$this->assertSame( $section_id, $result_section->id );
		$this->assertSame( $section, $result_section );
	}

	/**
	 * @ticket 34596
	 */
	public function test_add_setting_return_instance() {
		$manager = new WP_Customize_Manager();
		wp_set_current_user( self::$admin_user_id );

		$setting_id     = 'foo-setting';
		$result_setting = $manager->add_setting( $setting_id );

		$this->assertInstanceOf( 'WP_Customize_Setting', $result_setting );
		$this->assertSame( $setting_id, $result_setting->id );

		$setting        = new WP_Customize_Setting( $manager, $setting_id );
		$result_setting = $manager->add_setting( $setting );

		$this->assertInstanceOf( 'WP_Customize_Setting', $result_setting );
		$this->assertSame( $setting, $result_setting );
		$this->assertSame( $setting_id, $result_setting->id );
	}

	/**
	 * @ticket 34597
	 */
	public function test_add_setting_honoring_dynamic() {
		$manager = new WP_Customize_Manager();

		$setting_id = 'dynamic';
		$setting    = $manager->add_setting( $setting_id );
		$this->assertSame( 'WP_Customize_Setting', get_class( $setting ) );
		$this->assertObjectNotHasProperty( 'custom', $setting );
		$manager->remove_setting( $setting_id );

		add_filter( 'customize_dynamic_setting_class', array( $this, 'return_dynamic_customize_setting_class' ), 10, 3 );
		add_filter( 'customize_dynamic_setting_args', array( $this, 'return_dynamic_customize_setting_args' ), 10, 2 );
		$setting = $manager->add_setting( $setting_id );
		$this->assertSame( 'Test_Dynamic_Customize_Setting', get_class( $setting ) );
		$this->assertObjectHasProperty( 'custom', $setting );
		$this->assertSame( 'foo', $setting->custom );
	}

	/**
	 * Returns 'Test_Dynamic_Customize_Setting' in 'customize_dynamic_setting_class'.
	 *
	 * @param string $setting_class Setting class.
	 * @param array  $setting_args  Setting args.
	 * @param string $setting_id    Setting ID.
	 * @return string Setting class.
	 */
	public function return_dynamic_customize_setting_class( $setting_class, $setting_id, $setting_args ) {
		unset( $setting_args );
		if ( 0 === strpos( $setting_id, 'dynamic' ) ) {
			$setting_class = 'Test_Dynamic_Customize_Setting';
		}
		return $setting_class;
	}

	/**
	 * Returns 'foo' in 'customize_dynamic_setting_args'.
	 *
	 * @param array  $setting_args Setting args.
	 * @param string $setting_id   Setting ID.
	 * @return array Setting args.
	 */
	public function return_dynamic_customize_setting_args( $setting_args, $setting_id ) {
		if ( 0 === strpos( $setting_id, 'dynamic' ) ) {
			$setting_args['custom'] = 'foo';
		}
		return $setting_args;
	}

	/**
	 * @ticket 34596
	 */
	public function test_add_panel_return_instance() {
		$manager = new WP_Customize_Manager();
		wp_set_current_user( self::$admin_user_id );

		$panel_id     = 'foo-panel';
		$result_panel = $manager->add_panel(
			$panel_id,
			array(
				'title'    => 'Test Panel',
				'priority' => 2,
			)
		);

		$this->assertInstanceOf( 'WP_Customize_Panel', $result_panel );
		$this->assertSame( $panel_id, $result_panel->id );

		$panel        = new WP_Customize_Panel(
			$manager,
			$panel_id,
			array(
				'title' => 'Test Panel 2',
			)
		);
		$result_panel = $manager->add_panel( $panel );

		$this->assertInstanceOf( 'WP_Customize_Panel', $result_panel );
		$this->assertSame( $panel, $result_panel );
		$this->assertSame( $panel_id, $result_panel->id );
	}

	/**
	 * @ticket 34596
	 */
	public function test_add_control_return_instance() {
		$manager    = new WP_Customize_Manager();
		$section_id = 'foo-section';
		wp_set_current_user( self::$admin_user_id );
		$manager->add_section(
			$section_id,
			array(
				'title'    => 'Section',
				'priority' => 1,
			)
		);

		$control_id = 'foo-control';
		$manager->add_setting( $control_id );

		$result_control = $manager->add_control(
			$control_id,
			array(
				'section'  => $section_id,
				'priority' => 1,
				'setting'  => $control_id,
			)
		);
		$this->assertInstanceOf( 'WP_Customize_Control', $result_control );
		$this->assertSame( $control_id, $result_control->id );

		$control        = new WP_Customize_Control(
			$manager,
			$control_id,
			array(
				'section'  => $section_id,
				'priority' => 1,
				'setting'  => $control_id,
			)
		);
		$result_control = $manager->add_control( $control );

		$this->assertInstanceOf( 'WP_Customize_Control', $result_control );
		$this->assertSame( $control, $result_control );
		$this->assertSame( $control_id, $result_control->id );
	}


	/**
	 * Testing the return values both with and without filter.
	 *
	 * @ticket 31195
	 */
	public function test_get_previewable_devices() {

		// Setup the instance.
		$manager = new WP_Customize_Manager();

		// The default devices list.
		$default_devices = array(
			'desktop' => array(
				'label'   => __( 'Enter desktop preview mode' ),
				'default' => true,
			),
			'tablet'  => array(
				'label' => __( 'Enter tablet preview mode' ),
			),
			'mobile'  => array(
				'label' => __( 'Enter mobile preview mode' ),
			),
		);

		// Control test.
		$devices = $manager->get_previewable_devices();
		$this->assertSame( $default_devices, $devices );

		// Adding the filter.
		add_filter( 'customize_previewable_devices', array( $this, 'filter_customize_previewable_devices' ) );
		$devices = $manager->get_previewable_devices();
		$this->assertSame( $this->filtered_device_list(), $devices );

		// Clean up.
		remove_filter( 'customize_previewable_devices', array( $this, 'filter_customize_previewable_devices' ) );
	}

	/**
	 * Helper method for test_get_previewable_devices.
	 *
	 * @return array
	 */
	private function filtered_device_list() {
		return array(
			'custom-device' => array(
				'label'   => __( 'Enter custom-device preview mode' ),
				'default' => true,
			),
		);
	}

	/**
	 * Callback for the customize_previewable_devices filter.
	 *
	 * @param array $devices The list of devices.
	 *
	 * @return array
	 */
	public function filter_customize_previewable_devices( $devices ) {
		return $this->filtered_device_list();
	}

	/**
	 * @ticket 37128
	 */
	public function test_prepare_controls_wp_list_sort_controls() {
		wp_set_current_user( self::$admin_user_id );

		$controls        = array(
			'foo'    => 2,
			'bar'    => 4,
			'foobar' => 3,
			'key'    => 1,
		);
		$controls_sorted = array( 'key', 'foo', 'foobar', 'bar' );

		$this->manager->add_section( 'foosection', array() );

		foreach ( $controls as $control_id => $priority ) {
			$this->manager->add_setting( $control_id );
			$this->manager->add_control(
				$control_id,
				array(
					'priority' => $priority,
					'section'  => 'foosection',
				)
			);
		}

		$this->manager->prepare_controls();

		$result = $this->manager->controls();
		$this->assertSame( $controls_sorted, array_keys( $result ) );
	}

	/**
	 * @ticket 37128
	 */
	public function test_prepare_controls_wp_list_sort_sections() {
		wp_set_current_user( self::$admin_user_id );

		$sections        = array(
			'foo'    => 2,
			'bar'    => 4,
			'foobar' => 3,
			'key'    => 1,
		);
		$sections_sorted = array( 'key', 'foo', 'foobar', 'bar' );

		foreach ( $sections as $section_id => $priority ) {
			$this->manager->add_section(
				$section_id,
				array(
					'priority' => $priority,
				)
			);
		}

		$this->manager->prepare_controls();

		$result = $this->manager->sections();
		$this->assertSame( $sections_sorted, array_keys( $result ) );
	}

	/**
	 * @ticket 37128
	 */
	public function test_prepare_controls_wp_list_sort_panels() {
		wp_set_current_user( self::$admin_user_id );

		$panels        = array(
			'foo'    => 2,
			'bar'    => 4,
			'foobar' => 3,
			'key'    => 1,
		);
		$panels_sorted = array( 'key', 'foo', 'foobar', 'bar' );

		foreach ( $panels as $panel_id => $priority ) {
			$this->manager->add_panel(
				$panel_id,
				array(
					'priority' => $priority,
				)
			);
		}

		$this->manager->prepare_controls();

		$result = $this->manager->panels();
		$this->assertSame( $panels_sorted, array_keys( $result ) );
	}

	/**
	 * Verify sanitization of external header video URL will trim the whitespaces in the beginning and end of the URL.
	 *
	 * @ticket 39125
	 */
	public function test_sanitize_external_header_video_trim() {
		$this->manager->register_controls();
		$setting   = $this->manager->get_setting( 'external_header_video' );
		$video_url = 'https://www.youtube.com/watch?v=72xdCU__XCk';

		$whitespaces = array(
			' ',  // Space.
			"\t", // Horizontal tab.
			"\n", // Line feed.
			"\r", // Carriage return.
			"\f", // Form feed.
			"\v", // Vertical tab.
		);

		foreach ( $whitespaces as $whitespace ) {
			$sanitized = $setting->sanitize( $whitespace . $video_url . $whitespace );
			$this->assertSame( $video_url, $sanitized );
		}
	}
}

require_once ABSPATH . WPINC . '/class-wp-customize-setting.php';

/**
 * Class Test_Dynamic_Customize_Setting
 *
 * @see Tests_WP_Customize_Manager::test_add_setting_honoring_dynamic()
 */
class Test_Dynamic_Customize_Setting extends WP_Customize_Setting {
	public $type = 'dynamic';
	public $custom;
}

/**
 * Class Test_Setting_Without_Applying_Validate_Filter.
 *
 * @see Tests_WP_Customize_Manager::test_late_validate_setting_values()
 */
class Test_Setting_Without_Applying_Validate_Filter extends WP_Customize_Setting {

	/**
	 * Validates an input.
	 *
	 * @param mixed $value Value to validate.
	 * @return true|WP_Error True if the input was validated, otherwise WP_Error.
	 */
	public function validate( $value ) {
		if ( empty( $value ) ) {
			return new WP_Error( 'empty_value', __( 'You must supply a value' ) );
		}
		return true;
	}
}
