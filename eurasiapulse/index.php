<?php
/**
 * Generic listing (posts page, paged front page, fallback).
 *
 * @package EurasiaPulse
 */

get_header();
?>
<div class="container">
	<header class="archive-header">
		<p class="kicker"><?php echo esc_html( is_home() ? __( 'Latest', 'eurasiapulse' ) : eurasiapulse_archive_kicker() ); ?></p>
		<h1 class="archive-header__title"><?php echo esc_html( eurasiapulse_archive_heading() ); ?></h1>
	</header>
	<?php get_template_part( 'template-parts/loop' ); ?>
</div>
<?php
get_footer();
