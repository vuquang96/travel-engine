<?php
/**
 *
 * Admin Rest Controller
 *
 * @since 5.6.0
 */
namespace WPTravelEngine\Core\REST_API;

/**
 * Admin Rest Controller Class.
 */
class Admin_Controller extends Controller {

	protected $rest_base = 'dashboard';

	protected $api_url;

	public function __construct() {
		parent::__construct();

		$this->api_url = 'https://wptravelengine.com/';
		if ( defined( 'WPTRAVELENGINE_DATA_STORE_API_URL' ) ) {
			$this->api_url = WPTRAVELENGINE_DATA_STORE_API_URL;
		}
	}

	/**
	 * Register custom routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/themes',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_themes' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/addons',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_addons' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/systeminfo',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'system_info' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/tutorials',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tutorials' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/faqs',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_faqs' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/changelog',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_changelog' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to read data.', 'wp-travel-engine' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get Themes.
	 */
	public function get_themes( $request ) {

		$response = wp_safe_remote_get( $this->api_url . 'wp-json/wte-api/v1/themes?per_page=100' );

		if ( is_wp_error( $response ) ) {
			return  array();
		}

		$items = wp_remote_retrieve_body( $response );

		$items = json_decode( $items, true );

		if ( ! is_array( $items ) ) {
			$items = array();
		}

		$response = rest_ensure_response( $items );

		return $response;
	}

	/**
	 * Get Changelog
	 */
	public function get_changelog( $request ) {

		$has_changelog = get_option( 'wptravelengine_changelog_' . WP_TRAVEL_ENGINE_VERSION, false );

		$current = get_site_transient( 'update_plugins' );
		$file    = basename( dirname( WP_TRAVEL_ENGINE_FILE_PATH ) ) . '/' . basename( WP_TRAVEL_ENGINE_FILE_PATH );

		$new_version = WP_TRAVEL_ENGINE_VERSION;
		if ( isset( $current->response[ $file ]->new_version ) ) {
			$new_version = $current->response[ $file ]->new_version;
		}

		$has_changelog = get_option( 'wptravelengine_changelog_' . $new_version, false );

		if ( $has_changelog !== false ) {
			$response = json_decode( $has_changelog );
		} else {

			$response  = wp_safe_remote_get( 'http://plugins.svn.wordpress.org/wp-travel-engine/trunk/changelog.txt' );
			$stream    = wp_remote_retrieve_body( $response );
			$changelog = preg_split( '/\r?\n\r?\n/', $stream );

			$response = array();

			if ( is_array( $changelog ) ) {
				$changelog = array_slice( $changelog, 0, 6 );
				foreach ( $changelog as $changes ) {

					$version_changes = explode( "\n", $changes );

					$version_date = array_shift( $version_changes );

					if ( strpos( $version_date, '-' ) === false || ! preg_match( '/=(.*)=/', $version_date, $matches ) ) {
						continue;
					}

					list($version, $date) = explode( '-', trim( $matches[1] ) );

					$_changelog = array();
					foreach ( $version_changes as $change ) {
						if ( strpos( $change, ':' ) === false ) {
							$_changelog['Fix']['status']    = 'fix';
							$_changelog['Fix']['label']     = 'Fix';
							$_changelog['Fix']['content'][] = trim( preg_replace( '/^\*/', '', $change ) );
						} else {
							list( $change_type, $change_description ) = explode( ':', $change );
							$_type                                    = trim( preg_replace( '/^\*/', '', $change_type ) );
							$_changelog[ $_type ]['status']           = strtolower( str_replace( ' ', '-', $_type ) );
							$_changelog[ $_type ]['label']            = $_type;
							$_changelog[ $_type ]['content'][]        = trim( $change_description );
						}
					}

					$response[] = array(
						'version'         => trim( $version ),
						'date'            => trim( $date ),
						'contents'        => array_values( $_changelog ),
						'version_compare' => version_compare( trim( $version ), WP_TRAVEL_ENGINE_VERSION ),
						'is_latest'       => version_compare( trim( $version ), $new_version, '=' ),
					);
				}
			}

			$previous_changelog = get_option( 'wptravelengine_current_changelog', false );

			if ( $previous_changelog ) {
				delete_option( $previous_changelog );
			}
			update_option( 'wptravelengine_current_changelog', 'wptravelengine_changelog_' . $new_version );
			update_option( 'wptravelengine_changelog_' . $new_version, wp_json_encode( $response ) );
		}

		$response = rest_ensure_response( $response );
		return $response;
	}

	/**
	 * Get Addons.
	 */
	public function get_addons( $request ) {

		$items = wptravelengine_get_products_from_store();

		$data = array_column( $items, 'info' );

		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Get Tutorials.
	 */
	public function get_tutorials( $request ) {

		$response = wp_safe_remote_get( $this->api_url . 'wp-json/wte-api/v1/tutorials?orderby=menu_order&order=asc&per_page=100' );

		$_data = array();

		if ( is_wp_error( $response ) ) {
			rest_ensure_response( $_data );
		}

		$data = wp_remote_retrieve_body( $response );

		if ( ! empty( $data ) ) {
			$data = json_decode( $data, true );
		}

		static $index = -1;
		$categories_order = array();
		if ( is_array( $data ) ) {
			foreach ( $data as $object ) {
				$category_ids = $object['tutorials-category'];
				$categories   = $object['tutorial_categories'];
				foreach ( $category_ids as $_category_id ) {
					if ( ! isset( $categories_order[ $_category_id ] ) ) {
						$categories_order[ $_category_id ] = ++$index;
					}
					$data_index = $categories_order[ $_category_id ];
					$_data[ $data_index ]['label']   = $categories[ $_category_id ]['name'];
					$_data[ $data_index ]['items'][] = array(
						'content_type' => isset( $object['content_type'] ) ? $object['content_type'] : 'youtube',
						'title'        => $object['title']['rendered'],
						'src'          => $object['meta']['tutorial_link'],
					);
				}
			}
		}
		$response = rest_ensure_response( $_data );
		return $response;

	}

	/**
	 * Get FAQs.
	 */
	public function get_faqs() {
		$response = wp_safe_remote_get( $this->api_url . 'wp-json/wte-api/v1/faqs?order_by=date&order=asc' );

		$_data = array();

		if ( is_wp_error( $response ) ) {
			rest_ensure_response( $_data );
		}

		$data = wp_remote_retrieve_body( $response );

		if ( ! empty( $data ) ) {
			$data = json_decode( $data, true );
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $object ) {
				$_data[] = array(
					'q' => $object['title']['rendered'],
					'a' => $object['content']['rendered'],
				);
			}
		}

		$response = rest_ensure_response( $_data );
		return $response;
	}

	/**
	 * Site Info.
	 */
	public function system_info( $request ) {
		$data = wptravelengine_system_info();

		$response = rest_ensure_response( $data );
		return $response;
	}
}

new Admin_Controller();
