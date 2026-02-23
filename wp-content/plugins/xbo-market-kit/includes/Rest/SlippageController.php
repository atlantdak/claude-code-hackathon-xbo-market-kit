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

		$data   = $api_response->data;
		$book   = 'buy' === $side ? ( $data['asks'] ?? array() ) : ( $data['bids'] ?? array() );
		$result = self::calculate_slippage( $book, $amount );

		$bids     = $data['bids'] ?? array();
		$asks     = $data['asks'] ?? array();
		$best_bid = ! empty( $bids ) ? (float) $bids[0][0] : 0.0;
		$best_ask = ! empty( $asks ) ? (float) $asks[0][0] : 0.0;
		$spread   = $best_ask - $best_bid;

		return $this->success_response(
			array(
				'symbol'       => $symbol,
				'side'         => $side,
				'amount'       => $amount,
				'avg_price'    => $result['avg_price'],
				'best_price'   => $result['best_price'],
				'slippage_pct' => $result['slippage_pct'],
				'spread'       => round( $spread, 8 ),
				'spread_pct'   => $best_bid > 0 ? round( $spread / $best_bid * 100, 4 ) : 0,
				'depth_used'   => $result['depth_used'],
				'total_cost'   => $result['total_cost'],
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
				'avg_price'    => 0.0,
				'best_price'   => 0.0,
				'slippage_pct' => 0.0,
				'depth_used'   => 0.0,
				'total_cost'   => 0.0,
			);
		}

		$best_price = (float) $book[0][0];
		$remaining  = $amount;
		$total_cost = 0.0;
		$depth_used = 0.0;

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
