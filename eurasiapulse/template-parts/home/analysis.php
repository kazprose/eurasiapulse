<?php
/**
 * Homepage "Analysis" block: three standard cards from the chosen format.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_slug  = (string) eurasiapulse_mod( 'analysis_format' );
$eurasiapulse_count = (int) eurasiapulse_mod( 'analysis_count' );
if ( 'none' === $eurasiapulse_slug || $eurasiapulse_count < 1 ) {
	return;
}
$eurasiapulse_term = get_term_by( 'slug', $eurasiapulse_slug, 'format' );
if ( ! $eurasiapulse_term instanceof WP_Term ) {
	return;
}
$eurasiapulse_query = eurasiapulse_query(
	array(
		'posts_per_page' => $eurasiapulse_count,
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'format',
				'field'    => 'term_id',
				'terms'    => $eurasiapulse_term->term_id,
			),
		),
	)
);
if ( ! $eurasiapulse_query->posts ) {
	return;
}
$eurasiapulse_link = get_term_link( $eurasiapulse_term );
?>
<section class="section analysis" aria-labelledby="analysis-title">
	<div class="section__head">
		<h2 class="section__title" id="analysis-title"><?php echo esc_html( $eurasiapulse_term->name ); ?></h2>
		<?php if ( ! is_wp_error( $eurasiapulse_link ) ) : ?>
			<a class="section__more" href="<?php echo esc_url( $eurasiapulse_link ); ?>">
				<?php
				/* translators: %s: format name, e.g. Analysis */
				echo esc_html( sprintf( __( 'More %s', 'eurasiapulse' ), $eurasiapulse_term->name ) );
				?>
				&rarr;
			</a>
		<?php endif; ?>
	</div>
	<div class="grid analysis__grid">
		<?php foreach ( $eurasiapulse_query->posts as $eurasiapulse_item ) : ?>
			<div>
				<?php
				get_template_part(
					'template-parts/card',
					'standard',
					array(
						'post'        => $eurasiapulse_item,
						'ratio'       => '32',
						'size'        => 'ep-32-m',
						'show_kicker' => true,
						'show_dek'    => true,
						'dek_words'   => 24,
						'caption'     => true,
						'meta'        => array( 'author' ),
					)
				);
				?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
