<?php
/**
 * Homepage "By Region" strip (full-width navigation).
 *
 * @package EurasiaPulse
 */

if ( ! eurasiapulse_mod( 'show_regions' ) ) {
	return;
}
$eurasiapulse_terms = eurasiapulse_region_terms();
if ( ! $eurasiapulse_terms ) {
	return;
}
?>
<nav class="regions" aria-label="<?php esc_attr_e( 'By region', 'eurasiapulse' ); ?>">
	<div class="container regions__inner">
		<span class="section__title"><?php esc_html_e( 'By Region', 'eurasiapulse' ); ?></span>
		<?php
		foreach ( $eurasiapulse_terms as $eurasiapulse_term ) {
			$eurasiapulse_link = get_term_link( $eurasiapulse_term );
			if ( is_wp_error( $eurasiapulse_link ) ) {
				continue;
			}
			printf( '<a href="%s">%s</a>', esc_url( $eurasiapulse_link ), esc_html( $eurasiapulse_term->name ) );
		}
		?>
	</div>
</nav>
