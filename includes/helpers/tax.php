<?php
/**
 *
 * Tax related functions.
 *
 * @since 5.6.8
 */

/**
 * Get Tax label
 *
 * @return string
 */
function wptravelengine_get_tax_label( $tax_percentage = null ) {
	$global_settings = get_option( 'wp_travel_engine_settings', array() );
	$label_format    = ! empty( $global_settings['tax_label'] ) ? $global_settings['tax_label'] : __( 'Tax (%s%%)', 'wp-travel-engine' );

	if ( is_null( $tax_percentage ) ) {
		$tax_percentage = wte_array_get( $global_settings, 'tax_percentage', 0  );
	}

	return apply_filters( __FUNCTION__,  sprintf( $label_format, $tax_percentage ), $tax_percentage );
}
