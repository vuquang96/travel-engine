<?php
/**
 * Helper functions for analytics dashboard.
 *
 * @since 5.7
 * @package WP_Travel_Engine
 */

/**
 * Analytics Data.
 *
 * @param string $start_date Start Date.
 * @param string $end_date End Date.
 */
function wptravelengine_analytics_totals( $start_date, $end_date ) {
	$data                  = array();
	$datefilters_totals    = array();
	$datefilters_customers = array();
	$datefilters_refunds   = array();
	$totals                = wptravelengine_analytics_queries_without_date( 'total' );
	$total_refunds         = wptravelengine_analytics_queries_without_date( 'refunds' );
	$total_customers       = wptravelengine_analytics_queries_without_date( 'customer' );
	if ( '' != $start_date || '' != $end_date ) {
		$datefilters_totals    = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'total' );
		$datefilters_aov       = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'aov' );
		$datefilters_customers = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'customer' );
		$datefilters_refunds   = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'refunds' );
		$datefilters_bookings  = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'bookings' );
		$data                  = array(
			'datefilters_totals'    => $datefilters_totals,
			'datefilters_aov'       => $datefilters_aov,
			'datefilters_customers' => $datefilters_customers,
			'datefilters_refunds'   => $datefilters_refunds,
			'datefilters_bookings'  => $datefilters_bookings,
			'start_date'            => $start_date,
			'end_date'              => $end_date,
			'totals'                => $totals,
			'total_refunds'         => $total_refunds,
			'total_customers'       => $total_customers,
		);
	} else {
		$data = array(
			'totals'          => $totals,
			'total_refunds'   => $total_refunds,
			'total_customers' => $total_customers,
		);
	}
	$data = wptravelengine_analytics_data( $data );
	return $data;
}

/**
 * Analytics Queries with Date.
 *
 * @param string $start_date Start Date.
 * @param string $end_date End Date.
 * @param string $query Query Type.
 */
function wptravelengine_analytics_queries_with_date( $start_date, $end_date, $query ) {
	global $wpdb;
	$queries_data = array();
	$range        = date_diff( date_create( $start_date ), date_create( $end_date ) );
	if ( $range->days <= 90 ) {
		if ( 'total' === $query ) {
			$queries_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) as total_amount, SUM(pm.meta_value)/(COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) as total_earnings, DATE_FORMAT(p.post_date, '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND p.post_date >= '$start_date 00:00:00' AND p.post_date <= '$end_date 23:59:59' GROUP BY days ORDER BY days DESC" );
		}
	}
	if ( $range->days > 90 && $range->days <= 365 ) {
		if ( 'total' === $query ) {
			$queries_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) as total_amount, SUM(pm.meta_value)/(COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) as total_earnings, DATE_FORMAT(p.post_date, '%Y-%m') AS months FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND p.post_date >= '$start_date 00:00:00' AND p.post_date <= '$end_date 23:59:59' GROUP BY months ORDER BY months DESC" );
		}
	}
	if ( $range->days >= 365 ) {
		if ( 'total' === $query ) {
			$queries_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) as total_amount, SUM(pm.meta_value)/(COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) as total_earnings, DATE_FORMAT(p.post_date, '%Y') AS year FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND p.post_date >= '$start_date 00:00:00' AND p.post_date <= '$end_date 23:59:59' GROUP BY year ORDER BY year DESC" );
		}
	}
	if ( 'aov' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND p.post_date >= '$start_date 00:00:00' AND p.post_date <= '$end_date 23:59:59'" );
	}
	if ( 'customer' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT COUNT(*) as customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59'" );
	}
	if ( 'refunds' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59'))" );
	}
	if ( 'bookings' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND p.post_date >= '$start_date 00:00:00' AND p.post_date <= '$end_date 23:59:59'" );
	}
	if ( 'total_customer' === $query ) {
		$start_date   = '';
		$queries_data = $wpdb->get_results( "SELECT COUNT(*) as total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59'" );
	}
	if ( 'top_customer' === $query ) {
		$serialized_data = array();
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59') ORDER BY bookings DESC LIMIT 1" );

		// Unserialize the cost section and add them for respective customer_id.
		foreach ( $serialized_data as $data ) {
			$first_name     = str_replace( '"', '', $data->first_name );
			$last_name      = str_replace( '"', '', $data->last_name );
			$customer_name  = $first_name . ' ' . $last_name;
			$id             = $data->customer_id;
			$booked_trip    = $data->bookings;
			$queries_data[] = array(
				'customer_name' => array(
					'title' => html_entity_decode( $customer_name ),
					'url'   => add_query_arg(
						array(
							'post'   => $id,
							'action' => 'edit',
						),
						admin_url( 'post.php' )
					),
				),
				'booked_trip'   => $booked_trip,
			);
		}
	}
	if ( 'best_seller' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) as sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59') GROUP BY trip_id ORDER BY sales_count DESC" );
	}
	if ( 'top_performer' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59') GROUP BY trip_id ORDER BY total_earnings DESC LIMIT 1" );
	}
	if ( 'taxonomy' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND due_amount.meta_key = 'due_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND post_date >= '$start_date 00:00:00' AND post_date <= '$end_date 23:59:59') GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	return $queries_data;
}

/**
 * Analytics Queries without Date.
 *
 * @param string $query Query Type.
 */
function wptravelengine_analytics_queries_without_date( $query ) {
	global $wpdb;
	$queries_data = array();
	if ( 'total' === $query ) {
		$queries_data['range'] = $wpdb->get_results( "SELECT SUM(pm.meta_value) as total_amount, SUM(pm.meta_value)/(COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) as total_earnings, YEAR(p.post_date) as year, MONTH(p.post_date) as month FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') GROUP BY year, month ORDER BY year DESC, month DESC" );
		$queries_data['chart'] = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND MONTH(p.post_date) = MONTH(NOW()) GROUP BY days ORDER BY days DESC" );
	}
	if ( 'customer' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT COUNT(*) as customer_count, YEAR(post_date) AS year, MONTH(post_date) AS month FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') GROUP BY year, month ORDER BY year DESC, month DESC" );
	}
	if ( 'refunds' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, YEAR(p.post_date) AS year, MONTH(p.post_date) AS month FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') ) ) GROUP BY YEAR(p.post_date), MONTH(p.post_date) ORDER BY YEAR(p.post_date) DESC, MONTH(p.post_date) DESC" );
	}
	if ( 'total_customer' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT COUNT(*) as total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft')" );
	}
	if ( 'top_customer' === $query ) {
		$serialized_data = array();
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(CURRENT_DATE()) AND MONTH(post_date) = MONTH(CURRENT_DATE())) ORDER BY bookings DESC LIMIT 1" );

		// Unserialize the cost section and add them for respective customer_id.
		foreach ( $serialized_data as $data ) {
			$first_name     = str_replace( '"', '', $data->first_name );
			$last_name      = str_replace( '"', '', $data->last_name );
			$customer_name  = $first_name . ' ' . $last_name;
			$id             = $data->customer_id;
			$booked_trip    = $data->bookings;
			$queries_data[] = array(
				'customer_name' => array(
					'title' => html_entity_decode( $customer_name ),
					'url'   => add_query_arg(
						array(
							'post'   => $id,
							'action' => 'edit',
						),
						admin_url( 'post.php' )
					),
				),
				'booked_trip'   => $booked_trip,
			);
		}
	}
	if ( 'popular_trip' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id,COUNT(*) as trip_count, YEAR(p.post_date) AS YEAR, MONTH(p.post_date) AS MONTH, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount',due_amount.meta_value,0)) as total_bookings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0))/COUNT(*) as avg_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id LEFT JOIN {$wpdb->posts} AS p ON p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND  order_trips.post_id = p.id WHERE order_trips.meta_key = 'order_trips' AND SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='trip' AND post_status IN ('publish', 'draft')) AND paid_amount.meta_key = 'paid_amount' AND paid_amount.post_id = order_trips.post_id AND due_amount.meta_key = 'due_amount' AND due_amount.post_id = order_trips.post_id AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') ) GROUP BY SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1), YEAR, MONTH ORDER BY YEAR DESC, MONTH DESC, trip_count DESC LIMIT 0, 10" );
	}
	return $queries_data;
}

/**
 * Calculation of analytics data.
 *
 * @param array $data Analytics Data.
 */
function wptravelengine_analytics_data( $data ) {
	$data                 = $data;
	$sales_data           = array();
	$datefilters_totals   = array();
	$total_refunds        = array();
	$total_customers      = array();
	$chart_total_amount   = '';
	$chart_earnings       = '';
	$chart_aov            = '';
	$chart_customer_count = '';
	$chart_refunds        = '';
	$chart_bookings       = '';
	$filtered_base        = '';
	if ( isset( $data['end_date'] ) && '' != $data['end_date'] ) {
		$end_date              = $data['end_date'];
		$start_date            = $data['start_date'];
		$datefilters_totals    = $data['datefilters_totals'];
		$datefilters_aov       = $data['datefilters_aov'];
		$datefilters_customers = $data['datefilters_customers'];
		$datefilters_refunds   = $data['datefilters_refunds'];
		$datefilters_bookings  = $data['datefilters_bookings'];
		$parts                 = explode( '-', $end_date );
		$current_year          = $parts[0];
		$current_month         = $parts[1];
		$totals                = $data['totals'];
		$total_refunds         = $data['total_refunds'];
		$total_customers       = $data['total_customers'];
	} else {
		$current_year    = wp_date( 'Y' );
		$current_month   = wp_date( 'n' );
		$totals          = $data['totals'];
		$total_refunds   = $data['total_refunds'];
		$total_customers = $data['total_customers'];
	}
	$sales = array();

	if ( isset( $totals ) && ! ( count( $datefilters_totals ) > 0 ) ) {
		$_range = array();

		foreach ( $totals['chart'] as $results ) {
			$_days                = wp_date( 'j', strtotime( $results->days ) );
			$sales_data[ $_days ] = array(
				'total_amount'        => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $results->total_amount ) ) ),
				'average_order_value' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $results->average_order_value ) ) ),
				'total_earnings'      => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $results->total_earnings ) ) ),
				'days'                => wp_date( 'F', strtotime( $results->days ) ) . ' ' . $_days,
			);
		}
		$today          = wp_date( 'Y-m-d', strtotime( 'today' ) );
		$start_of_month = wp_date( 'Y-m-01' );
		$date1          = new DateTime( $today );
		$date2          = new DateTime( $start_of_month );
		$interval       = $date2->diff( $date1 );
		$month_name     = wp_date( 'F', strtotime( 'this month' ) );
		for ( $i = 1;$i <= $interval->days + 1; $i++ ) {
			$sales[] = isset( $sales_data [ $i ] ) ? $sales_data [ $i ] : array(
				'total_amount'        => 0,
				'average_order_value' => 0,
				'total_earnings'      => 0,
				'days'                => $month_name . ' ' . $i,
			);
		}
	}

	// Refunds data.
	$refund_data = array();
	$refunds     = array();
	foreach ( $total_refunds as $refund ) {
		$year          = $refund->year;
		$month         = $refund->month;
		$refund_amount = $refund->total_refunds;
		if ( ( $year == $current_year && $month >= $current_month - 1 ) || ( $year == $current_year - 1 && $month >= $current_month + 1 ) ) {
			$key                 = ( $year * 100 ) + $month;
			$refund_data[ $key ] = array(
				'year'          => $year,
				'month'         => $month,
				'total_refunds' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $refund_amount ) ) ),
			);
		}
	}
	for ( $i = 0; $i <= 1; $i++ ) {
		$year       = wp_date( 'Y', strtotime( "-{$i} months", strtotime( "{$current_year}-{$current_month}-01" ) ) );
		$month      = wp_date( 'n', strtotime( "-{$i} months", strtotime( "{$current_year}-{$current_month}-01" ) ) );
		$month_name = wp_date( 'F', mktime( 0, 0, 0, $month, 10 ) );
		$key        = ( $year * 100 ) + $month;
		$refunds[]  = isset( $refund_data[ $key ] ) ? $refund_data[ $key ] : array(
			'year'          => $year,
			'month'         => $month,
			'total_refunds' => 0,
		);
	}

	// Customers Data.
	$customer_data = array();
	$customers     = array();
	foreach ( $total_customers as $customer ) {
		$year           = $customer->year;
		$month          = $customer->month;
		$customer_count = $customer->customer_count;
		if ( ( $year == $current_year && $month >= $current_month - 1 ) || ( $year == $current_year - 1 && $month >= $current_month + 1 ) ) {
			$key                   = ( $year * 100 ) + $month;
			$customer_data[ $key ] = array(
				'year'           => $year,
				'month'          => $month,
				'customer_count' => $customer_count,
			);
		}
	}
	for ( $i = 0; $i <= 1; $i++ ) {
		$year        = wp_date( 'Y', strtotime( "-{$i} months", strtotime( "{$current_year}-{$current_month}-01" ) ) );
		$month       = wp_date( 'n', strtotime( "-{$i} months", strtotime( "{$current_year}-{$current_month}-01" ) ) );
		$key         = ( $year * 100 ) + $month;
		$customers[] = isset( $customer_data[ $key ] ) ? $customer_data[ $key ] : array(
			'year'           => $year,
			'month'          => $month,
			'customer_count' => 0,
		);
	}
	if ( isset( $datefilters_totals ) && isset( $data['end_date'] ) && '' != $data['end_date'] ) {
		$_range     = array();
		$range      = date_diff( date_create( $start_date ), date_create( $end_date ) );
		$start_year = wp_date( 'Y', strtotime( $start_date ) );
		$end_year   = wp_date( 'Y', strtotime( $end_date ) );
		if ( $range->days <= 90 ) {
			$sales = array();
			foreach ( $datefilters_totals as $result ) {
				$key            = $result->days;
				$total_amount   = $result->total_amount;
				$total_earnings = $result->total_earnings;
				$_day           = wp_date( 'M', strtotime( $key ) ) . ' ' . wp_date( 'j', strtotime( $key ) );

				$sales_data[ $key ] = array(
					'total_amount'   => $total_amount,
					'total_earnings' => $total_earnings,
					'days'           => $_day,
				);

			}
			for ( $i = 0; $i <= $range->days; $i++ ) {
				$key  = $end_date;
				$_day = wp_date( 'M', strtotime( $key ) ) . ' ' . wp_date( 'j', strtotime( $key ) );

				$sales[]  = isset( $sales_data[ $key ] ) ? $sales_data[ $key ] : array(
					'total_amount'   => 0,
					'total_earnings' => 0,
					'days'           => $_day,
				);
				$end_date = wp_date( 'Y-m-d', strtotime( $end_date . ' -1 days' ) );
			}
		}
		if ( $range->days > 90 && $range->days <= 365 ) {
			$sales = array();
			foreach ( $datefilters_totals as $result ) {
				$key            = $result->months;
				$total_amount   = $result->total_amount;
				$total_earnings = $result->total_earnings;
				$month          = wp_date( 'n', strtotime( $key ) );

				$sales_data[ $key ] = array(
					'total_amount'   => $total_amount,
					'total_earnings' => $total_earnings,
					'month'          => wp_date( 'M', strtotime( $key ) ),
				);

			}
			$start_month = wp_date( 'Y-m', strtotime( $start_date ) );
			$end_month   = wp_date( 'Y-m', strtotime( $end_date ) );
			$date1       = new DateTime( $end_month );
			$date2       = new DateTime( $start_month );
			$interval    = $date2->diff( $date1 )->m;
			for ( $i = 0;$i <= $interval; $i++ ) {
				$sales[]   = isset( $sales_data [ $end_month ] ) ? $sales_data [ $end_month ] : array(
					'total_amount'   => 0,
					'total_earnings' => 0,
					'month'          => wp_date( 'M', strtotime( $end_month ) ),
				);
				$end_month = wp_date( 'Y-m', strtotime( $end_month . ' -1 month' ) );
			}
		}
		if ( $range->days >= 365 ) {
			$sales = array();
			foreach ( $datefilters_totals as $result ) {
				$key            = $result->year;
				$total_amount   = $result->total_amount;
				$total_earnings = $result->total_earnings;
				$year           = wp_date( 'Y', strtotime( $key ) );

				$sales_data[ $year ] = array(
					'total_amount'   => $total_amount,
					'total_earnings' => $total_earnings,
					'year'           => wp_date( 'Y', strtotime( $key ) ),
				);

			}
			$start_year = wp_date( 'Y', strtotime( $start_date ) );
			$end_year   = wp_date( 'Y', strtotime( $end_date ) );
			$date1      = new DateTime( $end_year );
			$date2      = new DateTime( $start_year );
			$interval   = $date2->diff( $date1 );
			$j          = (int) $start_year;
			$ranges     = $interval->i + $j;
			for ( $i = $j;$i <= $ranges; $i++ ) {
				$sales[] = isset( $sales_data [ $i ] ) ? $sales_data [ $i ] : array(
					'total_amount'   => 0,
					'total_earnings' => 0,
					'year'           => $i,
				);
			}
			$sales = array_reverse( $sales );
		}
		$chart_amount         = array();
		$chart_aov            = array();
		$chart_earning        = array();
		$chart_customer_count = array();
		$chart_refunds        = array();
		foreach ( $datefilters_aov as $_aov ) {
			$chart_aov[] = $_aov->average_order_value;
		}
		foreach ( $sales as $sale ) {
			$chart_amount[]  = $sale['total_amount'];
			$chart_earning[] = $sale['total_earnings'];
		}

		foreach ( $customers as $customer ) {
			$chart_customer_count[] = $customer['customer_count'];
		}
		foreach ( $refunds as $refund ) {
			$chart_refunds[] = $refund['total_refunds'];
		}
		if ( isset( $datefilters_customers ) ) {
			$chart_customer_count = null === $datefilters_customers[0]->customer_count ? 0 : $datefilters_customers[0]->customer_count;
		}
		if ( isset( $datefilters_refunds ) ) {
			$chart_refunds = null === $datefilters_refunds[0]->total_refunds ? 0 : $datefilters_refunds[0]->total_refunds;
		}
		if ( isset( $datefilters_bookings ) ) {
			$chart_bookings = null === $datefilters_bookings[0]->total_bookings ? 0 : $datefilters_bookings[0]->total_bookings;
		}

		$filtered_base        = isset( $sales[0]['month'] ) ? 'month' : ( isset( $sales[0]['year'] ) ? 'year' : 'days' );
		$chart_total_amount   = array_sum( $chart_amount );
		$chart_earnings       = array_sum( $chart_earning );
		$chart_aov            = array_sum( $chart_aov );
		$chart_customer_count = is_array( $chart_customer_count ) ? array_sum( $chart_customer_count ) : $chart_customer_count;
		$chart_refunds        = is_array( $chart_refunds ) ? array_sum( $chart_refunds ) : $chart_refunds;
		$chart_bookings       = is_array( $chart_bookings ) ? array_sum( $chart_bookings ) : $chart_bookings;
		$sales_data           = array();
		$sales_data           = $sales;
		foreach ( $sales_data as $key => $value ) {
			$_range[ $key ] = array(
				'total_amount'   => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $value['total_amount'] ) ) ),
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $value['total_earnings'] ) ) ),
			);
			if ( isset( $value['month'] ) ) {
				$_range[ $key ]['month'] = $value['month'];
			}
			if ( isset( $value['days'] ) ) {
				$_range[ $key ]['days'] = $value['days'];
			}
			if ( isset( $value['year'] ) ) {
				$_range[ $key ]['year'] = $value['year'];
			}
		}
	}

	$filters = array(
		'total_amount'        => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $chart_total_amount ) ) ),
		'average_order_value' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $chart_aov ) ) ),
		'total_earnings'      => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $chart_earnings ) ) ),
		'total_refunds'       => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $chart_refunds ) ) ),
		'customer_count'      => $chart_customer_count,
		'total_bookings'      => $chart_bookings,
		'filtered_base'       => $filtered_base,
	);
	$data    = array(
		'sales' => $_range,
	);
	if ( isset( $filters ) ) {
		$data['aggregatedData'] = $filters;
	}

	return $data;
}

/**
 * Analytics Trips.
 *
 * @param string $per_page Per Page.
 * @param string $page Page.
 */
function wptravelengine_analytics_trips( $per_page, $page ) {
	$data                = array();
	$id                  = '"ID"';
	$offset              = 1 === $page ? 0 : ( $page - 1 ) * $per_page;
	$popular_trips       = wptravelengine_analytics_trips_queries( $offset, $per_page, $id, 'trips' );
	$popular_trips_count = wptravelengine_analytics_trips_queries( $offset, $per_page, $id, 'trip_count' );
	$data                = array(
		'popular_trips'       => $popular_trips,
		'popular_trips_count' => $popular_trips_count,
	);
	$data                = wptravelengine_analytics_trips_data( $data );
	return $data;
}

/**
 * Analytics Trips Queries.
 *
 * @param string $offset Offset.
 * @param string $per_page Per Page Value.
 * @param string $id ID.
 * @param string $query Query Type.
 */
function wptravelengine_analytics_trips_queries( $offset, $per_page, $id, $query ) {
	global $wpdb;
	$queries_data = array();
	if ( 'trips' === $query ) {
		$queries_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) as trip_count, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as paid_amount FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='trip' AND post_status IN ('publish', 'draft')) AND SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 2),':', -1) = '$id' AND paid_amount.meta_key = 'paid_amount' AND paid_amount.post_id = order_trips.post_id GROUP BY SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) ORDER BY trip_count DESC LIMIT $offset, $per_page" );
	}
	if ( 'trip_count' === $query ) {
		$queries_data = $wpdb->get_col( "SELECT COUNT(*) FROM ( SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) as trip_count, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as paid_amount FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='trip' AND post_status IN ('publish', 'draft')) AND SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 2),':', -1) = '$id' AND paid_amount.meta_key = 'paid_amount' AND paid_amount.post_id = order_trips.post_id GROUP BY SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) ORDER BY trip_count DESC) as popular_trips_count" );
	}
	return $queries_data;
}

/**
 * Calculation of analytics trip data.
 *
 * @param array $data Analytics Trip Data.
 */
function wptravelengine_analytics_trips_data( $data ) {
	$popular_trips       = $data['popular_trips'];
	$popular_trips_count = $data['popular_trips_count'];
	$trips_data          = array();
	foreach ( $popular_trips as $trip ) {
		$trip_id                       = $trip->trip_id;
		$trip_title                    = get_the_title( $trip_id );
		$trip_count                    = $trip->trip_count;
		$trip_earnings                 = $trip->paid_amount;
		$trips_data['popular_trips'][] = array(
			'title'       => html_entity_decode( $trip_title ),
			'url'         => add_query_arg(
				array(
					'post'   => $trip_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			),
			'earnings'    => $trip_earnings,
			'price'       => wp_travel_engine_get_actual_trip_price( $trip_id ),
			'sales_count' => $trip_count,
		);
	}
	$trips_data['popular_trips_count'] = $popular_trips_count[0];
	return $trips_data;
}

/**
 * Popular Trip Data.
 *
 * @param string $start_date Start Date.
 * @param string $end_date End Date.
 */
function wptravelengine_analytics_popular_trip( $start_date, $end_date ) {
	$data                      = array();
	$datefilters_best_seller   = array();
	$datefilters_top_performer = array();
	if ( '' != $start_date || '' != $end_date ) {
		$datefilters_best_seller   = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'best_seller' );
		$datefilters_top_performer = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'top_performer' );
		$data                      = array(
			'datefilters_best_seller'   => $datefilters_best_seller,
			'datefilters_top_performer' => $datefilters_top_performer,
			'start_date'                => $start_date,
			'end_date'                  => $end_date,
		);
	}
	$data = wptravelengine_popular_trips_data( $data );
	return $data;
}

/**
 * Calculation of popular trips.
 *
 * @param array $data Popular Trip Data.
 */
function wptravelengine_popular_trips_data( $data ) {
	$trips_data = array();
	if ( isset( $data['end_date'] ) && '' != $data['end_date'] ) {
		$datefilters_best_seller   = $data['datefilters_best_seller'];
		$datefilters_top_performer = $data['datefilters_top_performer'];
	}
	if ( isset( $datefilters_best_seller ) && count( $datefilters_best_seller ) > 0 ) {
		$trip_id                   = $datefilters_best_seller[0]->trip_id;
		$booking_count             = $datefilters_best_seller[0]->sales_count;
		$trip_title                = get_the_title( $trip_id );
		$trips_data['best_seller'] = array(
			'title'         => html_entity_decode( $trip_title ),
			'url'           => add_query_arg(
				array(
					'post'   => $trip_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			),
			'trip_id'       => $trip_id,
			'booking_count' => $booking_count,
		);
	}
	if ( isset( $datefilters_top_performer ) && count( $datefilters_top_performer ) > 0 ) {
		$trip_id                     = $datefilters_top_performer[0]->trip_id;
		$total_earnings              = $datefilters_top_performer[0]->total_earnings;
		$trip_title                  = get_the_title( $trip_id );
		$trips_data['top_performer'] = array(
			'title'          => html_entity_decode( $trip_title ),
			'url'            => add_query_arg(
				array(
					'post'   => $trip_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			),
			'trip_id'        => $trip_id,
			'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $total_earnings ) ) ),
		);
	}
	return $trips_data;
}

/**
 * Analytics Customers.
 *
 * @param string $start_date Start Date.
 * @param string $end_date End Date.
 */
function wptravelengine_analytics_customers( $start_date, $end_date ) {
	$data                  = array();
	$datefilters_customers = array();
	$total_customers       = wptravelengine_analytics_queries_without_date( 'total_customer' );
	$customers             = wptravelengine_analytics_queries_without_date( 'customer' );
	$top_customer          = wptravelengine_analytics_queries_without_date( 'top_customer' );
	if ( '' != $start_date || '' != $end_date ) {
		$datefilters_total_customers = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'total_customer' );
		$datefilters_customers       = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'customer' );
		$datefilters_top_customer    = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'top_customer' );
		$data                        = array(
			'datefilters_total_customers' => $datefilters_total_customers,
			'datefilters_customers'       => $datefilters_customers,
			'datefilters_top_customer'    => $datefilters_top_customer,
			'start_date'                  => $start_date,
			'end_date'                    => $end_date,
			'total_customers'             => $total_customers,
			'top_customer'                => $top_customer,
			'customers'                   => $customers,
		);
	} else {
		$data = array(
			'total_customers' => $total_customers,
			'customers'       => $customers,
			'top_customer'    => $top_customer,
		);
	}
	$data = wptravelengine_analytics_customers_data( $data );
	return $data;
}

/**
 * Calculation of analytics customers data.
 *
 * @param array $data Analytics Customer Data.
 */
function wptravelengine_analytics_customers_data( $data ) {
	$end_date   = '';
	$start_date = '';
	if ( isset( $data['end_date'] ) && '' != $data['end_date'] ) {
		$end_date                    = $data['end_date'];
		$start_date                  = $data['start_date'];
		$datefilters_customers       = $data['datefilters_customers'];
		$datefilters_total_customers = $data['datefilters_total_customers'];
		$datefilters_top_customer    = $data['datefilters_top_customer'];
	}
	$data = array();
	if ( '' != $end_date && '' != $start_date ) {
		if ( count( $datefilters_customers ) > 0 && count( $datefilters_total_customers ) > 0 ) {
			$datefilters_customers               = null === $datefilters_customers ? 0 : $datefilters_customers;
			$datefilters_total_customers         = null === $datefilters_total_customers ? 0 : $datefilters_total_customers;
			$datefilters_top_customer            = null === $datefilters_top_customer ? 0 : $datefilters_top_customer;
			$data['datefilters_customers']       = $datefilters_customers;
			$data['datefilters_total_customers'] = $datefilters_total_customers;
			$data['datefilters_top_customer']    = $datefilters_top_customer;
		}
	}
	return $data;
}

/**
 * Analytics Customers Table.
 *
 * @param string $per_page Per Page.
 * @param string $page Page.
 */
function wptravelengine_analytics_customers_table( $per_page, $page ) {
	$data           = array();
	$offset         = 1 === $page ? 0 : ( $page - 1 ) * $per_page;
	$customer_table = wptravelengine_analytics_customers_query( $offset, $per_page, 'customer_table' );
	$data           = array(
		'customers_table_data' => $customer_table,
	);
	$data           = wptravelengine_analytics_customers_table_data( $data );
	return $data;
}

/**
 * WP Travel Engine Analytics Date Filter Data.
 *
 * @param string $source Tab Name.
 * @param string $filter_type Filter Type.
 */
function wptravelengine_analytics_date_filters( $source, $filter_type ) {
	$data = array();
	if ( 'Overview' === $source ) {
		$overview_datefilter_data = wptravelengine_analytics_overview_datefilter_query( $filter_type );
		$data                     = array(
			'datefilter_data' => $overview_datefilter_data,
		);
		$data                     = wptravelengine_analytics_overview_datefilter_data( $data, $filter_type );
	}
	if ( 'Trips' === $source ) {
		$trips_datefilter_data = wptravelengine_analytics_trips_datefilter_query( $filter_type );
		$data                  = array(
			'datefilter_data' => $trips_datefilter_data,
		);
		$data                  = wptravelengine_analytics_trips_datefilter_data( $data, $filter_type );
	}
	if ( 'Customers' === $source ) {
		$customers_datefilter_data = wptravelengine_analytics_customers_datefilter_query( $filter_type );
		$data                      = array(
			'datefilter_data' => $customers_datefilter_data,
		);
		$data                      = wptravelengine_analytics_customers_datefilter_data( $data, $filter_type );
	}
	if ( 'Destinations' === $source || 'Activities' === $source || 'TripTypes' === $source ) {
		$destination_datefilter_data = wptravelengine_analytics_taxonomy_datefilter_query( $filter_type );
		$data                        = array(
			'datefilter_data' => $destination_datefilter_data,
		);
		$data                        = wptravelengine_analytics_taxonomy_datefilter_data( $data, $filter_type, $source );
	}
	return $data;
}

/**
 * Overview Tab Date Filter Query.
 *
 * @param string $filter_type Filter Type.
 */
function wptravelengine_analytics_overview_datefilter_query( $filter_type ) {
	global $wpdb;
	$totals    = array();
	$bookings  = array();
	$aov       = array();
	$customers = array();
	$refunds   = array();
	if ( 'today' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE_FORMAT(p.post_date, '%h:00 %p') AS hour FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d') GROUP BY hour ORDER BY STR_TO_DATE(hour, '%l:%i %p') DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE(post_date) AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d') GROUP BY days ORDER BY days DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d') ) ) GROUP BY days ORDER BY days DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')" );
		// Yesterday Data for comparison.
		$prev_totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE_FORMAT(p.post_date, '%h:00 %p') AS hour FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') GROUP BY hour ORDER BY STR_TO_DATE(hour, '%l:%i %p') DESC" );
		$prev_aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
		$prev_customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE(post_date) AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') GROUP BY days ORDER BY days DESC" );
		$prev_refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') ) ) GROUP BY days ORDER BY days DESC" );
		$prev_bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
	}
	if ( 'yesterday' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE_FORMAT(p.post_date, '%h:00 %p') AS hour FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') GROUP BY hour ORDER BY STR_TO_DATE(hour, '%l:%i %p') DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE(post_date) AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') GROUP BY days ORDER BY days DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d') ) ) GROUP BY days ORDER BY days DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
	}
	if ( 'this_week' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U') GROUP BY days ORDER BY days DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE(post_date) AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U') GROUP BY days ORDER BY days DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U') )) GROUP BY days ORDER BY days DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')" );
		// Last week Data for comparison.
		$prev_totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U') GROUP BY days ORDER BY days DESC" );
		$prev_aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
		$prev_customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE_FORMAT((post_date), '%Y-%m-%d') AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') =  DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U') GROUP BY days ORDER BY days DESC" );
		$prev_refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') =  DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U') )) GROUP BY days ORDER BY days DESC" );
		$prev_bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
	}
	if ( 'last_week' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U') GROUP BY days ORDER BY days DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE_FORMAT((post_date), '%Y-%m-%d') AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') =  DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U') GROUP BY days ORDER BY days DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') =  DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U') )) GROUP BY days ORDER BY days DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
	}
	if ( 'this_month' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND MONTH(p.post_date) = MONTH(NOW()) GROUP BY days ORDER BY days DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND MONTH(p.post_date) = MONTH(NOW())" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE_FORMAT((post_date), '%Y-%m-%d') AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND MONTH(post_date) = MONTH(NOW()) GROUP BY days ORDER BY days DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND MONTH(p.post_date) = MONTH(NOW()) AND YEAR(p.post_date) = YEAR(NOW()) ) ) GROUP BY days ORDER BY days DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND MONTH(p.post_date) = MONTH(NOW())" );
		// Last month data for comparison.
		$prev_totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) GROUP BY days ORDER BY days DESC" );
		$prev_aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))" );
		$prev_customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE_FORMAT((post_date), '%Y-%m-%d') AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))  GROUP BY days ORDER BY days DESC" );
		$prev_refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) ) ) GROUP BY days ORDER BY days DESC" );
		$prev_bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))" );
	}
	if ( 'last_month' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, DATE(p.post_date) AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) GROUP BY days ORDER BY days DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count,DATE_FORMAT((post_date), '%Y-%m-%d') AS days FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))  GROUP BY days ORDER BY days DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%Y-%m-%d') AS days FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) ) ) GROUP BY days ORDER BY days DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))" );
	}
	if ( 'this_quarter' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, QUARTER(p.post_date) as quarter, MONTH(p.post_date) as month FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND QUARTER(p.post_date) = QUARTER(NOW()) GROUP BY quarter, month ORDER BY quarter DESC, month DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND QUARTER(p.post_date) = QUARTER(NOW())" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft')  AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())))" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) AND QUARTER(p.post_date) = QUARTER(NOW())" );
	}
	if ( 'this_year' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, MONTH(p.post_date) AS month FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) GROUP BY month ORDER BY month DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW())" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count, DATE_FORMAT(post_date, '%c') AS months FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) GROUP BY months ORDER BY months DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%c') AS month_year FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW()) ) ) GROUP BY month_year ORDER BY month_year DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(NOW())" );
		// Last year data for comparison.
		$prev_totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, MONTH(p.post_date) AS month FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)) GROUP BY month ORDER BY month DESC" );
		$prev_aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR))" );
		$prev_customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count, DATE_FORMAT(post_date, '%c') AS months FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) - 1 GROUP BY months ORDER BY months DESC" );
		$prev_refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%c') AS month_year FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)) ) ) GROUP BY month_year ORDER BY month_year DESC" );
		$prev_bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR))" );
	}
	if ( 'last_year' === $filter_type ) {
		$totals    = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, MONTH(p.post_date) AS month FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)) GROUP BY month ORDER BY month DESC" );
		$aov       = $wpdb->get_results( "SELECT SUM(pm.meta_value) / (COUNT(DISTINCT p.ID)) AS average_order_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR))" );
		$customers = $wpdb->get_results( "SELECT COUNT(*) AS customer_count, DATE_FORMAT(post_date, '%c') AS months FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) - 1 GROUP BY months ORDER BY months DESC" );
		$refunds   = $wpdb->get_results( "SELECT SUM(meta_value) AS total_refunds, DATE_FORMAT((p.post_date), '%c') AS month_year FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount') AND pm.post_id IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wp_travel_engine_booking_status' AND meta_value = 'refunded' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR)) ) ) GROUP BY month_year ORDER BY month_year DESC" );
		$bookings  = $wpdb->get_results( "SELECT (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->posts} p WHERE p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 YEAR))" );
	}
	$queries_data[] = array( $totals, $aov, $customers, $refunds, $bookings );
	if ( isset( $prev_totals ) && isset( $prev_aov ) && isset( $prev_customers ) && isset( $prev_refunds ) ) {
		$queries_data[1] = array( $prev_totals, $prev_aov, $prev_customers, $prev_refunds, $prev_bookings );
	}
	return $queries_data;
}

/**
 * Trips Tab Date Filter Query.
 *
 * @param string $filter_type Filter Type.
 */
function wptravelengine_analytics_trips_datefilter_query( $filter_type ) {
	global $wpdb;
	$queries_data  = array();
	$best_seller   = array();
	$top_performer = array();
	if ( 'today' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'yesterday' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'this_week' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'last_week' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'this_month' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'last_month' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAl 1 MONTH), '%Y-%m')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAl 1 MONTH), '%Y-%m')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'this_year' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(NOW(), '%Y')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(NOW(), '%Y')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'last_year' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	if ( 'this_quarter' === $filter_type ) {
		$best_seller   = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, ';', 3),':', -1) AS trip_id, COUNT(*) AS sales_count FROM {$wpdb->postmeta} WHERE meta_key = 'order_trips' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC LIMIT 1" );
		$top_performer = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())) GROUP BY trip_id ORDER BY total_earnings DESC, CAST(trip_id AS UNSIGNED) DESC LIMIT 1" );
	}
	$queries_data = array( $best_seller, $top_performer );
	return $queries_data;
}

/**
 * Customers Tab Date Filter Query.
 *
 * @param string $filter_type Filter Type.
 */
function wptravelengine_analytics_customers_datefilter_query( $filter_type ) {
	global $wpdb;
	$queries_data    = array();
	$total_customer  = array();
	$new_customer    = array();
	$top_customer    = array();
	$serialized_data = array();
	if ( 'today' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') <= DATE_FORMAT(NOW(), '%Y-%m-%d')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
		// Yesterday data for comparison.
		$prev_total_customer = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
		$prev_new_customer   = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
	}
	if ( 'yesterday' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
	}
	if ( 'this_week' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') <= DATE_FORMAT(NOW(), '%Y-%U')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
		// Last week data for comparison.
		$prev_total_customer = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
		$prev_new_customer   = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
	}
	if ( 'last_week' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
	}
	if ( 'this_month' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') <= DATE_FORMAT(NOW(), '%Y-%m')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
		// Last month data for comparison.
		$prev_total_customer = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')" );
		$prev_new_customer   = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')" );
	}
	if ( 'last_month' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
	}
	if ( 'this_quarter' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND QUARTER(post_date) <= QUARTER(NOW())" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
	}
	if ( 'this_year' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') <= DATE_FORMAT(NOW(), '%Y')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(NOW(), '%Y')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(NOW(), '%Y')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
		// Last year data for comparison.
		$prev_total_customer = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')" );
		$prev_new_customer   = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')" );
	}
	if ( 'last_year' === $filter_type ) {
		$total_customer  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') <= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')" );
		$new_customer    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')" );
		$serialized_data = $wpdb->get_results( "SELECT cn.post_id AS customer_id, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings FROM {$wpdb->postmeta} AS cn LEFT JOIN {$wpdb->postmeta} AS pm ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')) ORDER BY bookings DESC, customer_id DESC LIMIT 1" );
	}
	// Unserialize the cost section and add them for respective customer_id.
	if ( is_array( $serialized_data ) && count( $serialized_data ) > 0 ) {
		foreach ( $serialized_data as $data ) {
			$first_name     = str_replace( '"', '', $data->first_name );
			$last_name      = str_replace( '"', '', $data->last_name );
			$customer_name  = $first_name . ' ' . $last_name;
			$id             = $data->customer_id;
			$top_customer[] = array(
				'title' => html_entity_decode( $customer_name ),
				'id'    => $id,
			);
		}
	}
	$queries_data[0] = array(
		$total_customer[0]->total_customer,
		$new_customer[0]->customer_count,
		isset( $top_customer[0] ) ? $top_customer[0] : '',
	);
	if ( isset( $prev_total_customer ) && isset( $prev_new_customer ) ) {
		$queries_data[1] = array(
			$prev_total_customer[0]->total_customer,
			$prev_new_customer[0]->customer_count,
		);
	}
	return $queries_data;
}

/**
 * Calculation of overview tab datefilter data.
 *
 * @param array  $data Overview Date Filter Data.
 * @param string $filter_type Overview Date Filter Type.
 */
function wptravelengine_analytics_overview_datefilter_data( $data, $filter_type ) {
	$datefilter_data = array();
	$datefilters     = $data['datefilter_data'];
	$total_data      = array();
	if ( 'today' === $filter_type || 'yesterday' === $filter_type ) {
		$current_time = current_time( 'h:i A' );
		foreach ( $datefilters[0][0] as $datefilter ) {
			$datefilter_data[ $datefilter->hour ] = array(
				'total_amount'   => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_amount ) ) ),
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_earnings ) ) ),
				'days'           => $datefilter->hour,
			);
		}
		$current_time  = 'today' === $filter_type ? new DateTime( $current_time ) : new DateTime( '11:59 PM' );
		$starting_time = new DateTime( '12:00 AM' );
		$interval      = $starting_time->diff( $current_time );
		for ( $i = 0; $i <= $interval->h; $i++ ) {
			$time          = strtotime( $i . ':00' );
			$time_format   = wp_date( 'h:00 A', $time );
			$_total_data[] = isset( $datefilter_data [ $time_format ] ) ? $datefilter_data [ $time_format ] : array(
				'total_amount'   => 0,
				'total_earnings' => 0,
				'days'           => $time_format,
			);
		}
	}
	if ( 'this_week' === $filter_type || 'last_week' === $filter_type ) {
		$day      = wp_date( 'l' );
		$timezone = wp_timezone_string();

		// Create a DateTime object for the current date and time in the timezone.
		$date = new DateTime( 'now', new DateTimeZone( $timezone ) );

		// Format the start of the week as a date string.
		$start_of_week = $date->format( 'Y-m-d' );
		$present_day   = wp_date( 'l' );
		$starter       = 'Sunday' === $present_day ? 'this' : 'last';
		$start_of_week = 'this_week' === $filter_type ? wp_date( 'Y-m-d', strtotime( "{$starter} Sunday" ) ) : wp_date( 'Y-m-d', strtotime( "{$starter} Sunday", time() - 7 * 24 * 60 * 60 ) );

		$today    = 'this_week' === $filter_type ? wp_date( 'Y-m-d', strtotime( "last {$day}", strtotime( 'next sunday' ) ) ) : wp_date( 'Y-m-d', strtotime( 'last Saturday' ) );
		$date1    = new DateTime( $today );
		$date2    = new DateTime( $start_of_week );
		$interval = $date2->diff( $date1 );
		foreach ( $datefilters[0][0] as $datefilter ) {
			$datefilters_days                     = wp_date( 'l', strtotime( $datefilter->days ) );
			$datefilter_data[ $datefilter->days ] = array(
				'total_amount'   => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_amount ) ) ),
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_earnings ) ) ),
				'days'           => $datefilters_days,
			);
		}
		for ( $i = 1;$i <= $interval->days + 1; $i++ ) {
			$_day          = 'this_week' === $filter_type ? wp_date( 'Y-m-d', strtotime( ' -' . $i - 1 . 'days' ) ) : wp_date( 'Y-m-d', strtotime( "{$starter} Sunday -" . $i . ' days' ) );
			$key           = $_day;
			$_day          = wp_date( 'l', strtotime( $_day ) );
			$_total_data[] = isset( $datefilter_data [ $key ] ) ? $datefilter_data [ $key ] : array(
				'total_amount'   => 0,
				'total_earnings' => 0,
				'days'           => $_day,
			);
		}
	}
	if ( 'this_month' === $filter_type || 'last_month' === $filter_type ) {
		foreach ( $datefilters[0][0] as $datefilter ) {
			$datefilters_days                     = wp_date( 'j', strtotime( $datefilter->days ) );
			$datefilter_data[ $datefilters_days ] = array(
				'total_amount'   => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_amount ) ) ),
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_earnings ) ) ),
				'days'           => wp_date( 'F', strtotime( $datefilter->days ) ) . ' ' . $datefilters_days,
			);
		}
		$today          = 'this_month' === $filter_type ? wp_date( 'Y-m-d', strtotime( 'today' ) ) : wp_date( 'Y-m-d', strtotime( 'last day of previous month' ) );
		$start_of_month = 'this_month' === $filter_type ? wp_date( 'Y-m-01' ) : wp_date( 'Y-m-01', strtotime( 'first day of last month' ) );
		$date1          = new DateTime( $today );
		$date2          = new DateTime( $start_of_month );
		$interval       = $date2->diff( $date1 );
		$month_name     = 'this_month' === $filter_type ? wp_date( 'F', strtotime( 'this month' ) ) : wp_date( 'F', strtotime( 'last month' ) );
		for ( $i = 1;$i <= $interval->days + 1; $i++ ) {
			$_total_data[] = isset( $datefilter_data [ $i ] ) ? $datefilter_data [ $i ] : array(
				'total_amount'   => 0,
				'total_earnings' => 0,
				'days'           => $month_name . ' ' . $i,
			);
		}
	}
	if ( 'this_year' === $filter_type || 'last_year' === $filter_type ) {
		foreach ( $datefilters[0][0] as $datefilter ) {
			$datefilters_months                    = wp_date( 'F', mktime( 0, 0, 0, $datefilter->month, 10 ) );
			$datefilter_data[ $datefilter->month ] = array(
				'total_amount'   => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_amount ) ) ),
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_earnings ) ) ),
				'days'           => $datefilters_months,
			);
		}
		$current_month = 'this_year' === $filter_type ? wp_date( 'F' ) : wp_date( 'F', strtotime( 'last day of December last year' ) );
		$start_month   = 'January';
		$date1         = new DateTime( $current_month );
		$date2         = new DateTime( $start_month );
		$interval      = $date2->diff( $date1 );
		for ( $i = 1;$i <= $interval->m + 1; $i++ ) {
			$_total_data[] = isset( $datefilter_data [ $i ] ) ? $datefilter_data [ $i ] : array(
				'total_amount'   => 0,
				'total_earnings' => 0,
				'days'           => wp_date( 'F', mktime( 0, 0, 0, $i, 10 ) ),
			);
		}
	}
	if ( 'this_quarter' === $filter_type ) {
		foreach ( $datefilters[0][0] as $datefilter ) {
			$datefilters_months                    = wp_date( 'F', mktime( 0, 0, 0, $datefilter->month, 10 ) );
			$datefilter_data[ $datefilter->month ] = array(
				'total_amount'   => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_amount ) ) ),
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $datefilter->total_earnings ) ) ),
				'days'           => $datefilters_months,
			);
		}
		$year              = wp_date( 'Y' );
		$current_month     = wp_date( 'n' );
		$quarter           = ceil( $current_month / 3 );
		$today             = wp_date( 'Y-m-d' );
		$quarter_start_day = wp_date( 'Y-m-d', strtotime( $year . '-' . ( ( $quarter - 1 ) * 3 + 1 ) . '-1' ) );
		$range             = date_diff( date_create( $quarter_start_day ), date_create( $today ) )->m;
		for ( $i = 0; $i <= $range; $i++ ) {
			$_total_data[] = isset( $datefilter_data[ $current_month ] ) ? $datefilter_data[ $current_month ] : array(
				'total_amount'   => 0,
				'total_earnings' => 0,
				'days'           => wp_date( 'F', mktime( 0, 0, 0, $current_month, 10 ) ),
			);
			$current_month--;
		}
	}
	if ( isset( $datefilters[0] ) && count( $datefilters[0] ) > 0 ) {
		$totals          = array();
		$earnings        = array();
		$aov             = array();
		$customers_count = array();
		$refunds         = array();
		$bookings        = array();
		foreach ( $datefilters[0][0] as $data ) {
			$totals[]   = $data->total_amount;
			$earnings[] = $data->total_earnings;
		}
		foreach ( $datefilters[0][1] as $_aov ) {
			$aov[] = $_aov->average_order_value;
		}
		foreach ( $datefilters[0][2] as $customer ) {
			$customers_count[] = $customer->customer_count;
		}
		foreach ( $datefilters[0][3] as $refund ) {
			$refunds[] = $refund->total_refunds;
		}
		foreach ( $datefilters[0][4] as $booking ) {
			$bookings[] = $booking->total_bookings;
		}
		$total                    = array_sum( $totals );
		$earning                  = array_sum( $earnings );
		$average_order_value      = array_sum( $aov );
		$customer_count           = array_sum( $customers_count );
		$total_refunds            = array_sum( $refunds );
		$total_bookings           = array_sum( $bookings );
		$total_data['collection'] = array_reverse( $_total_data );
		if ( 'this_week' === $filter_type || 'last_week' === $filter_type || 'this_quarter' === $filter_type ) {
			$total_data['collection'] = array_reverse( $total_data['collection'] );
		}
		$total_data['totals'] = array(
			'total_amount'        => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $total ) ) ),
			'total_earnings'      => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $earning ) ) ),
			'average_order_value' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $average_order_value ) ) ),
			'refunds'             => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $total_refunds ) ) ),
			'customers'           => $customer_count,
			'total_bookings'      => $total_bookings,
		);
	}
	if ( isset( $datefilters[1] ) && count( $datefilters[1] ) > 0 ) {
		$prev_totals          = array();
		$prev_earnings        = array();
		$prev_aov             = array();
		$prev_customers_count = array();
		$prev_refunds         = array();
		$prev_bookings        = array();
		foreach ( $datefilters[1][0] as $prev_data ) {
			$prev_totals[]   = $prev_data->total_amount;
			$prev_earnings[] = $prev_data->total_earnings;
		}
		foreach ( $datefilters[1][1] as $_prev_aov ) {
			$prev_aov[] = $_prev_aov->average_order_value;
		}
		foreach ( $datefilters[1][2] as $prev_customer ) {
			$prev_customers_count[] = $prev_customer->customer_count;
		}
		foreach ( $datefilters[1][3] as $prev_refund ) {
			$prev_refunds[] = $prev_refund->total_refunds;
		}
		foreach ( $datefilters[1][4] as $prev_booking ) {
			$prev_bookings[] = $prev_booking->total_bookings;
		}
		$prev_total                = array_sum( $prev_totals );
		$prev_earning              = array_sum( $prev_earnings );
		$prev_average_order_value  = array_sum( $prev_aov );
		$prev_customer_count       = array_sum( $prev_customers_count );
		$prev_total_refunds        = array_sum( $prev_refunds );
		$prev_total_bookings       = array_sum( $prev_bookings );
		$total_amount_range        = 0 != $prev_total ? ( ( $total - $prev_total ) / $prev_total ) * 100 : ( 0 === $total ? 0 : 200 );
		$total_earnings_range      = 0 != $prev_earning ? ( ( $earning - $prev_earning ) / $prev_earning ) * 100 : ( 0 === $earning ? 0 : 200 );
		$average_order_value_range = 0 != $prev_average_order_value ? ( ( $average_order_value - $prev_average_order_value ) / $prev_average_order_value ) * 100 : ( 0 === $average_order_value ? 0 : 200 );
		$customers_range           = 0 != $prev_customer_count ? ( ( $customer_count - $prev_customer_count ) / $prev_customer_count ) * 100 : ( 0 === $customer_count ? 0 : 200 );
		$refunds_range             = 0 != $prev_total_refunds ? ( ( $total_refunds - $prev_total_refunds ) / $prev_total_refunds ) * 100 : ( 0 === $total_refunds ? 0 : 200 );
		$bookings_range            = 0 != $prev_total_bookings ? ( ( $total_bookings - $prev_total_bookings ) / $prev_total_bookings ) * 100 : ( 0 === $total_bookings ? 0 : 200 );

		$total_data['comparison'] = array(
			'total_amount_range'        => round( $total_amount_range ),
			'total_earnings_range'      => round( $total_earnings_range ),
			'average_order_value_range' => round( $average_order_value_range ),
			'customers_range'           => round( $customers_range ),
			'refunds_range'             => round( $refunds_range ),
			'bookings_range'            => round( $bookings_range ),
		);
	}
	return $total_data;
}

/**
 * Calculation of trips tab datefilter data.
 *
 * @param array  $data Trips Date Filter Data.
 * @param string $filter_type Trips Date Filter Type.
 */
function wptravelengine_analytics_trips_datefilter_data( $data, $filter_type ) {
	$datefilter_data = array();
	$datefilters     = $data['datefilter_data'];
	if ( 'custom' != $filter_type ) {
		$datefilters_best_seller   = $datefilters[0];
		$datefilters_top_performer = $datefilters[1];
		if ( isset( $datefilters_best_seller ) && count( $datefilters_best_seller ) > 0 ) {
			$trip_id                        = $datefilters_best_seller[0]->trip_id;
			$booking_count                  = $datefilters_best_seller[0]->sales_count;
			$trip_title                     = get_the_title( $trip_id );
			$datefilter_data['best_seller'] = array(
				'title'         => html_entity_decode( $trip_title ),
				'url'           => add_query_arg(
					array(
						'post'   => $trip_id,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				),
				'trip_id'       => $trip_id,
				'booking_count' => $booking_count,
			);
		}
		if ( isset( $datefilters_top_performer ) && count( $datefilters_top_performer ) > 0 ) {
			$trip_id                          = $datefilters_top_performer[0]->trip_id;
			$total_earnings                   = $datefilters_top_performer[0]->total_earnings;
			$trip_title                       = get_the_title( $trip_id );
			$datefilter_data['top_performer'] = array(
				'title'          => html_entity_decode( $trip_title ),
				'url'            => add_query_arg(
					array(
						'post'   => $trip_id,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				),
				'trip_id'        => $trip_id,
				'total_earnings' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $total_earnings ) ) ),
			);
		}
	}
	return $datefilter_data;
}

/**
 * Calculation of customers tab datefilter data.
 *
 * @param array  $data Customers Date Filter Data.
 * @param string $filter_type Customers Date Filter Type.
 */
function wptravelengine_analytics_customers_datefilter_data( $data, $filter_type ) {
	$datefilter_data              = array();
	$datefilters                  = $data['datefilter_data'];
	$datefilter_data['customers'] = array(
		'customer_name'  => '' != $datefilters[0][2] ? $datefilters[0][2]['title'] : '',
		'total_customer' => $datefilters[0][0],
		'new_customer'   => $datefilters[0][1],
	);
	if ( isset( $datefilters[1] ) && count( $datefilters[1] ) > 0 ) {
			$prev_total_customer           = $datefilters[1][0];
			$prev_new_customer             = $datefilters[1][1];
			$total_customer_range          = 0 != $prev_total_customer ? ( ( $datefilters[0][0] - $prev_total_customer ) / $prev_total_customer ) * 100 : ( 0 === $datefilters[0][0] ? 0 : 200 );
			$new_customer_range            = 0 != $prev_new_customer ? ( ( $datefilters[0][1] - $prev_new_customer ) / $prev_new_customer ) * 100 : ( 0 === $datefilters[0][1] ? 0 : 200 );
			$datefilter_data['comparison'] = array(
				'total_customer_range' => round( $total_customer_range ),
				'new_customer_range'   => round( $new_customer_range ),
			);
	}
	if ( '' != $datefilters[0][2] ? $datefilters[0][2]['title'] : '' ) {
		$datefilter_data['customers']['customer_url'] = add_query_arg(
			array(
				'post'   => $datefilters[0][2]['id'],
				'action' => 'edit',
			),
			admin_url( 'post.php' )
		);
	}
	return $datefilter_data;
}

/**
 * Analytics Customers Query.
 *
 * @param string $offset Pffset.
 * @param string $per_page Per Page.
 * @param string $query Query Type.
 */
function wptravelengine_analytics_customers_query( $offset, $per_page, $query ) {
	global $wpdb;
	$queries_data   = array();
	$customers_data = array();
	if ( 'customer_table' === $query ) {
		$serialized_data = array();
		$serialized_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',16),':',-1) AS first_name, SUBSTRING_INDEX(SUBSTRING_INDEX(cn.meta_value,';',18),':',-1) AS last_name, p.id AS customer_id, p.post_title AS customer_email, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value,':',2),':',-1) AS DECIMAL(10,0)) AS bookings, SUBSTRING_INDEX(SUBSTRING_INDEX(pms.meta_value, '\"cost\";', -1), 's:3:\"due\"', 1) AS cost, DATE(p.post_date) AS date FROM {$wpdb->postmeta} AS pm LEFT JOIN {$wpdb->postmeta} AS pms ON pm.post_id = pms.post_id LEFT JOIN {$wpdb->posts} AS p ON pm.post_id = p.ID LEFT JOIN {$wpdb->postmeta} AS cn ON pm.post_id = cn.post_id WHERE pm.meta_key='wp_travel_engine_bookings' AND pms.meta_key='wp_travel_engine_booked_trip_setting' AND cn.meta_key = 'wp_travel_engine_booking_setting' AND pm.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft')) ORDER BY bookings DESC, cost DESC LIMIT $offset, $per_page" );
		$customer_count  = $wpdb->get_results( "SELECT COUNT(*) AS total_customer FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') " );
		// Unserialize the cost section and add them for respective customer_id.
		foreach ( $serialized_data as $data ) {
			$first_name        = str_replace( '"', '', $data->first_name );
			$last_name         = str_replace( '"', '', $data->last_name );
			$id                = $data->customer_id;
			$email             = $data->customer_email;
			$booked_trip       = $data->bookings;
			$cost_serialized   = $data->cost;
			$date              = $data->date;
			$cost_unserialized = unserialize( $cost_serialized );
			$total_cost        = array_sum( $cost_unserialized );
			$cost_serialized   = '' != $cost_unserialized && $cost_unserialized ? $total_cost : 0;

			$customer_name  = $first_name . ' ' . $last_name;
			$queries_data[] = array(
				'title'        => html_entity_decode( $customer_name ),
				'url'          => add_query_arg(
					array(
						'post'   => $id,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				),
				'email'        => $email,
				'booked_trip'  => $booked_trip,
				'amount_spent' => $cost_serialized,
				'date'         => $date,
			);
		}
		$customers_data['customer_data']  = $queries_data;
		$customers_data['customer_count'] = $customer_count[0]->total_customer;
	}
	return $customers_data;
}

/**
 * Calculation of Analytics Customers Table Data.
 *
 * @param array $data Ana;ytics Customers Table Data.
 */
function wptravelengine_analytics_customers_table_data( $data ) {
	$customers      = array();
	$customer_table = $data['customers_table_data'];
	$customers_data = array();
	foreach ( $customer_table['customer_data'] as $customer ) {
		$customers_data[] = array(
			'title'        => $customer['title'],
			'url'          => $customer['url'],
			'email'        => $customer['email'],
			'booked_trip'  => $customer['booked_trip'],
			'amount_spent' => $customer['amount_spent'],
			'date'         => $customer['date'],
		);
	}
	$customers['customer_data']  = $customers_data;
	$customers['customer_count'] = $customer_table['customer_count'];
	return $customers;
}


/**
 * WP Travel Engine Analytics Dashboard.
 */
function wptravelengine_analytics_dashboard_data() {
	global $wpdb;
	$queries_data    = array();
	$customer_data   = array();
	$customers_range = array();
	$trip_name       = '';
	// Today data.
	$todays_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND DATE_FORMAT(p.post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')" );
	// Current month data.
	$current_month_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft')AND MONTH(p.post_date) = MONTH(NOW()) AND YEAR(p.post_date) = YEAR(NOW())" );
	// Last month data.
	$last_month_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings, (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') AND YEAR(p.post_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND MONTH(p.post_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))" );
	// All time data.
	$overall_data = $wpdb->get_results( "SELECT SUM(pm.meta_value) AS total_amount, SUM(CASE WHEN pm.meta_key = 'paid_amount' THEN pm.meta_value ELSE 0 END) AS total_earnings , (COUNT(DISTINCT p.ID)) AS total_bookings FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key IN ('paid_amount', 'due_amount') AND p.post_type = 'booking' AND p.post_status IN ('publish', 'draft') " );
	// Recent bookings data.
	$bookings_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, ';', 18), ':', -1) AS fname, SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, ';', 20), ':', -1) AS lname, SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, ';', 11), ':', -1) AS trip_name, (pmpa.meta_value + pmda.meta_value) AS cost, pmpa.meta_value AS paid_amount, DATE_FORMAT(p.post_date, '%Y-%m-%d') AS booking_date, p.ID AS booking_id FROM {$wpdb->postmeta} AS pm LEFT JOIN {$wpdb->postmeta} AS pmpa ON pm.post_id = pmpa.post_id LEFT JOIN {$wpdb->postmeta} AS pmda ON pm.post_id = pmda.post_id LEFT JOIN {$wpdb->posts} AS p ON pm.post_id = p.id WHERE pm.meta_key = 'wp_travel_engine_booking_setting' AND pmpa.meta_key = 'paid_amount' AND pmda.meta_key = 'due_amount' AND pm.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type = 'booking' AND post_status IN ('publish', 'draft')) ORDER BY booking_date DESC LIMIT 4" );

	// Customer data.
	$current_month_customer_data = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')" );
	$prev_month_customer_data    = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')" );
	$current_year_customer_data  = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(NOW(), '%Y')" );
	$prev_year_customer_data     = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')" );
	$prev_year_current_month     = $wpdb->get_results( "SELECT COUNT(*) AS customer_count FROM {$wpdb->posts} WHERE post_type='customer' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 YEAR), '%Y-%m')" );
	$current_month_range         = 0 != $prev_month_customer_data[0]->customer_count ? ( ( $current_month_customer_data[0]->customer_count - $prev_month_customer_data[0]->customer_count ) / $prev_month_customer_data[0]->customer_count ) * 100 : ( 0 === $current_month_customer_data[0]->customer_count ? 0 : 200 );
	$current_year_range          = 0 != $prev_year_customer_data[0]->customer_count ? ( ( $current_year_customer_data[0]->customer_count - $prev_year_customer_data[0]->customer_count ) / $prev_year_customer_data[0]->customer_count ) * 100 : ( 0 === $current_year_customer_data[0]->customer_count ? 0 : 200 );
	$month_year_range            = 0 != $prev_year_current_month[0]->customer_count ? ( ( $current_month_customer_data[0]->customer_count - $prev_year_current_month[0]->customer_count ) / $prev_year_current_month[0]->customer_count ) * 100 : ( 0 === $current_month_customer_data[0]->customer_count ? 0 : 200 );
	$customers_range             = array(
		'mtd' => array(
			$current_month_customer_data[0]->customer_count,
			$current_month_range > 0 ? '+' . round( $current_month_range ) . '%' : round( $current_month_range ) . '%',
			$current_month_range > 0 ? 'positive' : 'negative',
		),
		'yom' => array(
			$current_month_customer_data[0]->customer_count,
			$month_year_range > 0 ? '+' . round( $month_year_range ) . '%' : round( $month_year_range ) . '%',
			$month_year_range > 0 ? 'positive' : 'negative',
		),
		'yoy' => array(
			$current_year_customer_data[0]->customer_count,
			$current_year_range > 0 ? '+' . round( $current_year_range ) . '%' : round( $current_year_range ) . '%',
			$current_year_range > 0 ? 'positive' : 'negative',
		),
	);
	foreach ( $bookings_data as $data ) {
		$first_name  = str_replace( '"', '', $data->fname );
		$last_name   = str_replace( '"', '', $data->lname );
		$trip_name   = str_replace( '"', '', $data->trip_name );
		$trip_cost   = $data->cost;
		$paid_amount = $data->paid_amount;
		$date        = $data->booking_date;
		$booked_date = wp_date( 'M d,Y', strtotime( $date ) );
		$id          = $data->booking_id;

		$customer_name   = $first_name . ' ' . $last_name;
		$customer_data[] = array(
			'title'        => html_entity_decode( $customer_name ),
			'url'          => add_query_arg(
				array(
					'post'   => $id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			),
			'booked_trip'  => $trip_name,
			'amount_spent' => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $paid_amount ) ) ),
			'trip_cost'    => preg_replace( '/\s+/', '', html_entity_decode( wte_get_formated_price( $trip_cost ) ) ),
			'date'         => $booked_date,
		);
	}
	$queries_data = array(
		'today'          => $todays_data,
		'current_month'  => $current_month_data,
		'last_month'     => $last_month_data,
		'overall'        => $overall_data,
		'customer_data'  => $customer_data,
		'customer_range' => $customers_range,
	);
	return $queries_data;
}

/**
 * Taxonomy Data.
 *
 * @param string $start_date Start Date.
 * @param string $end_date End Date.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomy( $start_date, $end_date, $source ) {
	$data                 = array();
	$datefilters_taxonomy = array();
	if ( '' != $start_date || '' != $end_date ) {
		$datefilters_taxonomy = wptravelengine_analytics_queries_with_date( $start_date, $end_date, 'taxonomy' );
		$data                 = array(
			'datefilters_taxonomy' => $datefilters_taxonomy,
			'start_date'           => $start_date,
			'end_date'             => $end_date,
		);
	}
	$data = wptravelengine_taxonomy_data( $data, $source );
	return $data;
}

/**
 * Calculation of taxonomy data.
 *
 * @param array  $data Taxonomy Data.
 * @param string $source Source.
 */
function wptravelengine_taxonomy_data( $data, $source ) {
	$taxonomy_data = array();
	if ( isset( $data['end_date'] ) && '' != $data['end_date'] ) {
		$datefilters_taxonomy = $data['datefilters_taxonomy'];
	}
	$taxonomy_calculation = wptravelengine_analytics_taxonomies_data_calculation( $datefilters_taxonomy, $source );
	$taxonomy_data        = isset( $taxonomy_calculation[0][0] ) ? $taxonomy_calculation[0][0] : '';
	return $taxonomy_data;
}

/**
 * Analytics Taxonomy Table.
 *
 * @param string $per_page Per Page.
 * @param string $page Page.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomy_table( $per_page, $page, $source ) {
	$data           = array();
	$offset         = 1 === $page ? 0 : ( $page - 1 ) * $per_page;
	$taxonomy_table = wptravelengine_analytics_taxonomy_query( $per_page, $offset, $source );
	$data           = array(
		'' . strtolower( $source ) . '_table_data' => $taxonomy_table[ '' . strtolower( $source ) . '_data' ],
		'total_' . strtolower( $source ) . ''      => $taxonomy_table[ 'total_' . strtolower( $source ) . '' ],
	);

	$data = wptravelengine_analytics_taxonomy_table_data( $data, $source );
	return $data;
}

/**
 * Analytics Taxonomy Chart.
 *
 * @param string $per_page Per Page.
 * @param string $page Page.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomy_chart( $per_page, $page, $source ) {
	$data           = array();
	$offset         = 1 === $page ? 0 : ( $page - 1 ) * $per_page;
	$taxonomy_table = wptravelengine_analytics_taxonomy_query( $per_page, $offset, $source );
	$data           = array(
		'' . strtolower( $source ) . '_table_data' => $taxonomy_table[ '' . strtolower( $source ) . '_data' ],
		'total_' . strtolower( $source ) . ''      => $taxonomy_table[ 'total_' . strtolower( $source ) . '' ],
	);

	$data = wptravelengine_analytics_taxonomy_chart_data( $data, $source );
	return $data;
}

/**
 * Taxonomy Tab Date Filter Query.
 *
 * @param string $filter_type Filter Type.
 */
function wptravelengine_analytics_taxonomy_datefilter_query( $filter_type ) {
	global $wpdb;
	$queries_data    = array();
	$top_destination = array();
	if ( 'today' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'yesterday' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m-%d') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'this_week' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(NOW(), '%Y-%U')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'last_week' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%U') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 WEEK), '%Y-%U')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'this_month' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'last_month' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAl 1 MONTH), '%Y-%m')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'this_year' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(NOW(), '%Y')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'last_year' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND DATE_FORMAT(post_date, '%Y') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y')) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	if ( 'this_quarter' === $filter_type ) {
		$top_destination = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) as total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') AND YEAR(post_date) = YEAR(NOW()) AND QUARTER(post_date) = QUARTER(NOW())) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	}
	$queries_data = array( $top_destination );
	return $queries_data;
}

/**
 * Calculation of taxonomy tab datefilter data.
 *
 * @param array  $data Taxonomy Date Filter Data.
 * @param string $filter_type Taxonomy Date Filter Type.
 */
function wptravelengine_analytics_taxonomy_datefilter_data( $data, $filter_type, $source ) {
	$datefilter_data        = array();
	$datefilter_destination = array();
	$datefilters            = $data['datefilter_data'];
	$taxonomy_calculation   = wptravelengine_analytics_taxonomies_data_calculation( $datefilters[0], $source );
	$datefilter_destination[ 'top_' . strtolower( $source ) . '' ] = isset( $taxonomy_calculation[0][0] ) ? $taxonomy_calculation[0][0] : '';
	return $datefilter_destination;
}

/**
 * Analytics Destination Query.
 *
 * @param string $per_page Per Page.
 * @param string $offset offset.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomy_query( $per_page, $offset, $source ) {
	global $wpdb;
	$queries_data = array();
	$data         = array();
	$queries_data = $wpdb->get_results( "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(order_trips.meta_value, ';', 3),':', -1) AS trip_id, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) AS total_earnings, SUM(IF(paid_amount.meta_key = 'paid_amount',paid_amount.meta_value,0)) + SUM(IF(due_amount.meta_key = 'due_amount', due_amount.meta_value, 0)) AS booking_value, COUNT(*) AS sales_count FROM {$wpdb->postmeta} AS order_trips LEFT JOIN {$wpdb->postmeta} AS paid_amount ON order_trips.post_id = paid_amount.post_id LEFT JOIN {$wpdb->postmeta} AS due_amount ON order_trips.post_id = due_amount.post_id WHERE order_trips.meta_key = 'order_trips' AND paid_amount.meta_key = 'paid_amount' AND due_amount.meta_key = 'due_amount' AND order_trips.post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type='booking' AND post_status IN ('publish', 'draft') ) GROUP BY trip_id ORDER BY sales_count DESC, CAST(trip_id AS UNSIGNED) ASC" );
	$data         = wptravelengine_analytics_taxonomies_data( $queries_data, $source, $offset, $per_page );
	return $data;
}

/**
 * Analytics Taxonomy Data.
 *
 * @param array  $queries_data Query Data.
 * @param string $source Source.
 * @param string $offset Offset.
 * @param string $per_page Per Page.
 */
function wptravelengine_analytics_taxonomies_data( $queries_data, $source, $offset, $per_page ) {
	$table_data           = array();
	$taxonomy_calculation = wptravelengine_analytics_taxonomies_data_calculation( $queries_data, $source );
	update_option( 'wpte_' . $source . '_table_data', $taxonomy_calculation[0] );
	$structured_data                                     = get_option( 'wpte_' . $source . '_table_data' );
	$updated_data                                        = array_slice( $structured_data, $offset, $per_page );
	$table_data[ '' . strtolower( $source ) . '_data' ]  = $updated_data;
	$table_data[ 'total_' . strtolower( $source ) . '' ] = count( $taxonomy_calculation[1] );
	return $table_data;
}

/**
 * Calculation of Analytics Destination Table Data.
 *
 * @param array  $data Analytics Destination Table Data.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomy_table_data( $data, $source ) {
	$taxonomies_data = array();
	$taxonomy_table  = $data[ '' . strtolower( $source ) . '_table_data' ];
	$taxonomyt_data  = array();
	foreach ( $taxonomy_table as $tax ) {
		$taxonomyt_data[] = array(
			'title'         => $tax['title'],
			'url'           => $tax['url'],
			'sales_count'   => $tax['sales_count'],
			'earnings'      => $tax['earnings'],
			'booking_value' => $tax['booking_value'],
		);
	}
	$taxonomies_data[ '' . strtolower( $source ) . '_data' ]  = $taxonomyt_data;
	$taxonomies_data[ 'total_' . strtolower( $source ) . '' ] = $data[ 'total_' . strtolower( $source ) . '' ];
	return $taxonomies_data;
}

/**
 * Calculation of Analytics Taxonomy Chart Data.
 *
 * @param array  $data Analytics Taxonomy Chart Data.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomy_chart_data( $data, $source ) {
	$taxonomies_data = array();
	$taxonomy_table  = $data[ '' . strtolower( $source ) . '_table_data' ];
	$taxonomyt_data  = array();
	foreach ( $taxonomy_table as $tax ) {
		$taxonomyt_data[] = array(
			'title'         => $tax['title'],
			'url'           => $tax['url'],
			'sales_count'   => $tax['sales_count'],
			'earnings'      => $tax['earnings'],
			'booking_value' => $tax['booking_value'],
		);
	}
	$taxonomies_data[ '' . strtolower( $source ) . '_data' ]  = $taxonomyt_data;
	$taxonomies_data[ 'total_' . strtolower( $source ) . '' ] = $data[ 'total_' . strtolower( $source ) . '' ];
	return $taxonomies_data;
}

/**
 * Calculation of data as per taxonomy.
 *
 * @param array  $queries_data Taxonomy Data.
 * @param string $source Source.
 */
function wptravelengine_analytics_taxonomies_data_calculation( $queries_data, $source ) {
	$taxonomy_data = array();
	$count_sum     = array();
	$term          = array();
	foreach ( $queries_data as $trip ) {
		$taxonomies = $trip->trip_id;
		if ( 'Destinations' === $source || 'destinations' === $source ) {
			$termlist = get_the_terms( $taxonomies, 'destination' );
		} elseif ( 'Activities' === $source ) {
			$termlist = get_the_terms( $taxonomies, 'activities' );
		} elseif ( 'TripTypes' === $source ) {
			$termlist = get_the_terms( $taxonomies, 'trip_types' );
		}
		if ( is_array( $termlist ) ) {
			foreach ( $termlist as $terms ) {
				$term                                 = $terms->term_id;
				$termname                             = $terms->name;
				$value                                = $term;
				$count_sum[ $value ]['name']          = isset( $count_sum[ $value ]['name'] ) ? $count_sum[ $value ]['name'] : $termname;
				$count_sum[ $value ]['count']         = isset( $count_sum[ $value ]['count'] ) ? $count_sum[ $value ]['count'] + $trip->sales_count : (int) $trip->sales_count;
				$count_sum[ $value ]['sum']           = isset( $count_sum[ $value ]['sum'] ) ? $count_sum[ $value ]['sum'] + $trip->total_earnings : (int) $trip->total_earnings;
				$count_sum[ $value ]['booking_value'] = isset( $count_sum[ $value ]['booking_value'] ) ? $count_sum[ $value ]['booking_value'] + $trip->booking_value : (int) $trip->booking_value;
			}
		}
	}

	// Sort the new array based on the count.
	uasort(
		$count_sum,
		function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);

	foreach ( $count_sum as $value => $data ) {
		$taxonomy_data[] = array(
			'term_id'       => $value,
			'title'         => $data['name'],
			'url'           => add_query_arg(
				array(
					'post'   => $value,
					'action' => 'edit',
				),
				admin_url( 'term.php?taxonomy=' . $source . '_&tag_ID=' . $value . '&post_type=trip' )
			),
			'earnings'      => $data['sum'],
			'booking_value' => $data['booking_value'],
			'sales_count'   => $data['count'],
		);
	}
	return array( $taxonomy_data, $count_sum );
}
