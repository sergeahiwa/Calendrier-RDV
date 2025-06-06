<?php
/**
 * Unit tests covering WP_REST_Taxonomies_Controller functionality.
 *
 * @package WordPress
 * @subpackage REST API
 *
 * @group restapi
 */
class WP_Test_REST_Taxonomies_Controller extends WP_Test_REST_Controller_Testcase {

	protected static $contributor_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$contributor_id = $factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$contributor_id );
	}

	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wp/v2/taxonomies', $routes );
		$this->assertArrayHasKey( '/wp/v2/taxonomies/(?P<taxonomy>[\w-]+)', $routes );
	}

	public function test_context_param() {
		// Collection.
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/taxonomies' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSameSets( array( 'view', 'edit', 'embed' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/taxonomies/post_tag' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSameSets( array( 'view', 'edit', 'embed' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function test_get_items() {
		$request    = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$taxonomies = $this->get_public_taxonomies( get_taxonomies( '', 'objects' ) );
		$this->assertCount( count( $taxonomies ), $data );
		$this->assertSame( 'Categories', $data['category']['name'] );
		$this->assertSame( 'category', $data['category']['slug'] );
		$this->assertTrue( $data['category']['hierarchical'] );
		$this->assertSame( 'Tags', $data['post_tag']['name'] );
		$this->assertSame( 'post_tag', $data['post_tag']['slug'] );
		$this->assertFalse( $data['post_tag']['hierarchical'] );
		$this->assertSame( 'tags', $data['post_tag']['rest_base'] );
	}

	/**
	 * @ticket 56481
	 */
	public function test_get_items_with_head_request_should_not_prepare_taxonomy_data() {
		$request   = new WP_REST_Request( 'HEAD', '/wp/v2/taxonomies' );
		$hook_name = 'rest_prepare_taxonomy';
		$filter    = new MockAction();
		$callback  = array( $filter, 'filter' );
		add_filter( $hook_name, $callback );
		$response = rest_get_server()->dispatch( $request );
		remove_filter( $hook_name, $callback );
		$this->assertSame( 200, $response->get_status(), 'The response status should be 200.' );
		$this->assertSame( 0, $filter->get_call_count(), 'The "' . $hook_name . '" filter was called when it should not be for HEAD requests.' );
		$this->assertSame( array(), $response->get_data(), 'The server should not generate a body in response to a HEAD request.' );
	}

	public function test_get_items_context_edit() {
		wp_set_current_user( self::$contributor_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$request->set_param( 'context', 'edit' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$taxonomies = get_taxonomies( '', 'objects' );
		unset( $taxonomies['nav_menu'] ); // Menus are not editable by contributors.
		$taxonomies = $this->get_public_taxonomies( $taxonomies );
		$this->assertCount( count( $taxonomies ), $data );
		$this->assertSame( 'Categories', $data['category']['name'] );
		$this->assertSame( 'category', $data['category']['slug'] );
		$this->assertTrue( $data['category']['hierarchical'] );
		$this->assertSame( 'Tags', $data['post_tag']['name'] );
		$this->assertSame( 'post_tag', $data['post_tag']['slug'] );
		$this->assertFalse( $data['post_tag']['hierarchical'] );
		$this->assertSame( 'tags', $data['post_tag']['rest_base'] );
	}


	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method HTTP method to use.
	 */
	public function test_get_items_invalid_permission_for_context( $method ) {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( $method, '/wp/v2/taxonomies' );
		$request->set_param( 'context', 'edit' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	/**
	 * Data provider intended to provide HTTP method names for testing GET and HEAD requests.
	 *
	 * @return array
	 */
	public static function data_readable_http_methods() {
		return array(
			'GET request'  => array( 'GET' ),
			'HEAD request' => array( 'HEAD' ),
		);
	}

	public function test_get_taxonomies_for_type() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$request->set_param( 'type', 'post' );
		$response = rest_get_server()->dispatch( $request );
		$this->check_taxonomies_for_type_response( 'post', $response );
	}

	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method HTTP method to use.
	 */
	public function test_get_taxonomies_for_invalid_type( $method ) {
		$request = new WP_REST_Request( $method, '/wp/v2/taxonomies' );
		$request->set_param( 'type', 'wingding' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		if ( 'HEAD' === $method ) {
			return null;
		}
		$data = $response->get_data();
		$this->assertSame( '{}', json_encode( $data ) );
	}

	public function test_get_item() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/category' );
		$response = rest_get_server()->dispatch( $request );
		$this->check_taxonomy_object_response( 'view', $response );
	}

	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method The HTTP method to use.
	 */
	public function test_get_item_should_allow_adding_headers_via_filter( $method ) {
		$request   = new WP_REST_Request( 'HEAD', '/wp/v2/taxonomies/category' );
		$hook_name = 'rest_prepare_taxonomy';
		$filter    = new MockAction();
		$callback  = array( $filter, 'filter' );
		add_filter( $hook_name, $callback );
		$header_filter = new class() {
			public static function add_custom_header( $response ) {
				$response->header( 'X-Test-Header', 'Test' );

				return $response;
			}
		};
		add_filter( $hook_name, array( $header_filter, 'add_custom_header' ) );
		$response = rest_get_server()->dispatch( $request );
		remove_filter( $hook_name, $callback );
		remove_filter( $hook_name, array( $header_filter, 'add_custom_header' ) );

		$this->assertSame( 200, $response->get_status(), 'The response status should be 200.' );
		$this->assertSame( 1, $filter->get_call_count(), 'The "' . $hook_name . '" filter was called when it should not be for HEAD requests.' );
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-Test-Header', $headers, 'The "X-Test-Header" header should be present in the response.' );
		$this->assertSame( 'Test', $headers['X-Test-Header'], 'The "X-Test-Header" header value should be equal to "Test".' );
		if ( 'HEAD' !== $method ) {
			return null;
		}
		$this->assertSame( array(), $response->get_data(), 'The server should not generate a body in response to a HEAD request.' );
	}

	/**
	 * @dataProvider data_head_request_with_specified_fields_returns_success_response
	 * @ticket 56481
	 *
	 * @param string $path The path to test.
	 */
	public function test_head_request_with_specified_fields_returns_success_response( $path ) {
		$request = new WP_REST_Request( 'HEAD', $path );
		$request->set_param( '_fields', 'name' );
		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		add_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );
		$response = apply_filters( 'rest_post_dispatch', $response, $server, $request );
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10 );

		$this->assertSame( 200, $response->get_status(), 'The response status should be 200.' );
	}

	/**
	 * Data provider intended to provide paths for testing HEAD requests.
	 *
	 * @return array
	 */
	public static function data_head_request_with_specified_fields_returns_success_response() {
		return array(
			'get_item request'  => array( '/wp/v2/taxonomies/category' ),
			'get_items request' => array( '/wp/v2/taxonomies' ),
		);
	}

	public function test_get_item_edit_context() {
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/category' );
		$request->set_param( 'context', 'edit' );
		$response = rest_get_server()->dispatch( $request );
		$this->check_taxonomy_object_response( 'edit', $response );
	}

	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method HTTP method to use.
	 */
	public function test_get_item_invalid_permission_for_context( $method ) {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( $method, '/wp/v2/taxonomies/category' );
		$request->set_param( 'context', 'edit' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden_context', $response, 401 );
	}

	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method HTTP method to use.
	 */
	public function test_get_invalid_taxonomy( $method ) {
		$request  = new WP_REST_Request( $method, '/wp/v2/taxonomies/invalid' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_taxonomy_invalid', $response, 404 );
	}

	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method HTTP method to use.
	 */
	public function test_get_non_public_taxonomy_not_authenticated( $method ) {
		register_taxonomy( 'api-private', 'post', array( 'public' => false ) );

		$request  = new WP_REST_Request( $method, '/wp/v2/taxonomies/api-private' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	/**
	 * @dataProvider data_readable_http_methods
	 * @ticket 56481
	 *
	 * @param string $method HTTP method to use.
	 */
	public function test_get_non_public_taxonomy_no_permission( $method ) {
		wp_set_current_user( self::$contributor_id );
		register_taxonomy( 'api-private', 'post', array( 'public' => false ) );

		$request  = new WP_REST_Request( $method, '/wp/v2/taxonomies/api-private' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_create_item() {
		/** Taxonomies can't be created */
		$request  = new WP_REST_Request( 'POST', '/wp/v2/taxonomies' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_update_item() {
		/** Taxonomies can't be updated */
		$request  = new WP_REST_Request( 'POST', '/wp/v2/taxonomies/category' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_delete_item() {
		/** Taxonomies can't be deleted */
		$request  = new WP_REST_Request( 'DELETE', '/wp/v2/taxonomies/category' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_prepare_item() {
		$tax      = get_taxonomy( 'category' );
		$endpoint = new WP_REST_Taxonomies_Controller();
		$request  = new WP_REST_Request();
		$request->set_param( 'context', 'edit' );
		$response = $endpoint->prepare_item_for_response( $tax, $request );
		$this->check_taxonomy_object( 'edit', $tax, $response->get_data(), $response->get_links() );
	}

	public function test_prepare_item_limit_fields() {
		$tax      = get_taxonomy( 'category' );
		$request  = new WP_REST_Request();
		$endpoint = new WP_REST_Taxonomies_Controller();
		$request->set_param( 'context', 'edit' );
		$request->set_param( '_fields', 'id,name' );
		$response = $endpoint->prepare_item_for_response( $tax, $request );
		$this->assertSame(
			array(
				// 'id' doesn't exist in this context.
				'name',
			),
			array_keys( $response->get_data() )
		);
	}

	/**
	 * @ticket 42209
	 */
	public function test_object_types_is_an_array_if_object_type_is_unregistered() {
		register_taxonomy_for_object_type( 'category', 'page' );
		register_taxonomy_for_object_type( 'category', 'attachment' );
		unregister_taxonomy_for_object_type( 'category', 'page' );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/category' );
		$response = rest_get_server()->dispatch( $request );

		$types = $response->get_data()['types'];
		$this->assertArrayHasKey( 0, $types );
		$this->assertSame( 'post', $types[0] );
		$this->assertArrayHasKey( 1, $types );
		$this->assertSame( 'attachment', $types[1] );
		$this->assertCount( 2, $types );
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wp/v2/taxonomies' );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertCount( 11, $properties );
		$this->assertArrayHasKey( 'capabilities', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'hierarchical', $properties );
		$this->assertArrayHasKey( 'labels', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'show_cloud', $properties );
		$this->assertArrayHasKey( 'types', $properties );
		$this->assertArrayHasKey( 'visibility', $properties );
		$this->assertArrayHasKey( 'rest_base', $properties );
		$this->assertArrayHasKey( 'rest_namespace', $properties );
	}

	/**
	 * Utility function for use in get_public_taxonomies
	 */
	private function is_public( $taxonomy ) {
		return ! empty( $taxonomy->show_in_rest );
	}
	/**
	 * Utility function to filter down to only public taxonomies
	 */
	private function get_public_taxonomies( $taxonomies ) {
		// Pass through array_values to re-index after filtering.
		return array_values( array_filter( $taxonomies, array( $this, 'is_public' ) ) );
	}

	protected function check_taxonomy_object( $context, $tax_obj, $data, $links ) {
		$this->assertSame( $tax_obj->label, $data['name'] );
		$this->assertSame( $tax_obj->name, $data['slug'] );
		$this->assertSame( $tax_obj->description, $data['description'] );
		$this->assertSame( $tax_obj->hierarchical, $data['hierarchical'] );
		$this->assertSame( $tax_obj->rest_base, $data['rest_base'] );
		$this->assertSame( $tax_obj->rest_namespace, $data['rest_namespace'] );
		$this->assertSame( rest_url( 'wp/v2/taxonomies' ), $links['collection'][0]['href'] );
		$this->assertArrayHasKey( 'https://api.w.org/items', $links );
		if ( 'edit' === $context ) {
			$this->assertSame( $tax_obj->cap, $data['capabilities'] );
			$this->assertSame( $tax_obj->labels, $data['labels'] );
			$this->assertSame( $tax_obj->show_tagcloud, $data['show_cloud'] );

			$this->assertSame( $tax_obj->public, $data['visibility']['public'] );
			$this->assertSame( $tax_obj->publicly_queryable, $data['visibility']['publicly_queryable'] );
			$this->assertSame( $tax_obj->show_admin_column, $data['visibility']['show_admin_column'] );
			$this->assertSame( $tax_obj->show_in_nav_menus, $data['visibility']['show_in_nav_menus'] );
			$this->assertSame( $tax_obj->show_in_quick_edit, $data['visibility']['show_in_quick_edit'] );
			$this->assertSame( $tax_obj->show_ui, $data['visibility']['show_ui'] );
		} else {
			$this->assertArrayNotHasKey( 'capabilities', $data );
			$this->assertArrayNotHasKey( 'labels', $data );
			$this->assertArrayNotHasKey( 'show_cloud', $data );
			$this->assertArrayNotHasKey( 'visibility', $data );
		}
	}

	protected function check_taxonomy_object_response( $context, $response ) {
		$this->assertSame( 200, $response->get_status() );
		$data     = $response->get_data();
		$category = get_taxonomy( 'category' );
		$this->check_taxonomy_object( $context, $category, $data, $response->get_links() );
	}

	protected function check_taxonomies_for_type_response( $type, $response ) {
		$this->assertSame( 200, $response->get_status() );
		$data       = $response->get_data();
		$taxonomies = $this->get_public_taxonomies( get_object_taxonomies( $type, 'objects' ) );
		$this->assertCount( count( $taxonomies ), $data );
	}

	/**
	 * @ticket 49116
	 */
	public function test_get_for_taxonomy_reuses_same_instance() {
		$this->assertSame(
			get_taxonomy( 'category' )->get_rest_controller(),
			get_taxonomy( 'category' )->get_rest_controller()
		);
	}

	/**
	 * @ticket 49116
	 */
	public function test_get_for_taxonomy_returns_terms_controller_if_custom_class_not_specified() {
		register_taxonomy(
			'test',
			'post',
			array(
				'show_in_rest' => true,
			)
		);

		$this->assertInstanceOf(
			WP_REST_Terms_Controller::class,
			get_taxonomy( 'test' )->get_rest_controller()
		);
	}
}
