<?php
/**
 * @group admin
 */
class Tests_Admin_IncludesTemplate extends WP_UnitTestCase {
	/**
	 * Editor user ID.
	 *
	 * @var int $editor_id
	 */
	public static $editor_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$editor_id = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * @ticket 51137
	 * @dataProvider data_wp_terms_checklist_with_selected_cats
	 */
	public function test_wp_terms_checklist_with_selected_cats( $term_id ) {
		$output = wp_terms_checklist(
			0,
			array(
				'selected_cats' => array( $term_id ),
				'echo'          => false,
			)
		);

		$this->assertStringContainsString( "checked='checked'", $output );
	}

	/**
	 * @ticket 51137
	 * @dataProvider data_wp_terms_checklist_with_selected_cats
	 */
	public function test_wp_terms_checklist_with_popular_cats( $term_id ) {
		$output = wp_terms_checklist(
			0,
			array(
				'popular_cats' => array( $term_id ),
				'echo'         => false,
			)
		);

		$this->assertStringContainsString( 'class="popular-category"', $output );
	}

	public function data_wp_terms_checklist_with_selected_cats() {
		return array(
			array( '1' ),
			array( 1 ),
		);
	}

	/**
	 * @ticket 49701
	 *
	 * @covers ::get_inline_data
	 */
	public function test_get_inline_data_contains_term_if_show_ui_is_false_but_show_on_quick_edit_is_true_for_hierarchical_taxonomy() {
		// Create a post with a term from a hierarchical taxonomy.
		register_taxonomy(
			'wptests_tax_1',
			'post',
			array(
				'show_ui'            => false,
				'show_in_quick_edit' => true,
				'hierarchical'       => true,
			)
		);
		$term = wp_insert_term( 'Test', 'wptests_tax_1' );
		$post = self::factory()->post->create_and_get();
		wp_set_object_terms( $post->ID, $term['term_id'], 'wptests_tax_1' );

		// Test that get_inline_data() has `post_category` div containing the assigned term.
		wp_set_current_user( self::$editor_id );
		get_inline_data( $post );
		$this->expectOutputRegex( '/<div class="post_category" id="wptests_tax_1_' . $post->ID . '">' . $term['term_id'] . '<\/div>/' );
	}

	/**
	 * @ticket 49701
	 *
	 * @covers ::get_inline_data
	 */
	public function test_get_inline_data_contains_term_if_show_ui_is_false_but_show_on_quick_edit_is_true_for_nonhierarchical_taxonomy() {
		// Create a post with a term from a non-hierarchical taxonomy.
		register_taxonomy(
			'wptests_tax_1',
			'post',
			array(
				'show_ui'            => false,
				'show_in_quick_edit' => true,
				'hierarchical'       => false,
			)
		);
		$term = wp_insert_term( 'Test', 'wptests_tax_1' );
		$post = self::factory()->post->create_and_get();
		wp_set_object_terms( $post->ID, $term['term_id'], 'wptests_tax_1' );

		// Test that get_inline_data() has `tags_input` div containing the assigned term.
		wp_set_current_user( self::$editor_id );
		get_inline_data( $post );
		$this->expectOutputRegex( '/<div class="tags_input" id="wptests_tax_1_' . $post->ID . '">Test<\/div>/' );
	}

	public function test_add_meta_box() {
		global $wp_meta_boxes;

		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', 'post' );

		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['post']['advanced']['default'] );
	}

	public function test_remove_meta_box() {
		global $wp_meta_boxes;

		// Add a meta box to remove.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', $current_screen = 'post' );

		// Confirm it's there.
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes[ $current_screen ]['advanced']['default'] );

		// Remove the meta box.
		remove_meta_box( 'testbox1', $current_screen, 'advanced' );

		// Check that it was removed properly (the meta box should be set to false once that it has been removed).
		$this->assertFalse( $wp_meta_boxes[ $current_screen ]['advanced']['default']['testbox1'] );
	}

	/**
	 * @ticket 15000
	 */
	public function test_add_meta_box_on_multiple_screens() {
		global $wp_meta_boxes;

		// Add a meta box to three different post types.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', array( 'post', 'comment', 'attachment' ) );

		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['post']['advanced']['default'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['comment']['advanced']['default'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['attachment']['advanced']['default'] );
	}

	/**
	 * @ticket 15000
	 */
	public function test_remove_meta_box_from_multiple_screens() {
		global $wp_meta_boxes;

		// Add a meta box to three different screens.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', array( 'post', 'comment', 'attachment' ) );

		// Remove meta box from posts.
		remove_meta_box( 'testbox1', 'post', 'advanced' );

		// Check that we have removed the meta boxes only from posts.
		$this->assertFalse( $wp_meta_boxes['post']['advanced']['default']['testbox1'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['comment']['advanced']['default'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['attachment']['advanced']['default'] );

		// Remove the meta box from the other screens.
		remove_meta_box( 'testbox1', array( 'comment', 'attachment' ), 'advanced' );

		$this->assertFalse( $wp_meta_boxes['comment']['advanced']['default']['testbox1'] );
		$this->assertFalse( $wp_meta_boxes['attachment']['advanced']['default']['testbox1'] );
	}

	/**
	 * @ticket 50019
	 */
	public function test_add_meta_box_with_previously_removed_box_and_sorted_priority() {
		global $wp_meta_boxes;

		// Add a meta box to remove.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', $current_screen = 'post' );

		// Remove the meta box.
		remove_meta_box( 'testbox1', $current_screen, 'advanced' );

		// Attempt to re-add the meta box with the 'sorted' priority.
		add_meta_box( 'testbox1', null, null, $current_screen, 'advanced', 'sorted' );

		// Check that the meta box was not re-added.
		$this->assertFalse( $wp_meta_boxes[ $current_screen ]['advanced']['default']['testbox1'] );
	}

	/**
	 * @ticket 17851
	 * @covers ::add_settings_section
	 */
	public function test_add_settings_section() {
		add_settings_section( 'test-section', 'Section title', '__return_false', 'test-page' );

		global $wp_settings_sections;
		$this->assertIsArray( $wp_settings_sections, 'List of sections is not initialized.' );
		$this->assertArrayHasKey( 'test-page', $wp_settings_sections, 'List of sections for the test page has not been added to sections list.' );
		$this->assertIsArray( $wp_settings_sections['test-page'], 'List of sections for the test page is not initialized.' );
		$this->assertArrayHasKey( 'test-section', $wp_settings_sections['test-page'], 'Test section has not been added to the list of sections for the test page.' );

		$this->assertEqualSetsWithIndex(
			array(
				'id'             => 'test-section',
				'title'          => 'Section title',
				'callback'       => '__return_false',
				'before_section' => '',
				'after_section'  => '',
				'section_class'  => '',
			),
			$wp_settings_sections['test-page']['test-section'],
			'Test section data does not match the expected dataset.'
		);
	}

	/**
	 * @ticket 17851
	 *
	 * @param array  $extra_args                   Extra arguments to pass to function `add_settings_section()`.
	 * @param array  $expected_section_data        Expected set of section data.
	 * @param string $expected_before_section_html Expected HTML markup to be rendered before the settings section.
	 * @param string $expected_after_section_html  Expected HTML markup to be rendered after the settings section.
	 *
	 * @covers ::add_settings_section
	 * @covers ::do_settings_sections
	 *
	 * @dataProvider data_extra_args_for_add_settings_section
	 */
	public function test_add_settings_section_with_extra_args( $extra_args, $expected_section_data, $expected_before_section_html, $expected_after_section_html ) {
		add_settings_section( 'test-section', 'Section title', '__return_false', 'test-page', $extra_args );
		add_settings_field( 'test-field', 'Field title', '__return_false', 'test-page', 'test-section' );

		global $wp_settings_sections;
		$this->assertIsArray( $wp_settings_sections, 'List of sections is not initialized.' );
		$this->assertArrayHasKey( 'test-page', $wp_settings_sections, 'List of sections for the test page has not been added to sections list.' );
		$this->assertIsArray( $wp_settings_sections['test-page'], 'List of sections for the test page is not initialized.' );
		$this->assertArrayHasKey( 'test-section', $wp_settings_sections['test-page'], 'Test section has not been added to the list of sections for the test page.' );

		$this->assertEqualSetsWithIndex(
			$expected_section_data,
			$wp_settings_sections['test-page']['test-section'],
			'Test section data does not match the expected dataset.'
		);

		ob_start();
		do_settings_sections( 'test-page' );
		$output = ob_get_clean();

		$this->assertStringContainsString( $expected_before_section_html, $output, 'Test page output does not contain the custom markup to be placed before the section.' );
		$this->assertStringContainsString( $expected_after_section_html, $output, 'Test page output does not contain the custom markup to be placed after the section.' );
	}

	/**
	 * @ticket 62746
	 *
	 * @param array  $extra_args                   Extra arguments to pass to function `add_settings_section()`.
	 * @param array  $expected_section_data        Expected set of section data.
	 * @param string $expected_before_section_html Expected HTML markup to be rendered before the settings section.
	 * @param string $expected_after_section_html  Expected HTML markup to be rendered after the settings section.
	 *
	 * @covers ::add_settings_section
	 * @covers ::do_settings_sections
	 *
	 * @dataProvider data_extra_args_for_add_settings_section
	 */
	public function test_add_settings_section_without_any_fields( $extra_args, $expected_section_data, $expected_before_section_html, $expected_after_section_html ) {
		add_settings_section( 'test-section', 'Section title', '__return_false', 'test-page', $extra_args );

		ob_start();
		do_settings_sections( 'test-page' );
		$output = ob_get_clean();

		$this->assertStringContainsString( $expected_before_section_html, $output, 'Test page output does not contain the custom markup to be placed before the section.' );
		$this->assertStringContainsString( $expected_after_section_html, $output, 'Test page output does not contain the custom markup to be placed after the section.' );
	}

	/**
	 * Data provider for `test_add_settings_section_with_extra_args()`.
	 *
	 * @return array
	 */
	public function data_extra_args_for_add_settings_section() {
		return array(
			'class placeholder section_class present' => array(
				array(
					'before_section' => '<div class="%s">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => 'test-section-wrap',
				),
				array(
					'id'             => 'test-section',
					'title'          => 'Section title',
					'callback'       => '__return_false',
					'before_section' => '<div class="%s">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => 'test-section-wrap',
				),
				'<div class="test-section-wrap">',
				'</div><!-- end of the test section -->',
			),
			'missing class placeholder section_class' => array(
				array(
					'before_section' => '<div class="testing-section-wrapper">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => 'test-section-wrap',
				),
				array(
					'id'             => 'test-section',
					'title'          => 'Section title',
					'callback'       => '__return_false',
					'before_section' => '<div class="testing-section-wrapper">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => 'test-section-wrap',
				),
				'<div class="testing-section-wrapper">',
				'</div><!-- end of the test section -->',
			),
			'empty section_class'                     => array(
				array(
					'before_section' => '<div class="test-section-container">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => '',
				),
				array(
					'id'             => 'test-section',
					'title'          => 'Section title',
					'callback'       => '__return_false',
					'before_section' => '<div class="test-section-container">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => '',
				),
				'<div class="test-section-container">',
				'</div><!-- end of the test section -->',
			),
			'section_class missing'                   => array(
				array(
					'before_section' => '<div class="wp-whitelabel-section">',
					'after_section'  => '</div><!-- end of the test section -->',
				),
				array(
					'id'             => 'test-section',
					'title'          => 'Section title',
					'callback'       => '__return_false',
					'before_section' => '<div class="wp-whitelabel-section">',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => '',
				),
				'<div class="wp-whitelabel-section">',
				'</div><!-- end of the test section -->',
			),
			'disallowed tag in before_section'        => array(
				array(
					'before_section' => '<div class="video-settings-section"><iframe src="https://www.wordpress.org/" />',
					'after_section'  => '</div><!-- end of the test section -->',
				),
				array(
					'id'             => 'test-section',
					'title'          => 'Section title',
					'callback'       => '__return_false',
					'before_section' => '<div class="video-settings-section"><iframe src="https://www.wordpress.org/" />',
					'after_section'  => '</div><!-- end of the test section -->',
					'section_class'  => '',
				),
				'<div class="video-settings-section">',
				'</div><!-- end of the test section -->',
			),
			'disallowed tag in after_section'         => array(
				array(
					'before_section' => '<div class="video-settings-section">',
					'after_section'  => '</div><iframe src="https://www.wordpress.org/" />',
				),
				array(
					'id'             => 'test-section',
					'title'          => 'Section title',
					'callback'       => '__return_false',
					'before_section' => '<div class="video-settings-section">',
					'after_section'  => '</div><iframe src="https://www.wordpress.org/" />',
					'section_class'  => '',
				),
				'<div class="video-settings-section">',
				'</div>',
			),
		);
	}

	/**
	 * Test calling get_settings_errors() with variations on where it gets errors from.
	 *
	 * @ticket 42498
	 * @covers ::get_settings_errors
	 * @global array $wp_settings_errors
	 */
	public function test_get_settings_errors_sources() {
		global $wp_settings_errors;

		$blogname_error        = array(
			'setting' => 'blogname',
			'code'    => 'blogname',
			'message' => 'Capital P dangit!',
			'type'    => 'error',
		);
		$blogdescription_error = array(
			'setting' => 'blogdescription',
			'code'    => 'blogdescription',
			'message' => 'Too short',
			'type'    => 'error',
		);

		$wp_settings_errors = null;
		$this->assertSame( array(), get_settings_errors( 'blogname' ) );

		// Test getting errors from transient.
		$_GET['settings-updated'] = '1';
		set_transient( 'settings_errors', array( $blogname_error ) );
		$wp_settings_errors = null;
		$this->assertSame( array( $blogname_error ), get_settings_errors( 'blogname' ) );

		// Test getting errors from transient and from global.
		$_GET['settings-updated'] = '1';
		set_transient( 'settings_errors', array( $blogname_error ) );
		$wp_settings_errors = null;
		add_settings_error( $blogdescription_error['setting'], $blogdescription_error['code'], $blogdescription_error['message'], $blogdescription_error['type'] );
		$this->assertSameSets( array( $blogname_error, $blogdescription_error ), get_settings_errors() );

		$wp_settings_errors = null;
	}

	/**
	 * @ticket 44941
	 * @covers ::settings_errors
	 * @global array $wp_settings_errors
	 * @dataProvider data_settings_errors_css_classes
	 */
	public function test_settings_errors_css_classes( $type, $expected ) {
		global $wp_settings_errors;

		add_settings_error( 'foo', 'bar', 'Capital P dangit!', $type );

		ob_start();
		settings_errors();
		$output = ob_get_clean();

		$wp_settings_errors = null;

		$expected = sprintf( 'notice %s settings-error is-dismissible', $expected );

		$this->assertStringContainsString( $expected, $output );
		$this->assertStringNotContainsString( 'notice-notice-', $output );
	}

	public function data_settings_errors_css_classes() {
		return array(
			array( 'error', 'notice-error' ),
			array( 'success', 'notice-success' ),
			array( 'warning', 'notice-warning' ),
			array( 'info', 'notice-info' ),
			array( 'updated', 'notice-success' ),
			array( 'notice-error', 'notice-error' ),
			array( 'error my-own-css-class hello world', 'error my-own-css-class hello world' ),
		);
	}

	/**
	 * @ticket 42791
	 */
	public function test_wp_add_dashboard_widget() {
		global $wp_meta_boxes;

		set_current_screen( 'dashboard' );

		if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
			require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		}

		// Some hardcoded defaults for core widgets.
		wp_add_dashboard_widget( 'dashboard_quick_press', 'Quick', '__return_false' );
		wp_add_dashboard_widget( 'dashboard_browser_nag', 'Nag', '__return_false' );

		$this->assertArrayHasKey( 'dashboard_quick_press', $wp_meta_boxes['dashboard']['side']['core'] );
		$this->assertArrayHasKey( 'dashboard_browser_nag', $wp_meta_boxes['dashboard']['normal']['high'] );

		// Location and priority defaults.
		wp_add_dashboard_widget( 'dashboard1', 'Widget 1', '__return_false', null, null, 'foo' );
		wp_add_dashboard_widget( 'dashboard2', 'Widget 2', '__return_false', null, null, null, 'bar' );

		$this->assertArrayHasKey( 'dashboard1', $wp_meta_boxes['dashboard']['foo']['core'] );
		$this->assertArrayHasKey( 'dashboard2', $wp_meta_boxes['dashboard']['normal']['bar'] );

		// Cleanup.
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_browser_nag', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard1', 'dashboard', 'foo' );

		// This doesn't actually get removed due to the invalid priority.
		remove_meta_box( 'dashboard2', 'dashboard', 'normal' );
	}
}
