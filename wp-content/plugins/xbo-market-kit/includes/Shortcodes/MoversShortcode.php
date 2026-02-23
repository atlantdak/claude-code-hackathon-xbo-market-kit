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

		$html  = '<div class="xbo-mk-movers bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" data-wp-init="actions.initMovers">';
		$html .= '<div class="flex border-b border-gray-200 dark:border-gray-700">';
		$html .= '<button class="flex-1 px-4 py-3 text-sm font-medium transition-colors" data-wp-class--bg-blue-500="state.moversIsGainers" data-wp-class--text-white="state.moversIsGainers" data-wp-class--text-gray-600="!state.moversIsGainers" data-wp-on--click="actions.setMoversGainers">' . esc_html__( 'Gainers', 'xbo-market-kit' ) . '</button>';
		$html .= '<button class="flex-1 px-4 py-3 text-sm font-medium transition-colors" data-wp-class--bg-red-500="state.moversIsLosers" data-wp-class--text-white="state.moversIsLosers" data-wp-class--text-gray-600="!state.moversIsLosers" data-wp-on--click="actions.setMoversLosers">' . esc_html__( 'Losers', 'xbo-market-kit' ) . '</button>';
		$html .= '</div>';
		$html .= '<div class="overflow-x-auto">';
		$html .= '<table class="w-full text-sm">';
		$html .= '<thead class="bg-gray-50 dark:bg-gray-900"><tr>';
		$html .= '<th class="px-4 py-2 text-left text-gray-500 font-medium">' . esc_html__( 'Pair', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="px-4 py-2 text-right text-gray-500 font-medium">' . esc_html__( 'Price', 'xbo-market-kit' ) . '</th>';
		$html .= '<th class="px-4 py-2 text-right text-gray-500 font-medium">' . esc_html__( '24h Change', 'xbo-market-kit' ) . '</th>';
		$html .= '</tr></thead>';
		$html .= '<tbody data-wp-each="state.moversItems">';
		$html .= '<template>';
		$html .= '<tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">';
		$html .= '<td class="px-4 py-3"><div class="flex items-center gap-2"><div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold" data-wp-text="context.item.firstLetter"></div><span class="font-medium" data-wp-text="context.item.symbol"></span></div></td>';
		$html .= '<td class="px-4 py-3 text-right font-mono" data-wp-text="context.item.price"></td>';
		$html .= '<td class="px-4 py-3 text-right font-mono" data-wp-class--text-green-500="context.item.isUp" data-wp-class--text-red-500="!context.item.isUp" data-wp-text="context.item.change"></td>';
		$html .= '</tr>';
		$html .= '</template>';
		$html .= '</tbody>';
		$html .= '</table></div></div>';

		return $this->render_wrapper( $html, $context );
	}
}
