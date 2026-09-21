<?php
/**
 * Footer: brand, three link columns (menus with sensible fallbacks), social, copyright.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_sections = eurasiapulse_terms_to_items( array_merge( eurasiapulse_default_categories(), eurasiapulse_nav_format_terms() ) );
$eurasiapulse_regions  = eurasiapulse_terms_to_items( eurasiapulse_region_terms() );
$eurasiapulse_company  = eurasiapulse_company_items();
$eurasiapulse_social   = eurasiapulse_social_items();
$eurasiapulse_tagline  = eurasiapulse_mod( 'footer_tagline' );
if ( ! $eurasiapulse_tagline ) {
	$eurasiapulse_tagline = get_bloginfo( 'description' );
}
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="grid footer__grid">
			<div class="footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</div>

			<?php if ( has_nav_menu( 'footer-sections' ) || $eurasiapulse_sections ) : ?>
				<nav class="footer__col" aria-label="<?php esc_attr_e( 'Sections', 'eurasiapulse' ); ?>">
					<h2 class="footer__heading"><?php esc_html_e( 'Sections', 'eurasiapulse' ); ?></h2>
					<?php eurasiapulse_footer_nav( 'footer-sections', $eurasiapulse_sections ); ?>
				</nav>
			<?php endif; ?>

			<?php if ( has_nav_menu( 'footer-regions' ) || $eurasiapulse_regions ) : ?>
				<nav class="footer__col" aria-label="<?php esc_attr_e( 'Regions', 'eurasiapulse' ); ?>">
					<h2 class="footer__heading"><?php esc_html_e( 'Regions', 'eurasiapulse' ); ?></h2>
					<?php eurasiapulse_footer_nav( 'footer-regions', $eurasiapulse_regions ); ?>
				</nav>
			<?php endif; ?>

			<div class="footer__col">
				<?php if ( has_nav_menu( 'footer-company' ) || $eurasiapulse_company ) : ?>
					<nav aria-label="<?php esc_attr_e( 'Company', 'eurasiapulse' ); ?>">
						<h2 class="footer__heading"><?php esc_html_e( 'Company', 'eurasiapulse' ); ?></h2>
						<?php eurasiapulse_footer_nav( 'footer-company', $eurasiapulse_company ); ?>
					</nav>
				<?php endif; ?>
				<?php if ( $eurasiapulse_social ) : ?>
					<h2 class="footer__heading"><?php esc_html_e( 'Follow', 'eurasiapulse' ); ?></h2>
					<ul class="footer__list footer__social">
						<?php foreach ( $eurasiapulse_social as $eurasiapulse_item ) : ?>
							<li><a href="<?php echo esc_url( $eurasiapulse_item['url'] ); ?>" rel="noopener"><?php echo esc_html( $eurasiapulse_item['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>

		<div class="footer__bottom">
			<span>
				<?php
				/* translators: 1: year, 2: site name */
				printf( esc_html__( '© %1$s %2$s. All rights reserved.', 'eurasiapulse' ), esc_html( wp_date( 'Y' ) ), esc_html( get_bloginfo( 'name' ) ) );
				?>
			</span>
			<?php if ( $eurasiapulse_tagline ) : ?>
				<span><?php echo esc_html( $eurasiapulse_tagline ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
