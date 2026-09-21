<?php
/**
 * Front page: the composed news homepage (lead, latest, analysis, section
 * blocks, region strip, opinion). Paged requests fall back to the plain list.
 *
 * @package EurasiaPulse
 */

if ( is_paged() ) {
	get_template_part( 'index' );
	return;
}

get_header();
?>
<div class="container">
	<?php
	get_template_part( 'template-parts/home/lead' );
	get_template_part( 'template-parts/home/latest' );
	get_template_part( 'template-parts/home/analysis' );
	for ( $eurasiapulse_slot = 1; $eurasiapulse_slot <= 5; $eurasiapulse_slot++ ) {
		get_template_part( 'template-parts/home/category', null, array( 'slot' => $eurasiapulse_slot ) );
	}
	?>
</div>
<?php get_template_part( 'template-parts/home/regions' ); ?>
<div class="container">
	<?php get_template_part( 'template-parts/home/opinion' ); ?>
</div>
<?php
get_footer();
