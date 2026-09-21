<?php
/**
 * Homepage lead block: hero story, cards under it, headline list beside it.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_lead = eurasiapulse_lead_query();
if ( ! $eurasiapulse_lead->posts ) {
	echo '<p class="empty">' . esc_html__( 'No articles have been published yet.', 'eurasiapulse' ) . '</p>';
	return;
}
$eurasiapulse_lead_post  = $eurasiapulse_lead->posts[0];
$eurasiapulse_sub_count  = (int) eurasiapulse_mod( 'lead_sub_count' );
$eurasiapulse_side_count = (int) eurasiapulse_mod( 'lead_side_count' );
$eurasiapulse_sub        = $eurasiapulse_sub_count > 0 ? eurasiapulse_query( array( 'posts_per_page' => $eurasiapulse_sub_count ) ) : null;
$eurasiapulse_side       = $eurasiapulse_side_count > 0 ? eurasiapulse_query( array( 'posts_per_page' => $eurasiapulse_side_count ) ) : null;
$eurasiapulse_thumbs     = (bool) eurasiapulse_mod( 'lead_side_thumbs' );
?>
<section class="lead grid" aria-label="<?php esc_attr_e( 'Top stories', 'eurasiapulse' ); ?>">
	<div class="lead__main">
		<?php get_template_part( 'template-parts/card', 'lead', array( 'post' => $eurasiapulse_lead_post ) ); ?>
		<?php if ( $eurasiapulse_sub && $eurasiapulse_sub->posts ) : ?>
			<div class="lead__sub">
				<?php
				foreach ( $eurasiapulse_sub->posts as $eurasiapulse_item ) {
					get_template_part(
						'template-parts/card',
						'standard',
						array(
							'post'  => $eurasiapulse_item,
							'ratio' => '169',
							'size'  => 'ep-169-s',
							'sizes' => '(min-width: 1240px) 383px, (min-width: 900px) 32vw, (min-width: 600px) 50vw, 100vw',
							'meta'  => array( 'region', 'category' ),
						)
					);
				}
				?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $eurasiapulse_side && $eurasiapulse_side->posts ) : ?>
		<ul class="lead__side">
			<?php foreach ( $eurasiapulse_side->posts as $eurasiapulse_item ) : ?>
				<li>
					<?php
					get_template_part(
						'template-parts/card',
						'compact',
						array(
							'post'       => $eurasiapulse_item,
							'show_image' => $eurasiapulse_thumbs,
							'sizes'      => '96px',
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
