<?php
declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

class TickerShortcode extends AbstractShortcode {

	protected function get_tag(): string {
		return 'xbo_ticker';
	}

	protected function get_defaults(): array {
		return array(
			'symbols' => 'BTC/USDT,ETH/USDT',
			'refresh' => '15',
			'columns' => '4',
		);
	}

	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-ticker', 'ticker.js' );
	}

	protected function render( array $atts ): string {
		$symbols  = array_map( 'trim', explode( ',', $atts['symbols'] ) );
		$refresh  = max( 5, (int) $atts['refresh'] );
		$columns  = max( 1, min( 4, (int) $atts['columns'] ) );
		$grid_cls = 'grid-cols-' . $columns;

		$context = array(
			'symbols' => $atts['symbols'],
			'refresh' => $refresh,
			'items'   => array(),
		);

		$cards = '';
		foreach ( $symbols as $symbol ) {
			$parts = explode( '/', $symbol );
			$base  = $parts[0] ?? '';
			$first = substr( $base, 0, 1 );

			$cards .= '<div class="xbo-mk-ticker-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">';
			$cards .= '<div class="flex items-center gap-3 mb-3">';
			$cards .= '<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm">' . esc_html( $first ) . '</div>';
			$cards .= '<div>';
			$cards .= '<div class="font-semibold text-gray-900 dark:text-white text-sm">' . esc_html( $base ) . '</div>';
			$cards .= '<div class="text-xs text-gray-500">' . esc_html( $symbol ) . '</div>';
			$cards .= '</div></div>';
			$cards .= '<div class="xbo-mk-price text-xl font-bold text-gray-900 dark:text-white" data-wp-text="state.tickerPrice_' . esc_attr( sanitize_key( $symbol ) ) . '">--</div>';
			$cards .= '<div class="xbo-mk-change text-sm mt-1" data-wp-class--text-green-500="state.tickerUp_' . esc_attr( sanitize_key( $symbol ) ) . '" data-wp-class--text-red-500="state.tickerDown_' . esc_attr( sanitize_key( $symbol ) ) . '" data-wp-text="state.tickerChange_' . esc_attr( sanitize_key( $symbol ) ) . '">0.00%</div>';
			$cards .= '<svg class="mt-2 w-full h-8" viewBox="0 0 100 30" preserveAspectRatio="none">';
			$cards .= '<polyline fill="none" stroke="currentColor" stroke-width="1.5" points="0,25 15,20 30,22 45,15 60,18 75,10 100,5" class="text-blue-400"/>';
			$cards .= '</svg>';
			$cards .= '</div>';
		}

		$html = '<div class="xbo-mk-ticker grid ' . esc_attr( $grid_cls ) . ' gap-4 md:grid-cols-2 sm:grid-cols-1"'
			. ' data-wp-init="actions.initTicker">'
			. $cards . '</div>';

		return $this->render_wrapper( $html, $context );
	}
}
