<?php
/**
 * Shortcode implementation.
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode handler: [vact_tip_of_the_day]
 *
 * Attributes:
 * - mode:  "single" (default) or "slider"
 * - count: number of tips to show in slider mode (1–20, default 5)
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function shortcode_tip_of_the_day( $atts = array() ) {
	wp_enqueue_style( 'vact-content-writing-tips' );

	$atts = shortcode_atts(
		array(
			'mode'  => 'single',
			'count' => 5,
		),
		$atts,
		'vact_tip_of_the_day'
	);

	$mode  = strtolower( (string) $atts['mode'] );
	$count = (int) $atts['count'];

	if ( $count < 1 ) {
		$count = 1;
	}
	if ( $count > 20 ) {
		$count = 20;
	}

	// Slider mode: show multiple tips.
	if ( 'slider' === $mode ) {
		wp_enqueue_script( 'vact-tip-slider' );

		$pool = get_daily_tip_pool();

		if ( is_wp_error( $pool ) ) {
			$message = $pool->get_error_message();

			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				return sprintf(
					'<p class="vact-tip__error"><strong>%s</strong> %s</p>',
					esc_html__( 'Content Writing Tips error:', 'vact-content-writing-tips' ),
					esc_html( $message )
				);
			}

			return '<p class="vact-tip__error">' . esc_html__( 'A content writing tip is not available right now.', 'vact-content-writing-tips' ) . '</p>';
		}

		if ( empty( $pool ) ) {
			return '<p class="vact-tip__error">' . esc_html__( 'No content writing tips are available today.', 'vact-content-writing-tips' ) . '</p>';
		}

		$count = min( $count, count( $pool ) );
		$tips  = array_slice( $pool, 0, $count );

		return build_slider_markup_from_tips( $tips );
	}

	// Default: single tip.
	return render_tip_markup();
}
add_shortcode( 'vact_tip_of_the_day', __NAMESPACE__ . '\\shortcode_tip_of_the_day' );