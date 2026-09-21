<?php
/**
 * Static page: single reading column.
 *
 * @package EurasiaPulse
 */

get_header();
?>
<div class="container">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'page-single' ); ?>>
			<header class="page__header">
				<h1 class="page__title"><?php the_title(); ?></h1>
			</header>
			<?php
			echo eurasiapulse_figure( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
				get_the_ID(),
				'169',
				'ep-169-l',
				array(
					'link'    => false,
					'caption' => true,
					'loading' => 'eager',
					'sizes'   => '(min-width: 964px) 900px, 100vw',
					'figure'  => 'article__figure',
				)
			);
			?>
			<div class="entry-content page__content">
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'eurasiapulse' ) . '">' . esc_html__( 'Pages:', 'eurasiapulse' ),
						'after'  => '</nav>',
					)
				);
				?>
			</div>
		</article>
		<?php
		if ( eurasiapulse_mod( 'enable_comments' ) && ( comments_open() || get_comments_number() ) ) {
			comments_template();
		}
	endwhile;
	?>
</div>
<?php
get_footer();
