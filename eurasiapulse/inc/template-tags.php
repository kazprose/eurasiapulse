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
	foreach ( array( 'politics', 'economy', 'security', 'energy', 'diplomacy' ) as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( $term instanceof WP_Term ) {
			$cats[] = $term;
		}
	}
	if ( ! $cats ) {
		$all = get_categories(
			array(
				'orderby' => 'count',
				'order'   => 'DESC',
				'number'  => 6,
				'parent'  => 0,
			)
		);
		foreach ( $all as $term ) {
			if ( 'uncategorized' !== $term->slug ) {
				$cats[] = $term;
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
		if ( $term instanceof WP_Term ) {
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
		'social_x'        => 'X',
		'social_telegram' => __( 'Telegram', 'eurasiapulse' ),
		'social_linkedin' => __( 'LinkedIn', 'eurasiapulse' ),
		'social_facebook' => __( 'Facebook', 'eurasiapulse' ),
		'social_youtube'  => __( 'YouTube', 'eurasiapulse' ),
	);
	$items = array();
	foreach ( $map as $key => $label ) {
		$url = eurasiapulse_mod( $key );
		if ( $url ) {
			$items[] = array(
				'url'   => $url,
				'label' => $label,
			);
		}
	}
	if ( eurasiapulse_mod( 'social_rss' ) ) {
		$items[] = array(
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
