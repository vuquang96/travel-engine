<?php
/**
 *
 * Analytics Rest Controller
 *
 * @since 5.7
 * @package WP_Travel_Engine
 */

namespace WPTravelEngine\Core\REST_API;

/**
 * Analytics Rest Controller Class.
 */
class Analytics_Controller extends Controller {
	/**
	 * Route base.
	 *
	 * @var string
	 * @since 5.7
	 */
	protected $rest_base = 'analytics';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Register custom routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/totals',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_totals' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/trips',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_trips' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/customers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_customers' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/popular_trip',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_popular_trip' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/customers_table',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_customers_table' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/datefilter',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_date_filter' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/taxonomy',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_taxonomies' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/taxonomy_table',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_taxonomy_table' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/taxonomy_chart',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics_taxonomy_chart' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Checks if a given request has access to read posts.
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
	 * Gets Analytics Total.
	 */
	public function get_analytics_totals() {
		$start_date = '';
		$end_date   = '';
		if ( isset( $_GET['start_date'] ) && isset( $_GET['end_date'] ) ) {
			$start_date = sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
			$end_date   = sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
		}
		$data     = wptravelengine_analytics_totals( $start_date, $end_date );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Trips.
	 */
	public function get_analytics_trips() {
		$per_page = '';
		$page     = '';
		if ( isset( $_GET['per_page'] ) && isset( $_GET['page'] ) ) {
			$per_page = sanitize_text_field( wp_unslash( $_GET['per_page'] ) );
			$page     = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		$data     = wptravelengine_analytics_trips( $per_page, $page );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Customers.
	 */
	public function get_analytics_customers() {
		$start_date = '';
		$end_date   = '';
		if ( isset( $_GET['start_date'] ) && isset( $_GET['end_date'] ) ) {
			$start_date = sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
			$end_date   = sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
		}
		$data     = wptravelengine_analytics_customers( $start_date, $end_date );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Popular Trips.
	 */
	public function get_analytics_popular_trip() {
		$start_date = '';
		$end_date   = '';
		if ( isset( $_GET['start_date'] ) && isset( $_GET['end_date'] ) ) {
			$start_date = sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
			$end_date   = sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
		}
		$data     = wptravelengine_analytics_popular_trip( $start_date, $end_date );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Customers Data.
	 */
	public function get_analytics_customers_table() {
		$per_page = '';
		$page     = '';
		if ( isset( $_GET['per_page'] ) && isset( $_GET['page'] ) ) {
			$per_page = sanitize_text_field( wp_unslash( $_GET['per_page'] ) );
			$page     = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}
		$data     = wptravelengine_analytics_customers_table( $per_page, $page );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Date Filter Data.
	 */
	public function get_analytics_date_filter() {
		$filter_type = '';
		$source      = '';
		if ( isset( $_GET['source'] ) && isset( $_GET['filter_type'] ) ) {
			$source      = sanitize_text_field( wp_unslash( $_GET['source'] ) );
			$filter_type = sanitize_text_field( wp_unslash( $_GET['filter_type'] ) );
		}
		$data     = wptravelengine_analytics_date_filters( $source, $filter_type );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Taxonomies.
	 */
	public function get_analytics_taxonomies() {
		$start_date = '';
		$end_date   = '';
		$source     = '';
		if ( isset( $_GET['start_date'] ) && isset( $_GET['end_date'] ) && isset( $_GET['source'] ) ) {
			$start_date = sanitize_text_field( wp_unslash( $_GET['start_date'] ) );
			$end_date   = sanitize_text_field( wp_unslash( $_GET['end_date'] ) );
		}
		if ( isset( $_GET['source'] ) ) {
			$source = sanitize_text_field( wp_unslash( $_GET['source'] ) );
		}
		$data     = wptravelengine_analytics_taxonomy( $start_date, $end_date, $source );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Taxonomy Table Data.
	 */
	public function get_analytics_taxonomy_table() {
		$per_page = '';
		$page     = '';
		$source   = '';
		if ( isset( $_GET['per_page'] ) && isset( $_GET['page'] ) && isset( $_GET['source'] ) ) {
			$per_page = sanitize_text_field( wp_unslash( $_GET['per_page'] ) );
			$page     = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			$source   = sanitize_text_field( wp_unslash( $_GET['source'] ) );
		}
		$data     = wptravelengine_analytics_taxonomy_table( $per_page, $page, $source );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Gets Analytics Taxonomy Chart Data.
	 */
	public function get_analytics_taxonomy_chart() {
		$per_page = '';
		$page     = '';
		$source   = '';
		if ( isset( $_GET['per_page'] ) && isset( $_GET['page'] ) && isset( $_GET['source'] ) ) {
			$per_page = sanitize_text_field( wp_unslash( $_GET['per_page'] ) );
			$page     = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			$source   = sanitize_text_field( wp_unslash( $_GET['source'] ) );
		}
		$data     = wptravelengine_analytics_taxonomy_chart( $per_page, $page, $source );
		$response = rest_ensure_response( $data );
		return $response;
	}
}

new Analytics_Controller();
