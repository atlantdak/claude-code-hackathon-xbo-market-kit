<?php
/**
 * Plugin class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Cache\CacheManager;
use XboMarketKit\Icons\IconResolver;

/**
 * Main plugin singleton class.
 *
 * Bootstraps all plugin components including REST controllers,
 * shortcodes, Gutenberg blocks, and admin settings.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * XBO API client instance.
	 *
	 * @var ApiClient
	 */
	private ApiClient $api_client;

	/**
	 * Cache manager instance.
	 *
	 * @var CacheManager
	 */
	private CacheManager $cache_manager;

	/**
	 * Icon resolver instance.
	 *
	 * @var IconResolver
	 */
	private IconResolver $icon_resolver;

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @return self Plugin instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Initializes core services.
	 */
	private function __construct() {
		$this->cache_manager = new CacheManager();
		$this->api_client    = new ApiClient( $this->cache_manager );
		$this->icon_resolver = new IconResolver(
			XBO_MARKET_KIT_DIR . 'assets/images/icons',
			XBO_MARKET_KIT_URL . 'assets/images/icons'
		);
	}

	/**
	 * Initialize the plugin by registering WordPress hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'admin_menu', array( $this, 'register_admin' ) );
		add_action( 'xbo_market_kit_sync_icons', array( $this, 'cron_sync_icons' ) );
	}

	/**
	 * Register all REST API route controllers.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controllers = array(
			new Rest\TickerController(),
			new Rest\MoversController(),
			new Rest\OrderbookController(),
			new Rest\TradesController(),
			new Rest\SlippageController(),
		);
		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Register all shortcode handlers.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		$shortcodes = array(
			new Shortcodes\TickerShortcode(),
			new Shortcodes\MoversShortcode(),
			new Shortcodes\OrderbookShortcode(),
			new Shortcodes\TradesShortcode(),
			new Shortcodes\SlippageShortcode(),
		);
		foreach ( $shortcodes as $shortcode ) {
			$shortcode->register();
		}
	}

	/**
	 * Register Gutenberg blocks.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$registrar = new Blocks\BlockRegistrar();
		$registrar->register();
	}

	/**
	 * Register admin settings page.
	 *
	 * @return void
	 */
	public function register_admin(): void {
		$settings = new Admin\AdminSettings();
		$settings->register();
	}

	/**
	 * Cron callback: sync missing crypto icons.
	 *
	 * @return void
	 */
	public function cron_sync_icons(): void {
		$sync = new Icons\IconSync( $this->icon_resolver );

		$response = $this->api_client->get_currencies();
		if ( ! $response->success ) {
			return;
		}

		$symbols = array_column( $response->data, 'code' );
		$sync->sync_missing( $symbols );
	}

	/**
	 * Get the API client instance.
	 *
	 * @return ApiClient API client.
	 */
	public function get_api_client(): ApiClient {
		return $this->api_client;
	}

	/**
	 * Get the icon resolver instance.
	 *
	 * @return IconResolver Icon resolver.
	 */
	public function get_icon_resolver(): IconResolver {
		return $this->icon_resolver;
	}

	/**
	 * Get the cache manager instance.
	 *
	 * @return CacheManager Cache manager.
	 */
	public function get_cache_manager(): CacheManager {
		return $this->cache_manager;
	}
}
