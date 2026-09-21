<?php
/**
 * Article meta: subtitle, source, image credit.
 *
 * Registered with register_post_meta() so the fields read and write through
 * the REST API ("meta" object on /wp/v2/posts), plus a plain meta box that
 * works in both the classic and the block editor.
 *
 * @package EurasiaPulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions.
 *
 * @return array<string, array<string, string>>
 */
function eurasiapulse_meta_fields() {
	return array(
		'ep_subtitle'     => array(
			'label'       => __( 'Subtitle (dek / standfirst)', 'eurasiapulse' ),
			'description' => __( 'One or two sentences shown under the headline and used as the meta description.', 'eurasiapulse' ),
			'type'        => 'text',
			'sanitize'    => 'eurasiapulse_sanitize_text',
		),
		'ep_source_name'  => array(
			'label'       => __( 'Source name', 'eurasiapulse' ),
			'description' => __( 'Original source of the story, e.g. an agency or publication.', 'eurasiapulse' ),
			'type'        => 'text',
			'sanitize'    => 'eurasiapulse_sanitize_text',
		),
		'ep_source_url'   => array(
			'label'       => __( 'Source URL', 'eurasiapulse' ),
			'description' => __( 'Link to the original source (http or https).', 'eurasiapulse' ),
			'type'        => 'url',
			'sanitize'    => 'eurasiapulse_sanitize_url',
		),
		'ep_image_credit' => array(
			'label'       => __( 'Image credit', 'eurasiapulse' ),
			'description' => __( 'Photographer or agency shown under the featured image.', 'eurasiapulse' ),
			'type'        => 'text',
			'sanitize'    => 'eurasiapulse_sanitize_text',
		),
	);
}

/**
 * Plain-text sanitizer (single line, no HTML).
 *
 * @param mixed $value Raw value.
 * @return string
 */
function eurasiapulse_sanitize_text( $value ) {
	return sanitize_text_field( (string) $value );
}

/**
 * URL sanitizer: http(s) only, empty string otherwise.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function eurasiapulse_sanitize_url( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	$url = sanitize_url( $value, array( 'http', 'https' ) );
	return $url ? $url : '';
}

/**
 * Who may write the fields (REST and meta box alike).
 *
 * @return bool
 */
function eurasiapulse_meta_auth() {
	return current_user_can( 'edit_posts' );
}

/**
 * Register the meta keys.
 */
function eurasiapulse_register_meta() {
	foreach ( eurasiapulse_meta_fields() as $key => $field ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'              => 'string',
				'description'       => $field['label'],
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => $field['sanitize'],
				'auth_callback'     => 'eurasiapulse_meta_auth',
			)
		);
	}
}
add_action( 'init', 'eurasiapulse_register_meta' );

/**
 * Add the meta box.
 */
function eurasiapulse_add_meta_box() {
	add_meta_box(
		'eurasiapulse-article',
		__( 'Article details', 'eurasiapulse' ),
		'eurasiapulse_render_meta_box',
		'post',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_post', 'eurasiapulse_add_meta_box' );

/**
 * Render the meta box.
 *
 * @param WP_Post $post Current post.
 */
function eurasiapulse_render_meta_box( $post ) {
	wp_nonce_field( 'eurasiapulse_meta_save', 'eurasiapulse_meta_nonce' );
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( eurasiapulse_meta_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		printf(
			'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="large-text" autocomplete="off"><p class="description">%5$s</p></td></tr>',
			esc_attr( $key ),
			esc_html( $field['label'] ),
			esc_attr( $field['type'] ),
			esc_attr( (string) $value ),
			esc_html( $field['description'] )
		);
	}
	echo '</tbody></table>';
}

/**
 * Save the meta box.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function eurasiapulse_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['eurasiapulse_meta_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['eurasiapulse_meta_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'eurasiapulse_meta_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( 'post' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( eurasiapulse_meta_fields() as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the field callback on the next line.
		$raw   = wp_unslash( $_POST[ $key ] );
		$value = call_user_func( $field['sanitize'], is_string( $raw ) ? $raw : '' );
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post_post', 'eurasiapulse_save_meta', 10, 2 );
