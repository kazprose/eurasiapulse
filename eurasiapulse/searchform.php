<?php
/**
 * Accessible search form (label is visually hidden, unique id per instance).
 *
 * @package EurasiaPulse
 */

$eurasiapulse_search_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $eurasiapulse_search_id ); ?>"><?php esc_html_e( 'Search articles', 'eurasiapulse' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $eurasiapulse_search_id ); ?>" class="search-form__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search articles', 'eurasiapulse' ); ?>" required>
	<button type="submit" class="search-form__submit"><?php esc_html_e( 'Search', 'eurasiapulse' ); ?></button>
</form>
