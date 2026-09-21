<?php
/**
 * Text-only card: headline + meta, or author name first (Opinion block).
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post, author_first: bool, heading: 'h2'|'h3', meta: string[] }
 */

$eurasiapulse_post = get_post( $args['post'] ?? null );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_heading = ( isset( $args['heading'] ) && 'h2' === $args['heading'] ) ? 'h2' : 'h3';
$eurasiapulse_author  = ! empty( $args['author_first'] );
?>
<article class="card card--text">
	<?php if ( $eurasiapulse_author ) : ?>
		<p class="card__author"><a href="<?php echo esc_url( get_author_posts_url( (int) $eurasiapulse_post->post_author ) ); ?>"><?php echo esc_html( get_the_author_meta( 'display_name', (int) $eurasiapulse_post->post_author ) ); ?></a></p>
	<?php endif; ?>
	<<?php echo $eurasiapulse_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?> class="headline"><a href="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>"><?php echo esc_html( get_the_title( $eurasiapulse_post ) ); ?></a></<?php echo $eurasiapulse_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	if ( ! $eurasiapulse_author ) {
		eurasiapulse_meta_line( $eurasiapulse_post, $args['meta'] ?? array( 'region', 'category' ) );
	}
	?>
</article>
