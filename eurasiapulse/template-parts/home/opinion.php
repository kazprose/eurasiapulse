<?php
/**
 * Homepage "Opinion" block: author-first, text-only cards. The posts are
 * reserved by front-page.php before the section blocks run, so opinion pieces
 * are never swallowed by a category block.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_slug  = (string) eurasiapulse_mod( 'opinion_format' );
$eurasiapulse_query = eurasiapulse_format_block_query( $eurasiapulse_slug, (int) eurasiapulse_mod( 'opinion_count' ) );
if ( ! $eurasiapulse_query || ! $eurasiapulse_query->posts ) {
	return;
}
$eurasiapulse_term = get_term_by( 'slug', $eurasiapulse_slug, 'format' );
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
