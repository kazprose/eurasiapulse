<?php
/**
 * Self-updates from GitHub Releases, without any plugin.
 *
 * How it works
 * - The repository is "owner/name" (Customizer > EurasiaPulse > Updates, or the
 *   EURASIAPULSE_GITHUB_REPO constant in wp-config.php; the constant wins).
 * - The latest release is fetched from the GitHub API (cached for 6 hours) and
 *   its tag (e.g. "v1.2.0") is compared with the theme's Version header.
 * - A newer release is injected into WordPress' own theme-update transient, so it
 *   shows under Dashboard > Updates / Appearance > Themes and installs with the
 *   standard updater. With "Install new releases automatically" on, WordPress
 *   auto-updates the theme on its twice-daily check.
 * - The release should carry an "eurasiapulse.zip" asset whose top folder is
 *   "eurasiapulse/" (built by .github/workflows/release.yml). Without an asset
 *   the GitHub zipball is used and its folder is renamed on install.
 * - Private repositories: define EURASIAPULSE_GITHUB_TOKEN in wp-config.php.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configured repository slug ("owner/name") or ''.
 *
 * @return string
 */
function eurasiapulse_github_repo() {
	$repo = defined( 'EURASIAPULSE_GITHUB_REPO' ) ? EURASIAPULSE_GITHUB_REPO : eurasiapulse_mod( 'github_repo' );
	return eurasiapulse_sanitize_repo( $repo );
}

/**
 * Transient key for the cached release.
 *
 * @return string
 */
function eurasiapulse_release_cache_key() {
	return 'eurasiapulse_release_' . md5( eurasiapulse_github_repo() );
}

/**
 * Forget the cached release (Customizer save, manual re-check).
 */
function eurasiapulse_flush_release_cache() {
	delete_site_transient( eurasiapulse_release_cache_key() );
}

/**
 * Latest release from GitHub: [ version, package, url, body ] (version '' when unknown).
 *
 * @return array|null Null when no repository is configured.
 */
function eurasiapulse_github_release() {
	$repo = eurasiapulse_github_repo();
	if ( ! $repo ) {
		return null;
	}
	$cached = get_site_transient( eurasiapulse_release_cache_key() );
	if ( is_array( $cached ) && isset( $cached['version'] ) ) {
		return $cached;
	}

	$args = array(
		'timeout' => 10,
		'headers' => array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'EurasiaPulse-Theme/' . EURASIAPULSE_VERSION . '; ' . home_url( '/' ),
		),
	);
	if ( defined( 'EURASIAPULSE_GITHUB_TOKEN' ) && EURASIAPULSE_GITHUB_TOKEN ) {
		$args['headers']['Authorization'] = 'Bearer ' . EURASIAPULSE_GITHUB_TOKEN;
	}

	$release  = array(
		'version' => '',
		'package' => '',
		'url'     => 'https://github.com/' . $repo . '/releases',
		'body'    => '',
	);
	$ttl      = HOUR_IN_SECONDS; // Retry sooner after a failure.
	$response = wp_remote_get( 'https://api.github.com/repos/' . $repo . '/releases/latest', $args );
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_array( $data ) && ! empty( $data['tag_name'] ) ) {
			$release['version'] = ltrim( sanitize_text_field( (string) $data['tag_name'] ), 'vV' );
			$release['url']     = ! empty( $data['html_url'] ) ? esc_url_raw( (string) $data['html_url'] ) : $release['url'];
			$release['body']    = wp_kses_post( (string) ( $data['body'] ?? '' ) );
			foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
				if ( isset( $asset['name'], $asset['browser_download_url'] ) && 'eurasiapulse.zip' === $asset['name'] ) {
					$release['package'] = esc_url_raw( (string) $asset['browser_download_url'] );
					break;
				}
			}
			if ( ! $release['package'] && ! empty( $data['zipball_url'] ) ) {
				$release['package'] = esc_url_raw( (string) $data['zipball_url'] );
			}
			$ttl = 6 * HOUR_IN_SECONDS;
		}
	}
	set_site_transient( eurasiapulse_release_cache_key(), $release, $ttl );
	return $release;
}

/**
 * Add a newer GitHub release to the theme-update transient.
 *
 * @param mixed $transient Update transient value.
 * @return mixed
 */
function eurasiapulse_check_for_update( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}
	$release = eurasiapulse_github_release();
	if ( ! $release || ! $release['version'] || ! $release['package'] ) {
		return $transient;
	}
	$slug    = get_template();
	$theme   = wp_get_theme( $slug );
	$current = (string) $theme->get( 'Version' );
	$item    = array(
		'theme'        => $slug,
		'new_version'  => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'requires'     => '6.5',
		'requires_php' => '8.1',
	);
	if ( version_compare( $release['version'], $current, '>' ) ) {
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$transient->response[ $slug ] = $item;
		if ( isset( $transient->no_update[ $slug ] ) ) {
			unset( $transient->no_update[ $slug ] );
		}
	} else {
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}
		$item['new_version']          = $current;
		$item['package']              = '';
		$transient->no_update[ $slug ] = $item;
		if ( isset( $transient->response[ $slug ] ) ) {
			unset( $transient->response[ $slug ] );
		}
	}
	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'eurasiapulse_check_for_update' );

/**
 * Normalise the extracted folder name so the theme keeps its directory name
 * (GitHub zipballs unpack as "owner-repo-hash/", possibly with the theme in a
 * subfolder).
 *
 * @param string      $source        Extracted source path.
 * @param string      $remote_source Temporary folder.
 * @param WP_Upgrader $upgrader      Upgrader.
 * @param array       $hook_extra    Extra data (contains 'theme' for theme updates).
 * @return string|WP_Error
 */
function eurasiapulse_upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
	if ( empty( $hook_extra['theme'] ) || get_template() !== $hook_extra['theme'] ) {
		return $source;
	}
	global $wp_filesystem;
	if ( ! $wp_filesystem ) {
		return $source;
	}
	$slug    = get_template();
	$source  = trailingslashit( $source );
	$desired = trailingslashit( $remote_source ) . $slug . '/';
	if ( $source === $desired ) {
		return $source;
	}
	// Repository layout: the theme lives in a subfolder of the archive.
	if ( $wp_filesystem->exists( $source . $slug . '/style.css' ) ) {
		return $source . $slug . '/';
	}
	// Zipball layout: the archive root is the theme itself, under a hashed name.
	if ( $wp_filesystem->exists( $source . 'style.css' ) && $wp_filesystem->move( $source, $desired ) ) {
		return $desired;
	}
	return $source;
}
add_filter( 'upgrader_source_selection', 'eurasiapulse_upgrader_source_selection', 10, 4 );

/**
 * Opt the theme into WordPress auto-updates when enabled in the Customizer.
 *
 * @param bool|null $update Whether to auto-update.
 * @param object    $item   Update item.
 * @return bool|null
 */
function eurasiapulse_auto_update( $update, $item ) {
	if ( isset( $item->theme ) && get_template() === $item->theme && eurasiapulse_github_repo() && eurasiapulse_mod( 'github_auto_update' ) ) {
		return true;
	}
	return $update;
}
add_filter( 'auto_update_theme', 'eurasiapulse_auto_update', 10, 2 );

/**
 * Re-check GitHub whenever an admin forces "Check again" on the Updates screen.
 */
function eurasiapulse_force_recheck() {
	if ( isset( $_GET['force-check'] ) && current_user_can( 'update_themes' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, core's own screen.
		eurasiapulse_flush_release_cache();
	}
}
add_action( 'load-update-core.php', 'eurasiapulse_force_recheck' );
