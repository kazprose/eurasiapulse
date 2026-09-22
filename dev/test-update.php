<?php
/** Exercise the GitHub updater: php dev/test-update.php <wp-dir> <port> [upgrade] */
$dir  = $argv[1] ?? '.local/wordpress';
$port = $argv[2] ?? '8080';
$mock = in_array( '--mock', $argv, true );   // use the local GitHub API mock on :8090 (.local/ghmock)
if ( in_array( '--token', $argv, true ) ) {   // simulate a private repository with a token
	define( 'EURASIAPULSE_GITHUB_TOKEN', 'mock-token' );
}
$_SERVER['HTTP_HOST'] = '127.0.0.1:' . $port;
require __DIR__ . '/../' . $dir . '/wp-load.php';
if ( $mock ) {
	add_filter( 'eurasiapulse_github_api_base', static fn() => 'http://127.0.0.1:8090' );
	add_filter( 'http_allowed_safe_ports', static fn( $ports ) => array_merge( (array) $ports, array( 8090 ) ) );
	echo "mode: mock GitHub API" . ( defined( 'EURASIAPULSE_GITHUB_TOKEN' ) ? ' + token (private repo)' : ' (public repo)' ) . "\n";
}
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/update.php';
wp_set_current_user( 1 );
echo "installed version: " . wp_get_theme( 'eurasiapulse' )->get( 'Version' ) . "\n";
eurasiapulse_flush_release_cache();
$release = eurasiapulse_github_release();
echo "github release: " . wp_json_encode( $release ) . "\n";
delete_site_transient( 'update_themes' );
wp_update_themes(); // Core's check (may fail offline); our filter runs inside it.
$transient = get_site_transient( 'update_themes' );
if ( ! is_object( $transient ) || empty( $transient->response['eurasiapulse'] ) ) {
	// Isolate the theme's own logic from wordpress.org availability.
	$transient = eurasiapulse_check_for_update( (object) array( 'checked' => array( 'eurasiapulse' => wp_get_theme( 'eurasiapulse' )->get( 'Version' ) ), 'response' => array(), 'no_update' => array() ) );
	set_site_transient( 'update_themes', $transient );
	echo "(transient built directly via eurasiapulse_check_for_update)\n";
}
$offer = $transient->response['eurasiapulse'] ?? null;
echo "update offered: " . ( $offer ? wp_json_encode( $offer ) : 'none' ) . "\n";
if ( $offer ) {
	$auto = ( new WP_Automatic_Updater() )->should_update( 'theme', (object) $offer, get_theme_root() );
	echo "auto-update would run: " . ( $auto ? 'yes' : 'no' ) . ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ? ' (AUTOMATIC_UPDATER_DISABLED is set in this wp-config)' : '' ) . "\n";
	echo "theme opts in via auto_update_theme filter: " . ( apply_filters( 'auto_update_theme', false, (object) $offer ) ? 'yes' : 'no' ) . "\n";
}
if ( $offer && in_array( 'upgrade', $argv, true ) ) {
	$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->upgrade( 'eurasiapulse' );
	echo "upgrade result: " . ( true === $result ? 'true' : wp_json_encode( $result ) ) . "\n";
	wp_clean_themes_cache();
	echo "version after upgrade: " . wp_get_theme( 'eurasiapulse' )->get( 'Version' ) . "\n";
	echo "folder: " . ( is_dir( get_theme_root() . '/eurasiapulse' ) ? 'eurasiapulse/ intact' : 'MISSING' ) . "\n";
}
