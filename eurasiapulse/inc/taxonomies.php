<?php
/**
 * Custom taxonomies: region (hierarchical) and format (flat).
 *
 * Both are exposed to the REST API. The REST collection names are plural
 * ("regions", "formats") to mirror core ("categories", "tags") and, for
 * "format", to avoid clashing with the core posts endpoint's own "format"
 * property (post formats). Endpoints: /wp/v2/regions, /wp/v2/formats.
 * Post objects carry "regions" and "formats" arrays of term IDs.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the taxonomies.
 */
function eurasiapulse_register_taxonomies() {
	register_taxonomy(
		'region',
		array( 'post' ),
		array(
			'labels'            => array(
				'name'              => _x( 'Regions', 'taxonomy general name', 'eurasiapulse' ),
				'singular_name'     => _x( 'Region', 'taxonomy singular name', 'eurasiapulse' ),
				'search_items'      => __( 'Search regions', 'eurasiapulse' ),
				'all_items'         => __( 'All regions', 'eurasiapulse' ),
				'parent_item'       => __( 'Parent region', 'eurasiapulse' ),
				'parent_item_colon' => __( 'Parent region:', 'eurasiapulse' ),
				'edit_item'         => __( 'Edit region', 'eurasiapulse' ),
				'update_item'       => __( 'Update region', 'eurasiapulse' ),
				'add_new_item'      => __( 'Add new region', 'eurasiapulse' ),
				'new_item_name'     => __( 'New region name', 'eurasiapulse' ),
				'menu_name'         => __( 'Regions', 'eurasiapulse' ),
				'not_found'         => __( 'No regions found.', 'eurasiapulse' ),
				'back_to_items'     => __( '&larr; Back to regions', 'eurasiapulse' ),
			),
			'description'       => __( 'Geographic coverage area of an article.', 'eurasiapulse' ),
			'public'            => true,
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'regions',
			'rest_namespace'    => 'wp/v2',
			'query_var'         => true,
			'rewrite'           => array(
				'slug'         => 'region',
				'hierarchical' => true,
				'with_front'   => false,
			),
		)
	);

	register_taxonomy(
		'format',
		array( 'post' ),
		array(
			'labels'            => array(
				'name'                       => _x( 'Formats', 'taxonomy general name', 'eurasiapulse' ),
				'singular_name'              => _x( 'Format', 'taxonomy singular name', 'eurasiapulse' ),
				'search_items'               => __( 'Search formats', 'eurasiapulse' ),
				'all_items'                  => __( 'All formats', 'eurasiapulse' ),
				'edit_item'                  => __( 'Edit format', 'eurasiapulse' ),
				'update_item'                => __( 'Update format', 'eurasiapulse' ),
				'add_new_item'               => __( 'Add new format', 'eurasiapulse' ),
				'new_item_name'              => __( 'New format name', 'eurasiapulse' ),
				'menu_name'                  => __( 'Formats', 'eurasiapulse' ),
				'separate_items_with_commas' => __( 'Separate formats with commas', 'eurasiapulse' ),
				'choose_from_most_used'      => __( 'Choose from the most used formats', 'eurasiapulse' ),
				'not_found'                  => __( 'No formats found.', 'eurasiapulse' ),
				'back_to_items'              => __( '&larr; Back to formats', 'eurasiapulse' ),
			),
			'description'       => __( 'Editorial format: News, Analysis, Opinion, Explainer, Interview.', 'eurasiapulse' ),
			'public'            => true,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'formats',
			'rest_namespace'    => 'wp/v2',
			'query_var'         => true,
			'meta_box_cb'       => 'post_categories_meta_box', // Checkbox UI in the classic editor: it is a fixed vocabulary.
			'rewrite'           => array(
				'slug'       => 'format',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'eurasiapulse_register_taxonomies', 5 );
