<?php
/**
 * Enquiry Email Settings.
 *
 * @since 5.7.1
 *
 * @package WP_Travel_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$wp_travel_engine_settings = get_option( 'wp_travel_engine_settings', array() );
$admin_email               = get_option( 'admin_email' );
$enquiry_emailaddress      = ! empty( $wp_travel_engine_settings['email']['enquiry_emailaddress'] ) ? $wp_travel_engine_settings['email']['enquiry_emailaddress'] : $admin_email;
?>
<div class="wpte-field wpte-email wpte-floated">
	<label class="wpte-field-label" for="wp_travel_engine_settings[email][enquiry_emailaddress]">
		<?php esc_html_e( 'Notification Email(s)', 'wp-travel-engine' ); ?>
	</label>
	<input type="text" name="wp_travel_engine_settings[email][enquiry_emailaddress]" id="wp_travel_engine_settings[email][enquiry_emailaddress]"
		value="<?php echo esc_attr( $enquiry_emailaddress ); ?>" />
	<span class="wpte-tooltip">
		<?php esc_html_e( 'Enter the email address(es) to receive notifications whenever an enquiry is made. Separate multiple addresses with a comma (,) without spaces.', 'wp-travel-engine' ); ?>
	</span>
</div>
<?php
$enable_customer_notification = isset( $wp_travel_engine_settings['email']['cust_notif'] ) ? $wp_travel_engine_settings['email']['cust_notif'] : ''
	?>
<div class="wpte-field wpte-checkbox advance-checkbox">
	<label class="wpte-field-label " for="enable-customer-enquiry-notification">
		<?php echo __( 'Enable Customer Enquiry Notification', 'wp-travel-engine' ); ?>
	</label>
	<div class="wpte-checkbox-wrap">
		<input type="checkbox"
			name="wp_travel_engine_settings[email][cust_notif]"
			<?php checked( '1', $enable_customer_notification ); ?>
			value="1" id="enable-customer-enquiry-notification">
		<label for="enable-customer-enquiry-notification"></label>
	</div>
	<span class="wpte-tooltip">
		<?php _e( 'Enable this to send enquiry notification emails to the customer.', 'wp-travel-engine' ); ?>
	</span>
</div>

<!-- Custom Enquiry Form -->
<div class="wpte-field wpte-checkbox advance-checkbox">
	<label class="wpte-field-label" for="wp_travel_engine_settings[custom_enquiry]">
		<?php esc_html_e( 'Custom Enquiry Form', 'wp-travel-engine' ); ?>
	</label>
	<div class="wpte-checkbox-wrap">
		<input type="hidden" name="wp_travel_engine_settings[custom_enquiry]" value="">
		<input type="checkbox"
			id="wp_travel_engine_settings[custom_enquiry]"
			class="custom-enquiry"
			name="wp_travel_engine_settings[custom_enquiry]"
			value="yes"
			data-onchange
			data-onchange-toggle-target="[data-show-custom-enquiry-shortcode]"
			data-onchange-toggle-off-value="no"
			<?php checked( isset( $wp_travel_engine_settings['custom_enquiry'] ) && '' !== $wp_travel_engine_settings['custom_enquiry'], true ); ?> />
		<label for="wp_travel_engine_settings[custom_enquiry]"></label>
	</div>
	<span class="wpte-tooltip">
		<?php esc_html_e( 'Enable this feature to utilize a custom form. Please ensure that a notification email is included within the custom form.', 'wp-travel-engine' ); ?>
	</span>
</div>

<!-- Shortcode for Enquiry Forms -->
<div class="wpte-field wpte-floated <?php echo ( ! isset( $wp_travel_engine_settings['custom_enquiry'] ) || 'yes' !== $wp_travel_engine_settings['custom_enquiry'] ) ? 'hidden' : ''; ?>" data-show-custom-enquiry-shortcode>
	<label class="wpte-field-label" for="wp_travel_engine_settings[enquiry_shortcode]">
		<?php esc_html_e( 'Form Shortcode', 'wp-travel-engine' ); ?>
	</label>
	<input type="text"
		id="wp_travel_engine_settings[enquiry_shortcode]"
		name="wp_travel_engine_settings[enquiry_shortcode]"
		value="<?php echo isset( $wp_travel_engine_settings['enquiry_shortcode'] ) ? esc_attr( $wp_travel_engine_settings['enquiry_shortcode'] ) : ''; ?>" />
	<span class="wpte-tooltip">
		<?php esc_html_e( 'Add the custom form shortcode. We have made WP Travel Engine compatible with popular forms likes Gravity Form, Ninja Forms and WPForms.', 'wp-travel-engine' ); ?>
	</span>
</div>

<!-- Hide Enquiry Form -->
<div class="wpte-field wpte-checkbox advance-checkbox">
	<label class="wpte-field-label" for="wp_travel_engine_settings[enquiry]">
		<?php esc_html_e( 'Hide Enquiry Form', 'wp-travel-engine' ); ?>
	</label>
	<div class="wpte-checkbox-wrap">
		<input type="hidden" name="wp_travel_engine_settings[enquiry]" value="">
		<input type="checkbox"
			id="wp_travel_engine_settings[enquiry]"
			class="hide-enquiry"
			name="wp_travel_engine_settings[enquiry]"
			value="1"
			<?php checked( isset( $wp_travel_engine_settings['enquiry'] ) && '' !== $wp_travel_engine_settings['enquiry'], true ); ?> />
		<label for="wp_travel_engine_settings[enquiry]"></label>
	</div>
	<span class="wpte-tooltip">
		<?php esc_html_e( 'Enable this to hide the enquiry form on Trip Page..', 'wp-travel-engine' ); ?>
	</span>
</div>
