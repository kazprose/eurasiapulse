<?php
/**
 * Single article: labels, headline, subtitle, meta, featured image, body,
 * source, tags, share, author, related. Single reading column, no sidebar.
 *
 * @package EurasiaPulse
 */

get_header();
?>
<div class="container">
	<?php
	while ( have_posts() ) :
		the_post();
		$eurasiapulse_post_id = get_the_ID();
		$eurasiapulse_format  = eurasiapulse_primary_term( $eurasiapulse_post_id, 'format' );
		$eurasiapulse_kicker  = $eurasiapulse_format ? $eurasiapulse_format : eurasiapulse_primary_term( $eurasiapulse_post_id, 'category' );
		$eurasiapulse_parts   = $eurasiapulse_format ? array( 'region', 'category' ) : array( 'region' );
		$eurasiapulse_dek     = get_post_meta( $eurasiapulse_post_id, 'ep_subtitle', true );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'article' ); ?>>
			<header class="article__header">
				<div class="article__labels">
					<?php
					if ( $eurasiapulse_kicker ) {
						$eurasiapulse_kicker_link = get_term_link( $eurasiapulse_kicker );
						if ( ! is_wp_error( $eurasiapulse_kicker_link ) ) {
							printf( '<a class="kicker" href="%s">%s</a>', esc_url( $eurasiapulse_kicker_link ), esc_html( $eurasiapulse_kicker->name ) );
						}
					}
					eurasiapulse_meta_line( $eurasiapulse_post_id, $eurasiapulse_parts );
					?>
				</div>
				<h1 class="article__title"><?php the_title(); ?></h1>
				<?php if ( $eurasiapulse_dek ) : ?>
					<p class="article__dek"><?php echo esc_html( $eurasiapulse_dek ); ?></p>
				<?php endif; ?>
				<?php get_template_part( 'template-parts/article-meta', null, array( 'post' => $eurasiapulse_post_id ) ); ?>
			</header>

			<?php
			echo eurasiapulse_figure( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
				$eurasiapulse_post_id,
				'169',
				'ep-169-xl',
				array(
					'link'          => false,
					'caption'       => true,
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'sizes'         => '(min-width: 964px) 900px, 100vw',
					'figure'        => 'article__figure',
				)
			);
			?>

			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Article pages', 'eurasiapulse' ) . '">' . esc_html__( 'Pages:', 'eurasiapulse' ),
						'after'  => '</nav>',
					)
				);
				?>
			</div>

			<footer class="article__footer">
				<?php
				get_template_part( 'template-parts/source-box', null, array( 'post' => $eurasiapulse_post_id ) );
				$eurasiapulse_tags = get_the_tags();
				if ( $eurasiapulse_tags ) :
					?>
					<div class="tags">
						<span class="section__title"><?php esc_html_e( 'Topics', 'eurasiapulse' ); ?></span>
						<ul class="tags__list">
							<?php foreach ( $eurasiapulse_tags as $eurasiapulse_tag ) : ?>
								<li><a href="<?php echo esc_url( get_tag_link( $eurasiapulse_tag ) ); ?>" rel="tag"><?php echo esc_html( $eurasiapulse_tag->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php
				endif;
				if ( eurasiapulse_mod( 'show_share' ) ) {
					get_template_part( 'template-parts/share', null, array( 'post' => $eurasiapulse_post_id ) );
				}
				if ( eurasiapulse_mod( 'show_author_box' ) ) {
					get_template_part( 'template-parts/author-box', null, array( 'post' => $eurasiapulse_post_id ) );
				}
				?>
			</footer>
		</article>

		<?php
		get_template_part( 'template-parts/related', null, array( 'post' => $eurasiapulse_post_id ) );
		if ( eurasiapulse_mod( 'enable_comments' ) && ( comments_open() || get_comments_number() ) ) {
			comments_template();
		}
	endwhile;
	?>
</div>
<?php
get_footer();
