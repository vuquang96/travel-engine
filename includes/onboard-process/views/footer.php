<?php
/**
 * Setup wizard footer template.
 *
 * @package    WP_Travel_ngine
 * @subpackage WP_Travel_ngine
 */

if ( function_exists( 'wp_print_media_templates' ) ) {
	wp_print_media_templates();
}
do_action( 'admin_print_footer_scripts' );
wp_print_scripts( 'wpte-user-onboarding' );
wp_print_scripts( 'all' );
?>
</body>
</html>
