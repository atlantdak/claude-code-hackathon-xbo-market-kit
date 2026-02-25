<?php
/**
 * MoversShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

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
			'mode'  => 'gainers',
			'limit' => '10',
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

		$context = array(
			'mode'  => $mode,
			'limit' => $limit,
			'items' => array(),
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
		$html .= '<template data-wp-each="state.moversItems">';
		$html .= '<tr class="xbo-mk-movers__row">';
		$html .= '<td class="xbo-mk-movers__cell xbo-mk-movers__cell--pair">'
			. '<div class="xbo-mk-movers__icon">'
			. '<img class="xbo-mk-movers__icon-img" data-wp-bind--src="context.item.iconUrl"'
			. ' data-wp-bind--alt="context.item.symbol"'
			. ' data-wp-bind--data-fallback="context.item.iconFallbackUrl"'
			. ' width="24" height="24" loading="lazy" decoding="async"'
			. ' onerror="if(!this.dataset.retry){this.dataset.retry=1;this.src=this.dataset.fallback}else{this.style.display=\'none\';this.nextElementSibling.style.display=\'\'}">'
			. '<span class="xbo-mk-movers__icon-text" style="display:none" data-wp-text="context.item.firstLetter"></span>'
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
}
