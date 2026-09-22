<?php
/**
 * SEO output used only when no SEO plugin is active: meta description,
 * canonical links for archives, Open Graph / Twitter cards and Schema.org
 * JSON-LD (NewsMediaOrganization, WebSite, NewsArticle variants, BreadcrumbList).
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect the common SEO plugins so their output is not duplicated.
 *
 * @return bool
 */
function eurasiapulse_has_seo_plugin() {
	$active = defined( 'WPSEO_VERSION' )
		|| class_exists( 'RankMath' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' )
		|| defined( 'SLIM_SEO_VER' )
		|| class_exists( 'SQ_Classes_ObjController' );

	/**
	 * Filter whether an SEO plugin is considered active.
	 *
	 * @param bool $active Detected state.
	 */
	return (bool) apply_filters( 'eurasiapulse_has_seo_plugin', $active );
}

/**
 * Whether the theme should print SEO tags and structured data.
 *
 * @return bool
 */
function eurasiapulse_seo_active() {
	return eurasiapulse_mod( 'schema_enabled' ) && ! eurasiapulse_has_seo_plugin();
}

/**
 * Cut a string at a word boundary.
 *
 * @param string $text   Text.
 * @param int    $length Max characters.
 * @return string
 */
function eurasiapulse_truncate( $text, $length = 160 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $length );
	$pos = mb_strrpos( $cut, ' ' );
	return rtrim( $pos ? mb_substr( $cut, 0, $pos ) : $cut, ' ,;:.' ) . '…';
}

/**
 * Meta description for the current view.
 *
 * @return string
 */
function eurasiapulse_meta_description() {
	if ( is_singular() ) {
		return eurasiapulse_truncate( eurasiapulse_dek( get_queried_object_id(), 40 ) );
	}
	if ( is_front_page() || is_home() ) {
		return eurasiapulse_truncate( get_bloginfo( 'description' ) );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();
		if ( $description ) {
			return eurasiapulse_truncate( $description );
		}
		/* translators: 1: term name, 2: site name */
		return eurasiapulse_truncate( sprintf( __( 'Latest %1$s coverage from %2$s.', 'eurasiapulse' ), single_term_title( '', false ), get_bloginfo( 'name' ) ) );
	}
	if ( is_author() ) {
		$bio = get_the_author_meta( 'description' );
		/* translators: %s: author name */
		return eurasiapulse_truncate( $bio ? $bio : sprintf( __( 'Articles by %s.', 'eurasiapulse' ), get_the_author() ) );
	}
	return '';
}

/**
 * Canonical URL for non-singular views (core handles singular ones).
 *
 * @return string
 */
function eurasiapulse_canonical_url() {
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	if ( $paged > 1 ) {
		return get_pagenum_link( $paged );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() ) {
		$page_for_posts = (int) get_option( 'page_for_posts' );
		return $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/' );
	}
	$object = get_queried_object();
	if ( $object instanceof WP_Term ) {
		$link = get_term_link( $object );
		return is_wp_error( $link ) ? '' : $link;
	}
	if ( $object instanceof WP_User ) {
		return get_author_posts_url( $object->ID );
	}
	return '';
}

/**
 * Organization logo (Customizer field, then the site logo).
 *
 * @return array|null [ url, width, height ]
 */
function eurasiapulse_org_logo() {
	$url = eurasiapulse_mod( 'org_logo' );
	if ( $url ) {
		$id = attachment_url_to_postid( $url );
		if ( $id ) {
			$src = wp_get_attachment_image_src( $id, 'full' );
			if ( $src ) {
				return array(
					'url'    => $src[0],
					'width'  => $src[1],
					'height' => $src[2],
				);
			}
		}
		return array(
			'url'    => $url,
			'width'  => 0,
			'height' => 0,
		);
	}
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => $src[1],
				'height' => $src[2],
			);
		}
	}
	return null;
}

/**
 * Share image for the current view.
 *
 * @return array|null [ url, width, height ]
 */
function eurasiapulse_share_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'ep-169-xl' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => $src[1],
				'height' => $src[2],
			);
		}
	}
	return eurasiapulse_org_logo();
}

/**
 * Print meta description, canonical (archives), Open Graph and Twitter tags.
 */
function eurasiapulse_seo_head() {
	if ( ! eurasiapulse_seo_active() ) {
		return;
	}
	$description = eurasiapulse_meta_description();
	if ( $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}

	$url = '';
	if ( is_singular() ) {
		$url = get_permalink();
	} elseif ( ! is_404() && ! is_search() ) {
		$url = eurasiapulse_canonical_url();
		if ( $url ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
		}
	}

	if ( is_404() || is_search() ) {
		return;
	}

	$tags = array(
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:locale'      => get_locale(),
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'       => is_singular() ? wp_strip_all_tags( get_the_title() ) : wp_get_document_title(),
		'og:description' => $description,
		'og:url'         => $url,
	);
	$image = eurasiapulse_share_image();
	if ( $image ) {
		$tags['og:image'] = $image['url'];
		if ( $image['width'] && $image['height'] ) {
			$tags['og:image:width']  = (string) $image['width'];
			$tags['og:image:height'] = (string) $image['height'];
		}
	}
	if ( is_singular( 'post' ) ) {
		$tags['article:published_time'] = get_the_date( DATE_W3C );
		$tags['article:modified_time']  = get_the_modified_date( DATE_W3C );
		$section                        = eurasiapulse_primary_term( get_queried_object_id(), 'category' );
		if ( $section ) {
			$tags['article:section'] = $section->name;
		}
	}
	foreach ( $tags as $property => $content ) {
		if ( '' !== $content && null !== $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
		}
	}
	if ( is_singular( 'post' ) ) {
		$post_tags = get_the_tags();
		if ( $post_tags ) {
			foreach ( $post_tags as $tag ) {
				printf( '<meta property="article:tag" content="%s">' . "\n", esc_attr( $tag->name ) );
			}
		}
	}

	$twitter = array(
		'twitter:card'        => ( $image && is_singular() ) ? 'summary_large_image' : 'summary',
		'twitter:title'       => $tags['og:title'],
		'twitter:description' => $description,
	);
	if ( $image ) {
		$twitter['twitter:image'] = $image['url'];
	}
	foreach ( $twitter as $name => $content ) {
		if ( '' !== $content ) {
			printf( '<meta name="%s" content="%s">' . "\n", esc_attr( $name ), esc_attr( $content ) );
		}
	}
}
add_action( 'wp_head', 'eurasiapulse_seo_head', 2 );

/**
 * Article node for the JSON-LD graph.
 *
 * @param WP_Post $post   Post.
 * @param string  $org_id Organization @id.
 * @return array
 */
function eurasiapulse_article_schema( $post, $org_id ) {
	$types = array(
		'opinion'   => 'OpinionNewsArticle',
		'analysis'  => 'AnalysisNewsArticle',
		'explainer' => 'BackgroundNewsArticle',
	);
	$slug  = eurasiapulse_format_slug( $post );
	$url   = get_permalink( $post );
	$data  = array(
		'@type'               => $types[ $slug ] ?? 'NewsArticle',
		'@id'                 => $url . '#article',
		'headline'            => wp_strip_all_tags( get_the_title( $post ) ),
		'url'                 => $url,
		'mainEntityOfPage'    => array(
			'@type' => 'WebPage',
			'@id'   => $url,
		),
		'datePublished'       => get_the_date( DATE_W3C, $post ),
		'dateModified'        => get_the_modified_date( DATE_W3C, $post ),
		'author'              => array(
			array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
				'url'   => get_author_posts_url( (int) $post->post_author ),
			),
		),
		'publisher'           => array( '@id' => $org_id ),
		'inLanguage'          => get_bloginfo( 'language' ),
		'isAccessibleForFree' => true,
		'wordCount'           => count( preg_split( '/\s+/u', trim( wp_strip_all_tags( $post->post_content ) ), -1, PREG_SPLIT_NO_EMPTY ) ),
	);

	$dek = eurasiapulse_dek( $post, 40 );
	if ( $dek ) {
		$data['description'] = wp_strip_all_tags( $dek );
	}
	if ( has_post_thumbnail( $post ) ) {
		$images = array();
		foreach ( array( 'ep-169-xl', 'ep-32-xl', 'full' ) as $size ) {
			$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post ), $size );
			if ( $src ) {
				$images[] = $src[0];
			}
		}
		if ( $images ) {
			$data['image'] = array_values( array_unique( $images ) );
		}
	}
	$section = eurasiapulse_primary_term( $post, 'category' );
	if ( $section ) {
		$data['articleSection'] = $section->name;
	}
	$region = eurasiapulse_primary_term( $post, 'region' );
	if ( $region ) {
		$data['contentLocation'] = array(
			'@type' => 'Place',
			'name'  => $region->name,
		);
	}
	$tags = get_the_tags( $post );
	if ( $tags ) {
		$data['keywords'] = wp_list_pluck( $tags, 'name' );
	}
	$source_url = get_post_meta( $post->ID, 'ep_source_url', true );
	if ( $source_url ) {
		$data['isBasedOn'] = $source_url;
	}
	return $data;
}

/**
 * BreadcrumbList node for the current view.
 *
 * @return array|null
 */
function eurasiapulse_breadcrumb_schema() {
	$items = array(
		array(
			'name' => __( 'Home', 'eurasiapulse' ),
			'item' => home_url( '/' ),
		),
	);
	if ( is_singular( 'post' ) ) {
		$post    = get_queried_object();
		$section = eurasiapulse_primary_term( $post, 'category' );
		if ( $section ) {
			$link = get_term_link( $section );
			if ( ! is_wp_error( $link ) ) {
				$items[] = array(
					'name' => $section->name,
					'item' => $link,
				);
			}
		}
		$items[] = array( 'name' => wp_strip_all_tags( get_the_title( $post ) ) );
	} elseif ( is_singular( 'page' ) ) {
		$page = get_queried_object();
		foreach ( array_reverse( get_post_ancestors( $page ) ) as $ancestor_id ) {
			$items[] = array(
				'name' => wp_strip_all_tags( get_the_title( $ancestor_id ) ),
				'item' => get_permalink( $ancestor_id ),
			);
		}
		$items[] = array( 'name' => wp_strip_all_tags( get_the_title( $page ) ) );
	} elseif ( is_category() || is_tag() || is_tax() || is_author() ) {
		$items[] = array( 'name' => eurasiapulse_archive_heading() );
	} else {
		return null;
	}

	$list = array();
	foreach ( $items as $index => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $item['name'],
		);
		if ( ! empty( $item['item'] ) ) {
			$entry['item'] = $item['item'];
		}
		$list[] = $entry;
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
}

/**
 * Print the JSON-LD graph.
 */
function eurasiapulse_jsonld() {
	if ( ! eurasiapulse_seo_active() || is_404() || is_search() ) {
		return;
	}
	$org_id  = home_url( '/#organization' );
	$site_id = home_url( '/#website' );

	$organization = array(
		'@type' => 'NewsMediaOrganization',
		'@id'   => $org_id,
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);
	$logo         = eurasiapulse_org_logo();
	if ( $logo ) {
		$organization['logo'] = array_filter(
			array(
				'@type'  => 'ImageObject',
				'url'    => $logo['url'],
				'width'  => $logo['width'],
				'height' => $logo['height'],
			)
		);
	}
	$same_as = array_filter( array_map( 'trim', explode( "\n", (string) eurasiapulse_mod( 'org_sameas' ) ) ) );
	foreach ( array( 'social_x', 'social_telegram', 'social_instagram', 'social_linkedin', 'social_facebook', 'social_youtube' ) as $key ) {
		$url = eurasiapulse_mod( $key );
		if ( $url ) {
			$same_as[] = $url;
		}
	}
	if ( $same_as ) {
		$organization['sameAs'] = array_values( array_unique( $same_as ) );
	}

	$website = array(
		'@type'      => 'WebSite',
		'@id'        => $site_id,
		'name'       => get_bloginfo( 'name' ),
		'url'        => home_url( '/' ),
		'publisher'  => array( '@id' => $org_id ),
		'inLanguage' => get_bloginfo( 'language' ),
	);
	if ( is_front_page() ) {
		$website['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		);
	}

	$graph = array( $organization, $website );
	if ( is_singular( 'post' ) ) {
		$graph[] = eurasiapulse_article_schema( get_queried_object(), $org_id );
	}
	$breadcrumbs = eurasiapulse_breadcrumb_schema();
	if ( $breadcrumbs ) {
		$graph[] = $breadcrumbs;
	}

	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	if ( $json ) {
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON built from escaped/encoded data.
	}
}
add_action( 'wp_head', 'eurasiapulse_jsonld', 5 );
