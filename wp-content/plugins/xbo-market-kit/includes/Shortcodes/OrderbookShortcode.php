<?php
/**
 * OrderbookShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

/**
 * Shortcode handler for the [xbo_orderbook] shortcode.
 *
 * Renders a live order book with bid/ask levels and spread indicator.
 */
class OrderbookShortcode extends AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	protected function get_tag(): string {
		return 'xbo_orderbook';
	}

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	protected function get_defaults(): array {
		return array(
			'symbol'  => 'BTC_USDT',
			'depth'   => '20',
			'refresh' => '15',
		);
	}

	/**
	 * Enqueue orderbook-specific frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-orderbook', 'orderbook.js' );
	}

	/**
	 * Render the order book shortcode HTML output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	protected function render( array $atts ): string {
		$symbol  = sanitize_text_field( $atts['symbol'] );
		$depth   = max( 1, min( 250, (int) $atts['depth'] ) );
		$refresh = max( 1, (int) $atts['refresh'] );

		$context = array(
			'symbol'  => $symbol,
			'depth'   => $depth,
			'refresh' => $refresh,
			'bids'    => array(),
			'asks'    => array(),
			'spread'  => '0',
		);

		$html  = '<div class="xbo-mk-orderbook" data-xbo-refresh="' . esc_attr( (string) $refresh ) . '" data-wp-init="actions.initOrderbook">';
		$html .= '<div class="xbo-mk-orderbook__header">';
		$html .= '<h3 class="xbo-mk-orderbook__title">' . esc_html__( 'Order Book', 'xbo-market-kit' ) . ' — ' . esc_html( str_replace( '_', '/', $symbol ) ) . '</h3>';
		$html .= '</div>';
		$html .= '<div class="xbo-mk-orderbook__grid">';

		// Bids column.
		$html .= '<div class="xbo-mk-orderbook__side">';
		$html .= '<div class="xbo-mk-orderbook__col-header"><span>' . esc_html__( 'Price', 'xbo-market-kit' ) . '</span><span>' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</span></div>';
		$html .= '<div><template data-wp-each="state.orderbookBids">';
		$html .= '<div class="xbo-mk-orderbook__level">';
		$html .= '<div class="xbo-mk-orderbook__depth xbo-mk-orderbook__depth--bid" data-wp-style--width="context.item.depthPct"></div>';
		$html .= '<span class="xbo-mk-orderbook__price xbo-mk-orderbook__price--bid" data-wp-text="context.item.price"></span>';
		$html .= '<span class="xbo-mk-orderbook__amount" data-wp-text="context.item.amount"></span>';
		$html .= '</div>';
		$html .= '</template></div></div>';

		// Asks column.
		$html .= '<div class="xbo-mk-orderbook__side">';
		$html .= '<div class="xbo-mk-orderbook__col-header"><span>' . esc_html__( 'Price', 'xbo-market-kit' ) . '</span><span>' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</span></div>';
		$html .= '<div><template data-wp-each="state.orderbookAsks">';
		$html .= '<div class="xbo-mk-orderbook__level">';
		$html .= '<div class="xbo-mk-orderbook__depth xbo-mk-orderbook__depth--ask" data-wp-style--width="context.item.depthPct"></div>';
		$html .= '<span class="xbo-mk-orderbook__price xbo-mk-orderbook__price--ask" data-wp-text="context.item.price"></span>';
		$html .= '<span class="xbo-mk-orderbook__amount" data-wp-text="context.item.amount"></span>';
		$html .= '</div>';
		$html .= '</template></div></div>';

		$html .= '</div>';

		// Spread indicator.
		$html .= '<div class="xbo-mk-orderbook__spread">';
		$html .= esc_html__( 'Spread:', 'xbo-market-kit' ) . ' <span class="xbo-mk-orderbook__spread-value" data-wp-text="state.orderbookSpread">--</span>';
		$html .= '</div></div>';

		return $this->render_wrapper( $html, $context );
	}
}
