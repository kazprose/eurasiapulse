<?php
/**
 * Homepage "Latest" list (Reuters-style, time stamps).
 *
 * @package EurasiaPulse
 */

$eurasiapulse_count = (int) eurasiapulse_mod( 'latest_count' );
if ( $eurasiapulse_count < 1 ) {
	return;
}
$eurasiapulse_query = eurasiapulse_query( array( 'posts_per_page' => $eurasiapulse_count ) );
if ( ! $eurasiapulse_query->posts ) {
	return;
}
$eurasiapulse_more = '';
$eurasiapulse_blog = (int) get_option( 'page_for_posts' );
if ( $eurasiapulse_blog ) {
	$eurasiapulse_more = get_permalink( $eurasiapulse_blog );
} else {
	$eurasiapulse_news = get_term_by( 'slug', 'news', 'format' );
	if ( $eurasiapulse_news instanceof WP_Term ) {
		$eurasiapulse_link = get_term_link( $eurasiapulse_news );
		$eurasiapulse_more = is_wp_error( $eurasiapulse_link ) ? '' : $eurasiapulse_link;
	}
}
$eurasiapulse_thumbs = (bool) eurasiapulse_mod( 'latest_thumbs' );
?>
<section class="section latest" aria-labelledby="latest-title">
	<div class="section__head">
		<h2 class="section__title" id="latest-title"><?php esc_html_e( 'Latest', 'eurasiapulse' ); ?></h2>
		<?php if ( $eurasiapulse_more ) : ?>
			<a class="section__more" href="<?php echo esc_url( $eurasiapulse_more ); ?>"><?php esc_html_e( 'All updates', 'eurasiapulse' ); ?> &rarr;</a>
		<?php endif; ?>
	</div>
	<ul class="latest__list">
		<?php
		foreach ( $eurasiapulse_query->posts as $eurasiapulse_item ) {
			get_template_part(
				'template-parts/card',
				'latest',
				array(
					'post'       => $eurasiapulse_item,
					'show_image' => $eurasiapulse_thumbs,
				)
			);
		}
		?>
	</ul>
</section>
