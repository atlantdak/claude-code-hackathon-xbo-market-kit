<?php
/**
 * SlippageShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

/**
 * Shortcode handler for the [xbo_slippage] shortcode.
 *
 * Renders an interactive slippage calculator with real-time order book data.
 */
class SlippageShortcode extends AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	protected function get_tag(): string {
		return 'xbo_slippage';
	}

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	protected function get_defaults(): array {
		return array(
			'symbol' => 'BTC_USDT',
			'side'   => 'buy',
			'amount' => '',
		);
	}

	/**
	 * Enqueue slippage-specific frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-slippage', 'slippage.js' );
	}

	/**
	 * Render the slippage calculator shortcode HTML output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	protected function render( array $atts ): string {
		$symbol = sanitize_text_field( $atts['symbol'] );
		$side   = in_array( $atts['side'], array( 'buy', 'sell' ), true ) ? $atts['side'] : 'buy';
		$amount = $atts['amount'];

		$context = array(
			'symbol'  => $symbol,
			'side'    => $side,
			'amount'  => $amount,
			'result'  => null,
			'loading' => false,
		);

		$html  = '<div class="xbo-mk-slippage bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" data-wp-init="actions.initSlippage">';
		$html .= '<div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
		$html .= '<h3 class="font-semibold text-gray-900 dark:text-white text-sm">' . esc_html__( 'Slippage Calculator', 'xbo-market-kit' ) . '</h3>';
		$html .= '</div>';

		// Form.
		$html .= '<div class="p-4 space-y-3">';
		$html .= '<div class="grid grid-cols-3 gap-3">';

		// Symbol input.
		$html .= '<div>';
		$html .= '<label class="block text-xs text-gray-500 mb-1">' . esc_html__( 'Pair', 'xbo-market-kit' ) . '</label>';
		$html .= '<input type="text" value="' . esc_attr( $symbol ) . '" class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600" data-wp-on--input="actions.slippageSymbolChange" />';
		$html .= '</div>';

		// Side toggle.
		$html .= '<div>';
		$html .= '<label class="block text-xs text-gray-500 mb-1">' . esc_html__( 'Side', 'xbo-market-kit' ) . '</label>';
		$html .= '<div class="flex rounded-lg border dark:border-gray-600 overflow-hidden">';
		$html .= '<button class="flex-1 px-3 py-2 text-sm font-medium transition-colors" data-wp-class--bg-green-500="state.slippageIsBuy" data-wp-class--text-white="state.slippageIsBuy" data-wp-on--click="actions.slippageSetBuy">' . esc_html__( 'Buy', 'xbo-market-kit' ) . '</button>';
		$html .= '<button class="flex-1 px-3 py-2 text-sm font-medium transition-colors" data-wp-class--bg-red-500="state.slippageIsSell" data-wp-class--text-white="state.slippageIsSell" data-wp-on--click="actions.slippageSetSell">' . esc_html__( 'Sell', 'xbo-market-kit' ) . '</button>';
		$html .= '</div></div>';

		// Amount input.
		$html .= '<div>';
		$html .= '<label class="block text-xs text-gray-500 mb-1">' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</label>';
		$html .= '<input type="number" step="0.001" min="0.001" placeholder="1.0" class="w-full px-3 py-2 border rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600" data-wp-on--input="actions.slippageAmountChange" />';
		$html .= '</div>';

		$html .= '</div></div>';

		// Results.
		$html .= '<div class="px-4 pb-4" data-wp-class--hidden="!state.slippageHasResult">';
		$html .= '<div class="grid grid-cols-2 gap-3">';

		$metrics = array(
			'avg_price'    => __( 'Avg Price', 'xbo-market-kit' ),
			'slippage_pct' => __( 'Slippage', 'xbo-market-kit' ),
			'spread'       => __( 'Spread', 'xbo-market-kit' ),
			'spread_pct'   => __( 'Spread %', 'xbo-market-kit' ),
			'depth_used'   => __( 'Depth Used', 'xbo-market-kit' ),
			'total_cost'   => __( 'Total Cost', 'xbo-market-kit' ),
		);

		foreach ( $metrics as $key => $label ) {
			$html .= '<div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">';
			$html .= '<div class="text-xs text-gray-500">' . esc_html( $label ) . '</div>';
			$html .= '<div class="text-sm font-mono font-semibold text-gray-900 dark:text-white" data-wp-text="state.slippageResult_' . esc_attr( $key ) . '">--</div>';
			$html .= '</div>';
		}

		$html .= '</div></div>';

		// Loading indicator.
		$html .= '<div class="px-4 pb-4 text-center text-sm text-gray-500" data-wp-class--hidden="!state.slippageLoading">';
		$html .= esc_html__( 'Calculating...', 'xbo-market-kit' );
		$html .= '</div>';

		$html .= '</div>';

		return $this->render_wrapper( $html, $context );
	}
}
