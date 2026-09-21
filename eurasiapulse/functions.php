<?php
/**
 * EurasiaPulse theme bootstrap.
 *
 * Classic theme, no build step, no plugin dependencies. Everything is split
 * into small modules under /inc.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EURASIAPULSE_VERSION', '1.0.0' );
define( 'EURASIAPULSE_DIR', get_template_directory() );
define( 'EURASIAPULSE_URI', get_template_directory_uri() );

require EURASIAPULSE_DIR . '/inc/setup.php';
require EURASIAPULSE_DIR . '/inc/enqueue.php';
require EURASIAPULSE_DIR . '/inc/taxonomies.php';
require EURASIAPULSE_DIR . '/inc/meta.php';
require EURASIAPULSE_DIR . '/inc/customizer.php';
require EURASIAPULSE_DIR . '/inc/template-tags.php';
require EURASIAPULSE_DIR . '/inc/schema.php';
