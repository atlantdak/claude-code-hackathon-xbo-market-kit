<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Api;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Api\ApiClient;
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
		$this->cache->shouldReceive( 'set' )->withArgs( function ( $key, $value, $ttl ) use ( $data ) {
			return $key === 'xbo_mk_stats' && $value === $data && $ttl > 0;
		} )->once();

		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/refresh_interval', XBO_MARKET_KIT_REFRESH_INTERVAL )
			->andReturn( 15 );
		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/api_base_url', 'https://api.xbo.com' )
			->andReturn( 'https://api.xbo.com' );
		Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $data ) ) );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( json_encode( $data ) );

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

	public function test_get_stats_uses_refresh_interval(): void {
		$data = array( array( 'symbol' => 'BTC/USDT' ) );
		$this->cache->shouldReceive( 'get' )->with( 'xbo_mk_stats' )->andReturn( null );
		$this->cache->shouldReceive( 'set' )->with( 'xbo_mk_stats', $data, 15 )->once();

		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/refresh_interval', XBO_MARKET_KIT_REFRESH_INTERVAL )
			->andReturn( 15 );
		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/api_base_url', 'https://api.xbo.com' )
			->andReturn( 'https://api.xbo.com' );
		Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $data ) ) );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( json_encode( $data ) );

		$this->client->get_stats();
	}

	public function test_get_orderbook_uses_refresh_interval(): void {
		$data = array( 'bids' => array(), 'asks' => array() );
		$this->cache->shouldReceive( 'get' )->andReturn( null );
		$this->cache->shouldReceive( 'set' )->withArgs( function ( $key, $value, $ttl ) {
			return $ttl === 15;
		} )->once();

		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/refresh_interval', XBO_MARKET_KIT_REFRESH_INTERVAL )
			->andReturn( 15 );
		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/api_base_url', 'https://api.xbo.com' )
			->andReturn( 'https://api.xbo.com' );
		Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $data ) ) );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( json_encode( $data ) );

		$this->client->get_orderbook( 'BTC/USDT', 20 );
	}

	public function test_get_trades_uses_refresh_interval(): void {
		$data = array( array( 'price' => '65000' ) );
		$this->cache->shouldReceive( 'get' )->andReturn( null );
		$this->cache->shouldReceive( 'set' )->withArgs( function ( $key, $value, $ttl ) {
			return $ttl === 15;
		} )->once();

		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/refresh_interval', XBO_MARKET_KIT_REFRESH_INTERVAL )
			->andReturn( 15 );
		Functions\expect( 'apply_filters' )
			->with( 'xbo_market_kit/api_base_url', 'https://api.xbo.com' )
			->andReturn( 'https://api.xbo.com' );
		Functions\expect( 'sanitize_key' )->andReturnUsing( function ( $key ) {
			return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) );
		} );
		Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $data ) ) );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( json_encode( $data ) );

		$this->client->get_trades( 'BTC/USDT' );
	}

	public function test_get_currencies_uses_long_cache(): void {
		$data = array( array( 'symbol' => 'BTC' ) );
		$this->cache->shouldReceive( 'get' )->with( 'xbo_mk_currencies' )->andReturn( null );
		$this->cache->shouldReceive( 'set' )->with( 'xbo_mk_currencies', $data, 21600 )->once();

		Functions\expect( 'apply_filters' )->andReturn( 'https://api.xbo.com' );
		Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $data ) ) );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( json_encode( $data ) );

		$this->client->get_currencies();
	}

	public function test_get_trading_pairs_uses_long_cache(): void {
		$data = array( array( 'symbol' => 'BTC/USDT' ) );
		$this->cache->shouldReceive( 'get' )->andReturn( null );
		$this->cache->shouldReceive( 'set' )->with( 'xbo_mk_pairs', $data, 21600 )->once();

		Functions\expect( 'apply_filters' )->andReturn( 'https://api.xbo.com' );
		Functions\expect( 'wp_remote_get' )->andReturn( array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $data ) ) );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->andReturn( json_encode( $data ) );

		$this->client->get_trading_pairs();
	}
}
