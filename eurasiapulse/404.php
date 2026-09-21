<?php
/**
 * 404: message, search form, section links.
 *
 * @package EurasiaPulse
 */

get_header();
?>
<div class="container">
	<header class="archive-header">
		<p class="kicker"><?php esc_html_e( 'Error 404', 'eurasiapulse' ); ?></p>
		<h1 class="archive-header__title"><?php esc_html_e( 'Page not found', 'eurasiapulse' ); ?></h1>
		<div class="archive-header__desc"><p><?php esc_html_e( 'The page you are looking for has moved or does not exist. Try a search, or start from one of the sections below.', 'eurasiapulse' ); ?></p></div>
		<?php get_search_form(); ?>
	</header>
	<nav class="link-list" aria-label="<?php esc_attr_e( 'Sections', 'eurasiapulse' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'eurasiapulse' ); ?></a>
		<?php
		foreach ( eurasiapulse_terms_to_items( array_merge( eurasiapulse_default_categories(), eurasiapulse_nav_format_terms() ) ) as $eurasiapulse_item ) {
			printf( '<a href="%s">%s</a>', esc_url( $eurasiapulse_item['url'] ), esc_html( $eurasiapulse_item['label'] ) );
		}
		?>
	</nav>
</div>
<?php
get_footer();
