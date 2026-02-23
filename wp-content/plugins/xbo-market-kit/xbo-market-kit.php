<?php
/**
 * Plugin Name: XBO Market Kit
 * Plugin URI:  https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit
 * Description: Live cryptocurrency market data widgets for WordPress. Shortcodes, Gutenberg blocks, and Elementor widgets powered by the XBO Public API.
 * Version:     0.1.0
 * Author:      atlantdak
 * Author URI:  https://github.com/atlantdak
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xbo-market-kit
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.7
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'XBO_MARKET_KIT_VERSION', '0.1.0' );
define( 'XBO_MARKET_KIT_FILE', __FILE__ );
define( 'XBO_MARKET_KIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'XBO_MARKET_KIT_URL', plugin_dir_url( __FILE__ ) );

// Autoload via Composer.
if ( file_exists( XBO_MARKET_KIT_DIR . 'vendor/autoload.php' ) ) {
	require_once XBO_MARKET_KIT_DIR . 'vendor/autoload.php';
}

/**
 * Initialize the plugin.
 */
function xbo_market_kit_init(): void {
	\XboMarketKit\Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'xbo_market_kit_init' );

/**
 * Plugin activation.
 */
function xbo_market_kit_activate(): void {
	// Demo page creation will be added in Task 10.
}
register_activation_hook( __FILE__, 'xbo_market_kit_activate' );

/**
 * Plugin deactivation.
 */
function xbo_market_kit_deactivate(): void {
	// Cleanup will be added in Task 10.
}
register_deactivation_hook( __FILE__, 'xbo_market_kit_deactivate' );
