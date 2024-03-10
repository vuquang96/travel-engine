<?php
/**
 * Alternative Text/Option to change static label.
 *
 * @since 5.7.3
 */

$wptravelengine_new_labels = get_option( 'wptravelengine_custom_strings', array() );
?>

<div class="wpte-field">
	<div class="wpte-info-block">
		<?php _e( 'The Custom Labels feature in our plugin provides you with the flexibility to personalize static strings on your website. For instance, if the default label in the plugin setting is "Travellers," you can modify it to "Travelers." This feature can also serve as a basic tool for translation. <br>
		Please note, this feature leverages the __() translation function in WordPress and is designed for simple, static strings. It may not support complex or lengthy strings. For advanced modifications or longer strings, you might need to explore alternative solutions or seek professional assistance.', 'wp-travel-engine' ); ?>
	</div>
</div>
<div class="wpte-field wpte-floated" id="wptravelengine-settings_display_labels">
	<div class="wpte-label-table-wrap">
		<table class="table wpte-label-table" id="wte-label-table">
			<thead>
				<tr>
					<th>
						<?php esc_html_e( 'Initial Label', 'wp-travel-engine' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'Modified Label', 'wp-travel-engine' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<input type="hidden" name="wptravelengine_custom_strings" />
				<?php
				if ( ! empty( $wptravelengine_new_labels ) ) {
					foreach ( $wptravelengine_new_labels as $key => $value ) {
						?>
						<tr>
							<td>
								<input type="text" name="wptravelengine_custom_strings[<?php echo $key; ?>][initial_label]" id="wptravelengine_custom_strings_<?php echo $key; ?>_initial_label" value="<?php echo esc_attr( $value['initial_label'] ); ?>" />
							</td>
							<td>
								<input type="text" name="wptravelengine_custom_strings[<?php echo $key; ?>][modified_label]" id="wptravelengine_custom_strings_<?php echo $key; ?>_modified_label" value="<?php echo esc_attr( $value['modified_label'] ); ?>" />
								<button class="delete-row-button"></button>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<button class="add-row-button">+</button>
	</div>
</div>
