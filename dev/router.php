<?php
/**
 * Router for PHP's built-in server so WordPress pretty permalinks work.
 * Usage: php -S 127.0.0.1:8080 -t ../.local/wordpress router.php
 */
$root = realpath( $_SERVER['DOCUMENT_ROOT'] ); // php -S -t <absolute dir>
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = $root . str_replace( '/', DIRECTORY_SEPARATOR, $path );
if ( $path !== '/' && is_file( $file ) ) {
	return false; // serve static file / php file directly
}
if ( is_dir( $file ) && is_file( rtrim( $file, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'index.php' ) ) {
	$_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php';
	require rtrim( $file, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'index.php';
	return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
