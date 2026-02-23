<?php
/**
 * TradesController class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for the recent trades endpoint.
 *
 * Returns the most recent trades for a specified trading pair.
 */
class TradesController extends AbstractController {

	/**
	 * Constructor. Sets the REST base path.
	 */
	public function __construct() {
		$this->rest_base = 'trades';
	}

	/**
	 * Register REST API routes for the trades endpoint.
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
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Get recent trades for the requested trading pair.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response REST response with trades data.
	 */
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

	/**
	 * Get the query parameters for the trades collection.
	 *
	 * @return array Collection parameters.
	 */
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
