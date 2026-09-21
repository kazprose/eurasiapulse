<?php
/**
 * Related articles: same region or category, newest first.
 *
 * @package EurasiaPulse
 *
 * @var array $args { post: int|WP_Post }
 */

$eurasiapulse_post  = get_post( $args['post'] ?? get_the_ID() );
$eurasiapulse_count = (int) eurasiapulse_mod( 'related_count' );
if ( ! $eurasiapulse_post || $eurasiapulse_count < 1 ) {
	return;
}

$eurasiapulse_tax_query = array( 'relation' => 'OR' );
$eurasiapulse_cats      = wp_get_post_categories( $eurasiapulse_post->ID, array( 'fields' => 'ids' ) );
if ( $eurasiapulse_cats && ! is_wp_error( $eurasiapulse_cats ) ) {
	$eurasiapulse_tax_query[] = array(
		'taxonomy' => 'category',
		'field'    => 'term_id',
		'terms'    => $eurasiapulse_cats,
	);
}
$eurasiapulse_regions = wp_get_post_terms( $eurasiapulse_post->ID, 'region', array( 'fields' => 'ids' ) );
if ( $eurasiapulse_regions && ! is_wp_error( $eurasiapulse_regions ) ) {
	$eurasiapulse_tax_query[] = array(
		'taxonomy' => 'region',
		'field'    => 'term_id',
		'terms'    => $eurasiapulse_regions,
	);
}

$eurasiapulse_base = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $eurasiapulse_count,
	'post__not_in'        => array( $eurasiapulse_post->ID ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
$eurasiapulse_query = null;
if ( count( $eurasiapulse_tax_query ) > 1 ) {
	$eurasiapulse_query = new WP_Query( array_merge( $eurasiapulse_base, array( 'tax_query' => $eurasiapulse_tax_query ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
}
if ( ! $eurasiapulse_query || ! $eurasiapulse_query->posts ) {
	$eurasiapulse_query = new WP_Query( $eurasiapulse_base );
}
if ( ! $eurasiapulse_query->posts ) {
	return;
}
?>
<section class="related" aria-labelledby="related-title">
	<div class="section__head">
		<h2 class="section__title" id="related-title"><?php esc_html_e( 'Related', 'eurasiapulse' ); ?></h2>
	</div>
	<div class="grid related__grid">
		<?php foreach ( $eurasiapulse_query->posts as $eurasiapulse_related ) : ?>
			<div>
				<?php
				get_template_part(
					'template-parts/card',
					'standard',
					array(
						'post'  => $eurasiapulse_related,
						'ratio' => '32',
						'size'  => 'ep-32-m',
						'sizes' => '(min-width: 1240px) 290px, (min-width: 900px) 25vw, (min-width: 600px) 50vw, 100vw',
						'meta'  => array( 'region', 'category' ),
					)
				);
				?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
