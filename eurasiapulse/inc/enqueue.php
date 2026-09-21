<?php
/**
 * Front-end assets: one stylesheet, one small script, font preloads.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache-busting version based on file modification time.
 *
 * @param string $relative Path relative to the theme root.
 * @return string
 */
function eurasiapulse_asset_version( $relative ) {
	$file = EURASIAPULSE_DIR . '/' . ltrim( $relative, '/' );
	$time = file_exists( $file ) ? filemtime( $file ) : false;
	return $time ? EURASIAPULSE_VERSION . '.' . $time : EURASIAPULSE_VERSION;
}

/**
 * Enqueue styles and scripts.
 */
function eurasiapulse_enqueue_assets() {
	wp_enqueue_style(
		'eurasiapulse-main',
		EURASIAPULSE_URI . '/assets/css/main.css',
		array(),
		eurasiapulse_asset_version( 'assets/css/main.css' )
	);

	wp_enqueue_script(
		'eurasiapulse-main',
		EURASIAPULSE_URI . '/assets/js/main.js',
		array(),
		eurasiapulse_asset_version( 'assets/js/main.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	// Core adds these for classic themes; the theme styles blocks itself and
	// ships its own small preset classes instead of the ~12 KB global-styles block.
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'eurasiapulse_enqueue_assets', 20 );

/**
 * Preload the two Latin font files used above the fold (variable weight axes).
 */
function eurasiapulse_preload_fonts() {
	$fonts = array(
		'source-serif-4-latin-wght-normal.woff2',
		'inter-latin-wght-normal.woff2',
	);
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( EURASIAPULSE_URI . '/assets/fonts/' . $font )
		);
	}
}
add_action( 'wp_head', 'eurasiapulse_preload_fonts', 1 );

/**
 * Swap the "no-js" class before first paint so CSS can rely on it.
 * Inline on purpose: it has to run before the stylesheet applies.
 */
function eurasiapulse_js_class_script() {
	echo "<script>document.documentElement.className=document.documentElement.className.replace('no-js','js');</script>\n";
}
add_action( 'wp_head', 'eurasiapulse_js_class_script', 0 );
