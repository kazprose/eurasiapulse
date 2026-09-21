<?php
/**
 * Homepage "Analysis" block: three standard cards from the chosen format.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_slug  = (string) eurasiapulse_mod( 'analysis_format' );
$eurasiapulse_query = eurasiapulse_format_block_query( $eurasiapulse_slug, (int) eurasiapulse_mod( 'analysis_count' ) );
if ( ! $eurasiapulse_query || ! $eurasiapulse_query->posts ) {
	return;
}
$eurasiapulse_term = get_term_by( 'slug', $eurasiapulse_slug, 'format' );
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
