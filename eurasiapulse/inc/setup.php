<?php
/**
 * Theme setup: supports, menus, image sizes, wp_head clean-up, comments policy.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme supports and menus.
 */
function eurasiapulse_setup() {
	load_theme_textdomain( 'eurasiapulse', EURASIAPULSE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'               => 60,
			'width'                => 320,
			'flex-height'          => true,
			'flex-width'           => true,
			'unlink-homepage-logo' => false,
		)
	);
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary'         => __( 'Primary navigation (sections)', 'eurasiapulse' ),
			'topbar'          => __( 'Top bar links (About, Newsletter)', 'eurasiapulse' ),
			'footer-sections' => __( 'Footer: Sections column', 'eurasiapulse' ),
			'footer-regions'  => __( 'Footer: Regions column', 'eurasiapulse' ),
			'footer-company'  => __( 'Footer: Company column', 'eurasiapulse' ),
			'footer-bottom'   => __( 'Footer: bottom row (pages)', 'eurasiapulse' ),
		)
	);

	// 16:9 crops (lead / section leads) and 3:2 crops (cards, thumbnails).
	add_image_size( 'ep-169-s', 480, 270, true );
	add_image_size( 'ep-169-m', 800, 450, true );
	add_image_size( 'ep-169-l', 1200, 675, true );
	add_image_size( 'ep-169-xl', 1600, 900, true );
	add_image_size( 'ep-32-s', 240, 160, true );
	add_image_size( 'ep-32-m', 480, 320, true );
	add_image_size( 'ep-32-l', 720, 480, true );
	add_image_size( 'ep-32-xl', 1080, 720, true );

	$GLOBALS['content_width'] = 680;
}
add_action( 'after_setup_theme', 'eurasiapulse_setup' );

/**
 * Expose the custom crops in the editor image-size dropdown.
 *
 * @param array $sizes Size name => label.
 * @return array
 */
function eurasiapulse_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'ep-169-l' => __( 'Wide 16:9 (1200px)', 'eurasiapulse' ),
			'ep-32-l'  => __( 'Card 3:2 (720px)', 'eurasiapulse' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'eurasiapulse_image_size_names' );

/**
 * Remove head output the site does not need.
 */
function eurasiapulse_clean_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );

	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'wp_resource_hints', 2 );
	add_filter( 'the_generator', '__return_empty_string' );
}
add_action( 'init', 'eurasiapulse_clean_head' );

/**
 * Only load the CSS of core blocks that are actually used in the content.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Comments are closed by default; a Customizer switch re-enables them.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function eurasiapulse_comments_policy( $open, $post_id ) {
	if ( ! eurasiapulse_mod( 'enable_comments' ) ) {
		return false;
	}
	return $open;
}
add_filter( 'comments_open', 'eurasiapulse_comments_policy', 20, 2 );
add_filter( 'pings_open', 'eurasiapulse_comments_policy', 20, 2 );

/**
 * Shorter automatic excerpts for cards.
 *
 * @return int
 */
function eurasiapulse_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'eurasiapulse_excerpt_length' );

/**
 * Plain ellipsis for automatic excerpts.
 *
 * @return string
 */
function eurasiapulse_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'eurasiapulse_excerpt_more' );

/**
 * Body classes used by the stylesheet.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function eurasiapulse_body_classes( $classes ) {
	if ( is_singular() && ! has_post_thumbnail() ) {
		$classes[] = 'no-featured-image';
	}
	if ( has_custom_logo() ) {
		$classes[] = 'has-image-logo';
	}
	return $classes;
}
add_filter( 'body_class', 'eurasiapulse_body_classes' );

/**
 * Create the default vocabularies once, when the theme is activated.
 *
 * Nothing is overwritten: terms are only added when their slug is missing.
 * The lists mirror the editorial assumptions in DECISIONS.md and can be
 * edited freely afterwards.
 */
function eurasiapulse_seed_default_terms() {
	$vocab = array(
		'category' => array( 'Politics', 'Economy', 'Security', 'Energy', 'Diplomacy' ),
		'region'   => array( 'Kazakhstan', 'Uzbekistan', 'Kyrgyzstan', 'Tajikistan', 'Turkmenistan', 'Caucasus', 'Russia', 'China', 'Global' ),
		'format'   => array( 'News', 'Analysis', 'Opinion', 'Explainer', 'Interview' ),
	);
	foreach ( $vocab as $taxonomy => $names ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		foreach ( $names as $name ) {
			if ( ! term_exists( sanitize_title( $name ), $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy );
			}
		}
	}
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'eurasiapulse_seed_default_terms' );
