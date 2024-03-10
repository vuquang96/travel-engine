<?php
/**
 * Single Trip Content
 *
 * This template can be overridden by copying it to yourtheme/wp-travel-engine/single-trip/trip-content-wrapper-end.php.
 *
 * @package Wp_Travel_Engine
 * @subpackage Wp_Travel_Engine/includes/templates
 * @since @release-version //TODO: change after travel muni is live
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = get_option( 'wp_travel_engine_settings', array() );
if ( empty( $settings['enquiry'] ) ) {
	/**
	 * Custom Enquiry Form Check
	 *
	 * @since 5.7.1
	 */
	if ( ! isset( $settings['custom_enquiry'] ) || ( isset( $settings['custom_enquiry'] ) && empty( $settings['custom_enquiry'] ) ) ) {
		do_action( 'wp_travel_engine_enquiry_form' );
	}

	/**
	 * Custom Enquiry Form Check
	 */
	$custom_enquiry_form_shortcode = ( isset( $settings['enquiry_shortcode'] ) && '' !== $settings['enquiry_shortcode'] ) ? $settings['enquiry_shortcode'] : '';
	if ( isset( $settings['custom_enquiry'] ) && ! empty( $settings['custom_enquiry'] ) && ! empty( $custom_enquiry_form_shortcode ) ) {
		/**
		* Check for multiple shortcodes in the custom enquiry form shortcode.
		* If multiple shortcodes are found, then only the first shortcode will be used.
		*/
		preg_match( '/\[([^\]]+)\]/', $custom_enquiry_form_shortcode, $matches );
		if ( ! empty( $matches ) ) {
			?> <div id="wte_enquiry_form_scroll_wrapper" class="wte_enquiry_contact_form-wrap"> <?php
			echo do_shortcode( '[' . $matches[1] . ']' );
			?> </div> <?php
		}
	}
}
?>
</div>
<!-- /#primary -->
<?php
/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
