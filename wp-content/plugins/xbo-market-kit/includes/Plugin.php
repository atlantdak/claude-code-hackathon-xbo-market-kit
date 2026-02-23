<?php
declare(strict_types=1);

namespace XboMarketKit;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Cache\CacheManager;

class Plugin {

	private static ?self $instance = null;
	private ApiClient $api_client;
	private CacheManager $cache_manager;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->cache_manager = new CacheManager();
		$this->api_client    = new ApiClient( $this->cache_manager );
	}

	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'admin_menu', array( $this, 'register_admin' ) );
	}

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

	public function register_blocks(): void {
		$registrar = new Blocks\BlockRegistrar();
		$registrar->register();
	}

	public function register_admin(): void {
		$settings = new Admin\AdminSettings();
		$settings->register();
	}

	public function get_api_client(): ApiClient {
		return $this->api_client;
	}

	public function get_cache_manager(): CacheManager {
		return $this->cache_manager;
	}
}
