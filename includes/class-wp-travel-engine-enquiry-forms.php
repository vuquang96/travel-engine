<?php
/**
 * Catch Custom Enquiry Form Data
 * to dissplay in WP Travel Engine
 * Enquiries Page
 *
 * @since 5.7.1
 *
 * @package WP_Travel_Engine
 */
class WP_Travel_Engine_Enquiry_Forms {
	/**
	 * Catch Ninja Forms Data
	 *
	 * @param array $form_data Form Data.
	 */
	public function catch_ninja_forms_data( $form_data ) {

		$new_post = array(
			'post_title'  => 'enquiry',
			'post_status' => 'publish',
			'post_type'   => 'enquiry',
		);

		$post_id = wp_insert_post( $new_post );

		if ( ! $post_id ) {
			return false;
		}

		if ( ! is_wp_error( $post_id ) ) {
			do_action( 'wte_after_enquiry_created', $post_id );
		}

		foreach ( $form_data['fields'] as $field ) {
			$label                       = $field['label'];
			$value                       = $field['value'];
			$entry_fields[ $label ]      = $value;
			$entry_fields['Plugin Name'] = 'Ninja Forms';
		}
		add_post_meta( $post_id, 'wp_travel_engine_setting', $entry_fields );
		add_post_meta( $post_id, 'wp_travel_engine_enquiry_formdata', $entry_fields );

		$post_data = array(
			'ID'         => $post_id,
			'post_title' => 'Enquiry #' . $post_id,
		);

		wp_update_post( $post_data );
	}

	/**
	 * Catch WPForms Data.
	 *
	 * @param array $message   Message.
	 * @param array $form_data Form Data.
	 */
	public function catch_wpforms_data( $message, $form_data ) {
		$new_post = array(
			'post_title'  => 'enquiry',
			'post_status' => 'publish',
			'post_type'   => 'enquiry',
		);

		$post_id = wp_insert_post( $new_post );

		if ( ! $post_id ) {
			return false;
		}

		if ( ! is_wp_error( $post_id ) ) {
			do_action( 'wte_after_enquiry_created', $post_id );
		}

		$field_values = wp_unslash( $_POST['wpforms']['fields'] );

		foreach ( $form_data['fields'] as $field ) {
			$label                       = $field['label'];
			$value                       = $field_values[ $field['id'] ];
			$entry_fields[ $label ]      = $value;
			$entry_fields['form_id']     = $form_data['id'];
			$entry_fields['Plugin Name'] = 'WPForms';
		}
		add_post_meta( $post_id, 'wp_travel_engine_setting', $entry_fields );
		add_post_meta( $post_id, 'wp_travel_engine_enquiry_formdata', $entry_fields );

		$post_data = array(
			'ID'         => $post_id,
			'post_title' => 'Enquiry #' . $post_id,
		);

		wp_update_post( $post_data );

		return $message;
	}

	/**
	 * Catch Gravity Forms Data
	 *
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data.
	 */
	public function catch_gravity_forms_data( $entry, $form_data ) {

		$new_post = array(
			'post_title'  => 'enquiry',
			'post_status' => 'publish',
			'post_type'   => 'enquiry',
		);

		$post_id = wp_insert_post( $new_post );

		if ( ! $post_id ) {
			return false;
		}

		if ( ! is_wp_error( $post_id ) ) {
			do_action( 'wte_after_enquiry_created', $post_id );
		}

		foreach ( $form_data['fields'] as $field ) {
			$inputs = $field->get_entry_inputs();
			if ( is_array( $inputs ) ) {
				foreach ( $inputs as $input ) {
					$label                  = $input['label'];
					$value                  = $entry[ $input['id'] ];
					$entry_fields[ $label ] = $value;
				}
			} else {
				$label                  = $field->label;
				$value                  = $entry[ $field->id ];
				$entry_fields[ $label ] = $value;
			}
			$entry_fields['form_id']     = $form_data['id'];
			$entry_fields['entry_id']    = $entry['id'];
			$entry_fields['Plugin Name'] = 'Gravity Forms';
		}
		add_post_meta( $post_id, 'wp_travel_engine_setting', $entry_fields );
		add_post_meta( $post_id, 'wp_travel_engine_enquiry_formdata', $entry_fields );

		$post_data = array(
			'ID'         => $post_id,
			'post_title' => 'Enquiry #' . $post_id,
		);

		wp_update_post( $post_data );
	}
}

