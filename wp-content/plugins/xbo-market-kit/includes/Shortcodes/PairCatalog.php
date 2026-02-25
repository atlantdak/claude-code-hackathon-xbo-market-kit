<?php
/**
 * PairCatalog class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Icons\IconResolver;

/**
 * Builds a catalog of trading pairs and icon URLs for dropdown selectors.
 */
class PairCatalog {

	/**
	 * API client instance.
	 *
	 * @var ApiClient
	 */
	private ApiClient $api;

	/**
	 * Icon resolver instance.
	 *
	 * @var IconResolver
	 */
	private IconResolver $icons;

	/**
	 * Constructor.
	 *
	 * @param ApiClient    $api   API client instance.
	 * @param IconResolver $icons Icon resolver instance.
	 */
	public function __construct( ApiClient $api, IconResolver $icons ) {
		$this->api   = $api;
		$this->icons = $icons;
	}

	/**
	 * Build the pairs catalog data.
	 *
	 * @return array{pairs_map: array<string, list<string>>, icons: array<string, string>}
	 */
	public function build(): array {
		$response = $this->api->get_trading_pairs();

		if ( ! $response->success ) {
			return array(
				'pairs_map' => array(),
				'icons'     => array(),
			);
		}

		$pairs_map = array();
		$symbols   = array();

		foreach ( $response->data as $pair ) {
			$parts = explode( '/', $pair['symbol'] ?? '', 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$base  = $parts[0];
			$quote = $parts[1];

			$pairs_map[ $base ][] = $quote;
			$symbols[ $base ]     = true;
			$symbols[ $quote ]    = true;
		}

		// Sort quotes alphabetically and deduplicate.
		foreach ( $pairs_map as $base => $quotes ) {
			$pairs_map[ $base ] = array_values( array_unique( $quotes ) );
			sort( $pairs_map[ $base ] );
		}

		// Sort bases alphabetically.
		ksort( $pairs_map );

		// Resolve icon URLs for all unique symbols.
		$icons = array();
		foreach ( array_keys( $symbols ) as $symbol ) {
			$icons[ $symbol ] = $this->icons->url( $symbol );
		}

		return array(
			'pairs_map' => $pairs_map,
			'icons'     => $icons,
		);
	}
}
