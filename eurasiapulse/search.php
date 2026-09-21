<?php
/**
 * Search results.
 *
 * @package EurasiaPulse
 */

get_header();
$eurasiapulse_query_text = get_search_query();
?>
<div class="container">
	<header class="archive-header">
		<p class="kicker"><?php esc_html_e( 'Search', 'eurasiapulse' ); ?></p>
		<h1 class="archive-header__title">
			<?php
			if ( $eurasiapulse_query_text ) {
				/* translators: %s: search query */
				echo esc_html( sprintf( __( 'Results for “%s”', 'eurasiapulse' ), $eurasiapulse_query_text ) );
			} else {
				esc_html_e( 'Search', 'eurasiapulse' );
			}
			?>
		</h1>
		<?php get_search_form(); ?>
	</header>
	<?php get_template_part( 'template-parts/loop' ); ?>
</div>
<?php
get_footer();
