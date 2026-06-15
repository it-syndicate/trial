<?php

if ( ! defined( 'OPTION_FEED_ALLOWED_FLAGS' ) ) {
	define( 'OPTION_FEED_ALLOWED_FLAGS', array( 'nocomments', 'nosrcset', 'plaindescription' ) );
}

/**
 * Register Feed Option rewrites for feed URLs with options.
 */
function register_feed_option_rewrites() {
	// Register rewrite tag and rule for feed options path (e.g., /feed/options/nocomments,nosrcset,plaindescription/).
	add_rewrite_tag( '%feed_options%', '([^&]+)' );
	add_rewrite_rule(
		'^feed/options/([^/]+)/?$',
		'index.php?feed=rss2&feed_options=$matches[1]',
		'top'
	);

	// Register rewrite rule for alt feed with options (e.g., /feed/alt/options/nocomments,nosrcset,plaindescription/).
	add_rewrite_rule(
		'^feed/alt/options/([^/]+)/?$',
		'index.php?feed=alt&feed_options=$matches[1]',
		'top'
	);
}
add_action( 'init', 'register_feed_option_rewrites', 9999 );

/**
 * Parse comma-separated feed options from URL path (e.g., nocomments,nosrcset,plaindescription).
 *
 * @return array Flags as keys: ['nocomments' => true, ...]
 */
function option_get_feed_flags_from_options_path() {
	$raw = get_query_var( 'feed_options' );
	if ( empty( $raw ) ) {
		return array();
	}

	$parts = array_map( 'trim', explode( ',', strtolower( $raw ) ) );
	$parts = array_filter( $parts );
	$parts = array_unique( $parts );

	return array_fill_keys( array_intersect( $parts, OPTION_FEED_ALLOWED_FLAGS ), true );
}

/**
 * Determine whether the current request is a feed variant with a specific flag.
 *
 * @param string $flag The flag to check (e.g., 'nocomments', 'nosrcset', 'plaindescription').
 * @return bool
 */
function option_has_feed_flag( $flag ) {
	if ( ! is_feed() || is_comment_feed() ) {
		return false;
	}

	$flags = option_get_feed_flags_from_options_path();
	return isset( $flags[ $flag ] );
}

/**
 * Helper function to determine if the current feed request should apply a specific flag's behavior.
 *
 * @param bool $flag Feed flag.
 */
function option_should_apply_feed_flag( $flag ) {
	return option_has_feed_flag( $flag );
}

/**
 * Options feed: Remove srcset from images in feeds when 'nosrcset' flag is present.
 *
 * @param array $sources Return image with or without srcset.
 */
function option_feed_remove_srcset( $sources ) {
	if ( ! option_should_apply_feed_flag( 'nosrcset' ) ) {
		return $sources;
	}

	return array();
}
add_filter( 'wp_calculate_image_srcset', 'option_feed_remove_srcset', 50 );

/**
 * Options feed: Convert excerpt to plain text when 'plaindescription' flag is present.
 */
function option_custom_feed_excerpt_plaintext( $excerpt ) {
	if ( ! option_should_apply_feed_flag( 'plaindescription' ) ) {
		return $excerpt;
	}

	$plain_text = wp_strip_all_tags( $excerpt, true );
	$plain_text = html_entity_decode( $plain_text, ENT_QUOTES, 'UTF-8' );
	$plain_text = preg_replace( '/\s+/', ' ', $plain_text );

	return trim( $plain_text );
}
add_filter( 'the_excerpt_rss', 'option_custom_feed_excerpt_plaintext', 50 );

/**
 * Options feed: Determine if the current feed request should apply the 'nocomments' flag.
 */
function option_is_nocomments_feed_request() {
	return option_should_apply_feed_flag( 'nocomments' );
}

/**
 * Options feed: Disable comments in the main feed when 'nocomments' flag is present.
 *
 *  @param bool $comments_open Comments open.
 */
function option_disable_comments_open_in_main_feed( $comments_open ) {
	if ( ! option_is_nocomments_feed_request() ) {
		return $comments_open;
	}

	return false;
}
add_filter( 'comments_open', 'option_disable_comments_open_in_main_feed', 10, 2 );

/**
 * Options feed: Set comments number to zero in the main feed when 'nocomments' flag is present.
 *
 * @param int $comments_number Comments open.
 */
function option_zero_comments_number_in_main_feed( $comments_number ) {
	if ( ! option_is_nocomments_feed_request() ) {
		return $comments_number;
	}

	return 0;
}
add_filter( 'get_comments_number', 'option_zero_comments_number_in_main_feed', 10, 2 );
