<?php
/**
 * "Latest" list item: time stamp, headline, meta, optional thumbnail.
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post, show_image: bool }
 */

$eurasiapulse_post = get_post( $args['post'] ?? null );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_thumb = ( ! isset( $args['show_image'] ) || $args['show_image'] )
	? eurasiapulse_image( $eurasiapulse_post, '32', 'ep-32-s', array( 'sizes' => '96px' ) )
	: '';
?>
<li class="latest__item<?php echo $eurasiapulse_thumb ? ' latest__item--thumb' : ''; ?>">
	<?php echo eurasiapulse_latest_time( $eurasiapulse_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>
	<div class="card__body">
		<h3 class="headline"><a href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>"><?php echo esc_html( get_the_title( $eurasiapulse_post ) ); ?></a></h3>
		<?php eurasiapulse_meta_line( $eurasiapulse_post, array( 'category', 'region' ) ); ?>
	</div>
	<?php if ( $eurasiapulse_thumb ) : ?>
		<a class="card__thumb" href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo $eurasiapulse_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?></a>
	<?php endif; ?>
</li>
