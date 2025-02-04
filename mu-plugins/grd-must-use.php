<?php
/**
 * Plugin Name: GRD - Must Use
 * Description: This plugin contains code that is required for gregrickaby.com.
 * Version: 1.0.0
 * Author: Greg Rickaby
 * Author URI: https://gregrickaby.com
 */

/**
 * Disable image size threshold.
 *
 * @return bool
 */
add_filter( 'big_image_size_threshold', '__return_false' );

/**
 * Allow additional mime types on upload.
 *
 * @param array $mimes Existing mime types.
 * @return array Modified mime types.
 */
function grd_additional_mime_types( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';
	$mimes['avif'] = 'image/avif';
	return $mimes;
}
add_filter( 'upload_mimes', 'grd_additional_mime_types' );

/**
 * Don't remove the custom field meta box.
 *
 * @return bool
 */
add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' );

/**
 * Remove generator meta tags.
 *
 * @return bool
 */
add_filter( 'the_generator', '__return_false' );

/**
 * Disable XML RPC.
 *
 * @return bool
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Change default REST-API header from "null" to "*".
 */
function grd_cors_control() {
	header( 'Access-Control-Allow-Origin: *' );
}
add_action( 'rest_api_init', 'grd_cors_control' );

/**
 * Registers custom fields for categories, tags, featured image URL, author name, and Gravatar URL in the REST API response.
 */
function grd_add_custom_fields_to_rest_api() {
	register_rest_field(
		'post',
		'category_names',
		array(
			'get_callback'    => 'grd_get_categories_names',
			'update_callback' => null,
			'schema'          => null,
		)
	);

	register_rest_field(
		'post',
		'tag_names',
		array(
			'get_callback'    => 'grd_get_tags_names',
			'update_callback' => null,
			'schema'          => null,
		)
	);

	register_rest_field(
		'post',
		'featured_image_data',
		array(
			'get_callback'    => 'grd_get_featured_image_data',
			'update_callback' => null,
			'schema'          => null,
		)
	);

	register_rest_field(
		'post',
		'author_name',
		array(
			'get_callback'    => 'grd_get_author_name',
			'update_callback' => null,
			'schema'          => null,
		)
	);

	register_rest_field(
		'post',
		'author_gravatar_url',
		array(
			'get_callback'    => 'grd_get_author_gravatar_url',
			'update_callback' => null,
			'schema'          => null,
		)
	);
}
add_action( 'rest_api_init', 'grd_add_custom_fields_to_rest_api' );

/**
 * Retrieves category names for a given post.
 *
 * @param array $object The post object.
 *
 * @return array An array of category names and IDs.
 */
function grd_get_categories_names( $object ) {
	$categories     = get_the_category( $object['id'] );
	$category_names = array();

	if ( ! empty( $categories ) ) {
		foreach ( $categories as $category ) {
			$category_names[] = array(
				'id'   => $category->term_id,
				'name' => $category->name,
			);
		}
	}

	return $category_names;
}

/**
 * Retrieves tag names for a given post.
 *
 * @param array $object The post object.
 *
 * @return array An array of tag names and IDs.
 */
function grd_get_tags_names( $object ) {
	$tags      = get_the_tags( $object['id'] );
	$tag_names = array();

	if ( ! empty( $tags ) ) {
		foreach ( $tags as $tag ) {
			$tag_names[] = array(
				'id'   => $tag->term_id,
				'name' => $tag->name,
			);
		}
	}

	return $tag_names;
}

/**
 * Retrieves the metadata of the featured image for a given post.
 *
 * @param array $object The post object.
 *
 * @return array An array containing the URL, height, width, and alt text of the featured image.
 */
function grd_get_featured_image_data( $object ) {
	$featured_image_id = get_post_thumbnail_id( $object['id'] );
	if ( $featured_image_id ) {
		$featured_image_url  = wp_get_attachment_image_url( $featured_image_id, 'full' );
		$featured_image_meta = wp_get_attachment_metadata( $featured_image_id );
		$featured_image_alt  = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );

		return array(
			'url'    => $featured_image_url,
			'height' => isset( $featured_image_meta['height'] ) ? $featured_image_meta['height'] : '',
			'width'  => isset( $featured_image_meta['width'] ) ? $featured_image_meta['width'] : '',
			'alt'    => $featured_image_alt,
		);
	}
	return array();
}

/**
 * Retrieves the author name for a given post.
 *
 * @param array $object The post object.
 *
 * @return string The author's display name.
 */
function grd_get_author_name( $object ) {
	$author_id   = $object['author'];
	$author_name = get_the_author_meta( 'display_name', $author_id );
	return $author_name;
}

/**
 * Retrieves the Gravatar URL for a given post's author.
 *
 * @param array $object The post object.
 *
 * @return string The URL of the author's Gravatar.
 */
function grd_get_author_gravatar_url( $object ) {
	$author_id    = $object['author'];
	$author_email = get_the_author_meta( 'user_email', $author_id );
	$gravatar_url = get_avatar_url( $author_email );
	return $gravatar_url;
}

/**
 * Clean up rendered content in REST API responses.
 *
 * WordPress adds whitespace, tabs, and newline information in the database
 * when saving a post or page, and this data is sent as-is in the REST API response.
 *
 * This function removes excessive whitespace, including newline, tab, and
 * multiple spaces, from the rendered content before sending it to the client.
 *
 * @param WP_REST_Response $data    The response object.
 * @param WP_Post          $post    The post object.
 * @param WP_REST_Request  $request The REST request object.
 *
 * @return WP_REST_Response Filtered response object with cleaned content.
 */
function grd_clean_rendered_content( $data, $post, $request ) {
	// Apply only to specific routes for posts and pages.
	$route = $request->get_route();
	if ( strpos( $route, '/wp/v2/posts' ) === false && strpos( $route, '/wp/v2/pages' ) === false ) {
		return $data;
	}

	// Check if the content is present in the response.
	if ( isset( $data->data['content']['rendered'] ) ) {
		// Perform selective whitespace cleanup.
		$data->data['content']['rendered'] = trim(
			preg_replace(
				'/\s{2,}/', // Match two or more whitespace characters.
				' ', // Replace with a single space.
				$data->data['content']['rendered']
			)
		);
	}

	return $data;
}
add_filter( 'rest_prepare_post', 'grd_clean_rendered_content', 10, 3 );
add_filter( 'rest_prepare_page', 'grd_clean_rendered_content', 10, 3 );
