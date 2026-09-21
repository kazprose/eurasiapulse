<?php
/**
 * Compact card: small 3:2 thumbnail beside the headline (or text-only when
 * the post has no image). "row" variant is used in archive lists.
 *
 * @package EurasiaPulse
 *
 * @var array $args {
 *   post       int|WP_Post
 *   show_image bool      default true
 *   variant    ''|'row'  default ''
 *   size       string    default 'ep-32-s'
 *   sizes      string    default '120px'
 *   heading    'h2'|'h3' default 'h3'
 *   show_dek   bool      default false
 *   dek_words  int       default 0
 *   meta       string[]  default [ 'region', 'category' ]
 * }
 */

$eurasiapulse_post = get_post( $args['post'] ?? null );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_row     = isset( $args['variant'] ) && 'row' === $args['variant'];
$eurasiapulse_heading = ( isset( $args['heading'] ) && 'h2' === $args['heading'] ) ? 'h2' : 'h3';
$eurasiapulse_meta    = $args['meta'] ?? array( 'region', 'category' );
$eurasiapulse_dek     = ! empty( $args['show_dek'] ) ? eurasiapulse_dek( $eurasiapulse_post, (int) ( $args['dek_words'] ?? 0 ) ) : '';
$eurasiapulse_thumb   = '';
if ( ! isset( $args['show_image'] ) || $args['show_image'] ) {
	$eurasiapulse_thumb = eurasiapulse_image(
		$eurasiapulse_post,
		'32',
		$args['size'] ?? ( $eurasiapulse_row ? 'ep-32-m' : 'ep-32-s' ),
		array(
			'sizes'         => $args['sizes'] ?? ( $eurasiapulse_row ? '(min-width: 900px) 240px, (min-width: 600px) 200px, 100vw' : '120px' ),
			'loading'       => $args['loading'] ?? 'lazy',
			'fetchpriority' => $args['fetchpriority'] ?? '',
		)
	);
}
$eurasiapulse_class = 'card card--compact' . ( $eurasiapulse_row ? ' card--row' : '' ) . ( $eurasiapulse_thumb ? '' : ' card--noimg' );
?>
<article class="<?php echo esc_attr( $eurasiapulse_class ); ?>">
	<?php if ( $eurasiapulse_thumb ) : ?>
		<a class="card__thumb" href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo $eurasiapulse_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?></a>
	<?php endif; ?>
	<div class="card__body">
		<<?php echo $eurasiapulse_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?> class="headline"><a href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>"><?php echo esc_html( get_the_title( $eurasiapulse_post ) ); ?></a></<?php echo $eurasiapulse_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $eurasiapulse_dek ) : ?>
			<p class="dek"><?php echo esc_html( $eurasiapulse_dek ); ?></p>
		<?php endif; ?>
		<?php eurasiapulse_meta_line( $eurasiapulse_post, $eurasiapulse_meta ); ?>
	</div>
</article>
