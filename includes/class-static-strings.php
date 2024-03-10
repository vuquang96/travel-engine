<?php
/**
 * String Translation.
 *
 *
 * @since 5.7.3
 */
namespace WPTravelEngine;

class StaticStrings {

	protected static $custom_strings = array();

	protected static $option_key = 'wptravelengine_custom_strings';

	/**
	 * Register custom strings array.
	 */
	public static function init() {
		$custom_strings = get_option( static::$option_key );
		if ( ! is_array( $custom_strings ) ) {
			$custom_strings = array();
			add_option( static::$option_key, $custom_strings );
		}

		static::$custom_strings = $custom_strings;

		add_filter( 'gettext_wp-travel-engine', array( __CLASS__, 'translateString' ), 11, 3 );

		add_action( 'wpte_after_save_global_settings_data', [ __CLASS__, 'save_custom_strings_settings' ] );
	}

	/**
	 * Saves modified labels to DB.
	 * Removes labels from DB.
	 *
	 * @param array $posted_data Posted Data.
	 * @since 5.7.3
	 */
	public static function save_custom_strings_settings( $posted_data ) {
		if ( isset( $posted_data[self::$option_key] ) ) {
			if ( is_string( $posted_data[self::$option_key] ) ) {
				update_option( self::$option_key, array() );
				return;
			}
			$custom_strings = array();
			foreach ( $posted_data[self::$option_key] as $value ) {
				$label_key      = sanitize_key( $value['initial_label'] );

				$custom_strings[ $label_key ] = array(
					'initial_label' => sanitize_text_field( $value['initial_label'] ),
					'modified_label' => sanitize_text_field( $value['modified_label'] )
				);
			}
			update_option( self::$option_key, $custom_strings );
		}
	}

	/**
	 * Add custom string and alternative translation.
	 *
	 * @param string $id           Unique identifier for the string.
	 * @param string $translation  Alternative translation for the string.
	 */
	public static function addCustomString( $id, $translation ) {
		if ( ! isset( $custom_strings[ sanitize_key( $id ) ] ) ) {
			static::$custom_strings[ sanitize_key( $id ) ] = $translation;
			update_option( static::$option_key, static::$custom_strings );
		}
	}

	/**
	 * Replace translated strings with custom alternatives.
	 *
	 * @param string $translated  Translated string.
	 * @param string $original    Original string to be translated.
	 * @param string $domain      Text domain.
	 * @return string             Custom translation if available, otherwise the translated string.
	 */
	public static function translateString( $translated, $original, $domain ) {
		$custom_strings = static::$custom_strings;
		$key = sanitize_key( $translated );

		return isset( $custom_strings[ $key ] ) ? $custom_strings[ $key ]['modified_label'] : $translated;

	}
}

// Register the hooks.
add_action( 'init', [ __NAMESPACE__ . '\\StaticStrings', 'init' ] );
