<?php
/**
 * Plugin Name:       Content Writing Tips
 * Description:       Displays centrally managed content writing tips in the dashboard, via shortcode, and via a block.
 * Version:           1.6.1
 * Author:            US Department of Veterans Affairs
 * Requires at least: 6.8
 * Tested up to:      6.9
 * Requires PHP:      7.2
 * Text Domain:       vact-content-writing-tips
 *
 * @package vact\Content_Writing_Tips
 */

namespace vact\Content_Writing_Tips;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VACT_CWT_VERSION', '1.6.1' );
define( 'VACT_CWT_PLUGIN_FILE', __FILE__ );
define( 'VACT_CWT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VACT_CWT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require VACT_CWT_PLUGIN_DIR . 'inc/core.php';
require VACT_CWT_PLUGIN_DIR . 'inc/assets.php';
require VACT_CWT_PLUGIN_DIR . 'inc/dashboard-widget.php';
require VACT_CWT_PLUGIN_DIR . 'inc/shortcode.php';
require VACT_CWT_PLUGIN_DIR . 'inc/block.php';
require VACT_CWT_PLUGIN_DIR . 'inc/settings-page.php';

/**
 * Load the plugin text domain.
 *
 * @return void
 */
function load_textdomain() {
	load_plugin_textdomain(
		'vact-content-writing-tips',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_textdomain' );