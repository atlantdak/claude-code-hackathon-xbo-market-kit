# XBO Market Kit Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a complete WordPress plugin with 5 crypto market widgets (Ticker, Movers, Orderbook, Trades, Slippage Calculator), REST API, caching, Gutenberg blocks, and a demo page.

**Architecture:** Monolithic Controller pattern — one Plugin class bootstraps ApiClient, CacheManager, REST controllers, Shortcodes, Blocks, and Admin. Data flows: Browser → WP REST API → CacheManager → ApiClient → api.xbo.com.

**Tech Stack:** PHP 8.1+, WordPress 6.9, Tailwind CSS CDN, WP Interactivity API, WP REST API (WC-style controllers), WordPress Transients for caching.

**Design doc:** `docs/plans/2026-02-23-xbo-market-kit-full-design.md`

---

## Parallelism Map

```
Phase 1: Foundation (sequential)
  Task 1 → Task 2 → Task 3

Phase 2: REST API (parallel after Task 3)
  Task 4 ──┐
  Task 5 ──┤ (parallel)
            ↓
Phase 3: Shortcodes + Frontend JS (parallel after Phase 2)
  Task 6 ──┐
  Task 7 ──┤ (parallel)
  Task 8 ──┘
            ↓
Phase 4: Blocks + Admin (parallel after Phase 3)
  Task 9 ──┐
  Task 10 ─┘ (parallel)
            ↓
Phase 5: Polish (sequential)
  Task 11
```

**All file paths relative to:** `wp-content/plugins/xbo-market-kit/`

---

## Phase 1: Foundation

### Task 1: Plugin Bootstrap + Test Infrastructure

**Goal:** Wire up the Plugin class that initializes all components, add Brain\Monkey for WP function mocking in tests.

**Files:**
- Modify: `composer.json`
- Modify: `tests/bootstrap.php`
- Modify: `xbo-market-kit.php`
- Create: `includes/Plugin.php`
- Create: `tests/Unit/PluginTest.php`

**Step 1: Add brain/monkey dependency**

Add `"brain/monkey": "^2.6"` to `require-dev` in `composer.json`, then run:

```bash
cd wp-content/plugins/xbo-market-kit && composer require --dev brain/monkey:^2.6
```

**Step 2: Update test bootstrap**

Replace `tests/bootstrap.php` with:

```php
<?php
declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define WordPress constants used by plugin.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}
if ( ! defined( 'XBO_MARKET_KIT_VERSION' ) ) {
    define( 'XBO_MARKET_KIT_VERSION', '0.1.0' );
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
```

**Step 3: Create Plugin class**

Create `includes/Plugin.php`:

```php
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
        // Will be implemented in Task 4-5.
    }

    public function register_shortcodes(): void {
        // Will be implemented in Task 6-8.
    }

    public function register_blocks(): void {
        // Will be implemented in Task 9.
    }

    public function register_admin(): void {
        // Will be implemented in Task 10.
    }

    public function get_api_client(): ApiClient {
        return $this->api_client;
    }

    public function get_cache_manager(): CacheManager {
        return $this->cache_manager;
    }
}
```

**Step 4: Update main plugin file**

Add to end of `xbo-market-kit.php` (after autoload):

```php
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
```

**Step 5: Write Plugin test**

Create `tests/Unit/PluginTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Plugin;

class PluginTest extends TestCase {
    use MockeryPHPUnitIntegration;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        // Reset singleton for test isolation.
        $reflection = new \ReflectionClass( Plugin::class );
        $instance   = $reflection->getProperty( 'instance' );
        $instance->setAccessible( true );
        $instance->setValue( null, null );
        parent::tearDown();
    }

    public function test_instance_returns_singleton(): void {
        Functions\expect( 'get_option' )->andReturn( array() );
        $a = Plugin::instance();
        $b = Plugin::instance();
        $this->assertSame( $a, $b );
    }

    public function test_init_registers_hooks(): void {
        Functions\expect( 'get_option' )->andReturn( array() );
        Functions\expect( 'add_action' )->times( 4 );
        Plugin::instance()->init();
    }
}
```

**Step 6: Run tests**

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
```

Expected: 2 tests pass.

**Step 7: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/
git commit -m "feat: add Plugin bootstrap class and test infrastructure"
```

---

### Task 2: API Client + API Response

**Goal:** HTTP client that fetches data from api.xbo.com via wp_remote_get with error handling.

**Files:**
- Create: `includes/Api/ApiResponse.php`
- Create: `includes/Api/ApiClient.php`
- Create: `tests/Unit/Api/ApiClientTest.php`

**Step 1: Create ApiResponse DTO**

Create `includes/Api/ApiResponse.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Api;

class ApiResponse {

    public function __construct(
        public readonly bool $success,
        public readonly array $data,
        public readonly string $error_message = '',
        public readonly int $status_code = 200,
    ) {}

    public static function success( array $data ): self {
        return new self( true, $data );
    }

    public static function error( string $message, int $status_code = 500 ): self {
        return new self( false, array(), $message, $status_code );
    }
}
```

**Step 2: Create ApiClient**

Create `includes/Api/ApiClient.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Api;

use XboMarketKit\Cache\CacheManager;

class ApiClient {

    private const BASE_URL = 'https://api.xbo.com';
    private const TIMEOUT  = 10;

    private CacheManager $cache;

    public function __construct( CacheManager $cache ) {
        $this->cache = $cache;
    }

    public function get_stats(): ApiResponse {
        return $this->cached_request( 'xbo_mk_stats', '/trading-pairs/stats', 30 );
    }

    public function get_orderbook( string $symbol, int $depth = 20 ): ApiResponse {
        $symbol = $this->to_underscore_format( $symbol );
        $depth  = min( max( $depth, 1 ), 250 );
        $key    = sprintf( 'xbo_mk_ob_%s_%d', $symbol, $depth );
        $url    = sprintf( '/orderbook/%s?depth=%d', $symbol, $depth );
        return $this->cached_request( $key, $url, 5 );
    }

    public function get_trades( string $symbol ): ApiResponse {
        $symbol = $this->to_slash_format( $symbol );
        $key    = sprintf( 'xbo_mk_trades_%s', sanitize_key( $symbol ) );
        $url    = sprintf( '/trades?symbol=%s', rawurlencode( $symbol ) );
        return $this->cached_request( $key, $url, 10 );
    }

    public function get_currencies(): ApiResponse {
        return $this->cached_request( 'xbo_mk_currencies', '/currencies', 21600 );
    }

    public function get_trading_pairs(): ApiResponse {
        return $this->cached_request( 'xbo_mk_pairs', '/trading-pairs', 21600 );
    }

    private function cached_request( string $cache_key, string $path, int $ttl ): ApiResponse {
        $cached = $this->cache->get( $cache_key );
        if ( null !== $cached ) {
            return ApiResponse::success( $cached );
        }

        $response = $this->fetch( $path );
        if ( $response->success ) {
            $this->cache->set( $cache_key, $response->data, $ttl );
        }
        return $response;
    }

    private function fetch( string $path ): ApiResponse {
        $url = apply_filters( 'xbo_market_kit/api_base_url', self::BASE_URL ) . $path;

        $response = wp_remote_get(
            $url,
            array(
                'timeout'   => self::TIMEOUT,
                'sslverify' => true,
            )
        );

        if ( is_wp_error( $response ) ) {
            return ApiResponse::error( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 ) {
            return ApiResponse::error(
                sprintf( 'API returned HTTP %d', $code ),
                $code
            );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return ApiResponse::error( 'Invalid JSON response' );
        }

        return ApiResponse::success( $data );
    }

    private function to_underscore_format( string $symbol ): string {
        return str_replace( '/', '_', $symbol );
    }

    private function to_slash_format( string $symbol ): string {
        return str_replace( '_', '/', $symbol );
    }
}
```

**Step 3: Write ApiClient tests**

Create `tests/Unit/Api/ApiClientTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Api;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Api\ApiClient;
use XboMarketKit\Api\ApiResponse;
use XboMarketKit\Cache\CacheManager;

class ApiClientTest extends TestCase {
    use MockeryPHPUnitIntegration;

    private CacheManager $cache;
    private ApiClient $client;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        $this->cache  = \Mockery::mock( CacheManager::class );
        $this->client = new ApiClient( $this->cache );
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_get_stats_returns_cached_data(): void {
        $data = array( array( 'symbol' => 'BTC/USDT', 'lastPrice' => '65000' ) );
        $this->cache->shouldReceive( 'get' )->with( 'xbo_mk_stats' )->andReturn( $data );

        $response = $this->client->get_stats();
        $this->assertTrue( $response->success );
        $this->assertSame( $data, $response->data );
    }

    public function test_get_stats_fetches_from_api_on_cache_miss(): void {
        $data = array( array( 'symbol' => 'BTC/USDT' ) );
        $this->cache->shouldReceive( 'get' )->with( 'xbo_mk_stats' )->andReturn( null );
        $this->cache->shouldReceive( 'set' )->with( 'xbo_mk_stats', $data, 30 )->once();

        Functions\expect( 'apply_filters' )->andReturn( 'https://api.xbo.com' );
        Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( $data ) ) );
        Functions\expect( 'is_wp_error' )->andReturn( false );
        Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
        Functions\expect( 'wp_remote_retrieve_body' )->andReturn( wp_json_encode( $data ) );
        Functions\expect( 'wp_json_encode' )->andReturnUsing( 'json_encode' );

        $response = $this->client->get_stats();
        $this->assertTrue( $response->success );
    }

    public function test_get_stats_returns_error_on_wp_error(): void {
        $this->cache->shouldReceive( 'get' )->andReturn( null );

        $wp_error = \Mockery::mock( 'WP_Error' );
        $wp_error->shouldReceive( 'get_error_message' )->andReturn( 'Connection timeout' );

        Functions\expect( 'apply_filters' )->andReturn( 'https://api.xbo.com' );
        Functions\expect( 'wp_remote_get' )->andReturn( $wp_error );
        Functions\expect( 'is_wp_error' )->andReturn( true );

        $response = $this->client->get_stats();
        $this->assertFalse( $response->success );
        $this->assertSame( 'Connection timeout', $response->error_message );
    }

    public function test_get_orderbook_uses_underscore_format(): void {
        $data = array( 'bids' => array(), 'asks' => array() );
        $this->cache->shouldReceive( 'get' )->with( 'xbo_mk_ob_BTC_USDT_20' )->andReturn( $data );

        $response = $this->client->get_orderbook( 'BTC/USDT', 20 );
        $this->assertTrue( $response->success );
    }

    public function test_get_orderbook_clamps_depth(): void {
        $this->cache->shouldReceive( 'get' )->with( 'xbo_mk_ob_BTC_USDT_250' )->andReturn( array() );
        $this->client->get_orderbook( 'BTC_USDT', 999 );
        // No exception means depth was clamped to 250.
        $this->assertTrue( true );
    }

    public function test_get_trades_uses_slash_format(): void {
        $this->cache->shouldReceive( 'get' )->withArgs( function ( $key ) {
            return str_contains( $key, 'trades' );
        } )->andReturn( array() );

        Functions\expect( 'sanitize_key' )->andReturnUsing( function ( $key ) {
            return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) );
        } );

        $response = $this->client->get_trades( 'BTC_USDT' );
        $this->assertTrue( $response->success );
    }
}
```

**Step 4: Run tests**

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
```

Expected: All tests pass.

**Step 5: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Api/ wp-content/plugins/xbo-market-kit/tests/Unit/Api/
git commit -m "feat: add ApiClient and ApiResponse with caching and tests"
```

---

### Task 3: Cache Manager

**Goal:** Transient-based caching with configurable TTL multiplier.

**Files:**
- Create: `includes/Cache/CacheManager.php`
- Create: `tests/Unit/Cache/CacheManagerTest.php`

**Step 1: Create CacheManager**

Create `includes/Cache/CacheManager.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Cache;

class CacheManager {

    private const TRANSIENT_PREFIX = 'xbo_mk_';
    private float $ttl_multiplier;

    public function __construct() {
        $options              = get_option( 'xbo_market_kit_settings', array() );
        $mode                 = $options['cache_mode'] ?? 'normal';
        $this->ttl_multiplier = match ( $mode ) {
            'fast'  => 0.5,
            'slow'  => 2.0,
            default => 1.0,
        };
    }

    public function get( string $key ): mixed {
        $value = get_transient( $key );
        return false === $value ? null : $value;
    }

    public function set( string $key, mixed $data, int $ttl ): void {
        $adjusted_ttl = (int) round( $ttl * $this->ttl_multiplier );
        set_transient( $key, $data, max( $adjusted_ttl, 1 ) );
    }

    public function delete( string $key ): void {
        delete_transient( $key );
    }

    public function flush_all(): int {
        global $wpdb;
        $prefix  = '_transient_' . self::TRANSIENT_PREFIX;
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $prefix . '%',
                '_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
            )
        );
        return (int) $deleted;
    }
}
```

**Step 2: Write CacheManager tests**

Create `tests/Unit/Cache/CacheManagerTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Cache;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Cache\CacheManager;

class CacheManagerTest extends TestCase {
    use MockeryPHPUnitIntegration;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_get_returns_null_on_miss(): void {
        Functions\expect( 'get_option' )->andReturn( array() );
        Functions\expect( 'get_transient' )->with( 'xbo_mk_test' )->andReturn( false );

        $cache = new CacheManager();
        $this->assertNull( $cache->get( 'xbo_mk_test' ) );
    }

    public function test_get_returns_data_on_hit(): void {
        $data = array( 'price' => '65000' );
        Functions\expect( 'get_option' )->andReturn( array() );
        Functions\expect( 'get_transient' )->with( 'xbo_mk_test' )->andReturn( $data );

        $cache = new CacheManager();
        $this->assertSame( $data, $cache->get( 'xbo_mk_test' ) );
    }

    public function test_set_uses_ttl_multiplier_normal(): void {
        Functions\expect( 'get_option' )->andReturn( array( 'cache_mode' => 'normal' ) );
        Functions\expect( 'set_transient' )->with( 'key', 'data', 30 )->once();

        $cache = new CacheManager();
        $cache->set( 'key', 'data', 30 );
    }

    public function test_set_uses_ttl_multiplier_fast(): void {
        Functions\expect( 'get_option' )->andReturn( array( 'cache_mode' => 'fast' ) );
        Functions\expect( 'set_transient' )->with( 'key', 'data', 15 )->once();

        $cache = new CacheManager();
        $cache->set( 'key', 'data', 30 );
    }

    public function test_delete_calls_delete_transient(): void {
        Functions\expect( 'get_option' )->andReturn( array() );
        Functions\expect( 'delete_transient' )->with( 'xbo_mk_test' )->once();

        $cache = new CacheManager();
        $cache->delete( 'xbo_mk_test' );
    }
}
```

**Step 3: Run tests**

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Cache/ wp-content/plugins/xbo-market-kit/tests/Unit/Cache/
git commit -m "feat: add CacheManager with transient-based caching and TTL multiplier"
```

---

## Phase 2: REST API (Tasks 4-5 run in PARALLEL)

### Task 4: AbstractController + TickerController + MoversController

**Goal:** WC-style REST controllers for /xbo/v1/ticker and /xbo/v1/movers.

**Files:**
- Create: `includes/Rest/AbstractController.php`
- Create: `includes/Rest/TickerController.php`
- Create: `includes/Rest/MoversController.php`
- Create: `tests/Unit/Rest/TickerControllerTest.php`
- Create: `tests/Unit/Rest/MoversControllerTest.php`
- Modify: `includes/Plugin.php` (register routes)

**Step 1: Create AbstractController**

Create `includes/Rest/AbstractController.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Controller;
use WP_REST_Response;

abstract class AbstractController extends WP_REST_Controller {

    protected string $namespace = 'xbo/v1';

    protected function success_response( array $data, int $status = 200 ): WP_REST_Response {
        $response = new WP_REST_Response( $data, $status );
        $response->header( 'X-XBO-Cache', 'hit-or-miss' );
        return $response;
    }

    protected function error_response( string $message, string $code = 'xbo_error', int $status = 500 ): WP_REST_Response {
        return new WP_REST_Response(
            array(
                'code'    => $code,
                'message' => $message,
                'data'    => array( 'status' => $status ),
            ),
            $status
        );
    }

    protected function get_api_client(): \XboMarketKit\Api\ApiClient {
        return \XboMarketKit\Plugin::instance()->get_api_client();
    }
}
```

**Step 2: Create TickerController**

Create `includes/Rest/TickerController.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class TickerController extends AbstractController {

    public function __construct() {
        $this->rest_base = 'ticker';
    }

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    public function get_items( $request ): WP_REST_Response {
        $symbols_raw = $request->get_param( 'symbols' );
        $symbols     = array_map( 'trim', explode( ',', $symbols_raw ) );

        $api_response = $this->get_api_client()->get_stats();
        if ( ! $api_response->success ) {
            return $this->error_response( $api_response->error_message );
        }

        $filtered = array_values(
            array_filter(
                $api_response->data,
                fn( $item ) => in_array( $item['symbol'] ?? '', $symbols, true )
            )
        );

        $normalized = array_map( array( $this, 'normalize_item' ), $filtered );
        return $this->success_response( $normalized );
    }

    private function normalize_item( array $item ): array {
        $parts = explode( '/', $item['symbol'] ?? '' );
        return array(
            'symbol'          => $item['symbol'] ?? '',
            'base'            => $parts[0] ?? '',
            'quote'           => $parts[1] ?? '',
            'last_price'      => (float) ( $item['lastPrice'] ?? 0 ),
            'change_pct_24h'  => (float) ( $item['priceChangePercent24H'] ?? 0 ),
            'high_24h'        => (float) ( $item['highestPrice24H'] ?? 0 ),
            'low_24h'         => (float) ( $item['lowestPrice24H'] ?? 0 ),
            'volume_24h'      => (float) ( $item['quoteVolume'] ?? 0 ),
            'highest_bid'     => (float) ( $item['highestBid'] ?? 0 ),
            'lowest_ask'      => (float) ( $item['lowestAsk'] ?? 0 ),
        );
    }

    public function get_collection_params(): array {
        return array(
            'symbols' => array(
                'description'       => 'Comma-separated trading pair symbols (e.g. BTC/USDT,ETH/USDT).',
                'type'              => 'string',
                'default'           => 'BTC/USDT,ETH/USDT',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    public function get_item_schema(): array {
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'xbo-ticker',
            'type'       => 'object',
            'properties' => array(
                'symbol'          => array( 'type' => 'string' ),
                'base'            => array( 'type' => 'string' ),
                'quote'           => array( 'type' => 'string' ),
                'last_price'      => array( 'type' => 'number' ),
                'change_pct_24h'  => array( 'type' => 'number' ),
                'high_24h'        => array( 'type' => 'number' ),
                'low_24h'         => array( 'type' => 'number' ),
                'volume_24h'      => array( 'type' => 'number' ),
            ),
        );
    }
}
```

**Step 3: Create MoversController**

Create `includes/Rest/MoversController.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class MoversController extends AbstractController {

    public function __construct() {
        $this->rest_base = 'movers';
    }

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ),
            )
        );
    }

    public function get_items( $request ): WP_REST_Response {
        $mode  = $request->get_param( 'mode' );
        $limit = $request->get_param( 'limit' );

        $api_response = $this->get_api_client()->get_stats();
        if ( ! $api_response->success ) {
            return $this->error_response( $api_response->error_message );
        }

        $items = $api_response->data;

        // Filter out items without price change data.
        $items = array_filter(
            $items,
            fn( $item ) => isset( $item['priceChangePercent24H'] ) && '' !== $item['priceChangePercent24H']
        );

        // Sort by 24h change.
        usort(
            $items,
            fn( $a, $b ) => 'gainers' === $mode
                ? (float) $b['priceChangePercent24H'] <=> (float) $a['priceChangePercent24H']
                : (float) $a['priceChangePercent24H'] <=> (float) $b['priceChangePercent24H']
        );

        $items = array_slice( $items, 0, $limit );

        $normalized = array_map(
            fn( $item ) => array(
                'symbol'         => $item['symbol'] ?? '',
                'base'           => explode( '/', $item['symbol'] ?? '' )[0] ?? '',
                'quote'          => explode( '/', $item['symbol'] ?? '' )[1] ?? '',
                'last_price'     => (float) ( $item['lastPrice'] ?? 0 ),
                'change_pct_24h' => (float) ( $item['priceChangePercent24H'] ?? 0 ),
                'volume_24h'     => (float) ( $item['quoteVolume'] ?? 0 ),
            ),
            $items
        );

        return $this->success_response( $normalized );
    }

    public function get_collection_params(): array {
        return array(
            'mode'  => array(
                'description'       => 'Sort mode: gainers or losers.',
                'type'              => 'string',
                'default'           => 'gainers',
                'enum'              => array( 'gainers', 'losers' ),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'limit' => array(
                'description'       => 'Number of items to return.',
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 50,
                'sanitize_callback' => 'absint',
            ),
        );
    }
}
```

**Step 4: Update Plugin.php to register routes**

In `Plugin::register_rest_routes()`:

```php
public function register_rest_routes(): void {
    $controllers = array(
        new \XboMarketKit\Rest\TickerController(),
        new \XboMarketKit\Rest\MoversController(),
    );
    foreach ( $controllers as $controller ) {
        $controller->register_routes();
    }
}
```

**Step 5: Write tests, run, commit**

Tests should verify normalization logic and sorting for movers. Pattern same as ApiClient tests — mock API client, verify output format.

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
git add wp-content/plugins/xbo-market-kit/
git commit -m "feat: add TickerController and MoversController REST endpoints"
```

---

### Task 5: OrderbookController + TradesController + SlippageController (PARALLEL with Task 4)

**Goal:** Remaining 3 REST controllers including slippage calculation logic.

**Files:**
- Create: `includes/Rest/OrderbookController.php`
- Create: `includes/Rest/TradesController.php`
- Create: `includes/Rest/SlippageController.php`
- Create: `tests/Unit/Rest/SlippageControllerTest.php`
- Modify: `includes/Plugin.php` (register routes)

**Step 1: Create OrderbookController**

Create `includes/Rest/OrderbookController.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class OrderbookController extends AbstractController {

    public function __construct() {
        $this->rest_base = 'orderbook';
    }

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ),
            )
        );
    }

    public function get_items( $request ): WP_REST_Response {
        $symbol = $request->get_param( 'symbol' );
        $depth  = $request->get_param( 'depth' );

        $api_response = $this->get_api_client()->get_orderbook( $symbol, $depth );
        if ( ! $api_response->success ) {
            return $this->error_response( $api_response->error_message );
        }

        $data = $api_response->data;
        $bids = array_slice( $data['bids'] ?? array(), 0, $depth );
        $asks = array_slice( $data['asks'] ?? array(), 0, $depth );

        $best_bid = ! empty( $bids ) ? (float) $bids[0][0] : 0.0;
        $best_ask = ! empty( $asks ) ? (float) $asks[0][0] : 0.0;
        $spread   = $best_ask - $best_bid;

        return $this->success_response(
            array(
                'symbol'     => $symbol,
                'bids'       => array_map( fn( $b ) => array( 'price' => (float) $b[0], 'amount' => (float) $b[1] ), $bids ),
                'asks'       => array_map( fn( $a ) => array( 'price' => (float) $a[0], 'amount' => (float) $a[1] ), $asks ),
                'spread'     => $spread,
                'spread_pct' => $best_bid > 0 ? round( $spread / $best_bid * 100, 4 ) : 0,
                'timestamp'  => $data['timestamp'] ?? '',
            )
        );
    }

    public function get_collection_params(): array {
        return array(
            'symbol' => array(
                'description'       => 'Trading pair in underscore format (e.g. BTC_USDT).',
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'depth'  => array(
                'description' => 'Order book depth.',
                'type'        => 'integer',
                'default'     => 20,
                'minimum'     => 1,
                'maximum'     => 250,
            ),
        );
    }
}
```

**Step 2: Create TradesController**

Create `includes/Rest/TradesController.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class TradesController extends AbstractController {

    public function __construct() {
        $this->rest_base = 'trades';
    }

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ),
            )
        );
    }

    public function get_items( $request ): WP_REST_Response {
        $symbol = $request->get_param( 'symbol' );
        $limit  = $request->get_param( 'limit' );

        $api_response = $this->get_api_client()->get_trades( $symbol );
        if ( ! $api_response->success ) {
            return $this->error_response( $api_response->error_message );
        }

        $trades     = array_slice( $api_response->data, 0, $limit );
        $normalized = array_map(
            fn( $t ) => array(
                'id'        => $t['id'] ?? '',
                'symbol'    => $t['symbol'] ?? $symbol,
                'side'      => strtolower( $t['type'] ?? 'buy' ),
                'price'     => (float) ( $t['price'] ?? 0 ),
                'amount'    => (float) ( $t['volume'] ?? 0 ),
                'total'     => (float) ( $t['quoteVolume'] ?? 0 ),
                'timestamp' => $t['timeStamp'] ?? '',
            ),
            $trades
        );

        return $this->success_response( $normalized );
    }

    public function get_collection_params(): array {
        return array(
            'symbol' => array(
                'description'       => 'Trading pair in slash format (e.g. BTC/USDT).',
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'limit'  => array(
                'description' => 'Number of trades to return.',
                'type'        => 'integer',
                'default'     => 20,
                'minimum'     => 1,
                'maximum'     => 100,
            ),
        );
    }
}
```

**Step 3: Create SlippageController**

Create `includes/Rest/SlippageController.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class SlippageController extends AbstractController {

    public function __construct() {
        $this->rest_base = 'slippage';
    }

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ),
            )
        );
    }

    public function get_items( $request ): WP_REST_Response {
        $symbol = $request->get_param( 'symbol' );
        $side   = $request->get_param( 'side' );
        $amount = (float) $request->get_param( 'amount' );
        $depth  = $request->get_param( 'depth' );

        $api_response = $this->get_api_client()->get_orderbook( $symbol, $depth );
        if ( ! $api_response->success ) {
            return $this->error_response( $api_response->error_message );
        }

        $data  = $api_response->data;
        $book  = 'buy' === $side ? ( $data['asks'] ?? array() ) : ( $data['bids'] ?? array() );
        $result = self::calculate_slippage( $book, $amount );

        $bids     = $data['bids'] ?? array();
        $asks     = $data['asks'] ?? array();
        $best_bid = ! empty( $bids ) ? (float) $bids[0][0] : 0.0;
        $best_ask = ! empty( $asks ) ? (float) $asks[0][0] : 0.0;
        $spread   = $best_ask - $best_bid;

        return $this->success_response(
            array(
                'symbol'      => $symbol,
                'side'        => $side,
                'amount'      => $amount,
                'avg_price'   => $result['avg_price'],
                'best_price'  => $result['best_price'],
                'slippage_pct' => $result['slippage_pct'],
                'spread'      => round( $spread, 8 ),
                'spread_pct'  => $best_bid > 0 ? round( $spread / $best_bid * 100, 4 ) : 0,
                'depth_used'  => $result['depth_used'],
                'total_cost'  => $result['total_cost'],
            )
        );
    }

    /**
     * Walk the order book and calculate average execution price and slippage.
     *
     * @param array $book  Array of [price, amount] levels.
     * @param float $amount Desired trade amount in base currency.
     * @return array{avg_price: float, best_price: float, slippage_pct: float, depth_used: float, total_cost: float}
     */
    public static function calculate_slippage( array $book, float $amount ): array {
        if ( empty( $book ) || $amount <= 0 ) {
            return array(
                'avg_price'    => 0,
                'best_price'   => 0,
                'slippage_pct' => 0,
                'depth_used'   => 0,
                'total_cost'   => 0,
            );
        }

        $best_price  = (float) $book[0][0];
        $remaining   = $amount;
        $total_cost  = 0.0;
        $depth_used  = 0.0;

        foreach ( $book as $level ) {
            $price     = (float) $level[0];
            $available = (float) $level[1];

            if ( $remaining <= 0 ) {
                break;
            }

            $fill        = min( $remaining, $available );
            $total_cost += $fill * $price;
            $depth_used += $fill;
            $remaining  -= $fill;
        }

        $filled    = $amount - $remaining;
        $avg_price = $filled > 0 ? $total_cost / $filled : 0;
        $slippage  = $best_price > 0 ? abs( $avg_price - $best_price ) / $best_price * 100 : 0;

        return array(
            'avg_price'    => round( $avg_price, 8 ),
            'best_price'   => round( $best_price, 8 ),
            'slippage_pct' => round( $slippage, 4 ),
            'depth_used'   => round( $depth_used, 8 ),
            'total_cost'   => round( $total_cost, 8 ),
        );
    }

    public function get_collection_params(): array {
        return array(
            'symbol' => array(
                'description'       => 'Trading pair in underscore format (e.g. BTC_USDT).',
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'side'   => array(
                'description' => 'Trade side: buy or sell.',
                'type'        => 'string',
                'default'     => 'buy',
                'enum'        => array( 'buy', 'sell' ),
            ),
            'amount' => array(
                'description' => 'Trade amount in base currency.',
                'type'        => 'number',
                'required'    => true,
                'minimum'     => 0.001,
            ),
            'depth'  => array(
                'description' => 'Order book depth to fetch.',
                'type'        => 'integer',
                'default'     => 250,
                'minimum'     => 1,
                'maximum'     => 250,
            ),
        );
    }
}
```

**Step 4: Write slippage calculation tests**

Create `tests/Unit/Rest/SlippageControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use XboMarketKit\Rest\SlippageController;

class SlippageControllerTest extends TestCase {

    public function test_slippage_exact_fill_at_best_price(): void {
        $book   = array( array( '100.00', '10.0' ) );
        $result = SlippageController::calculate_slippage( $book, 5.0 );
        $this->assertSame( 100.0, $result['avg_price'] );
        $this->assertSame( 0.0, $result['slippage_pct'] );
        $this->assertSame( 500.0, $result['total_cost'] );
    }

    public function test_slippage_across_multiple_levels(): void {
        $book = array(
            array( '100.00', '5.0' ),
            array( '101.00', '5.0' ),
            array( '102.00', '5.0' ),
        );
        $result = SlippageController::calculate_slippage( $book, 10.0 );
        // 5 * 100 + 5 * 101 = 1005, avg = 100.5.
        $this->assertSame( 100.5, $result['avg_price'] );
        $this->assertSame( 1005.0, $result['total_cost'] );
        $this->assertSame( 10.0, $result['depth_used'] );
        $this->assertGreaterThan( 0, $result['slippage_pct'] );
    }

    public function test_slippage_insufficient_liquidity(): void {
        $book   = array( array( '100.00', '2.0' ) );
        $result = SlippageController::calculate_slippage( $book, 5.0 );
        // Only 2.0 filled out of 5.0.
        $this->assertSame( 2.0, $result['depth_used'] );
        $this->assertSame( 200.0, $result['total_cost'] );
    }

    public function test_slippage_empty_book(): void {
        $result = SlippageController::calculate_slippage( array(), 1.0 );
        $this->assertSame( 0.0, $result['avg_price'] );
    }

    public function test_slippage_zero_amount(): void {
        $book   = array( array( '100.00', '10.0' ) );
        $result = SlippageController::calculate_slippage( $book, 0.0 );
        $this->assertSame( 0.0, $result['avg_price'] );
    }
}
```

**Step 5: Update Plugin.php, run tests, commit**

Add OrderbookController, TradesController, SlippageController to `register_rest_routes()`.

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
git add wp-content/plugins/xbo-market-kit/
git commit -m "feat: add Orderbook, Trades, and Slippage REST controllers"
```

---

## Phase 3: Shortcodes + Frontend JS (Tasks 6-8 run in PARALLEL)

### Task 6: Ticker + Movers Shortcodes and JS

**Goal:** [xbo_ticker] and [xbo_movers] shortcodes with Tailwind UI and Interactivity API.

**Files:**
- Create: `includes/Shortcodes/AbstractShortcode.php`
- Create: `includes/Shortcodes/TickerShortcode.php`
- Create: `includes/Shortcodes/MoversShortcode.php`
- Create: `assets/js/interactivity/ticker.js`
- Create: `assets/js/interactivity/movers.js`
- Create: `assets/css/widgets.css`
- Modify: `includes/Plugin.php` (register shortcodes, enqueue assets)

**Implementation notes:**

AbstractShortcode provides:
- `register()` method to call `add_shortcode()`
- `enqueue_tailwind()` — loads Tailwind CDN once
- `enqueue_interactivity()` — loads widget JS as viewScriptModule
- `render_wrapper()` — wraps content in `data-wp-interactive` container

TickerShortcode renders horizontal cards with:
- Crypto icon (colored circle with first letter)
- Symbol pair name
- Last price
- 24h change % (green/red)
- Decorative SVG sparkline

MoversShortcode renders:
- Tab bar: Gainers | Losers (uses `data-wp-bind--class` for active tab)
- Table with Pair, Price, 24h Change columns

JS files use `@wordpress/interactivity` store API:
- `store('xbo-market-kit', { state: {...}, actions: {...} })`
- Polling via `setInterval` in `actions.init()`
- `wp.apiFetch` or plain `fetch` to `/wp-json/xbo/v1/ticker`

**Step 1-3:** Create AbstractShortcode, TickerShortcode, MoversShortcode (PHP renders HTML with Tailwind classes and data-wp-* directives)

**Step 4-5:** Create ticker.js and movers.js Interactivity API stores

**Step 6:** Create widgets.css with price-flash animations:

```css
.xbo-mk-price-up { animation: xbo-mk-flash-green 0.5s; }
.xbo-mk-price-down { animation: xbo-mk-flash-red 0.5s; }
@keyframes xbo-mk-flash-green { 0%,100% { background: transparent; } 50% { background: rgba(34,197,94,0.2); } }
@keyframes xbo-mk-flash-red { 0%,100% { background: transparent; } 50% { background: rgba(239,68,68,0.2); } }
```

**Step 7:** Update Plugin.php to register shortcodes in `register_shortcodes()`

**Step 8:** Run tests, commit

```bash
git commit -m "feat: add Ticker and Movers shortcodes with Interactivity API frontend"
```

---

### Task 7: Orderbook + Trades Shortcodes and JS (PARALLEL with Task 6)

**Goal:** [xbo_orderbook] and [xbo_trades] shortcodes with live-updating UI.

**Files:**
- Create: `includes/Shortcodes/OrderbookShortcode.php`
- Create: `includes/Shortcodes/TradesShortcode.php`
- Create: `assets/js/interactivity/orderbook.js`
- Create: `assets/js/interactivity/trades.js`

**Implementation notes:**

OrderbookShortcode renders:
- Split layout: Bids (left, green) | Asks (right, red)
- Each row: price, amount, depth bar (width % of max amount)
- Spread indicator at bottom center
- 5s auto-refresh

TradesShortcode renders:
- Table: Time, Side (Buy/Sell badge), Price, Amount
- Side badges: green "Buy" / red "Sell"
- 10s auto-refresh

**Commit:**
```bash
git commit -m "feat: add Orderbook and Trades shortcodes with live updating"
```

---

### Task 8: Slippage Calculator Shortcode and JS (PARALLEL with Task 6)

**Goal:** [xbo_slippage] interactive calculator widget.

**Files:**
- Create: `includes/Shortcodes/SlippageShortcode.php`
- Create: `assets/js/interactivity/slippage.js`

**Implementation notes:**

SlippageShortcode renders:
- Form section: Pair dropdown, Buy/Sell toggle, Amount input
- Results section: Avg Price, Slippage %, Spread, Depth Used, Total Cost
- "Calculate" triggers on input change with 300ms debounce
- Fetches `/wp-json/xbo/v1/slippage?symbol=X&side=Y&amount=Z`
- No auto-refresh — user-driven

Pair dropdown populated from `/xbo/v1/ticker` on mount (or hardcoded popular pairs).

**Commit:**
```bash
git commit -m "feat: add Slippage Calculator shortcode with interactive form"
```

---

## Phase 4: Blocks + Admin (Tasks 9-10 run in PARALLEL)

### Task 9: Gutenberg Blocks

**Goal:** 5 dynamic Gutenberg blocks wrapping existing shortcodes.

**Files:**
- Create: `includes/Blocks/BlockRegistrar.php`
- Create: `includes/Blocks/ticker/block.json`
- Create: `includes/Blocks/ticker/render.php`
- Create: `includes/Blocks/movers/block.json`
- Create: `includes/Blocks/movers/render.php`
- Create: `includes/Blocks/orderbook/block.json`
- Create: `includes/Blocks/orderbook/render.php`
- Create: `includes/Blocks/trades/block.json`
- Create: `includes/Blocks/trades/render.php`
- Create: `includes/Blocks/slippage/block.json`
- Create: `includes/Blocks/slippage/render.php`
- Modify: `includes/Plugin.php`

**Implementation notes:**

BlockRegistrar:
- Registers custom block category `xbo-market-kit`
- Calls `register_block_type()` for each block directory

Each `block.json`:
- `apiVersion: 3`
- `name: "xbo-market-kit/ticker"` etc.
- `category: "xbo-market-kit"`
- `attributes` matching shortcode params
- `render: "file:./render.php"`
- `supports: { html: false }`

Each `render.php`:
- Extracts attributes from `$attributes`
- Calls corresponding shortcode render function
- Wraps in `$wrapper_attributes = get_block_wrapper_attributes()`

No `edit.js` for MVP — use `ServerSideRender` from core (no build step needed, use `wp_register_script` with inline JS or a simple script that imports from `@wordpress/server-side-render`).

**Commit:**
```bash
git commit -m "feat: add 5 Gutenberg dynamic blocks for all widgets"
```

---

### Task 10: Admin Settings + Demo Page (PARALLEL with Task 9)

**Goal:** Settings page and auto-created demo page.

**Files:**
- Create: `includes/Admin/AdminSettings.php`
- Create: `includes/Admin/DemoPage.php`
- Create: `assets/js/admin/settings.js`
- Modify: `includes/Plugin.php`
- Modify: `xbo-market-kit.php` (activation/deactivation hooks)

**Implementation notes:**

AdminSettings:
- Registers settings page under Settings menu
- Fields: default_symbols (text), cache_mode (select), enable_tailwind (checkbox)
- Uses `register_setting()` + `add_settings_section()` + `add_settings_field()`
- "Clear Cache" button → AJAX endpoint → `CacheManager::flush_all()`

DemoPage:
- `create()` — called on activation, uses `wp_insert_post()` to create draft page
- Page content includes all 5 shortcodes arranged in a responsive layout
- Stores page ID in `xbo_market_kit_demo_page_id` option
- `delete()` — called on deactivation, trashes the demo page

**Commit:**
```bash
git commit -m "feat: add admin settings page and auto-created demo page"
```

---

## Phase 5: Polish

### Task 11: Code Quality + Final Verification

**Goal:** PHPCS/PHPStan compliance, all tests green, visual check.

**Steps:**

1. Run PHPCS and fix all issues:
```bash
cd wp-content/plugins/xbo-market-kit && composer run phpcs
composer run phpcbf  # auto-fix
```

2. Run PHPStan and fix all issues:
```bash
composer run phpstan
```

3. Run all tests:
```bash
composer run test
```

4. Activate plugin in WP admin and verify demo page renders correctly

5. Test each REST endpoint manually:
```bash
curl http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/ticker?symbols=BTC/USDT,ETH/USDT
curl http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/movers?mode=gainers&limit=5
curl http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/orderbook?symbol=BTC_USDT&depth=10
curl http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/trades?symbol=BTC/USDT&limit=5
curl "http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/slippage?symbol=BTC_USDT&side=buy&amount=1"
```

6. Fix any remaining issues

7. Final commit:
```bash
git add wp-content/plugins/xbo-market-kit/
git commit -m "chore: fix code quality issues and verify all endpoints"
```

---

## Summary

| Task | Phase | Parallel? | Depends On | Est. Files |
|------|-------|-----------|------------|------------|
| 1. Plugin Bootstrap | 1 | No | — | 5 |
| 2. ApiClient | 1 | No | Task 1 | 3 |
| 3. CacheManager | 1 | No | Task 2 | 2 |
| 4. Ticker + Movers REST | 2 | Yes | Task 3 | 5 |
| 5. Orderbook + Trades + Slippage REST | 2 | Yes | Task 3 | 5 |
| 6. Ticker + Movers Shortcodes + JS | 3 | Yes | Phase 2 | 7 |
| 7. Orderbook + Trades Shortcodes + JS | 3 | Yes | Phase 2 | 4 |
| 8. Slippage Shortcode + JS | 3 | Yes | Phase 2 | 2 |
| 9. Gutenberg Blocks | 4 | Yes | Phase 3 | 12 |
| 10. Admin + Demo Page | 4 | Yes | Phase 3 | 4 |
| 11. Polish + Verification | 5 | No | Phase 4 | 0 |
