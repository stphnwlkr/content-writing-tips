<?php
/**
 * Gutenberg block for Content Writing Tips.
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render callback for the Tip of the Day block.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Inner content (unused).
 * @return string
 */
function render_tip_block( $attributes, $content ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	$mode = isset( $attributes['displayMode'] ) && 'slider' === $attributes['displayMode']
		? 'slider'
		: 'single';

	$count = isset( $attributes['tipCount'] ) ? (int) $attributes['tipCount'] : 5;
	$count = max( 1, min( 20, $count ) );

	// Ensure tips are available for today.
	$pool = get_daily_tip_pool();
	if ( empty( $pool ) || ! is_array( $pool ) ) {
		return '';
	}

	if ( 'slider' === $mode ) {
		// Use up to $count tips from today's pool.
		$tips = array_slice( $pool, 0, $count );

		return build_slider_markup_from_tips( $tips );
	}

	// Single mode: use the first tip from today's pool.
	$tip = $pool[0];

	return build_single_tip_markup( $tip );
}

/**
 * Register the Tip of the Day block using block.json (Block API v3).
 *
 * @return void
 */
function register_tip_block() {
	// Path to the block metadata directory (contains block.json).
	$metadata_path = VACT_CWT_PLUGIN_DIR . 'blocks/content-writing-tips';

	if ( ! file_exists( $metadata_path . '/block.json' ) ) {
		return;
	}

	register_block_type(
		$metadata_path,
		array(
			'render_callback' => __NAMESPACE__ . '\\render_tip_block',
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_tip_block' );