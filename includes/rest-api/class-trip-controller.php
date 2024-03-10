<?php
/**
 * Trip Controller
 */

namespace WPTravelEngine\Core\REST_API;

class TripController extends \WP_REST_Posts_Controller {

    /**
	 * Constructor.
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct() {
		$this->post_type = 'trip';
		$obj             = get_post_type_object( $this->post_type );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
		$this->namespace = 'wptravelengine/v1';

		$this->meta = new \WP_REST_Post_Meta_Fields( $this->post_type );

        add_filter( 'rest_trip_query', array( $this, 'rest_trip_query' ), 10, 2);
	}

    /**
	 * Registers the routes for posts.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		parent::register_routes();

	}

    /**
	 * Retrieves a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
        return parent::get_items( $request );
    }

    /**
     * Filters Rest Query Args.
     *
     * @param array $args
     * @param WP_REST_Request $request
     * @return array
     */
    public function rest_trip_query( $args, $request ) {
        if ( isset( $request['by'] ) ) {
            switch( $request['by'] ) {
                case 'featured':
                    $args['meta_key'] = 'wp_travel_engine_featured_trip';
                    $args['meta_value'] = 'yes';
                    break;
                case 'onsale':
                    $args['meta_key'] = '_s_has_sale';
                    $args['meta_value'] = 'yes';
                    break;
            }
        }
        return $args;
    }
}

$trip_controller = new TripController;
$trip_controller->register_routes();
