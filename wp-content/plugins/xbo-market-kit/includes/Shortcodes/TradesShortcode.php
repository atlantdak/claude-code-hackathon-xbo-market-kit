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

		$html  = '<div class="xbo-mk-trades" data-wp-init="actions.initTrades">';
		$html .= '<div class="xbo-mk-trades__header">';
		$html .= '<h3 class="xbo-mk-trades__title">' . esc_html__( 'Recent Trades', 'xbo-market-kit' ) . ' — ' . esc_html( $symbol ) . '</h3>';
		$html .= '</div>';
		$html .= '<div class="xbo-mk-trades__scroll">';
		$html .= '<table class="xbo-mk-trades__table">';
		$html .= '<thead class="xbo-mk-trades__thead"><tr>';
		$html .= '<th class="xbo-mk-trades__th xbo-mk-trades__th--time">' . esc_html__( 'Time', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="xbo-mk-trades__th xbo-mk-trades__th--side">' . esc_html__( 'Side', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="xbo-mk-trades__th xbo-mk-trades__th--price">' . esc_html__( 'Price', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="xbo-mk-trades__th xbo-mk-trades__th--amount">' . esc_html__( 'Amount', 'xbo-market-kit' ) . '</th>';
		$html .= '</tr></thead>';
		$html .= '<tbody>';
		$html .= '<template data-wp-each="state.tradesItems">';
		$html .= '<tr class="xbo-mk-trades__row">';
		$html .= '<td class="xbo-mk-trades__cell xbo-mk-trades__cell--time" data-wp-text="context.item.time"></td>';
		$html .= '<td class="xbo-mk-trades__cell xbo-mk-trades__cell--side">'
			. '<span class="xbo-mk-trades__badge"'
			. ' data-wp-class--xbo-mk-trades__badge--buy="context.item.isBuy"'
			. ' data-wp-class--xbo-mk-trades__badge--sell="!context.item.isBuy"'
			. ' data-wp-text="context.item.sideLabel"></span></td>';
		$html .= '<td class="xbo-mk-trades__cell xbo-mk-trades__cell--price" data-wp-text="context.item.price"></td>';
		$html .= '<td class="xbo-mk-trades__cell xbo-mk-trades__cell--amount" data-wp-text="context.item.amount"></td>';
		$html .= '</tr>';
		$html .= '</template>';
		$html .= '</tbody>';
		$html .= '</table></div></div>';

		return $this->render_wrapper( $html, $context );
	}
}
