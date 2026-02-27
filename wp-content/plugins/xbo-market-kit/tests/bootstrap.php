<?php
declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define WordPress constants used by plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}
if ( ! defined( 'XBO_MARKET_KIT_VERSION' ) ) {
	define( 'XBO_MARKET_KIT_VERSION', '1.0.0' );
}
if ( ! defined( 'XBO_MARKET_KIT_FILE' ) ) {
	define( 'XBO_MARKET_KIT_FILE', dirname( __DIR__ ) . '/xbo-market-kit.php' );
}
if ( ! defined( 'XBO_MARKET_KIT_DIR' ) ) {
	define( 'XBO_MARKET_KIT_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'XBO_MARKET_KIT_URL' ) ) {
	define( 'XBO_MARKET_KIT_URL', 'http://example.com/wp-content/plugins/xbo-market-kit/' );
}
if ( ! defined( 'XBO_MARKET_KIT_REFRESH_INTERVAL' ) ) {
	define( 'XBO_MARKET_KIT_REFRESH_INTERVAL', 15 );
}

// Helper function for testing.
if ( ! function_exists( 'xbo_market_kit_get_refresh_interval' ) ) {
	/**
	 * Get the refresh interval with filter support.
	 *
	 * @return int Refresh interval in seconds.
	 */
	function xbo_market_kit_get_refresh_interval(): int {
		return (int) apply_filters(
			'xbo_market_kit/refresh_interval', // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Project uses slash convention for hooks.
			XBO_MARKET_KIT_REFRESH_INTERVAL
		);
	}
}

// Minimal WP class stubs for unit testing.
if ( ! class_exists( 'WP_REST_Controller' ) ) {
	// phpcs:disable
	class WP_REST_Controller {
		protected $namespace = '';
		protected $rest_base = '';
		public function register_routes(): void {}
		public function get_public_item_schema(): array { return array(); }
	}
	class WP_REST_Response {
		public $data;
		public $status;
		private array $headers = array();
		public function __construct( $data = null, int $status = 200 ) {
			$this->data = $data;
			$this->status = $status;
		}
		public function header( string $key, string $value = '' ): void {
			$this->headers[ $key ] = $value;
		}
		public function get_data() { return $this->data; }
		public function get_status(): int { return $this->status; }
	}
	class WP_REST_Request {
		private array $params = array();
		public function __construct( string $method = 'GET', string $route = '' ) {}
		public function set_param( string $key, $value ): void { $this->params[ $key ] = $value; }
		public function get_param( string $key ) { return $this->params[ $key ] ?? null; }
	}
	class WP_REST_Server {
		const READABLE = 'GET';
	}
	// phpcs:enable
}
