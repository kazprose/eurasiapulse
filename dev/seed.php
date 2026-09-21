<?php
/**
 * Seed neutral placeholder content for layout testing (local dev only).
 *
 * Run AFTER dev/activate.php:  php dev/seed.php [--force]
 * Creates: two sample authors, company pages, three GD-generated placeholder
 * images, ~40 posts across categories / regions / formats (with and without
 * featured images, short and very long titles, Kazakh and Russian samples),
 * article meta (subtitle, source, credit), one sticky lead, one updated post.
 * No invented experts, quotes or statistics: all copy is explicit placeholder text.
 */

$_SERVER['HTTP_HOST'] = '127.0.0.1:8080';
require __DIR__ . '/../.local/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$force = in_array( '--force', $argv, true );
$reset = in_array( '--reset', $argv, true );
if ( $reset ) {
	// Remove every post, page and attachment so the seed starts from a clean slate.
	foreach ( get_posts( array( 'post_type' => array( 'post', 'page', 'attachment' ), 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
		wp_delete_post( $id, true );
	}
	delete_option( 'eurasiapulse_seeded' );
	echo "Reset: all posts, pages and attachments deleted.\n";
} elseif ( get_option( 'eurasiapulse_seeded' ) && ! $force ) {
	echo "Already seeded (use --force to add again, --reset to start over).\n";
	exit;
}
// Default WordPress sample content is not useful for layout testing.
foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
	$default = get_page_by_path( $slug, OBJECT, $type );
	if ( $default ) {
		wp_delete_post( $default->ID, true );
	}
}
if ( 'eurasiapulse' !== get_option( 'stylesheet' ) ) {
	echo "Theme not active. Run dev/activate.php first.\n";
	exit( 1 );
}
if ( function_exists( 'eurasiapulse_seed_default_terms' ) ) {
	eurasiapulse_seed_default_terms();
}

/* ---------- Users ---------- */
$authors = array();
foreach ( array( 'sample-author-one' => 'Sample Author One', 'sample-author-two' => 'Sample Author Two' ) as $login => $display ) {
	$user = get_user_by( 'login', $login );
	if ( ! $user ) {
		$id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => wp_generate_password( 24 ),
				'user_email'   => $login . '@example.com',
				'display_name' => $display,
				'role'         => 'author',
				'description'  => 'Placeholder biography used to test the author box and the author archive. It says nothing about a real person.',
			)
		);
		$authors[] = (int) $id;
	} else {
		$authors[] = (int) $user->ID;
	}
}

/* ---------- Pages ---------- */
$page_copy = '<!-- wp:paragraph --><p>This page contains placeholder text used to test the static page template. It is long enough to wrap across several lines so that the reading column, paragraph spacing and heading rhythm can be checked.</p><!-- /wp:paragraph --><!-- wp:heading --><h2 class="wp-block-heading">Section heading for layout testing</h2><!-- /wp:heading --><!-- wp:paragraph --><p>A second paragraph of placeholder text. Nothing here describes a real organisation, policy or person.</p><!-- /wp:paragraph -->';
foreach ( array( 'About', 'Methodology', 'Editorial Standards', 'Contact', 'Privacy', 'Newsletter' ) as $title ) {
	$existing = get_page_by_path( sanitize_title( $title ) );
	if ( ! $existing ) {
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => sanitize_title( $title ),
				'post_content' => $page_copy,
			)
		);
	}
}

/* ---------- Placeholder images (GD) ---------- */
function ep_seed_image( $label, $shade, $index ) {
	$w  = 1600;
	$h  = 1067;
	$im = imagecreatetruecolor( $w, $h );
	$bg = imagecolorallocate( $im, $shade, $shade, $shade );
	$fg = imagecolorallocate( $im, $shade - 40, $shade - 40, $shade - 40 );
	$tx = imagecolorallocate( $im, 90, 90, 90 );
	imagefill( $im, 0, 0, $bg );
	// Simple neutral geometry so crops are recognisable.
	imagefilledrectangle( $im, (int) ( $w * 0.1 ), (int) ( $h * 0.55 ), (int) ( $w * 0.9 ), (int) ( $h * 0.9 ), $fg );
	imagefilledellipse( $im, (int) ( $w * 0.72 ), (int) ( $h * 0.3 ), 220, 220, $fg );
	imagestring( $im, 5, 40, 30, $label, $tx );
	$upload = wp_upload_bits( 'placeholder-' . $index . '.jpg', null, '' );
	imagejpeg( $im, $upload['file'], 82 );
	imagedestroy( $im );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => 'Placeholder image ' . $index,
			'post_excerpt'   => 'Placeholder caption for layout testing (' . $label . ').',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Placeholder image for layout testing' );
	return (int) $attachment_id;
}
$images = array(
	ep_seed_image( 'Placeholder image A', 218, 1 ),
	ep_seed_image( 'Placeholder image B', 200, 2 ),
	ep_seed_image( 'Placeholder image C', 184, 3 ),
);

/* ---------- Body copy ---------- */
$p = array(
	'This paragraph exists only to test article typography. It is long enough to wrap across several lines at every breakpoint, so that line length, leading and paragraph spacing can be checked against the design without reading anything into the words themselves.',
	'A second paragraph checks the rhythm between paragraphs. Nothing in this text describes a real event, person or organisation; it is placeholder copy written for layout testing and should be replaced by editorial content published through the pipeline.',
	'Links inside body text look like <a href="#">this placeholder link</a>, and emphasis looks like <em>this</em> or <strong>this</strong>. The measure is capped at about 680 pixels so that a line holds roughly 65 to 75 characters.',
	'A closing paragraph of placeholder text ends the article body. It is followed by the source box, topics, share links and the author box, which are all rendered from the theme templates.',
);
function ep_seed_body( $p, $image_id, $variant ) {
	$img = $image_id ? wp_get_attachment_image_src( $image_id, 'ep-169-l' ) : null;
	$out = '<!-- wp:paragraph --><p>' . $p[0] . '</p><!-- /wp:paragraph -->';
	$out .= '<!-- wp:paragraph --><p>' . $p[1] . '</p><!-- /wp:paragraph -->';
	if ( $variant % 2 === 0 ) {
		$out .= '<!-- wp:heading --><h2 class="wp-block-heading">Section heading for layout testing</h2><!-- /wp:heading -->';
		$out .= '<!-- wp:paragraph --><p>' . $p[2] . '</p><!-- /wp:paragraph -->';
		$out .= '<!-- wp:quote --><blockquote class="wp-block-quote"><p>A block quotation of placeholder text, set off from the body with a hairline rule on the left. It is not a quotation from any real person.</p><cite>Placeholder attribution</cite></blockquote><!-- /wp:quote -->';
		$out .= '<!-- wp:paragraph --><p>' . $p[1] . '</p><!-- /wp:paragraph -->';
		if ( $img ) {
			$out .= '<!-- wp:image {"id":' . $image_id . ',"sizeSlug":"ep-169-l"} --><figure class="wp-block-image size-ep-169-l"><img src="' . esc_url( $img[0] ) . '" alt="Placeholder image for layout testing" class="wp-image-' . $image_id . '" width="' . $img[1] . '" height="' . $img[2] . '" loading="lazy"/><figcaption class="wp-element-caption">Placeholder caption inside the article body.</figcaption></figure><!-- /wp:image -->';
		}
		$out .= '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Sub-heading for layout testing</h3><!-- /wp:heading -->';
		$out .= '<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>First placeholder list item</li><!-- /wp:list-item --><!-- wp:list-item --><li>Second placeholder list item, long enough to wrap onto a second line on narrow screens so that hanging indents can be checked</li><!-- /wp:list-item --><!-- wp:list-item --><li>Third placeholder list item</li><!-- /wp:list-item --></ul><!-- /wp:list -->';
		$out .= '<!-- wp:pullquote --><figure class="wp-block-pullquote"><blockquote><p>A pull quote of placeholder text, centred between two hairline rules.</p><cite>Placeholder</cite></blockquote></figure><!-- /wp:pullquote -->';
		$out .= '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Column A</th><th>Column B</th><th>Column C</th><th>Column D</th><th>Column E</th><th>Column F</th></tr></thead><tbody><tr><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td></tr><tr><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td><td>Placeholder</td></tr></tbody></table><figcaption class="wp-element-caption">Placeholder table; scrolls horizontally on narrow screens.</figcaption></figure><!-- /wp:table -->';
		$out .= '<!-- wp:html --><figure class="wp-block-embed is-type-video wp-embed-aspect-16-9 wp-has-aspect-ratio"><div class="wp-block-embed__wrapper"><iframe title="Placeholder embed" src="about:blank" loading="lazy"></iframe></div><figcaption class="wp-element-caption">Placeholder embed at 16:9.</figcaption></figure><!-- /wp:html -->';
		$out .= '<!-- wp:code --><pre class="wp-block-code"><code>placeholder code block</code></pre><!-- /wp:code -->';
	} else {
		$out .= '<!-- wp:paragraph --><p>' . $p[2] . '</p><!-- /wp:paragraph -->';
	}
	$out .= '<!-- wp:paragraph --><p>' . $p[3] . '</p><!-- /wp:paragraph -->';
	return $out;
}

/* ---------- Posts ---------- */
$categories = array( 'politics', 'economy', 'security', 'energy', 'diplomacy' );
$regions    = array( 'kazakhstan', 'uzbekistan', 'kyrgyzstan', 'tajikistan', 'turkmenistan', 'caucasus', 'russia', 'china', 'global' );
// Seven entries so the format rotation never lines up with the five categories.
$formats    = array( 'news', 'analysis', 'news', 'opinion', 'explainer', 'news', 'interview' );
$long_title = 'Sample headline for layout testing with a deliberately long title that wraps across several lines to check spacing, hyphenation and the vertical rhythm of cards';
$titles     = array();
for ( $i = 1; $i <= 40; $i++ ) {
	$titles[ $i ] = sprintf( 'Sample headline for layout testing %02d', $i );
}
$titles[3]  = $long_title . ' 03';
$titles[11] = $long_title . ' 11';
$titles[20] = 'Үлгі тақырып: макетті тексеруге арналған қазақша толтырғыш мәтін 20';
$titles[21] = 'Образец заголовка для проверки макета на русском языке 21';
$titles[30] = $long_title . ' 30';

$created = array();
$now     = time();
for ( $i = 1; $i <= 40; $i++ ) {
	$has_image = ( $i % 4 !== 0 );            // every 4th post has no featured image
	$image_id  = $has_image ? $images[ $i % 3 ] : 0;
	$hours_ago = ( $i - 1 ) * 7 + ( $i % 3 );  // spread over ~12 days, several today
	$date      = $now - $hours_ago * HOUR_IN_SECONDS;
	$format    = $formats[ $i % count( $formats ) ];
	$content   = ep_seed_body( $p, $image_id, $i );
	if ( 20 === $i ) {
		$content = '<!-- wp:paragraph --><p>Бұл мәтін тек макетті тексеруге арналған толтырғыш. Онда нақты оқиға, адам немесе ұйым туралы ешқандай мәлімет жоқ. Ә, Ғ, Қ, Ң, Ө, Ұ, Ү, Һ, І әріптері қаріп жиынтығын тексеру үшін қосылған.</p><!-- /wp:paragraph -->' . $content;
	}
	if ( 21 === $i ) {
		$content = '<!-- wp:paragraph --><p>Этот текст является заполнителем для проверки макета и шрифтов. Он не описывает реальные события, людей или организации.</p><!-- /wp:paragraph -->' . $content;
	}
	$post_id = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $titles[ $i ],
			'post_content'  => $content,
			'post_author'   => $authors[ $i % 2 ],
			'post_date'     => wp_date( 'Y-m-d H:i:s', $date ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $date ),
			'post_category' => array( (int) get_category_by_slug( $categories[ $i % 5 ] )->term_id ),
			'tags_input'    => ( $i % 3 === 0 ) ? array( 'Sample topic A', 'Sample topic B' ) : array( 'Sample topic A' ),
			'meta_input'    => array(
				'ep_subtitle'     => ( $i % 5 === 0 ) ? '' : 'Placeholder subtitle (dek) for layout testing: one or two sentences that summarise the article without making any factual claim.',
				'ep_source_name'  => ( $i % 2 === 0 ) ? 'Sample source' : '',
				'ep_source_url'   => ( $i % 2 === 0 ) ? 'https://example.com/sample-source' : '',
				'ep_image_credit' => $has_image ? 'Photo: Placeholder agency' : '',
			),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		echo 'Error: ' . $post_id->get_error_message() . "\n";
		continue;
	}
	wp_set_object_terms( $post_id, $regions[ $i % 9 ], 'region' );
	wp_set_object_terms( $post_id, $format, 'format' );
	if ( $image_id ) {
		set_post_thumbnail( $post_id, $image_id );
	}
	$created[] = $post_id;
}

// Sticky lead (newest post with an image), and one "updated" post.
$lead = get_posts(
	array(
		'numberposts' => 1,
		'meta_key'    => '_thumbnail_id',
		'post_status' => 'publish',
	)
);
if ( $lead ) {
	stick_post( $lead[0]->ID );
}
if ( isset( $created[5] ) ) {
	wp_update_post(
		array(
			'ID'           => $created[5],
			'post_content' => get_post_field( 'post_content', $created[5] ) . '<!-- wp:paragraph --><p>Update: this paragraph was added later to test the "Updated" time stamp.</p><!-- /wp:paragraph -->',
		)
	);
}

update_option( 'eurasiapulse_seeded', time() );
flush_rewrite_rules();
printf( "Created %d posts, %d images, authors %s, sticky lead %s\n", count( $created ), count( $images ), implode( ',', $authors ), $lead ? $lead[0]->ID : 'none' );
