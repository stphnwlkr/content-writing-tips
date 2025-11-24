<?php
/**
 * Dashboard widget.
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard widget callback: slider of daily tips.
 *
 * @return void
 */
function render_dashboard_widget() {
	wp_enqueue_style( 'vact-content-writing-tips' );
	wp_enqueue_script( 'vact-tip-slider' );

	$tips = get_tips_of_the_day();

	if ( is_wp_error( $tips ) ) {
		$message = $tips->get_error_message();

		echo '<p class="vact-tip__error"><strong>' . esc_html__( 'Content Writing Tips error:', 'vact-content-writing-tips' ) . '</strong> ' . esc_html( $message ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	if ( empty( $tips ) ) {
		echo '<p class="vact-tip__error">' . esc_html__( 'No content writing tips are available today.', 'vact-content-writing-tips' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	echo build_slider_markup_from_tips( $tips ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Register the dashboard widget.
 *
 * @return void
 */
function register_dashboard_widget() {
	wp_add_dashboard_widget(
		'vact_content_writing_tips_widget',
		esc_html__( 'Content Writing Tips', 'vact-content-writing-tips' ),
		__NAMESPACE__ . '\\render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\register_dashboard_widget' );