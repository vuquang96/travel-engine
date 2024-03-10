<?php
/**
 *
 * Main Rest Controller.
 *
 * @since 5.6.0
 */
namespace WPTravelEngine\Core\REST_API;

class Controller extends \WP_REST_Controller {

	protected $namespace = 'wptravelengine/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

}
