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
			'amount' => '1',
		);
	}

	/**
	 * Parse a trading pair symbol into base and quote currencies.
	 *
	 * @param string $symbol Symbol in underscore (BTC_USDT) or slash (BTC/USDT) format.
	 * @return array{base: string, quote: string} Parsed currencies.
	 */
	protected function parse_symbol( string $symbol ): array {
		$separator = str_contains( $symbol, '/' ) ? '/' : '_';
		$parts     = explode( $separator, $symbol, 2 );

		if ( count( $parts ) === 2 && '' !== $parts[0] && '' !== $parts[1] ) {
			return array(
				'base'  => strtoupper( trim( $parts[0] ) ),
				'quote' => strtoupper( trim( $parts[1] ) ),
			);
		}

		return array(
			'base'  => 'BTC',
			'quote' => 'USDT',
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

		$html  = '<div class="xbo-mk-slippage" data-wp-init="actions.initSlippage">';
		$html .= '<div class="xbo-mk-slippage__header">';
		$html .= '<h3 class="xbo-mk-slippage__title">' . esc_html__( 'Slippage Calculator', 'xbo-market-kit' ) . '</h3>';
		$html .= '</div>';

		// Form.
		$html .= '<div class="xbo-mk-slippage__form">';
		$html .= '<div class="xbo-mk-slippage__fields">';

		// Symbol input.
		$html .= '<div class="xbo-mk-slippage__field">';
		$html .= '<label class="xbo-mk-slippage__label">' . esc_html__( 'Pair', 'xbo-market-kit' ) . '</label>';
		$html .= '<input type="text" value="' . esc_attr( $symbol ) . '" class="xbo-mk-slippage__input" data-wp-on--input="actions.slippageSymbolChange" />';
		$html .= '</div>';

		// Side toggle.
		$html .= '<div class="xbo-mk-slippage__field">';
		$html .= '<label class="xbo-mk-slippage__label">' . esc_html__( 'Side', 'xbo-market-kit' ) . '</label>';
		$html .= '<div class="xbo-mk-slippage__toggle">';
		$html .= '<button class="xbo-mk-slippage__toggle-btn"'
			. ' data-wp-class--xbo-mk-slippage__toggle-btn--buy-active="state.slippageIsBuy"'
			. ' data-wp-on--click="actions.slippageSetBuy">'
			. esc_html__( 'Buy', 'xbo-market-kit' ) . '</button>';
		$html .= '<button class="xbo-mk-slippage__toggle-btn"'
			. ' data-wp-class--xbo-mk-slippage__toggle-btn--sell-active="state.slippageIsSell"'
			. ' data-wp-on--click="actions.slippageSetSell">'
			. esc_html__( 'Sell', 'xbo-market-kit' ) . '</button>';
		$html .= '</div></div>';

		// Amount input.
		$html .= '<div class="xbo-mk-slippage__field">';
		$html .= '<label class="xbo-mk-slippage__label">' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</label>';
		$html .= '<input type="number" step="0.001" min="0.001" placeholder="1.0" class="xbo-mk-slippage__input" data-wp-on--input="actions.slippageAmountChange" />';
		$html .= '</div>';

		$html .= '</div></div>';

		// Results.
		$html .= '<div class="xbo-mk-slippage__results" data-wp-class--xbo-mk-hidden="!state.slippageHasResult">';
		$html .= '<div class="xbo-mk-slippage__metrics">';

		$metrics = array(
			'avg_price'    => __( 'Avg Price', 'xbo-market-kit' ),
			'slippage_pct' => __( 'Slippage', 'xbo-market-kit' ),
			'spread'       => __( 'Spread', 'xbo-market-kit' ),
			'spread_pct'   => __( 'Spread %', 'xbo-market-kit' ),
			'depth_used'   => __( 'Depth Used', 'xbo-market-kit' ),
			'total_cost'   => __( 'Total Cost', 'xbo-market-kit' ),
		);

		foreach ( $metrics as $key => $label ) {
			$html .= '<div class="xbo-mk-slippage__metric">';
			$html .= '<div class="xbo-mk-slippage__metric-label">' . esc_html( $label ) . '</div>';
			$html .= '<div class="xbo-mk-slippage__metric-value" data-wp-text="state.slippageResult_' . esc_attr( $key ) . '">--</div>';
			$html .= '</div>';
		}

		$html .= '</div></div>';

		// Loading indicator.
		$html .= '<div class="xbo-mk-slippage__loading" data-wp-class--xbo-mk-hidden="!state.slippageLoading">';
		$html .= esc_html__( 'Calculating...', 'xbo-market-kit' );
		$html .= '</div>';

		$html .= '</div>';

		return $this->render_wrapper( $html, $context );
	}
}
