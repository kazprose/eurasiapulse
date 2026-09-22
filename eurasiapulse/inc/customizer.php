<?php
/**
 * Customizer: homepage composition, header/footer links, article options, SEO.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default values for every theme mod.
 *
 * @return array<string, mixed>
 */
function eurasiapulse_defaults() {
	return array(
		// Header.
		'topbar_show_date'      => true,
		'newsletter_url'        => '',
		'topbar_show_languages' => true,
		'language_links'        => '',
		'topbar_show_social'    => true,
		'dark_mode_toggle'      => true,
		'dark_mode_auto'        => true,
		// Updates.
		'github_repo'           => 'kazprose/eurasiapulse',
		'github_auto_update'    => true,
		// Homepage.
		'lead_style'          => 'overlay',
		'lead_source'         => 'sticky',
		'lead_sub_count'      => 2,
		'lead_side_count'     => 8,
		'lead_side_thumbs'    => true,
		'latest_count'        => 6,
		'latest_thumbs'       => true,
		'analysis_format'     => 'analysis',
		'analysis_count'      => 3,
		'section_1'           => 'auto',
		'section_2'           => 'auto',
		'section_3'           => 'auto',
		'section_4'           => 'auto',
		'section_5'           => 'auto',
		'section_small_count' => 4,
		'show_regions'        => true,
		'opinion_format'      => 'opinion',
		'opinion_count'       => 4,
		// Article.
		'show_reading_time'   => true,
		'show_share'          => true,
		'show_author_box'     => true,
		'related_count'       => 4,
		'enable_comments'     => false,
		// Footer.
		'footer_tagline'      => '',
		'social_x'            => '',
		'social_telegram'     => '',
		'social_instagram'    => '',
		'social_linkedin'     => '',
		'social_facebook'     => '',
		'social_youtube'      => '',
		'social_rss'          => true,
		// SEO.
		'schema_enabled'      => true,
		'org_logo'            => '',
		'org_sameas'          => '',
	);
}

/**
 * Read a theme mod with its default.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function eurasiapulse_mod( $key ) {
	$defaults = eurasiapulse_defaults();
	return get_theme_mod( $key, $defaults[ $key ] ?? null );
}

/**
 * Sanitize a checkbox.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function eurasiapulse_sanitize_checkbox( $value ) {
	return rest_sanitize_boolean( $value );
}

/**
 * Sanitize a select against its control's choices.
 *
 * @param mixed                $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return string
 */
function eurasiapulse_sanitize_choice( $value, $setting ) {
	$value   = sanitize_key( (string) $value );
	$control = $setting->manager->get_control( $setting->id );
	if ( $control && isset( $control->choices[ $value ] ) ) {
		return $value;
	}
	return $setting->default;
}

/**
 * Sanitize an integer within the control's min/max.
 *
 * @param mixed                $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return int
 */
function eurasiapulse_sanitize_count( $value, $setting ) {
	$value   = absint( $value );
	$control = $setting->manager->get_control( $setting->id );
	$min     = isset( $control->input_attrs['min'] ) ? (int) $control->input_attrs['min'] : 0;
	$max     = isset( $control->input_attrs['max'] ) ? (int) $control->input_attrs['max'] : 20;
	return max( $min, min( $max, $value ) );
}

/**
 * Sanitize a section slot: "auto", "none" or a category ID.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function eurasiapulse_sanitize_section( $value ) {
	$value = (string) $value;
	if ( in_array( $value, array( 'auto', 'none' ), true ) ) {
		return $value;
	}
	$id = absint( $value );
	return $id > 0 ? (string) $id : 'auto';
}

/**
 * Sanitize a format slot: "none" or a term slug.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function eurasiapulse_sanitize_format_slot( $value ) {
	$value = sanitize_title( (string) $value );
	return '' === $value ? 'none' : $value;
}

/**
 * Sanitize a multi-line list of URLs.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function eurasiapulse_sanitize_url_list( $value ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
	$clean = array();
	foreach ( (array) $lines as $line ) {
		$url = eurasiapulse_sanitize_url( $line );
		if ( $url ) {
			$clean[] = $url;
		}
	}
	return implode( "\n", $clean );
}

/**
 * Category choices for the section slots.
 *
 * @return array<string, string>
 */
function eurasiapulse_category_choices() {
	$choices = array(
		'auto' => __( 'Automatic (default section for this slot)', 'eurasiapulse' ),
		'none' => __( 'Hide this block', 'eurasiapulse' ),
	);
	$cats    = get_categories( array( 'hide_empty' => false ) );
	foreach ( $cats as $cat ) {
		$choices[ (string) $cat->term_id ] = $cat->name;
	}
	return $choices;
}

/**
 * Format choices for the Analysis / Opinion blocks.
 *
 * @return array<string, string>
 */
function eurasiapulse_format_choices() {
	$choices = array( 'none' => __( 'Hide this block', 'eurasiapulse' ) );
	$terms   = get_terms(
		array(
			'taxonomy'   => 'format',
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$choices[ $term->slug ] = $term->name;
		}
	}
	if ( ! isset( $choices['analysis'] ) ) {
		$choices['analysis'] = __( 'Analysis', 'eurasiapulse' );
	}
	if ( ! isset( $choices['opinion'] ) ) {
		$choices['opinion'] = __( 'Opinion', 'eurasiapulse' );
	}
	return $choices;
}

/**
 * Register panel, sections, settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function eurasiapulse_customize_register( $wp_customize ) {
	$defaults = eurasiapulse_defaults();

	$wp_customize->add_panel(
		'eurasiapulse',
		array(
			'title'       => __( 'EurasiaPulse', 'eurasiapulse' ),
			'description' => __( 'Homepage composition, header and footer links, article options.', 'eurasiapulse' ),
			'priority'    => 30,
		)
	);

	$sections = array(
		'eurasiapulse_header'  => __( 'Header', 'eurasiapulse' ),
		'eurasiapulse_home'    => __( 'Homepage blocks', 'eurasiapulse' ),
		'eurasiapulse_article' => __( 'Article page', 'eurasiapulse' ),
		'eurasiapulse_footer'  => __( 'Footer & social', 'eurasiapulse' ),
		'eurasiapulse_seo'     => __( 'Structured data', 'eurasiapulse' ),
		'eurasiapulse_updates' => __( 'Updates (GitHub)', 'eurasiapulse' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title' => $title,
				'panel' => 'eurasiapulse',
			)
		);
	}

	/**
	 * Helper to add a setting + control pair.
	 *
	 * @param string $key     Setting key.
	 * @param string $section Section id.
	 * @param array  $control Control args (type, label, description, choices, input_attrs).
	 * @param string $sanitize Sanitize callback.
	 */
	$add = static function ( $key, $section, $control, $sanitize ) use ( $wp_customize, $defaults ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);
		$control['section'] = $section;
		if ( 'image' === $control['type'] ) {
			unset( $control['type'] );
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key, $control ) );
			return;
		}
		$wp_customize->add_control( $key, $control );
	};

	// Header.
	$add(
		'topbar_show_date',
		'eurasiapulse_header',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show today\'s date in the top bar', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'newsletter_url',
		'eurasiapulse_header',
		array(
			'type'        => 'url',
			'label'       => __( 'Newsletter link', 'eurasiapulse' ),
			'description' => __( 'Shown in the top bar when no "Top bar links" menu is assigned.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_url'
	);
	$add(
		'topbar_show_languages',
		'eurasiapulse_header',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Show the language switcher', 'eurasiapulse' ),
			'description' => __( 'Languages come from Polylang or WPML automatically; otherwise from the list below.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'language_links',
		'eurasiapulse_header',
		array(
			'type'        => 'textarea',
			'label'       => __( 'Language links (fallback)', 'eurasiapulse' ),
			'description' => __( 'One per line as "Label|URL", e.g. "KK|https://example.com/kk/". Used only when no multilingual plugin is active.', 'eurasiapulse' ),
		),
		'sanitize_textarea_field'
	);
	$add(
		'topbar_show_social',
		'eurasiapulse_header',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Show social icons in the top bar', 'eurasiapulse' ),
			'description' => __( 'Profile URLs are set under "Footer & social".', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'dark_mode_toggle',
		'eurasiapulse_header',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show the light / dark mode switch', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'dark_mode_auto',
		'eurasiapulse_header',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Follow the visitor\'s system dark mode by default', 'eurasiapulse' ),
			'description' => __( 'An explicit choice made with the switch always wins.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);

	// Homepage.
	$add(
		'lead_style',
		'eurasiapulse_home',
		array(
			'type'    => 'select',
			'label'   => __( 'Lead story layout', 'eurasiapulse' ),
			'choices' => array(
				'overlay' => __( 'Headline over the image (as in the design)', 'eurasiapulse' ),
				'stacked' => __( 'Headline below the image', 'eurasiapulse' ),
			),
		),
		'eurasiapulse_sanitize_choice'
	);
	$add(
		'lead_source',
		'eurasiapulse_home',
		array(
			'type'    => 'select',
			'label'   => __( 'Lead story selection', 'eurasiapulse' ),
			'choices' => array(
				'sticky' => __( 'Sticky post if any, otherwise the newest post', 'eurasiapulse' ),
				'latest' => __( 'Always the newest post', 'eurasiapulse' ),
			),
		),
		'eurasiapulse_sanitize_choice'
	);
	$add(
		'lead_sub_count',
		'eurasiapulse_home',
		array(
			'type'        => 'number',
			'label'       => __( 'Cards under the lead story', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 0,
				'max' => 4,
			),
		),
		'eurasiapulse_sanitize_count'
	);
	$add(
		'lead_side_count',
		'eurasiapulse_home',
		array(
			'type'        => 'number',
			'label'       => __( 'Headlines beside the lead story', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 0,
				'max' => 12,
			),
		),
		'eurasiapulse_sanitize_count'
	);
	$add(
		'lead_side_thumbs',
		'eurasiapulse_home',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show thumbnails beside the lead story', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'latest_count',
		'eurasiapulse_home',
		array(
			'type'        => 'number',
			'label'       => __( '"Latest" list: number of items', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 0,
				'max' => 20,
			),
		),
		'eurasiapulse_sanitize_count'
	);
	$add(
		'latest_thumbs',
		'eurasiapulse_home',
		array(
			'type'  => 'checkbox',
			'label' => __( '"Latest" list: show thumbnails', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'analysis_format',
		'eurasiapulse_home',
		array(
			'type'    => 'select',
			'label'   => __( '"Analysis" block: format', 'eurasiapulse' ),
			'choices' => eurasiapulse_format_choices(),
		),
		'eurasiapulse_sanitize_format_slot'
	);
	$add(
		'analysis_count',
		'eurasiapulse_home',
		array(
			'type'        => 'number',
			'label'       => __( '"Analysis" block: number of cards', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 0,
				'max' => 6,
			),
		),
		'eurasiapulse_sanitize_count'
	);
	for ( $i = 1; $i <= 5; $i++ ) {
		$add(
			'section_' . $i,
			'eurasiapulse_home',
			array(
				'type'    => 'select',
				/* translators: %d: block number */
				'label'   => sprintf( __( 'Section block %d: category', 'eurasiapulse' ), $i ),
				'choices' => eurasiapulse_category_choices(),
			),
			'eurasiapulse_sanitize_section'
		);
	}
	$add(
		'section_small_count',
		'eurasiapulse_home',
		array(
			'type'        => 'number',
			'label'       => __( 'Section blocks: small items beside the lead card', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 1,
				'max' => 6,
			),
		),
		'eurasiapulse_sanitize_count'
	);
	$add(
		'show_regions',
		'eurasiapulse_home',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show the "By Region" strip', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'opinion_format',
		'eurasiapulse_home',
		array(
			'type'    => 'select',
			'label'   => __( '"Opinion" block: format', 'eurasiapulse' ),
			'choices' => eurasiapulse_format_choices(),
		),
		'eurasiapulse_sanitize_format_slot'
	);
	$add(
		'opinion_count',
		'eurasiapulse_home',
		array(
			'type'        => 'number',
			'label'       => __( '"Opinion" block: number of items', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 0,
				'max' => 8,
			),
		),
		'eurasiapulse_sanitize_count'
	);

	// Article.
	$add(
		'show_reading_time',
		'eurasiapulse_article',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show estimated reading time', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'show_share',
		'eurasiapulse_article',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show share links (X, Telegram, LinkedIn, copy link)', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'show_author_box',
		'eurasiapulse_article',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show the author box', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'related_count',
		'eurasiapulse_article',
		array(
			'type'        => 'number',
			'label'       => __( 'Related articles', 'eurasiapulse' ),
			'input_attrs' => array(
				'min' => 0,
				'max' => 8,
			),
		),
		'eurasiapulse_sanitize_count'
	);
	$add(
		'enable_comments',
		'eurasiapulse_article',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Enable comments', 'eurasiapulse' ),
			'description' => __( 'Comments are closed site-wide until this is switched on.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);

	// Footer.
	$add(
		'footer_tagline',
		'eurasiapulse_footer',
		array(
			'type'        => 'text',
			'label'       => __( 'Footer line', 'eurasiapulse' ),
			'description' => __( 'Defaults to the site tagline.', 'eurasiapulse' ),
		),
		'sanitize_text_field'
	);
	$socials = array(
		'social_x'        => __( 'X (Twitter) profile URL', 'eurasiapulse' ),
		'social_telegram' => __( 'Telegram channel URL', 'eurasiapulse' ),
		'social_instagram' => __( 'Instagram profile URL', 'eurasiapulse' ),
		'social_linkedin' => __( 'LinkedIn page URL', 'eurasiapulse' ),
		'social_facebook' => __( 'Facebook page URL', 'eurasiapulse' ),
		'social_youtube'  => __( 'YouTube channel URL', 'eurasiapulse' ),
	);
	foreach ( $socials as $key => $label ) {
		$add(
			$key,
			'eurasiapulse_footer',
			array(
				'type'  => 'url',
				'label' => $label,
			),
			'eurasiapulse_sanitize_url'
		);
	}
	$add(
		'social_rss',
		'eurasiapulse_footer',
		array(
			'type'  => 'checkbox',
			'label' => __( 'Show RSS link', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);

	// SEO / structured data.
	$add(
		'schema_enabled',
		'eurasiapulse_seo',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Output JSON-LD and Open Graph tags', 'eurasiapulse' ),
			'description' => __( 'Automatically disabled when an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework, Slim SEO) is active.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
	$add(
		'org_logo',
		'eurasiapulse_seo',
		array(
			'type'        => 'image',
			'label'       => __( 'Organization logo for structured data', 'eurasiapulse' ),
			'description' => __( 'Square or wide PNG, at least 112px high. Falls back to the site logo.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_url'
	);
	$add(
		'org_sameas',
		'eurasiapulse_seo',
		array(
			'type'        => 'textarea',
			'label'       => __( 'Organization profiles (sameAs)', 'eurasiapulse' ),
			'description' => __( 'One URL per line. Social profile URLs above are added automatically.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_url_list'
	);

	// Updates from GitHub.
	$add(
		'github_repo',
		'eurasiapulse_updates',
		array(
			'type'        => 'text',
			'label'       => __( 'GitHub repository (owner/name)', 'eurasiapulse' ),
			'description' => __( 'The theme checks this repository\'s latest release and offers it under Dashboard > Updates. Each release needs an eurasiapulse.zip asset (built by the bundled GitHub Actions workflow). Can also be set with the EURASIAPULSE_GITHUB_REPO constant.', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_repo'
	);
	$add(
		'github_auto_update',
		'eurasiapulse_updates',
		array(
			'type'        => 'checkbox',
			'label'       => __( 'Install new releases automatically', 'eurasiapulse' ),
			'description' => __( 'Uses WordPress auto-updates (checked twice a day).', 'eurasiapulse' ),
		),
		'eurasiapulse_sanitize_checkbox'
	);
}
add_action( 'customize_register', 'eurasiapulse_customize_register' );

/**
 * Sanitize a GitHub "owner/name" repository slug.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function eurasiapulse_sanitize_repo( $value ) {
	$value = trim( (string) $value, "/ \t\n\r" );
	$value = preg_replace( '#^https?://github\.com/#i', '', $value );
	return preg_match( '#^[\w.-]+/[\w.-]+$#', $value ) ? $value : '';
}

/**
 * Forget the cached GitHub release when Customizer settings are saved.
 */
function eurasiapulse_customize_saved() {
	if ( function_exists( 'eurasiapulse_flush_release_cache' ) ) {
		eurasiapulse_flush_release_cache();
	}
}
add_action( 'customize_save_after', 'eurasiapulse_customize_saved' );
