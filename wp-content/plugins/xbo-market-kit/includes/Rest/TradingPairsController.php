<?php
/**
 * TradingPairsController class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for the trading pairs list endpoint.
 *
 * Returns a sorted flat array of all available trading pair symbols
 * in slash format (e.g. BTC/USDT) for use by block editor controls.
 */
class TradingPairsController extends AbstractController {

	/**
	 * Constructor. Sets the REST base path.
	 */
	public function __construct() {
		$this->rest_base = 'trading-pairs';
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Return all trading pair symbols as a sorted flat array.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response REST response with symbol list.
	 */
	public function get_items( $request ): WP_REST_Response {
		$api_response = $this->get_api_client()->get_trading_pairs();
		if ( ! $api_response->success ) {
			return $this->error_response( $api_response->error_message );
		}

		$symbols = self::extract_symbols( $api_response->data );
		return $this->success_response( $symbols );
	}

	/**
	 * Extract and sort symbol strings from API trading-pairs data.
	 *
	 * Static to allow unit testing without mocking WP globals.
	 *
	 * @param array $data Raw trading pairs data from ApiClient.
	 * @return string[] Sorted array of SLASH-format symbols.
	 */
	public static function extract_symbols( array $data ): array {
		$symbols = array();
		foreach ( $data as $pair ) {
			$symbol = $pair['symbol'] ?? '';
			if ( '' !== $symbol ) {
				$symbols[] = $symbol;
			}
		}
		sort( $symbols );
		return $symbols;
	}
}
