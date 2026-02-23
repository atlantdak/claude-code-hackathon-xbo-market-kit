<?php
declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

class OrderbookShortcode extends AbstractShortcode {

	protected function get_tag(): string {
		return 'xbo_orderbook';
	}

	protected function get_defaults(): array {
		return array(
			'symbol'  => 'BTC_USDT',
			'depth'   => '20',
			'refresh' => '5',
		);
	}

	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-orderbook', 'orderbook.js' );
	}

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

		$html  = '<div class="xbo-mk-orderbook bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" data-wp-init="actions.initOrderbook">';
		$html .= '<div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
		$html .= '<h3 class="font-semibold text-gray-900 dark:text-white text-sm">' . esc_html__( 'Order Book', 'xbo-market-kit' ) . ' — ' . esc_html( str_replace( '_', '/', $symbol ) ) . '</h3>';
		$html .= '</div>';
		$html .= '<div class="grid grid-cols-2 gap-0">';

		// Bids column.
		$html .= '<div class="p-2">';
		$html .= '<div class="text-xs font-medium text-gray-500 mb-1 flex justify-between px-2"><span>' . esc_html__( 'Price', 'xbo-market-kit' ) . '</span><span>' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</span></div>';
		$html .= '<div data-wp-each="state.orderbookBids"><template>';
		$html .= '<div class="relative flex justify-between px-2 py-0.5 text-xs font-mono">';
		$html .= '<div class="absolute inset-0 bg-green-500/10 origin-right" data-wp-style--width="context.item.depthPct"></div>';
		$html .= '<span class="relative text-green-600" data-wp-text="context.item.price"></span>';
		$html .= '<span class="relative text-gray-600" data-wp-text="context.item.amount"></span>';
		$html .= '</div>';
		$html .= '</template></div></div>';

		// Asks column.
		$html .= '<div class="p-2">';
		$html .= '<div class="text-xs font-medium text-gray-500 mb-1 flex justify-between px-2"><span>' . esc_html__( 'Price', 'xbo-market-kit' ) . '</span><span>' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</span></div>';
		$html .= '<div data-wp-each="state.orderbookAsks"><template>';
		$html .= '<div class="relative flex justify-between px-2 py-0.5 text-xs font-mono">';
		$html .= '<div class="absolute inset-0 bg-red-500/10 origin-left" data-wp-style--width="context.item.depthPct"></div>';
		$html .= '<span class="relative text-red-600" data-wp-text="context.item.price"></span>';
		$html .= '<span class="relative text-gray-600" data-wp-text="context.item.amount"></span>';
		$html .= '</div>';
		$html .= '</template></div></div>';

		$html .= '</div>';

		// Spread indicator.
		$html .= '<div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 text-center text-xs text-gray-500">';
		$html .= esc_html__( 'Spread:', 'xbo-market-kit' ) . ' <span class="font-mono" data-wp-text="state.orderbookSpread">--</span>';
		$html .= '</div></div>';

		return $this->render_wrapper( $html, $context );
	}
}
