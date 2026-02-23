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
