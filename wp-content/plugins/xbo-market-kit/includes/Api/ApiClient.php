<?php
/**
 * ApiClient class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Api;

use XboMarketKit\Cache\CacheManager;

/**
 * HTTP client for the XBO public API.
 *
 * Handles all server-side requests to api.xbo.com with
 * caching support via CacheManager.
 */
class ApiClient {

	/**
	 * XBO API base URL.
	 *
	 * @var string
	 */
	private const BASE_URL = 'https://api.xbo.com';

	/**
	 * HTTP request timeout in seconds.
	 *
	 * @var int
	 */
	private const TIMEOUT = 10;

	/**
	 * Cache manager instance.
	 *
	 * @var CacheManager
	 */
	private CacheManager $cache;

	/**
	 * Constructor.
	 *
	 * @param CacheManager $cache Cache manager instance.
	 */
	public function __construct( CacheManager $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Get trading pair statistics.
	 *
	 * @return ApiResponse API response with trading pair stats data.
	 */
	public function get_stats(): ApiResponse {
		return $this->cached_request(
			'xbo_mk_stats',
			'/trading-pairs/stats',
			xbo_market_kit_get_refresh_interval()
		);
	}

	/**
	 * Get the order book for a trading pair.
	 *
	 * @param string $symbol Trading pair symbol (e.g. BTC/USDT or BTC_USDT).
	 * @param int    $depth  Order book depth (1-250).
	 * @return ApiResponse API response with order book data.
	 */
	public function get_orderbook( string $symbol, int $depth = 20 ): ApiResponse {
		$symbol = $this->to_underscore_format( $symbol );
		$depth  = min( max( $depth, 1 ), 250 );
		$key    = sprintf( 'xbo_mk_ob_%s_%d', $symbol, $depth );
		$url    = sprintf( '/orderbook/%s?depth=%d', $symbol, $depth );
		return $this->cached_request( $key, $url, xbo_market_kit_get_refresh_interval() );
	}

	/**
	 * Get recent trades for a trading pair.
	 *
	 * @param string $symbol Trading pair symbol (e.g. BTC/USDT).
	 * @return ApiResponse API response with trades data.
	 */
	public function get_trades( string $symbol ): ApiResponse {
		$symbol = $this->to_slash_format( $symbol );
		$key    = sprintf( 'xbo_mk_trades_%s', sanitize_key( $symbol ) );
		$url    = sprintf( '/trades?symbol=%s', rawurlencode( $symbol ) );
		return $this->cached_request( $key, $url, xbo_market_kit_get_refresh_interval() );
	}

	/**
	 * Get all available currencies.
	 *
	 * @return ApiResponse API response with currencies data.
	 */
	public function get_currencies(): ApiResponse {
		return $this->cached_request( 'xbo_mk_currencies', '/currencies', 21600 );
	}

	/**
	 * Get all available trading pairs.
	 *
	 * @return ApiResponse API response with trading pairs data.
	 */
	public function get_trading_pairs(): ApiResponse {
		return $this->cached_request( 'xbo_mk_pairs', '/trading-pairs', 21600 );
	}

	/**
	 * Perform a cached API request.
	 *
	 * Returns cached data if available, otherwise fetches from the API
	 * and stores the result in the cache.
	 *
	 * @param string $cache_key Transient cache key.
	 * @param string $path      API endpoint path.
	 * @param int    $ttl       Cache time-to-live in seconds.
	 * @return ApiResponse API response.
	 */
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

	/**
	 * Perform an HTTP GET request to the XBO API.
	 *
	 * @param string $path API endpoint path.
	 * @return ApiResponse API response.
	 */
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

	/**
	 * Convert a trading pair symbol from slash to underscore format.
	 *
	 * @param string $symbol Symbol in slash format (e.g. BTC/USDT).
	 * @return string Symbol in underscore format (e.g. BTC_USDT).
	 */
	private function to_underscore_format( string $symbol ): string {
		return str_replace( '/', '_', $symbol );
	}

	/**
	 * Convert a trading pair symbol from underscore to slash format.
	 *
	 * @param string $symbol Symbol in underscore format (e.g. BTC_USDT).
	 * @return string Symbol in slash format (e.g. BTC/USDT).
	 */
	private function to_slash_format( string $symbol ): string {
		return str_replace( '_', '/', $symbol );
	}
}
