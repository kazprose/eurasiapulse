<?php
/**
 * Archive: category, region, format, tag and date archives share this template.
 *
 * @package EurasiaPulse
 */

get_header();
$eurasiapulse_description = get_the_archive_description();
?>
<div class="container">
	<header class="archive-header">
		<p class="kicker"><?php echo esc_html( eurasiapulse_archive_kicker() ); ?></p>
		<h1 class="archive-header__title"><?php echo esc_html( eurasiapulse_archive_heading() ); ?></h1>
		<?php if ( $eurasiapulse_description ) : ?>
			<div class="archive-header__desc"><?php echo wp_kses_post( $eurasiapulse_description ); ?></div>
		<?php endif; ?>
	</header>
	<?php get_template_part( 'template-parts/loop' ); ?>
</div>
<?php
get_footer();
