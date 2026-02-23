<?php
/**
 * MoversController class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for the top movers endpoint.
 *
 * Returns the biggest gainers or losers by 24-hour price change percentage.
 */
class MoversController extends AbstractController {

	/**
	 * Constructor. Sets the REST base path.
	 */
	public function __construct() {
		$this->rest_base = 'movers';
	}

	/**
	 * Register REST API routes for the movers endpoint.
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
	 * Get top mover items sorted by 24-hour price change.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response REST response with movers data.
	 */
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
				'base'           => explode( '/', $item['symbol'] ?? '' )[0],
				'quote'          => explode( '/', $item['symbol'] ?? '' )[1] ?? '',
				'last_price'     => (float) ( $item['lastPrice'] ?? 0 ),
				'change_pct_24h' => (float) ( $item['priceChangePercent24H'] ?? 0 ),
				'volume_24h'     => (float) ( $item['quoteVolume'] ?? 0 ),
			),
			$items
		);

		return $this->success_response( $normalized );
	}

	/**
	 * Get the query parameters for the movers collection.
	 *
	 * @return array Collection parameters.
	 */
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
