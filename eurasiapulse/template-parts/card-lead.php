<?php
/**
 * Lead story card: large 16:9 image with the headline over it (design default)
 * or below it, subtitle, byline and reading time. Degrades to a text card
 * when there is no featured image.
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post }
 */

$eurasiapulse_post = get_post( $args['post'] ?? null );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_has_image = has_post_thumbnail( $eurasiapulse_post );
$eurasiapulse_overlay   = $eurasiapulse_has_image && 'overlay' === eurasiapulse_mod( 'lead_style' );
$eurasiapulse_dek       = eurasiapulse_dek( $eurasiapulse_post, 32 );
$eurasiapulse_caption   = $eurasiapulse_has_image ? wp_get_attachment_caption( get_post_thumbnail_id( $eurasiapulse_post ) ) : '';
$eurasiapulse_credit    = get_post_meta( $eurasiapulse_post->ID, 'ep_image_credit', true );
?>
<article class="card card--lead <?php echo $eurasiapulse_overlay ? 'card--lead-overlay' : 'card--lead-stacked'; ?>">
	<?php if ( $eurasiapulse_has_image ) : ?>
		<figure class="figure figure--lead">
			<div class="lead-hero">
				<a class="figure__link" href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php
					echo eurasiapulse_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
						$eurasiapulse_post,
						'169',
						'ep-169-l',
						array(
							'sizes'         => '(min-width: 1240px) 790px, (min-width: 900px) 66vw, 100vw',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>
				</a>
				<?php if ( $eurasiapulse_overlay ) : ?>
					<div class="lead-hero__overlay">
						<?php eurasiapulse_kicker( $eurasiapulse_post ); ?>
						<h2 class="headline"><a href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>"><?php echo esc_html( get_the_title( $eurasiapulse_post ) ); ?></a></h2>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $eurasiapulse_caption || $eurasiapulse_credit ) : ?>
				<figcaption class="figure__caption">
					<?php echo esc_html( $eurasiapulse_caption ); ?>
					<?php if ( $eurasiapulse_credit ) : ?>
						<span class="figure__credit"><?php echo esc_html( $eurasiapulse_credit ); ?></span>
					<?php endif; ?>
				</figcaption>
			<?php endif; ?>
		</figure>
	<?php endif; ?>

	<?php if ( ! $eurasiapulse_overlay ) : ?>
		<?php eurasiapulse_kicker( $eurasiapulse_post ); ?>
		<h2 class="headline"><a href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>"><?php echo esc_html( get_the_title( $eurasiapulse_post ) ); ?></a></h2>
	<?php endif; ?>

	<?php if ( $eurasiapulse_dek ) : ?>
		<p class="dek"><?php echo esc_html( $eurasiapulse_dek ); ?></p>
	<?php endif; ?>

	<?php eurasiapulse_meta_line( $eurasiapulse_post, array( 'author', 'reading' ) ); ?>
</article>
