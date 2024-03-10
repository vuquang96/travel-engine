<?php
global $post;
$wp_travel_engine_postmeta_settings = get_post_meta( $post->ID, 'wp_travel_engine_booking_setting', true );

$fields = array(
	'fname' => array(
		'label' => __( 'First Name', 'wp-travel-engine' ),
	),
	'lname' => array(
		'label' => __( 'Last Name', 'wp-travel-engine' ),
	),
	'email' => array(
		'label' => __( 'Email', 'wp-travel-engine' ),
		'field_type' => 'email',
		'readonly' => isset( $wp_travel_engine_postmeta_settings['place_order']['booking']['email'] ) && ! empty( $wp_travel_engine_postmeta_settings['place_order']['booking']['email'] ),
	),
	'address' => array(
		'label' => __( 'Address', 'wp-travel-engine' ),
	),
	'city' => array(
		'label' => __( 'City', 'wp-travel-engine' ),
	),
	'country' => array(
		'label' => __( 'Country', 'wp-travel-engine' ),
	),
	'postcode' => array(
		'label' => __( 'Post Code', 'wp-travel-engine' )
	)
);

$data_mode = ! isset( $wp_travel_engine_postmeta_settings['place_order']['booking'] ) ? 'edit' : 'readonly';
?>
	<div class="customer-info-meta">
		<div class="wpte-block">
			<div class="wpte-block-content" style="position: relative;">
				<div class="wpte-button-wrap wpte-edit-customer-detail" style="position:absolute;right:0;top:0;">
					<a href="#" class="wpte-btn-transparent wpte-btn-sm wpte-edit-customer-detail-btn" data-target="#wpte-customer-details">
						<svg fill="currentColor" data-prefix="fas" data-icon="pencil-alt" xmlns="http://www.w3.org/2000/svg" class="svg-inline--fa" viewBox="0 0 512 512" height="24" width="24"><path d="M421.7 220.3L188.5 453.4L154.6 419.5L158.1 416H112C103.2 416 96 408.8 96 400V353.9L92.51 357.4C87.78 362.2 84.31 368 82.42 374.4L59.44 452.6L137.6 429.6C143.1 427.7 149.8 424.2 154.6 419.5L188.5 453.4C178.1 463.8 165.2 471.5 151.1 475.6L30.77 511C22.35 513.5 13.24 511.2 7.03 504.1C.8198 498.8-1.502 489.7 .976 481.2L36.37 360.9C40.53 346.8 48.16 333.9 58.57 323.5L291.7 90.34L421.7 220.3zM492.7 58.75C517.7 83.74 517.7 124.3 492.7 149.3L444.3 197.7L314.3 67.72L362.7 19.32C387.7-5.678 428.3-5.678 453.3 19.32L492.7 58.75z"></path></svg>				Edit			</a>
				</div>
				<ul class="wpte-list" id="wpte-customer-details" data-mode="<?php echo esc_attr( $data_mode ); ?>">
					<li>
						<b><?php esc_html_e( 'Customer ID', 'wp-travel-engine' ); ?></b>
						<input type="text" readonly value="<?php echo esc_attr( $post->ID ); ?>"/>
						<span>
							<?php echo esc_attr( $post->ID ); ?>
						</span>
					</li>
					<?php
					foreach ( $fields as $field_name => $field ) {
						$field_value = '';
						if ( isset( $wp_travel_engine_postmeta_settings['place_order']['booking'][$field_name] ) ) {
							$field_value = $wp_travel_engine_postmeta_settings['place_order']['booking'][$field_name];
						}
						?>
						<li>
							<b><?php echo esc_html( $field['label'] ); ?></b>
							<input
								value="<?php echo esc_attr( $field_value ); ?>"
								name="<?php echo esc_attr( 'edit' === $data_mode ? 'wp_travel_engine_booking_setting[place_order][booking][' . esc_attr( $field_name ) . ']'  : '' ); ?>"
								data-name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $field_name ); ?>]"
								<?php isset( $field['readonly'] ) && true === $field['readonly'] && print( 'readonly' ); ?>
								type="<?php echo isset( $field['field_type'] ) ? $field['field_type'] : 'text'; ?>" />
							<span>
								<?php echo esc_html( $field_value ); ?>
							</span>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
		<script>
		;(function() {
			document.addEventListener('click', function(event) {
				if(event.target.matches('.wpte-edit-customer-detail-btn')) {
					var target = document.querySelector(event.target.dataset.target)
					if(target) {
						if(target.dataset.mode === 'readonly') {
							event.target.remove()
							target.dataset.mode = 'edit'
							var inputs = target.querySelectorAll('input[data-name]')
							if(inputs) {
								inputs.forEach(function(_input) {
									_input.name = _input.dataset.name
								})
							}
						}
					}
				}
			})
		})();
		</script>
	</div>
<?php
