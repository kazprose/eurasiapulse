<?php
/**
 * Homepage "Opinion" block: author-first, text-only cards.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_slug  = (string) eurasiapulse_mod( 'opinion_format' );
$eurasiapulse_count = (int) eurasiapulse_mod( 'opinion_count' );
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
<section class="section opinion" aria-labelledby="opinion-title">
	<div class="section__head">
		<h2 class="section__title" id="opinion-title"><?php echo esc_html( $eurasiapulse_term->name ); ?></h2>
		<?php if ( ! is_wp_error( $eurasiapulse_link ) ) : ?>
			<a class="section__more" href="<?php echo esc_url( $eurasiapulse_link ); ?>">
				<?php
				/* translators: %s: format name, e.g. Opinion */
				echo esc_html( sprintf( __( 'All %s', 'eurasiapulse' ), $eurasiapulse_term->name ) );
				?>
				&rarr;
			</a>
		<?php endif; ?>
	</div>
	<div class="grid opinion__grid">
		<?php foreach ( $eurasiapulse_query->posts as $eurasiapulse_item ) : ?>
			<div>
				<?php
				get_template_part(
					'template-parts/card',
					'text-only',
					array(
						'post'         => $eurasiapulse_item,
						'author_first' => true,
					)
				);
				?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
