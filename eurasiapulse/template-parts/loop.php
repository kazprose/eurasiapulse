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
		$eurasiapulse_index = 0;
		while ( have_posts() ) :
			the_post();
			get_template_part(
				'template-parts/card',
				'compact',
				array(
					'post'          => get_the_ID(),
					'variant'       => 'row',
					'heading'       => 'h2',
					'show_dek'      => true,
					'dek_words'     => 24,
					'meta'          => array( 'format', 'region', 'category', 'date' ),
					// The first row is usually the largest contentful paint: load it eagerly.
					'loading'       => 0 === $eurasiapulse_index ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $eurasiapulse_index ? 'high' : '',
				)
			);
			++$eurasiapulse_index;
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
