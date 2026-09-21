<?php
/**
 * Homepage section block: one large 16:9 card plus a list of compact cards.
 *
 * @package EurasiaPulse
 *
 * @var array $args { slot: int }
 */

$eurasiapulse_slot = (int) ( $args['slot'] ?? 0 );
$eurasiapulse_cat  = $eurasiapulse_slot ? eurasiapulse_section_category( $eurasiapulse_slot ) : null;
if ( ! $eurasiapulse_cat ) {
	return;
}
$eurasiapulse_small = max( 1, (int) eurasiapulse_mod( 'section_small_count' ) );
$eurasiapulse_query = eurasiapulse_query(
	array(
		'posts_per_page' => 1 + $eurasiapulse_small,
		'cat'            => $eurasiapulse_cat->term_id,
	)
);
if ( ! $eurasiapulse_query->posts ) {
	return;
}
$eurasiapulse_posts = $eurasiapulse_query->posts;
$eurasiapulse_first = array_shift( $eurasiapulse_posts );
$eurasiapulse_link  = get_category_link( $eurasiapulse_cat );
$eurasiapulse_id    = 'section-' . $eurasiapulse_cat->slug . '-title';
?>
<section class="section cat" aria-labelledby="<?php echo esc_attr( $eurasiapulse_id ); ?>">
	<div class="section__head">
		<h2 class="section__title" id="<?php echo esc_attr( $eurasiapulse_id ); ?>"><?php echo esc_html( $eurasiapulse_cat->name ); ?></h2>
		<a class="section__more" href="<?php echo esc_url( $eurasiapulse_link ); ?>">
			<?php
			/* translators: %s: section name, e.g. Politics */
			echo esc_html( sprintf( __( 'All %s', 'eurasiapulse' ), $eurasiapulse_cat->name ) );
			?>
			&rarr;
		</a>
	</div>
	<div class="grid cat__grid">
		<div class="cat__lead">
			<?php
			get_template_part(
				'template-parts/card',
				'standard',
				array(
					'post'      => $eurasiapulse_first,
					'ratio'     => '169',
					'size'      => 'ep-169-m',
					'sizes'     => '(min-width: 1240px) 596px, (min-width: 900px) 50vw, 100vw',
					'show_dek'  => true,
					'dek_words' => 24,
					'caption'   => true,
					'meta'      => array( 'author', 'region' ),
				)
			);
			?>
		</div>
		<?php if ( $eurasiapulse_posts ) : ?>
			<div class="cat__list">
				<?php
				foreach ( $eurasiapulse_posts as $eurasiapulse_item ) {
					get_template_part(
						'template-parts/card',
						'compact',
						array(
							'post' => $eurasiapulse_item,
							'meta' => array( 'region' ),
						)
					);
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</section>
