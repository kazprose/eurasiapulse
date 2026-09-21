<?php
/**
 * Archive / search / index loop: rows with thumbnail, dek and meta, plus pagination.
 *
 * @package EurasiaPulse
 */

if ( have_posts() ) :
	?>
	<div class="archive-list">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part(
				'template-parts/card',
				'compact',
				array(
					'post'      => get_the_ID(),
					'variant'   => 'row',
					'heading'   => 'h2',
					'show_dek'  => true,
					'dek_words' => 24,
					'meta'      => array( 'format', 'region', 'category', 'date' ),
				)
			);
		endwhile;
		?>
	</div>
	<?php
	eurasiapulse_pagination();
else :
	?>
	<div class="archive-empty">
		<p><?php esc_html_e( 'No articles found.', 'eurasiapulse' ); ?></p>
		<?php get_search_form(); ?>
	</div>
	<?php
endif;
