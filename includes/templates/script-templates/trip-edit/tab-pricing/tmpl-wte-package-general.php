<?php
use WPTravelEngine\Packages;

$pricing_categories = Packages\get_packages_pricing_categories();

$options = array();

foreach ( $pricing_categories as $pricing_category ) {
	$options[ $pricing_category->term_id ] = array(
		'label'      => $pricing_category->name,
		'attributes' => array(
			'selected' => '{{primaryCategory == ' . $pricing_category->term_id . " ? ' selected ' : ''}}",
		),
	);
}

$trip_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
?>

<script type="text/html" id="tmpl-wte-package-general">
	<#
	var tripPackage = data.tripPackage
	var idSuffix = '_' + tripPackage.id
	var index = +tripPackage.id
	var primaryCategory = tripPackage.primary_pricing_category

	var categories = {}
	#>
	<div class="wpte-block-content">
		<div class="wpte-block-heading">
			<h4><?php esc_html_e( 'General Package Settings', 'wp-travel-engine' ); ?></h4>
		</div>
		<?php

		$build_args = array(
			array(
				'label'       => __( 'Short Description', 'wp-travel-engine' ),
				'name'        => 'packages_descriptions[{{tripPackage.id}}]',
				'type'        => 'textarea',
				'value'       => '{{tripPackage.content.raw}}',
				'placeholder' => 'Add short description for pricing package..',
				'id'       => 'wpte-package-description-editor_{{tripPackage.id}}'
			),
		);

		$wpml_default_language = apply_filters( 'wpml_default_language', null );

		$original_trip_id = apply_filters( 'wpml_object_id', $trip_id, WP_TRAVEL_ENGINE_POST_TYPE, true, $wpml_default_language );

		if ( (int) $original_trip_id !== (int) $trip_id ) {
			$original_packages = get_post_meta( $original_trip_id, 'packages_ids', true );

			if ( is_array( $original_packages ) ) {
				$packages_selector_options = array();
				foreach ( $original_packages as $original_package ) {
					$packages_selector_options[ $original_package ] = array(
						'label' => get_the_title( $original_package )
					);
				}
				$build_args[] = array(
					'label'       => __( 'Original Package', 'wp-travel-engine' ),
					'name'        => 'packages_original_package[{{tripPackage.id}}]',
					'type'        => 'select',
					'options'     => $packages_selector_options,
					'value'       => '{{tripPackage.content.raw}}',
					'placeholder' => 'Add short description for pricing package..',
				);
			}
		}

		$field_builder = new WTE_Field_Builder_Admin( $build_args );

		$field_builder->render();
		?>
	</div>
</script>
