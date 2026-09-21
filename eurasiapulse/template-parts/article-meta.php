<?php
/**
 * Article byline, publication / update dates and reading time.
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
$eurasiapulse_updated   = eurasiapulse_is_updated( $eurasiapulse_post );
$eurasiapulse_reading   = eurasiapulse_mod( 'show_reading_time' ) ? eurasiapulse_reading_time( $eurasiapulse_post ) : 0;
?>
<div class="article__meta">
	<p class="article__byline">
		<?php
		printf(
			/* translators: %s: linked author name */
			esc_html__( 'By %s', 'eurasiapulse' ),
			'<a href="' . esc_url( get_author_posts_url( $eurasiapulse_author_id ) ) . '" rel="author">' . esc_html( get_the_author_meta( 'display_name', $eurasiapulse_author_id ) ) . '</a>'
		);
		?>
	</p>
	<p class="article__dates">
		<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $eurasiapulse_post ) ); ?>">
			<?php
			/* translators: 1: date, 2: time */
			echo esc_html( sprintf( __( 'Published %1$s, %2$s', 'eurasiapulse' ), get_the_date( '', $eurasiapulse_post ), get_the_time( '', $eurasiapulse_post ) ) );
			?>
		</time>
		<?php if ( $eurasiapulse_updated ) : ?>
			<span class="sep" aria-hidden="true">·</span>
			<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C, $eurasiapulse_post ) ); ?>">
				<?php
				/* translators: 1: date, 2: time */
				echo esc_html( sprintf( __( 'Updated %1$s, %2$s', 'eurasiapulse' ), get_the_modified_date( '', $eurasiapulse_post ), get_the_modified_time( '', $eurasiapulse_post ) ) );
				?>
			</time>
		<?php endif; ?>
		<?php if ( $eurasiapulse_reading ) : ?>
			<span class="sep" aria-hidden="true">·</span>
			<?php
			/* translators: %d: minutes */
			echo esc_html( sprintf( _n( '%d min read', '%d min read', $eurasiapulse_reading, 'eurasiapulse' ), $eurasiapulse_reading ) );
			?>
		<?php endif; ?>
	</p>
</div>
