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
			'symbol'         => $item['symbol'] ?? '',
			'base'           => $parts[0] ?? '',
			'quote'          => $parts[1] ?? '',
			'last_price'     => (float) ( $item['lastPrice'] ?? 0 ),
			'change_pct_24h' => (float) ( $item['priceChangePercent24H'] ?? 0 ),
			'high_24h'       => (float) ( $item['highestPrice24H'] ?? 0 ),
			'low_24h'        => (float) ( $item['lowestPrice24H'] ?? 0 ),
			'volume_24h'     => (float) ( $item['quoteVolume'] ?? 0 ),
			'highest_bid'    => (float) ( $item['highestBid'] ?? 0 ),
			'lowest_ask'     => (float) ( $item['lowestAsk'] ?? 0 ),
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
				'symbol'         => array( 'type' => 'string' ),
				'base'           => array( 'type' => 'string' ),
				'quote'          => array( 'type' => 'string' ),
				'last_price'     => array( 'type' => 'number' ),
				'change_pct_24h' => array( 'type' => 'number' ),
				'high_24h'       => array( 'type' => 'number' ),
				'low_24h'        => array( 'type' => 'number' ),
				'volume_24h'     => array( 'type' => 'number' ),
			),
		);
	}
}
