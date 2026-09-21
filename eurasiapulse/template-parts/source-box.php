<?php
/**
 * "Source" box: original source name and link (nofollow).
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post }
 */

$eurasiapulse_post = get_post( $args['post'] ?? get_the_ID() );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_name = get_post_meta( $eurasiapulse_post->ID, 'ep_source_name', true );
$eurasiapulse_url  = get_post_meta( $eurasiapulse_post->ID, 'ep_source_url', true );
if ( ! $eurasiapulse_name && ! $eurasiapulse_url ) {
	return;
}
if ( ! $eurasiapulse_name ) {
	$eurasiapulse_name = wp_parse_url( $eurasiapulse_url, PHP_URL_HOST );
}
?>
<aside class="source-box" aria-labelledby="source-box-title">
	<h2 class="section__title" id="source-box-title"><?php esc_html_e( 'Source', 'eurasiapulse' ); ?></h2>
	<p>
		<?php if ( $eurasiapulse_url ) : ?>
			<a href="<?php echo esc_url( $eurasiapulse_url ); ?>" rel="nofollow noopener"><?php echo esc_html( $eurasiapulse_name ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $eurasiapulse_name ); ?>
		<?php endif; ?>
	</p>
</aside>
