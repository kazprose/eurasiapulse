<?php
/**
 * Header: skip link, top bar (date, links, languages, social, dark mode),
 * masthead, sticky primary navigation, search.
 *
 * @package EurasiaPulse
 */

$eurasiapulse_logo_tag = ( is_front_page() && ! is_paged() ) ? 'h1' : 'p';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js"<?php echo eurasiapulse_mod( 'dark_mode_auto' ) ? ' data-theme-auto' : ''; ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'eurasiapulse' ); ?></a>

<header class="site-header">
	<div class="container">
		<div class="topbar">
			<?php if ( eurasiapulse_mod( 'topbar_show_date' ) ) : ?>
				<time class="topbar__date" datetime="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
					<?php
					/* translators: PHP date format for the top bar, e.g. "Monday, September 21, 2026". */
					echo esc_html( wp_date( __( 'l, F j, Y', 'eurasiapulse' ) ) );
					?>
				</time>
			<?php endif; ?>
			<div class="topbar__right">
				<nav class="topbar__nav" aria-label="<?php esc_attr_e( 'Secondary', 'eurasiapulse' ); ?>">
					<?php eurasiapulse_topbar_nav(); ?>
				</nav>
				<?php
				if ( eurasiapulse_mod( 'topbar_show_languages' ) ) {
					eurasiapulse_language_nav();
				}
				if ( eurasiapulse_mod( 'topbar_show_social' ) ) {
					eurasiapulse_topbar_social();
				}
				if ( eurasiapulse_mod( 'dark_mode_toggle' ) ) {
					eurasiapulse_theme_toggle();
				}
				?>
			</div>
		</div>

		<div class="masthead">
			<?php if ( has_custom_logo() ) : ?>
				<<?php echo $eurasiapulse_logo_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?> class="masthead__logo masthead__logo--image">
					<?php the_custom_logo(); ?>
				</<?php echo $eurasiapulse_logo_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php else : ?>
				<<?php echo $eurasiapulse_logo_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="masthead__logo">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				</<?php echo $eurasiapulse_logo_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endif; ?>
		</div>
	</div>

	<nav class="nav" id="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'eurasiapulse' ); ?>">
		<div class="container nav__inner">
			<button class="nav__toggle" type="button" aria-expanded="false" aria-controls="primary-menu" data-nav-toggle>
				<span class="nav__toggle-icon" aria-hidden="true"></span>
				<span class="nav__toggle-label"><?php esc_html_e( 'Menu', 'eurasiapulse' ); ?></span>
			</button>
			<?php eurasiapulse_primary_nav(); ?>
			<a class="nav__search-toggle" href="#header-search" aria-label="<?php esc_attr_e( 'Search', 'eurasiapulse' ); ?>" aria-expanded="false" aria-controls="header-search" data-search-toggle>
				<svg class="nav__search-icon" viewBox="0 0 16 16" width="15" height="15" aria-hidden="true" focusable="false"><circle cx="7" cy="7" r="5"></circle><path d="M11 11l4 4"></path></svg>
				<span class="nav__search-label"><?php esc_html_e( 'Search', 'eurasiapulse' ); ?></span>
			</a>
		</div>
		<div class="nav__search" id="header-search" hidden>
			<div class="container">
				<?php get_search_form(); ?>
			</div>
		</div>
	</nav>
</header>

<main id="content" class="site-main">
