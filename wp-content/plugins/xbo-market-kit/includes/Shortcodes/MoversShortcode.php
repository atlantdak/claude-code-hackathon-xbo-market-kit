<?php
/**
 * MoversShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Cache\CacheManager;
use XboMarketKit\Icons\IconResolver;

/**
 * Shortcode handler for the [xbo_movers] shortcode.
 *
 * Renders a table of top gaining or losing trading pairs.
 */
class MoversShortcode extends AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	protected function get_tag(): string {
		return 'xbo_movers';
	}

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	protected function get_defaults(): array {
		return array(
			'mode'    => 'gainers',
			'limit'   => '10',
			'refresh' => '15',
		);
	}

	/**
	 * Enqueue movers-specific frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-movers', 'movers.js' );
	}

	/**
	 * Render the movers shortcode HTML output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	protected function render( array $atts ): string {
		$mode  = in_array( $atts['mode'], array( 'gainers', 'losers' ), true ) ? $atts['mode'] : 'gainers';
		$limit = max( 1, min( 50, (int) $atts['limit'] ) );

		$movers_items = $this->fetch_movers( $mode, $limit );

		$context = array(
			'mode'     => $mode,
			'limit'    => $limit,
			'items'    => $movers_items,
			'iconsUrl' => XBO_MARKET_KIT_URL . 'assets/images/icons',
		);

		$html  = '<div class="xbo-mk-movers" data-wp-init="actions.initMovers">';
		$html .= '<div class="xbo-mk-movers__tabs">';
		$html .= '<button class="xbo-mk-movers__tab"'
			. ' data-wp-class--xbo-mk-movers__tab--gainers-active="state.moversIsGainers"'
			. ' data-wp-on--click="actions.setMoversGainers">'
			. esc_html__( 'Gainers', 'xbo-market-kit' ) . '</button>';
		$html .= '<button class="xbo-mk-movers__tab"'
			. ' data-wp-class--xbo-mk-movers__tab--losers-active="state.moversIsLosers"'
			. ' data-wp-on--click="actions.setMoversLosers">'
			. esc_html__( 'Losers', 'xbo-market-kit' ) . '</button>';
		$html .= '</div>';
		$html .= '<div class="xbo-mk-movers__scroll">';
		$html .= '<table class="xbo-mk-movers__table">';
		$html .= '<thead class="xbo-mk-movers__thead"><tr>';
		$html .= '<th class="xbo-mk-movers__th">' . esc_html__( 'Pair', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="xbo-mk-movers__th xbo-mk-movers__th--price">' . esc_html__( 'Price', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="xbo-mk-movers__th xbo-mk-movers__th--change">' . esc_html__( '24h Change', 'xbo-market-kit' ) . '</th>';
		$html .= '</tr></thead>';
		$html .= '<tbody>';

		// Static preloaded rows — visible until Interactivity API hydrates.
		foreach ( $movers_items as $item ) {
			$change_class = $item['isUp'] ? 'xbo-mk-movers__change--positive' : 'xbo-mk-movers__change--negative';
			$html        .= '<tr class="xbo-mk-movers__row" data-wp-bind--hidden="state.moversHydrated">';
			$html        .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--pair">'
				. '<div class="xbo-mk-movers__icon">'
				. '<img class="xbo-mk-movers__icon-img" src="' . esc_url( $item['iconUrl'] ) . '"'
				. ' width="24" height="24" loading="lazy" decoding="async">'
				. '</div>'
				. '<span class="xbo-mk-movers__symbol">' . esc_html( $item['symbol'] ) . '</span></td>';
			$html        .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--price">' . esc_html( $item['price'] ) . '</td>';
			$html        .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--change ' . esc_attr( $change_class ) . '">'
				. esc_html( $item['change'] ) . '</td>';
			$html        .= '</tr>';
		}

		$html .= '<template data-wp-each="state.moversItems">';
		$html .= '<tr class="xbo-mk-movers__row">';
		$html .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--pair">'
			. '<div class="xbo-mk-movers__icon">'
			. '<img class="xbo-mk-movers__icon-img" data-wp-bind--src="context.item.iconUrl"'
			. ' width="24" height="24">'
			. '</div>'
			. '<span class="xbo-mk-movers__symbol" data-wp-text="context.item.symbol"></span></td>';
		$html .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--price" data-wp-text="context.item.price"></td>';
		$html .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--change"'
			. ' data-wp-class--xbo-mk-movers__change--positive="context.item.isUp"'
			. ' data-wp-class--xbo-mk-movers__change--negative="!context.item.isUp"'
			. ' data-wp-text="context.item.change"></td>';
		$html .= '</tr>';
		$html .= '</template>';
		$html .= '</tbody>';
		$html .= '</table></div></div>';

		return $this->render_wrapper( $html, $context );
	}

	/**
	 * Fetch and format top movers data from the XBO API.
	 *
	 * @param string $mode  Sort mode: 'gainers' or 'losers'.
	 * @param int    $limit Number of items to return.
	 * @return array Formatted mover items for display.
	 */
	private function fetch_movers( string $mode, int $limit ): array {
		$api_client   = new ApiClient( new CacheManager() );
		$api_response = $api_client->get_stats();
		if ( ! $api_response->success ) {
			return array();
		}

		$items = array_filter(
			$api_response->data,
			fn( $item ) => isset( $item['priceChangePercent24H'] ) && '' !== $item['priceChangePercent24H']
		);

		usort(
			$items,
			fn( $a, $b ) => 'gainers' === $mode
				? (float) $b['priceChangePercent24H'] <=> (float) $a['priceChangePercent24H']
				: (float) $a['priceChangePercent24H'] <=> (float) $b['priceChangePercent24H']
		);

		$items = array_slice( $items, 0, $limit );

		$icons_dir     = XBO_MARKET_KIT_DIR . 'assets/images/icons';
		$icons_url     = XBO_MARKET_KIT_URL . 'assets/images/icons';
		$icon_resolver = new IconResolver( $icons_dir, $icons_url );

		return array_map(
			function ( $item ) use ( $icon_resolver ) {
				$symbol     = $item['symbol'] ?? '';
				$base       = explode( '/', $symbol )[0];
				$last_price = (float) ( $item['lastPrice'] ?? 0 );
				$change_pct = (float) ( $item['priceChangePercent24H'] ?? 0 );

				return array(
					'symbol'  => $symbol,
					'iconUrl' => $icon_resolver->url( $base ),
					'price'   => '$' . $this->format_price( $last_price ),
					'change'  => ( $change_pct >= 0 ? '+' : '' ) . number_format( $change_pct, 2 ) . '%',
					'isUp'    => $change_pct >= 0,
				);
			},
			$items
		);
	}

	/**
	 * Format a price value with appropriate decimal places.
	 *
	 * @param float $price Price value.
	 * @return string Formatted price string.
	 */
	private function format_price( float $price ): string {
		if ( $price >= 1.0 ) {
			return number_format( $price, 2 );
		}
		return rtrim( rtrim( number_format( $price, 8 ), '0' ), '.' );
	}
}
