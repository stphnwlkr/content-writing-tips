<?php
/**
 * Asset registration.
 *
 * @package vact\Content_Writing_Tips
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shared styles and scripts for the plugin.
 *
 * - Style: vact-content-writing-tips
 * - Slider JS: vact-tip-slider
 * - Block JS: vact-tip-of-day-block (Content Writing Tips block)
 *
 * @return void
 */
function register_styles_and_scripts() {
	// Shared styles for widget, shortcode, and block.
	wp_register_style(
		'vact-content-writing-tips',
		VACT_CWT_PLUGIN_URL . 'assets/css/vact-tip.css',
		array(),
		VACT_CWT_VERSION
	);

	// Slider behavior for any .vact-tip-slider instance.
	wp_register_script(
		'vact-tip-slider',
		VACT_CWT_PLUGIN_URL . 'assets/js/vact-tip-slider.js',
		array(),
		VACT_CWT_VERSION,
		true
	);

	// Block editor script for the Content Writing Tips block.
	// Uses ServerSideRender to preview the dynamic PHP output in the editor.
	wp_register_script(
		'vact-tip-of-day-block',
		VACT_CWT_PLUGIN_URL . 'assets/js/vact-tip-block.js',
		array(
			'wp-blocks',
			'wp-element',
			'wp-i18n',
			'wp-editor',
			'wp-components',
			'wp-block-editor',
			'wp-server-side-render',
		),
		VACT_CWT_VERSION,
		true
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_styles_and_scripts' );