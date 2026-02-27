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

/**
 * Default refresh interval for live data widgets (seconds).
 * Applied to cache TTL and frontend auto-refresh timers.
 *
 * @since 1.0.0
 */
if ( ! defined( 'XBO_MARKET_KIT_REFRESH_INTERVAL' ) ) {
	define( 'XBO_MARKET_KIT_REFRESH_INTERVAL', 15 );
}

/**
 * Get the refresh interval with filter support.
 *
 * Allows advanced users to override the default refresh interval
 * via the 'xbo_market_kit/refresh_interval' filter.
 *
 * @since 1.0.0
 * @return int Refresh interval in seconds.
 */
function xbo_market_kit_get_refresh_interval(): int {
	return (int) apply_filters(
		'xbo_market_kit/refresh_interval',
		XBO_MARKET_KIT_REFRESH_INTERVAL
	);
}

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
 * Enqueue widget styles in the block editor for live preview.
 */
function xbo_market_kit_editor_assets(): void {
	wp_enqueue_style(
		'xbo-market-kit-fonts',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	wp_enqueue_style(
		'xbo-market-kit-editor',
		XBO_MARKET_KIT_URL . 'assets/css/dist/widgets' . $suffix . '.css',
		array( 'xbo-market-kit-fonts' ),
		XBO_MARKET_KIT_VERSION
	);
}
add_action( 'enqueue_block_editor_assets', 'xbo_market_kit_editor_assets' );

/**
 * Plugin activation.
 */
function xbo_market_kit_activate(): void {
	\XboMarketKit\Admin\PageManager::create();
	if ( ! wp_next_scheduled( 'xbo_market_kit_sync_icons' ) ) {
		wp_schedule_event( time(), 'daily', 'xbo_market_kit_sync_icons' );
	}
}
register_activation_hook( __FILE__, 'xbo_market_kit_activate' );

/**
 * Plugin deactivation.
 */
function xbo_market_kit_deactivate(): void {
	\XboMarketKit\Admin\PageManager::delete();
	wp_clear_scheduled_hook( 'xbo_market_kit_sync_icons' );
}
register_deactivation_hook( __FILE__, 'xbo_market_kit_deactivate' );

// Register WP-CLI commands.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'xbo icons', \XboMarketKit\Cli\IconsCommand::class );
}
