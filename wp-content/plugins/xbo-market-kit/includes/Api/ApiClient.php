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
