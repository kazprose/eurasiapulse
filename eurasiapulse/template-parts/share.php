<?php
/**
 * Share links: plain intent URLs (X, Telegram, LinkedIn) and a copy-link button.
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post }
 */

$eurasiapulse_post = get_post( $args['post'] ?? get_the_ID() );
if ( ! $eurasiapulse_post ) {
	return;
}
$eurasiapulse_items = eurasiapulse_share_items( $eurasiapulse_post );
?>
<div class="share">
	<span class="share__label section__title"><?php esc_html_e( 'Share', 'eurasiapulse' ); ?></span>
	<ul class="share__list">
		<?php foreach ( $eurasiapulse_items as $eurasiapulse_item ) : ?>
			<li><a href="<?php echo esc_url( $eurasiapulse_item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $eurasiapulse_item['label'] ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'eurasiapulse' ); ?></span></a></li>
		<?php endforeach; ?>
		<li>
			<button type="button" class="share__copy" hidden data-copy-link="<?php echo esc_url( get_permalink( $eurasiapulse_post ) ); ?>" data-copied-label="<?php esc_attr_e( 'Link copied', 'eurasiapulse' ); ?>"><?php esc_html_e( 'Copy link', 'eurasiapulse' ); ?></button>
		</li>
	</ul>
	<span class="share__status" id="share-status" role="status" aria-live="polite"></span>
</div>
