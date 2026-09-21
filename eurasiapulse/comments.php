<?php
/**
 * Comments template. Comments are closed site-wide unless enabled in the
 * Customizer (EurasiaPulse > Article page > Enable comments).
 *
 * @package EurasiaPulse
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title">
			<?php
			$eurasiapulse_count = get_comments_number();
			/* translators: %s: number of comments */
			echo esc_html( sprintf( _n( '%s comment', '%s comments', $eurasiapulse_count, 'eurasiapulse' ), number_format_i18n( $eurasiapulse_count ) ) );
			?>
		</h2>
		<ol class="comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 0,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
		<?php if ( ! comments_open() ) : ?>
			<p class="comments__closed"><?php esc_html_e( 'Comments are closed.', 'eurasiapulse' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'title_reply'        => __( 'Leave a comment', 'eurasiapulse' ),
			'title_reply_before' => '<h2 id="reply-title" class="comments__reply-title">',
			'title_reply_after'  => '</h2>',
			'class_form'         => 'comment-form',
		)
	);
	?>
</section>
