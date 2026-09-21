<?php
/**
 * Author archive: name, biography, articles.
 *
 * @package EurasiaPulse
 */

get_header();
$eurasiapulse_bio = get_the_author_meta( 'description' );
?>
<div class="container">
	<header class="archive-header">
		<p class="kicker"><?php esc_html_e( 'Author', 'eurasiapulse' ); ?></p>
		<h1 class="archive-header__title"><?php echo esc_html( get_the_author() ); ?></h1>
		<?php if ( $eurasiapulse_bio ) : ?>
			<div class="archive-header__desc"><p><?php echo esc_html( $eurasiapulse_bio ); ?></p></div>
		<?php endif; ?>
	</header>
	<?php get_template_part( 'template-parts/loop' ); ?>
</div>
<?php
get_footer();
