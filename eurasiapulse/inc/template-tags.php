<?php
/**
 * Template tags: queries, cards helpers, images, navigation fallbacks.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Homepage de-duplication
 * ---------------------------------------------------------------------- */

/**
 * Remember post IDs already rendered on the current page.
 *
 * @param int[]|int $ids Post IDs.
 * @return int[] All tracked IDs.
 */
function eurasiapulse_track_posts( $ids = array() ) {
	static $shown = array();
	foreach ( (array) $ids as $id ) {
		$id = (int) $id;
		if ( $id > 0 ) {
			$shown[ $id ] = $id;
		}
	}
	return array_values( $shown );
}

/**
 * Run a query that skips posts already shown on this page and tracks its results.
 *
 * @param array $args WP_Query args.
 * @return WP_Query
 */
function eurasiapulse_query( array $args ) {
	$defaults = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	$args     = wp_parse_args( $args, $defaults );
	$shown    = eurasiapulse_track_posts();
	if ( $shown ) {
		$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $shown ) ) );
	}
	$query = new WP_Query( $args );
	eurasiapulse_track_posts( wp_list_pluck( $query->posts, 'ID' ) );
	return $query;
}

/**
 * The lead story: the newest sticky post (if enabled) or the newest post.
 *
 * @return WP_Query
 */
function eurasiapulse_lead_query() {
	$sticky = array_map( 'intval', (array) get_option( 'sticky_posts', array() ) );
	if ( 'sticky' === eurasiapulse_mod( 'lead_source' ) && $sticky ) {
		$query = eurasiapulse_query(
			array(
				'post__in'       => $sticky,
				'posts_per_page' => 1,
				'orderby'        => 'date',
			)
		);
		if ( $query->have_posts() ) {
			return $query;
		}
	}
	return eurasiapulse_query( array( 'posts_per_page' => 1 ) );
}

/**
 * Posts for a format-driven homepage block (Analysis, Opinion), cached per call
 * so the front page can reserve them before the section blocks run.
 *
 * @param string $slug  Format term slug, or "none".
 * @param int    $count Number of posts.
 * @return WP_Query|null
 */
function eurasiapulse_format_block_query( $slug, $count ) {
	static $cache = array();
	$slug  = (string) $slug;
	$count = (int) $count;
	$key   = $slug . '|' . $count;
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}
	$cache[ $key ] = null;
	if ( 'none' === $slug || '' === $slug || $count < 1 ) {
		return null;
	}
	$term = get_term_by( 'slug', $slug, 'format' );
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	$cache[ $key ] = eurasiapulse_query(
		array(
			'posts_per_page' => $count,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'format',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);
	return $cache[ $key ];
}

/* -------------------------------------------------------------------------
 * Terms
 * ---------------------------------------------------------------------- */

/**
 * First term of a taxonomy for a post (skipping "Uncategorized").
 *
 * @param int|WP_Post $post     Post.
 * @param string      $taxonomy Taxonomy.
 * @return WP_Term|null
 */
function eurasiapulse_primary_term( $post, $taxonomy ) {
	$terms = get_the_terms( $post, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	foreach ( $terms as $term ) {
		if ( 'category' === $taxonomy && 'uncategorized' === $term->slug && count( $terms ) > 1 ) {
			continue;
		}
		return $term;
	}
	return $terms[0];
}

/**
 * Slug of the post's format term ("analysis", "opinion", ...), or ''.
 *
 * @param int|WP_Post $post Post.
 * @return string
 */
function eurasiapulse_format_slug( $post ) {
	$term = eurasiapulse_primary_term( $post, 'format' );
	return $term ? $term->slug : '';
}

/**
 * Kicker label term: format first, category as a fallback.
 *
 * @param int|WP_Post $post Post.
 * @return WP_Term|null
 */
function eurasiapulse_kicker_term( $post ) {
	$term = eurasiapulse_primary_term( $post, 'format' );
	return $term ? $term : eurasiapulse_primary_term( $post, 'category' );
}

/**
 * Print the kicker link.
 *
 * @param int|WP_Post $post Post.
 */
function eurasiapulse_kicker( $post ) {
	$term = eurasiapulse_kicker_term( $post );
	if ( ! $term ) {
		return;
	}
	$link = get_term_link( $term );
	if ( is_wp_error( $link ) ) {
		return;
	}
	printf( '<a class="kicker" href="%s">%s</a>', esc_url( $link ), esc_html( $term->name ) );
}

/**
 * Print the small meta line ("Kazakhstan · Economy", "By Author · 4 min read").
 *
 * @param int|WP_Post $post  Post.
 * @param string[]    $parts Any of: region, category, format, author, date, reading.
 */
function eurasiapulse_meta_line( $post, $parts = array( 'region', 'category' ) ) {
	$post  = get_post( $post );
	$items = array();
	foreach ( $parts as $part ) {
		switch ( $part ) {
			case 'region':
			case 'category':
			case 'format':
				$term = eurasiapulse_primary_term( $post, $part );
				if ( $term ) {
					$link = get_term_link( $term );
					if ( ! is_wp_error( $link ) ) {
						$items[] = sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $term->name ) );
					}
				}
				break;
			case 'author':
				$items[] = sprintf(
					/* translators: %s: author name */
					esc_html__( 'By %s', 'eurasiapulse' ),
					sprintf( '<a href="%s">%s</a>', esc_url( get_author_posts_url( (int) $post->post_author ) ), esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ) )
				);
				break;
			case 'date':
				$items[] = sprintf( '<time datetime="%s">%s</time>', esc_attr( get_the_date( DATE_W3C, $post ) ), esc_html( get_the_date( '', $post ) ) );
				break;
			case 'reading':
				if ( eurasiapulse_mod( 'show_reading_time' ) ) {
					$minutes = eurasiapulse_reading_time( $post );
					/* translators: %d: minutes */
					$items[] = esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'eurasiapulse' ), $minutes ) );
				}
				break;
		}
	}
	if ( ! $items ) {
		return;
	}
	echo '<p class="meta">' . implode( '<span class="sep" aria-hidden="true">·</span>', $items ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
}

/* -------------------------------------------------------------------------
 * Text helpers
 * ---------------------------------------------------------------------- */

/**
 * Dek / standfirst: ep_subtitle, then the manual excerpt, then (optionally) a trimmed auto-excerpt.
 *
 * @param int|WP_Post $post  Post.
 * @param int         $words Words for the auto-excerpt fallback; 0 disables the fallback.
 * @return string Plain text.
 */
function eurasiapulse_dek( $post, $words = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$subtitle = get_post_meta( $post->ID, 'ep_subtitle', true );
	if ( $subtitle ) {
		return (string) $subtitle;
	}
	if ( has_excerpt( $post ) ) {
		return wp_strip_all_tags( $post->post_excerpt );
	}
	if ( $words > 0 ) {
		// strip_tags() also removes block comments; avoiding excerpt_remove_blocks() keeps block CSS off list pages.
		$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		return wp_trim_words( $text, $words, '&hellip;' );
	}
	return '';
}

/**
 * Estimated reading time in minutes (Unicode-aware, ~220 wpm).
 *
 * @param int|WP_Post $post Post.
 * @return int
 */
function eurasiapulse_reading_time( $post ) {
	$post  = get_post( $post );
	$text  = wp_strip_all_tags( strip_shortcodes( $post ? $post->post_content : '' ) );
	$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
	$count = is_array( $words ) ? count( $words ) : 0;
	return max( 1, (int) ceil( $count / 220 ) );
}

/**
 * Whether the post was meaningfully updated after publication (> 1 hour later).
 *
 * @param int|WP_Post $post Post.
 * @return bool
 */
function eurasiapulse_is_updated( $post ) {
	$published = (int) get_post_time( 'U', true, $post );
	$modified  = (int) get_post_modified_time( 'U', true, $post );
	return ( $modified - $published ) > HOUR_IN_SECONDS;
}

/**
 * Time stamp for the "Latest" list: clock time today, short date otherwise.
 *
 * @param int|WP_Post $post Post.
 * @return string HTML <time>.
 */
function eurasiapulse_latest_time( $post ) {
	$timestamp = get_post_timestamp( $post );
	if ( ! $timestamp ) {
		return '';
	}
	$is_today = wp_date( 'Y-m-d', $timestamp ) === wp_date( 'Y-m-d' );
	$label    = $is_today ? wp_date( 'H:i', $timestamp ) : wp_date( 'j M', $timestamp );
	return sprintf( '<time datetime="%s">%s</time>', esc_attr( wp_date( DATE_W3C, $timestamp ) ), esc_html( $label ) );
}

/* -------------------------------------------------------------------------
 * Images
 * ---------------------------------------------------------------------- */

/**
 * Responsive featured image with a fixed aspect ratio, or '' when there is none.
 *
 * @param int|WP_Post $post  Post.
 * @param string      $ratio "169" or "32".
 * @param string      $size  Registered size for the fallback src (e.g. ep-32-m).
 * @param array       $args  sizes, loading (lazy|eager), fetchpriority, class.
 * @return string
 */
function eurasiapulse_image( $post, $ratio = '32', $size = 'ep-32-m', $args = array() ) {
	$post = get_post( $post );
	if ( ! $post || ! has_post_thumbnail( $post ) ) {
		return '';
	}
	$args = wp_parse_args(
		$args,
		array(
			'sizes'         => '(min-width: 900px) 33vw, 100vw',
			'loading'       => 'lazy',
			'fetchpriority' => '',
			'class'         => '',
		)
	);
	$attachment_id = get_post_thumbnail_id( $post );
	$src           = wp_get_attachment_image_src( $attachment_id, $size );
	if ( ! $src ) {
		return '';
	}
	$srcset = wp_get_attachment_image_srcset( $attachment_id, $size );
	$alt    = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	$attr = array(
		'src'      => $src[0],
		'width'    => $src[1],
		'height'   => $src[2],
		'alt'      => $alt,
		'class'    => trim( 'img img--' . $ratio . ' ' . $args['class'] ),
		'decoding' => 'async',
	);
	if ( $srcset ) {
		$attr['srcset'] = $srcset;
		$attr['sizes']  = $args['sizes'];
	}
	if ( 'lazy' === $args['loading'] ) {
		$attr['loading'] = 'lazy';
	}
	if ( 'high' === $args['fetchpriority'] ) {
		$attr['fetchpriority'] = 'high';
	}

	$html = '<img';
	foreach ( $attr as $name => $value ) {
		$html .= sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) );
	}
	return $html . '>';
}

/**
 * Figure with (optional) permalink, caption and credit.
 *
 * @param int|WP_Post $post  Post.
 * @param string      $ratio "169" or "32".
 * @param string      $size  Registered size.
 * @param array       $args  Image args plus: link (bool), caption (bool), class (figure class).
 * @return string
 */
function eurasiapulse_figure( $post, $ratio = '32', $size = 'ep-32-m', $args = array() ) {
	$post = get_post( $post );
	$args = wp_parse_args(
		$args,
		array(
			'link'    => true,
			'caption' => false,
			'figure'  => '',
		)
	);
	$img  = eurasiapulse_image( $post, $ratio, $size, $args );
	if ( ! $img ) {
		return '';
	}
	$html = '<figure class="' . esc_attr( trim( 'figure ' . $args['figure'] ) ) . '">';
	if ( $args['link'] ) {
		$html .= '<a class="figure__link" href="' . esc_url( get_permalink( $post ) ) . '" tabindex="-1" aria-hidden="true">' . $img . '</a>';
	} else {
		$html .= $img;
	}
	if ( $args['caption'] ) {
		$caption = wp_get_attachment_caption( get_post_thumbnail_id( $post ) );
		$credit  = get_post_meta( $post->ID, 'ep_image_credit', true );
		if ( $caption || $credit ) {
			$html .= '<figcaption class="figure__caption">';
			if ( $caption ) {
				$html .= esc_html( $caption );
			}
			if ( $credit ) {
				$html .= ' <span class="figure__credit">' . esc_html( $credit ) . '</span>';
			}
			$html .= '</figcaption>';
		}
	}
	return $html . '</figure>';
}

/* -------------------------------------------------------------------------
 * Navigation and footer fallbacks
 * ---------------------------------------------------------------------- */

/**
 * Categories used when no menu is assigned and for the "auto" section slots.
 *
 * @return WP_Term[]
 */
function eurasiapulse_default_categories() {
	static $cats = null;
	if ( null !== $cats ) {
		return $cats;
	}
	$cats = array();
	$seen = array();
	// Preferred editorial sections first, but only when they actually hold posts.
	foreach ( array( 'politics', 'economy', 'security', 'energy', 'diplomacy' ) as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( $term instanceof WP_Term && $term->count > 0 ) {
			$cats[]                   = $term;
			$seen[ $term->term_id ] = true;
		}
	}
	// Fill up with the most-used top-level categories of the site (current language under Polylang/WPML).
	if ( count( $cats ) < 5 ) {
		$all = get_categories(
			array(
				'orderby' => 'count',
				'order'   => 'DESC',
				'number'  => 12,
				'parent'  => 0,
			)
		);
		foreach ( $all as $term ) {
			if ( 'uncategorized' === $term->slug || isset( $seen[ $term->term_id ] ) ) {
				continue;
			}
			$cats[]                   = $term;
			$seen[ $term->term_id ] = true;
			if ( count( $cats ) >= 5 ) {
				break;
			}
		}
	}
	return $cats;
}

/**
 * Format terms shown in navigation (Analysis, Opinion) when they exist.
 *
 * @return WP_Term[]
 */
function eurasiapulse_nav_format_terms() {
	$terms = array();
	foreach ( array( 'analysis', 'opinion' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'format' );
		if ( $term instanceof WP_Term && $term->count > 0 ) {
			$terms[] = $term;
		}
	}
	return $terms;
}

/**
 * Print a plain list of links.
 *
 * @param array  $items List of [ 'url' => string, 'label' => string ].
 * @param string $class UL class.
 * @param string $id    UL id.
 */
function eurasiapulse_link_list( $items, $class = '', $id = '' ) {
	if ( ! $items ) {
		return;
	}
	printf( '<ul%s%s>', $class ? ' class="' . esc_attr( $class ) . '"' : '', $id ? ' id="' . esc_attr( $id ) . '"' : '' );
	foreach ( $items as $item ) {
		printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( $item['url'] ), esc_html( $item['label'] ) );
	}
	echo '</ul>';
}

/**
 * Term list as link items.
 *
 * @param WP_Term[] $terms Terms.
 * @return array
 */
function eurasiapulse_terms_to_items( $terms ) {
	$items = array();
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$items[] = array(
				'url'   => $link,
				'label' => $term->name,
			);
		}
	}
	return $items;
}

/**
 * Primary navigation: assigned menu or category/format fallback.
 */
function eurasiapulse_primary_nav() {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_id'        => 'primary-menu',
				'menu_class'     => 'nav__menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$items = eurasiapulse_terms_to_items( array_merge( eurasiapulse_default_categories(), eurasiapulse_nav_format_terms() ) );
	eurasiapulse_link_list( $items, 'nav__menu', 'primary-menu' );
}

/**
 * Top bar links: assigned menu or About page + newsletter URL.
 */
function eurasiapulse_topbar_nav() {
	if ( has_nav_menu( 'topbar' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'topbar',
				'container'      => false,
				'menu_class'     => 'topbar__links',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	$items = array();
	$about = get_page_by_path( 'about' );
	if ( $about instanceof WP_Post && 'publish' === $about->post_status ) {
		$items[] = array(
			'url'   => get_permalink( $about ),
			'label' => get_the_title( $about ),
		);
	}
	$newsletter = eurasiapulse_mod( 'newsletter_url' );
	if ( $newsletter ) {
		$items[] = array(
			'url'   => $newsletter,
			'label' => __( 'Newsletter', 'eurasiapulse' ),
		);
	}
	eurasiapulse_link_list( $items, 'topbar__links' );
}

/**
 * Footer column: assigned menu or a computed fallback list.
 *
 * @param string $location Menu location.
 * @param array  $fallback Fallback items.
 */
function eurasiapulse_footer_nav( $location, $fallback ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'footer__list',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	eurasiapulse_link_list( $fallback, 'footer__list' );
}

/**
 * Top-level region terms (non-empty ones; all of them if none has posts yet).
 *
 * @return WP_Term[]
 */
function eurasiapulse_region_terms() {
	if ( ! taxonomy_exists( 'region' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'region',
			'parent'     => 0,
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	return $terms;
}

/**
 * Company pages for the footer, found by slug.
 *
 * @return array
 */
function eurasiapulse_company_items() {
	$items = array();
	foreach ( array( 'about', 'methodology', 'editorial-standards', 'contact', 'privacy', 'privacy-policy' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			$items[ $page->ID ] = array(
				'id'    => $page->ID,
				'url'   => get_permalink( $page ),
				'label' => get_the_title( $page ),
			);
		}
	}
	return array_values( $items );
}

/**
 * Social links configured in the Customizer.
 *
 * @return array
 */
function eurasiapulse_social_items() {
	$map   = array(
		'social_x'         => 'X',
		'social_telegram'  => __( 'Telegram', 'eurasiapulse' ),
		'social_instagram' => __( 'Instagram', 'eurasiapulse' ),
		'social_facebook'  => __( 'Facebook', 'eurasiapulse' ),
		'social_youtube'   => __( 'YouTube', 'eurasiapulse' ),
		'social_linkedin'  => __( 'LinkedIn', 'eurasiapulse' ),
	);
	$items = array();
	foreach ( $map as $key => $label ) {
		$url = eurasiapulse_mod( $key );
		if ( $url ) {
			$items[] = array(
				'key'   => substr( $key, 7 ),
				'url'   => $url,
				'label' => $label,
			);
		}
	}
	if ( eurasiapulse_mod( 'social_rss' ) ) {
		$items[] = array(
			'key'   => 'rss',
			'url'   => get_feed_link(),
			'label' => __( 'RSS', 'eurasiapulse' ),
		);
	}
	return $items;
}

/**
 * Resolve a homepage section slot to a category.
 *
 * @param int $slot 1-based slot number.
 * @return WP_Term|null
 */
function eurasiapulse_section_category( $slot ) {
	$value = (string) eurasiapulse_mod( 'section_' . $slot );
	if ( 'none' === $value ) {
		return null;
	}
	if ( 'auto' === $value ) {
		$defaults = eurasiapulse_default_categories();
		return $defaults[ $slot - 1 ] ?? null;
	}
	$term = get_term( (int) $value, 'category' );
	return $term instanceof WP_Term ? $term : null;
}

/* -------------------------------------------------------------------------
 * Article page helpers
 * ---------------------------------------------------------------------- */

/**
 * Share targets (plain URLs, no SDKs).
 *
 * @param int|WP_Post $post Post.
 * @return array
 */
function eurasiapulse_share_items( $post ) {
	$url   = get_permalink( $post );
	$title = get_the_title( $post );
	return array(
		array(
			'label' => 'X',
			'url'   => 'https://x.com/intent/post?' . http_build_query(
				array(
					'url'  => $url,
					'text' => $title,
				)
			),
		),
		array(
			'label' => __( 'Telegram', 'eurasiapulse' ),
			'url'   => 'https://t.me/share/url?' . http_build_query(
				array(
					'url'  => $url,
					'text' => $title,
				)
			),
		),
		array(
			'label' => __( 'LinkedIn', 'eurasiapulse' ),
			'url'   => 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query( array( 'url' => $url ) ),
		),
	);
}

/**
 * Archive kicker ("Section", "Region", ...).
 *
 * @return string
 */
function eurasiapulse_archive_kicker() {
	if ( is_category() ) {
		return __( 'Section', 'eurasiapulse' );
	}
	if ( is_tax( 'region' ) ) {
		return __( 'Region', 'eurasiapulse' );
	}
	if ( is_tax( 'format' ) ) {
		return __( 'Format', 'eurasiapulse' );
	}
	if ( is_tag() ) {
		return __( 'Topic', 'eurasiapulse' );
	}
	if ( is_author() ) {
		return __( 'Author', 'eurasiapulse' );
	}
	if ( is_date() ) {
		return __( 'Archive', 'eurasiapulse' );
	}
	if ( is_search() ) {
		return __( 'Search', 'eurasiapulse' );
	}
	return __( 'Archive', 'eurasiapulse' );
}

/**
 * Archive heading without the "Category:" prefix.
 *
 * @return string
 */
function eurasiapulse_archive_heading() {
	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false );
	}
	if ( is_author() ) {
		return get_the_author();
	}
	if ( is_search() ) {
		return get_search_query();
	}
	if ( is_home() ) {
		return get_option( 'page_for_posts' ) ? get_the_title( (int) get_option( 'page_for_posts' ) ) : __( 'All articles', 'eurasiapulse' );
	}
	return wp_strip_all_tags( get_the_archive_title() );
}

/**
 * Numbered pagination with accessible labels.
 */
function eurasiapulse_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => __( '&larr; Newer', 'eurasiapulse' ),
			'next_text'          => __( 'Older &rarr;', 'eurasiapulse' ),
			'screen_reader_text' => __( 'Articles navigation', 'eurasiapulse' ),
			'class'              => 'pagination',
		)
	);
}

/* -------------------------------------------------------------------------
 * Top bar: icons, languages, dark mode; footer pages
 * ---------------------------------------------------------------------- */

/**
 * Inline stroke icon (16px, currentColor). Simple geometric glyphs, no icon font.
 *
 * @param string $name x, telegram, instagram, facebook, youtube, linkedin, rss, moon, sun.
 * @return string SVG markup or ''.
 */
function eurasiapulse_icon( $name ) {
	$paths = array(
		'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
		'telegram'  => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>',
		'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'youtube'   => '<path d="M2.5 17a24 24 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.6 49.6 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24 24 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.6 49.6 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
		'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>',
		'rss'       => '<path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1" fill="currentColor"/>',
		'moon'      => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
		'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Languages from Polylang or WPML, else the Customizer "Label|URL" list.
 *
 * @return array List of [ code, name, url, current ].
 */
function eurasiapulse_language_items() {
	$items = array();
	if ( function_exists( 'pll_the_languages' ) ) {
		$langs = pll_the_languages(
			array(
				'raw'           => 1,
				'hide_if_empty' => 0,
			)
		);
		foreach ( (array) $langs as $lang ) {
			$items[] = array(
				'code'    => (string) ( $lang['slug'] ?? '' ),
				'name'    => (string) ( $lang['name'] ?? '' ),
				'url'     => (string) ( $lang['url'] ?? '' ),
				'current' => ! empty( $lang['current_lang'] ),
			);
		}
	} else {
		$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
		foreach ( (array) $langs as $lang ) {
			$items[] = array(
				'code'    => (string) ( $lang['language_code'] ?? $lang['code'] ?? '' ),
				'name'    => (string) ( $lang['native_name'] ?? $lang['translated_name'] ?? '' ),
				'url'     => (string) ( $lang['url'] ?? '' ),
				'current' => ! empty( $lang['active'] ),
			);
		}
	}
	if ( ! $items ) {
		$current = untrailingslashit( home_url( '/' ) );
		foreach ( (array) preg_split( '/\r\n|\r|\n/', (string) eurasiapulse_mod( 'language_links' ) ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
				continue;
			}
			$items[] = array(
				'code'    => $parts[0],
				'name'    => $parts[0],
				'url'     => $parts[1],
				'current' => untrailingslashit( $parts[1] ) === $current,
			);
		}
	}
	$items = array_values( array_filter( $items, static fn( $item ) => '' !== $item['code'] && '' !== $item['url'] ) );

	/**
	 * Filter the language switcher entries.
	 *
	 * @param array $items List of [ code, name, url, current ].
	 */
	return apply_filters( 'eurasiapulse_language_items', $items );
}

/**
 * Print the language switcher (nothing when there is a single language).
 */
function eurasiapulse_language_nav() {
	$items = eurasiapulse_language_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	echo '<nav class="lang" aria-label="' . esc_attr__( 'Languages', 'eurasiapulse' ) . '"><ul class="lang__list">';
	foreach ( $items as $item ) {
		printf(
			'<li><a class="lang__link%1$s" href="%2$s" lang="%3$s" hreflang="%3$s" title="%4$s"%5$s>%6$s</a></li>',
			$item['current'] ? ' is-current' : '',
			esc_url( $item['url'] ),
			esc_attr( $item['code'] ),
			esc_attr( $item['name'] ),
			$item['current'] ? ' aria-current="true"' : '',
			esc_html( strtoupper( $item['code'] ) )
		);
	}
	echo '</ul></nav>';
}

/**
 * Print the social icons for the top bar.
 */
function eurasiapulse_topbar_social() {
	$items = eurasiapulse_social_items();
	if ( ! $items ) {
		return;
	}
	echo '<ul class="topbar__social">';
	foreach ( $items as $item ) {
		$icon = eurasiapulse_icon( $item['key'] );
		printf(
			'<li><a href="%1$s" rel="noopener" aria-label="%2$s">%3$s</a></li>',
			esc_url( $item['url'] ),
			esc_attr( $item['label'] ),
			$icon ? $icon : esc_html( $item['label'] ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);
	}
	echo '</ul>';
}

/**
 * Print the light / dark mode switch.
 */
function eurasiapulse_theme_toggle() {
	printf(
		'<button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="%1$s" data-label-dark="%1$s" data-label-light="%2$s">%3$s%4$s</button>',
		esc_attr__( 'Switch to dark mode', 'eurasiapulse' ),
		esc_attr__( 'Switch to light mode', 'eurasiapulse' ),
		eurasiapulse_icon( 'moon' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		eurasiapulse_icon( 'sun' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	);
}

/**
 * Top-level pages for the footer bottom row (up to 8), skipping given IDs.
 *
 * @param int[] $exclude Page IDs to leave out.
 * @return array
 */
function eurasiapulse_footer_page_items( $exclude = array() ) {
	$exclude[] = (int) get_option( 'page_on_front' );
	$exclude[] = (int) get_option( 'page_for_posts' );
	$pages     = get_pages(
		array(
			'parent'      => 0,
			'sort_column' => 'menu_order,post_title',
			'exclude'     => array_filter( array_map( 'intval', $exclude ) ),
			'number'      => 8,
		)
	);
	$items     = array();
	foreach ( (array) $pages as $page ) {
		$items[] = array(
			'url'   => get_permalink( $page ),
			'label' => get_the_title( $page ),
		);
	}
	return $items;
}
