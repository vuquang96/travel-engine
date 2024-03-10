<?php
return array(
	'label'       => __( 'Display', 'wp-travel-engine' ),
	'order'       => 3,
	'has_updates' => 'wte_note_5.6.4',
	'sub_tabs'    => array(
		'trip-card'   => array(
			'label'        => __( 'Trip Card/Listing', 'wp-travel-engine' ),
			'content_path' => __DIR__ . '/trip-card.php',
			'current'      => true,
		),
		'single-trip' => array(
			'label'        => __( 'Single Trip', 'wp-travel-engine' ),
			'content_path' => __DIR__ . '/single-trip.php',
			'has_updates' => 'wte_note_5.6.4',
		),
		'trip-archive' => array(
			'label'        => __( 'Trip Archive', 'wp-travel-engine' ),
			'content_path' => __DIR__ . '/trip-archive.php',
		),
		'labels' => array(
			'label'        => __( 'Labels', 'wp-travel-engine' ),
			'content_path' => __DIR__ . '/labels.php',
		),
	),
);
