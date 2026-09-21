<?php
/** Activate the eurasiapulse theme in the local WordPress. Run: php dev/activate.php */
$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
require __DIR__ . '/../.local/wordpress/wp-load.php';
$theme = wp_get_theme( 'eurasiapulse' );
if ( ! $theme->exists() ) {
	echo "Theme not found\n";
	exit( 1 );
}
$errors = $theme->errors();
if ( $errors ) {
	echo 'Theme errors: ' . implode( '; ', $errors->get_error_messages() ) . "\n";
	exit( 1 );
}
switch_theme( 'eurasiapulse' );
echo 'Active theme: ' . get_option( 'stylesheet' ) . "\n";
