<?php
/**
 * Booking Inventory.
 *
 * @since 5.5.3
 */
namespace WPTravelEngine\Core;

class Booking_Inventory {

	/**
	 * Booking Object.
	 */
	protected $trip_booking;

	public function set( $key, $value ) {
		$this->{$key} = $value;
	}

	public static function get_original_trip_id( $trip_id ) {

		$original_trip_id = apply_filters( 'wpml_object_id', (int) $trip_id, WP_TRAVEL_ENGINE_POST_TYPE, true, apply_filters( 'wpml_default_language', null ) );

		return $original_trip_id;
	}

	public function get_booking_inventory_record( $trip_id ) {

		$original_trip_id = self::get_original_trip_id( $trip_id );

		$dates = get_post_meta( $original_trip_id, '_booking_inventory', true );

		if ( ! is_array( $dates ) ) {
			$dates = array();
		}

		return $dates;

	}

	public function update_booking_inventory_record( $trip_id, $data = array() ) {
		update_post_meta( self::get_original_trip_id( $trip_id ), '_booking_inventory', $data );
	}

	public function update_pax( $date_key, $pax = 0, $trip_id = 0, $booking_id = 0 ) {
		if ( ! $booking_id ) {
			return;
		}

		list( $prefix, $trip_id, $price_key, $trip_date, $trip_time ) = explode( '_', $date_key );

		$trip_id    = self::get_original_trip_id( $trip_id );
		$_price_key = get_post_meta( $price_key, '_original_package_id', true );
		if ( ! empty( $_price_key ) ) {
			$price_key = $_price_key;
		}

		$date_key = "cart_{$trip_id}_{$price_key}_{$trip_date}_{$trip_time}";

		$dates = $this->get_booking_inventory_record( $trip_id );
		if ( $pax <= 0 ) {
			unset( $dates[ $date_key ][ $booking_id ] );
		} else {
			$dates[ $date_key ][ $booking_id ] = $pax;
		}
		$this->update_booking_inventory_record( $trip_id, $dates );
	}

	public function update_inventory_by_booking( $booking ) {
		$items = get_post_meta( $booking->ID, 'order_trips', true );

		foreach ( $items as $cart_key => $item ) {
			$item    = (object) $item;
			$trip_id = $item->ID;

			$pax = 0;
			if ( is_array( $item->pax ) ) {
				$pax = array_sum( $item->pax );
			}

			if ( $pax <= 0 ) {
				continue;
			}

			$this->update_pax( $cart_key, $pax, $trip_id, $booking->ID );
		}
	}

	public static function get_date_from_cart_key( $cart_key ) {
		preg_match( '/(cart)_(\d+)_(\d+)_([\d-]+)_([\d-]+)/', $cart_key, $chunks );
		$datetime = new \DateTime();

		$datetime->setTimezone( new \DateTimeZone( 'utc' ) );
		$datetime->setDate( ...explode( '-', $chunks[4] ) );
		$datetime->setTime( ...explode( '-', $chunks[5] ) );

		return $datetime;
	}

	public static function booking_inventory( $data, $trip_id ) {

		$original_trip_id = self::get_original_trip_id( $trip_id );

		$instance   = new self();
		$inventory  = $instance->get_booking_inventory_record( $original_trip_id );
		$_inventory = array();
		if ( is_array( $inventory ) ) {
			foreach ( $inventory as $cart_id => $pax ) {
				$datetime                 = self::get_date_from_cart_key( $cart_id );
				$timestamp                = $datetime->getTimestamp();
				$_inventory[ $timestamp ] = array(
					'booked'  => array_sum( $pax ),
					'datestr' => $timestamp,
				);
				$pattern                  = '/cart_\d+_(\d+)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}/';
				$package                  = 0;
				if ( preg_match( $pattern, $cart_id, $matches ) ) {
					$package = $matches[1];
				}

				// Added package specific data to fix multiple package with same date issue.
				$_inventory[ $timestamp + $package ] = array(
					'booked'  => array_sum( $pax ),
					'datestr' => $timestamp + $package,
				);
			}
		}

		$old_booking_record = get_post_meta( $original_trip_id, 'wte_fsd_booked_seats', true );
		if ( is_array( $old_booking_record ) ) {
			$_inventory = array_column( array_merge( $old_booking_record, $_inventory ), null, 'datestr' );
		}

		return $_inventory;

	}
}
