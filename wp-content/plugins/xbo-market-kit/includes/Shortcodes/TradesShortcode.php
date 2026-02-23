<?php
/**
 * TradesShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

/**
 * Shortcode handler for the [xbo_trades] shortcode.
 *
 * Renders a live table of recent trades for a specified trading pair.
 */
class TradesShortcode extends AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	protected function get_tag(): string {
		return 'xbo_trades';
	}

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	protected function get_defaults(): array {
		return array(
			'symbol'  => 'BTC/USDT',
			'limit'   => '20',
			'refresh' => '10',
		);
	}

	/**
	 * Enqueue trades-specific frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-trades', 'trades.js' );
	}

	/**
	 * Render the trades shortcode HTML output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	protected function render( array $atts ): string {
		$symbol  = sanitize_text_field( $atts['symbol'] );
		$limit   = max( 1, min( 100, (int) $atts['limit'] ) );
		$refresh = max( 1, (int) $atts['refresh'] );

		$context = array(
			'symbol'  => $symbol,
			'limit'   => $limit,
			'refresh' => $refresh,
			'trades'  => array(),
		);

		$html  = '<div class="xbo-mk-trades bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" data-wp-init="actions.initTrades">';
		$html .= '<div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
		$html .= '<h3 class="font-semibold text-gray-900 dark:text-white text-sm">' . esc_html__( 'Recent Trades', 'xbo-market-kit' ) . ' — ' . esc_html( $symbol ) . '</h3>';
		$html .= '</div>';
		$html .= '<div class="overflow-x-auto">';
		$html .= '<table class="w-full text-xs">';
		$html .= '<thead class="bg-gray-50 dark:bg-gray-900"><tr>';
		$html .= '<th class="px-3 py-2 text-left text-gray-500">' . esc_html__( 'Time', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="px-3 py-2 text-center text-gray-500">' . esc_html__( 'Side', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="px-3 py-2 text-right text-gray-500">' . esc_html__( 'Price', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="px-3 py-2 text-right text-gray-500">' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</th>';
		$html .= '</tr></thead>';
		$html .= '<tbody>';
		$html .= '<template data-wp-each="state.tradesItems">';
		$html .= '<tr class="border-b border-gray-100 dark:border-gray-700">';
		$html .= '<td class="px-3 py-2 text-gray-500 font-mono" data-wp-text="context.item.time"></td>';
		$html .= '<td class="px-3 py-2 text-center"><span class="px-2 py-0.5 rounded text-xs font-medium" data-wp-class--bg-green-100="context.item.isBuy" data-wp-class--text-green-700="context.item.isBuy" data-wp-class--bg-red-100="!context.item.isBuy" data-wp-class--text-red-700="!context.item.isBuy" data-wp-text="context.item.sideLabel"></span></td>';
		$html .= '<td class="px-3 py-2 text-right font-mono" data-wp-text="context.item.price"></td>';
		$html .= '<td class="px-3 py-2 text-right font-mono" data-wp-text="context.item.amount"></td>';
		$html .= '</tr>';
		$html .= '</template>';
		$html .= '</tbody>';
		$html .= '</table></div></div>';

		return $this->render_wrapper( $html, $context );
	}
}
