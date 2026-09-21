<?php
/**
 * Author box: name, biography, link to the author archive. No avatars
 * (no third-party requests).
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post }
 */

$eurasiapulse_post = get_post( $args['post'] ?? get_the_ID() );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_author_id = (int) $eurasiapulse_post->post_author;
$eurasiapulse_name      = get_the_author_meta( 'display_name', $eurasiapulse_author_id );
$eurasiapulse_bio       = get_the_author_meta( 'description', $eurasiapulse_author_id );
$eurasiapulse_url       = get_author_posts_url( $eurasiapulse_author_id );
?>
<aside class="author-box" aria-labelledby="author-box-title">
	<h2 class="section__title" id="author-box-title"><?php esc_html_e( 'About the author', 'eurasiapulse' ); ?></h2>
	<p class="author-box__name"><a href="<?php echo esc_url( $eurasiapulse_url ); ?>" rel="author"><?php echo esc_html( $eurasiapulse_name ); ?></a></p>
	<?php if ( $eurasiapulse_bio ) : ?>
		<p class="author-box__bio"><?php echo esc_html( $eurasiapulse_bio ); ?></p>
	<?php endif; ?>
	<p class="author-box__more">
		<a href="<?php echo esc_url( $eurasiapulse_url ); ?>">
			<?php
			/* translators: %s: author name */
			echo esc_html( sprintf( __( 'More articles by %s', 'eurasiapulse' ), $eurasiapulse_name ) );
			?>
			&rarr;
		</a>
	</p>
</aside>
