<?php
/**
 * Core logic for Content Writing Tips.
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin settings with defaults.
 *
 * @return array
 */
function get_settings() {
	$defaults = array(
		'tip_count'  => 5,       // Number of tips to select per day (for widget & default slider).
		'categories' => array(), // Tip-category IDs to include; empty = all.
	);

	$settings = get_option( 'vact_cwt_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$settings = wp_parse_args( $settings, $defaults );

	if ( ! is_array( $settings['categories'] ) ) {
		$settings['categories'] = array();
	}

	$settings['tip_count'] = (int) $settings['tip_count'];
	if ( ! in_array( $settings['tip_count'], array( 5, 10, 15, 20 ), true ) ) {
		$settings['tip_count'] = 5;
	}

	return $settings;
}

/**
 * Return the filterable endpoint URL for the tip API.
 *
 * @return string
 */
function get_endpoint_url() {
	/**
	 * Filter the endpoint URL used to fetch the tip of the day.
	 *
	 * @param string $url Endpoint URL.
	 */
	$default = 'https://digital.va.gov/wpnavigator/wp-json/wp/v2/content-writing-tip?per_page=100';

	return apply_filters( 'vact_cwt_endpoint_url', $default );
}

/**
 * Return the filterable endpoint URL for the tip categories API.
 *
 * @return string
 */
function get_categories_endpoint_url() {
	/**
	 * Filter the endpoint URL used to fetch tip categories.
	 *
	 * @param string $url Endpoint URL.
	 */
	$default = 'https://digital.va.gov/wpnavigator/wp-json/wp/v2/tip-category?per_page=100';

	return apply_filters( 'vact_cwt_categories_endpoint_url', $default );
}

/**
 * Generate a transient key that changes once per day (for the tip pool).
 *
 * @return string
 */
function get_daily_transient_key() {
	// Use GMT so it is consistent regardless of site timezone.
	return 'vact_cwt_tip_' . gmdate( 'Ymd' ) . '_set';
}

/**
 * Wrapper for remote GET that prefers vip_safe_wp_remote_get() when available.
 *
 * @param string $url  URL to request.
 * @param array  $args Arguments for the request.
 * @return array|\WP_Error
 */
function remote_get( $url, array $args = array() ) {
	$defaults = array(
		'timeout'     => 3,
		'redirection' => 1,
		'user-agent'  => 'VACT-Content-Writing-Tips/' . VACT_CWT_VERSION . '; ' . home_url(),
	);

	$args = wp_parse_args( $args, $defaults );

	if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
		// WP VIP-safe HTTP. Params: url, fallback, timeout, redirection, max_failed_requests, args.
		return vip_safe_wp_remote_get( $url, '', 3, 1, 20, $args );
	}

	return wp_safe_remote_get( $url, $args );
}

/**
 * Normalize a single tip item from the REST response into a consistent array.
 *
 * @param object $item REST item object.
 * @return array
 */
function normalize_tip_item( $item ) {
	// Title.
	$title = '';
	if ( isset( $item->title ) ) {
		if ( is_object( $item->title ) && isset( $item->title->rendered ) ) {
			$title = (string) $item->title->rendered;
		} elseif ( is_string( $item->title ) ) {
			$title = $item->title;
		}
	}

	// Custom fields from Meta Box JSON: meta_box.{tip,detail_link,link_aria_label}.
	$tip_text        = '';
	$detail_link     = '';
	$link_aria_label = '';

	if ( isset( $item->meta_box ) && is_object( $item->meta_box ) ) {
		if ( isset( $item->meta_box->tip ) && is_string( $item->meta_box->tip ) ) {
			$tip_text = $item->meta_box->tip;
		}
		if ( isset( $item->meta_box->detail_link ) && is_string( $item->meta_box->detail_link ) ) {
			$detail_link = $item->meta_box->detail_link;
		}
		if ( isset( $item->meta_box->link_aria_label ) && is_string( $item->meta_box->link_aria_label ) ) {
			$link_aria_label = $item->meta_box->link_aria_label;
		}
	}

	// Optional fallbacks if fields are ever exposed at top level.
	if ( '' === $tip_text && isset( $item->tip ) && is_string( $item->tip ) ) {
		$tip_text = $item->tip;
	}
	if ( '' === $detail_link && isset( $item->detail_link ) && is_string( $item->detail_link ) ) {
		$detail_link = $item->detail_link;
	}
	if ( '' === $link_aria_label && isset( $item->link_aria_label ) && is_string( $item->link_aria_label ) ) {
		$link_aria_label = $item->link_aria_label;
	}

	// Category names from tip_category_names array.
	$category = '';
	if ( isset( $item->tip_category_names ) && is_array( $item->tip_category_names ) ) {
		$names    = array_filter( array_map( 'strval', $item->tip_category_names ) );
		$category = implode( ', ', $names );
	}

	return array(
		'title'           => $title,
		'tip'             => $tip_text,
		'url'             => $detail_link,
		'link_aria_label' => $link_aria_label,
		'category'        => $category,
	);
}

/**
 * Fetch available tip categories via REST, cached.
 *
 * @return array|\WP_Error Array of term objects or WP_Error.
 */
function get_tip_categories() {
	$transient_key = 'vact_cwt_tip_cats';
	$cached        = get_transient( $transient_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$endpoint = get_categories_endpoint_url();

	if ( '' === $endpoint ) {
		return new \WP_Error(
			'vact_cwt_no_cat_endpoint',
			__( 'No endpoint URL has been configured for tip categories.', 'vact-content-writing-tips' )
		);
	}

	$response = remote_get( $endpoint );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		/* translators: %d: HTTP status code. */
		$message = sprintf(
			__( 'The tip categories endpoint returned HTTP %d.', 'vact-content-writing-tips' ),
			(int) $code
		);

		return new \WP_Error(
			'vact_cwt_cat_http_error',
			$message
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === $body ) {
		return new \WP_Error(
			'vact_cwt_cat_empty_body',
			__( 'The tip categories endpoint returned an empty response.', 'vact-content-writing-tips' )
		);
	}

	$data = json_decode( $body );

	if ( null === $data || ! is_array( $data ) ) {
		return new \WP_Error(
			'vact_cwt_cat_bad_json',
			__( 'The tip categories endpoint returned invalid JSON.', 'vact-content-writing-tips' )
		);
	}

	set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );

	return $data;
}

/**
 * Get the full daily pool of tips (up to 100), cached once per day.
 *
 * Applies category filters and stores the full normalized list.
 *
 * @return array|\WP_Error
 */
function get_daily_tip_pool() {
	$settings   = get_settings();
	$categories = $settings['categories'];

	$transient_key = get_daily_transient_key();
	$cached        = get_transient( $transient_key );

	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	$endpoint = get_endpoint_url();

	// If specific categories are selected, filter by those.
	if ( ! empty( $categories ) && is_array( $categories ) ) {
		$cats = array_filter( array_map( 'absint', $categories ) );
		if ( ! empty( $cats ) ) {
			$endpoint = add_query_arg(
				'tip-category',
				implode( ',', $cats ),
				$endpoint
			);
		}
	}

	$response = remote_get( $endpoint );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		/* translators: %d: HTTP status code. */
		$message = sprintf(
			__( 'The Content Writing Tips endpoint returned HTTP %d.', 'vact-content-writing-tips' ),
			(int) $code
		);

		return new \WP_Error(
			'vact_cwt_http_error',
			$message
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === $body ) {
		return new \WP_Error(
			'vact_cwt_empty_body',
			__( 'The Content Writing Tips endpoint returned an empty response.', 'vact-content-writing-tips' )
		);
	}

	$data = json_decode( $body );

	if ( null === $data || ! is_array( $data ) || empty( $data ) ) {
		return new \WP_Error(
			'vact_cwt_no_tips',
			__( 'No tips were returned from the Content Writing Tips endpoint.', 'vact-content-writing-tips' )
		);
	}

	// Randomize once for the day.
	shuffle( $data );

	$normalized = array();

	foreach ( $data as $item ) {
		if ( ! is_object( $item ) ) {
			continue;
		}
		$normalized[] = normalize_tip_item( $item );
	}

	if ( empty( $normalized ) ) {
		return new \WP_Error(
			'vact_cwt_no_valid_tips',
			__( 'The Content Writing Tips endpoint did not return any valid tips.', 'vact-content-writing-tips' )
		);
	}

	set_transient( $transient_key, $normalized, 12 * HOUR_IN_SECONDS );

	return $normalized;
}

/**
 * Fetch a random set of tips for the day, sized by settings tip_count.
 *
 * @return array|\WP_Error Array of normalized tip arrays, or WP_Error on failure.
 */
function get_tips_of_the_day() {
	$settings  = get_settings();
	$tip_count = $settings['tip_count'];

	$pool = get_daily_tip_pool();

	if ( is_wp_error( $pool ) ) {
		return $pool;
	}

	if ( empty( $pool ) ) {
		return new \WP_Error(
			'vact_cwt_empty_pool',
			__( 'No content writing tips are available today.', 'vact-content-writing-tips' )
		);
	}

	$tip_count = max( 1, (int) $tip_count );
	$tip_count = min( $tip_count, count( $pool ) );

	return array_slice( $pool, 0, $tip_count );
}

/**
 * Convenience wrapper: get a single "tip of the day" (first of the daily random set).
 *
 * @return array|\WP_Error Normalized tip array, or WP_Error.
 */
function get_tip_of_the_day() {
	$pool = get_daily_tip_pool();

	if ( is_wp_error( $pool ) ) {
		return $pool;
	}

	if ( empty( $pool ) ) {
		return new \WP_Error(
			'vact_cwt_empty_pool',
			__( 'No content writing tip is available today.', 'vact-content-writing-tips' )
		);
	}

	return $pool[0];
}

/**
 * Build the HTML for a single tip.
 *
 * Meta area layout: Category | Learn more | More tips
 *
 * @param array $tip Normalized tip array.
 * @return string
 */
function build_tip_markup( array $tip ) {
	$title           = isset( $tip['title'] ) ? $tip['title'] : '';
	$tip_text        = isset( $tip['tip'] ) ? $tip['tip'] : '';
	$url             = isset( $tip['url'] ) ? $tip['url'] : '';
	$link_aria_label = isset( $tip['link_aria_label'] ) ? $tip['link_aria_label'] : '';
	$category        = isset( $tip['category'] ) ? $tip['category'] : '';

	$output  = '<section class="vact-tip" aria-label="' . esc_attr__( 'Content writing tip of the day', 'vact-content-writing-tips' ) . '">';
	$output .= '<div class="vact-tip__inner">';

	if ( '' !== $title ) {
		$output .= '<h3 class="vact-tip__title">' . esc_html( wp_strip_all_tags( $title ) ) . '</h3>';
	}

	if ( '' !== $tip_text ) {
		$output .= '<p class="vact-tip__tip">' . esc_html( wp_strip_all_tags( $tip_text ) ) . '</p>';
	}

	if ( '' !== $category || '' !== $url ) {
		$output .= '<div class="vact-tip__meta">';

		if ( '' !== $category ) {
			$output .= '<span class="vact-tip__category">' . esc_html( $category ) . '</span>';
		}

		if ( '' !== $url ) {
			$aria_attr = '';

			if ( '' !== $link_aria_label ) {
				$aria_attr = ' aria-label="' . esc_attr( $link_aria_label ) . '"';
			}

			$output .= '<a class="vact-tip__link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"' . $aria_attr . '>';
			$output .= esc_html__( 'Learn more', 'vact-content-writing-tips' );
			$output .= '</a>';
		}

		$output .= '<a class="vact-tip__moretips" href="' . esc_url( 'https://digital.va.gov/wpnavigator/content-tips/' ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Visit WP Navigator for more tips (opens in a new window).', 'vact-content-writing-tips' ) . '">';
		$output .= esc_html__( 'More tips', 'vact-content-writing-tips' );
		$output .= '</a>';

		$output .= '</div>';
	}

	$output .= '</div>';
	$output .= '</section>';

	return $output;
}

/**
 * Build the markup for a single tip (for shortcode/block single mode).
 *
 * @return string
 */
function render_tip_markup() {
	$tip = get_tip_of_the_day();

	if ( is_wp_error( $tip ) ) {
		$message = $tip->get_error_message();

		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			return sprintf(
				'<p class="vact-tip__error"><strong>%s</strong> %s</p>',
				esc_html__( 'Content Writing Tips error:', 'vact-content-writing-tips' ),
				esc_html( $message )
			);
		}

		return '<p class="vact-tip__error">' . esc_html__( 'A content writing tip is not available right now.', 'vact-content-writing-tips' ) . '</p>';
	}

	return build_tip_markup( $tip );
}

/**
 * Build slider markup from a list of tips (shared by widget, shortcode, block).
 *
 * @param array $tips Array of normalized tip arrays.
 * @return string
 */
function build_slider_markup_from_tips( array $tips ) {
	if ( empty( $tips ) ) {
		return '';
	}

	$count  = count( $tips );
	$output = '<div class="vact-tip-slider" data-tip-count="' . esc_attr( $count ) . '">';
	$output .= '<div class="vact-tip-slider__track">';

	foreach ( $tips as $index => $tip ) {
		$class = ( 0 === $index ) ? ' vact-tip--active' : '';
		$output .= '<div class="vact-tip-slider__item' . esc_attr( $class ) . '" data-tip-index="' . esc_attr( $index ) . '">';
		$output .= build_tip_markup( $tip );
		$output .= '</div>';
	}

	$output .= '</div>'; // track

	$output .= '<div class="vact-tip-slider__controls">';
	$output .= '<button type="button" class="vact-tip-slider__prev" aria-label="' . esc_attr__( 'Show previous tip', 'vact-content-writing-tips' ) . '">&lsaquo;</button>';
	$output .= '<span class="vact-tip-slider__status" aria-live="polite"></span>';
	$output .= '<button type="button" class="vact-tip-slider__next" aria-label="' . esc_attr__( 'Show next tip', 'vact-content-writing-tips' ) . '">&rsaquo;</button>';
	$output .= '</div>';

	$output .= '</div>'; // slider

	return $output;
}

/**
 * Flush today's tip cache (supporting both legacy and current keys).
 *
 * @return void
 */
function flush_tip_cache() {
	$today = gmdate( 'Ymd' );

	// Legacy key (pre-1.3).
	delete_transient( 'vact_cwt_tip_' . $today );

	// Current pooled key.
	delete_transient( 'vact_cwt_tip_' . $today . '_set' );

	// Also clear cached categories so new ones are picked up if needed.
	delete_transient( 'vact_cwt_tip_cats' );
}