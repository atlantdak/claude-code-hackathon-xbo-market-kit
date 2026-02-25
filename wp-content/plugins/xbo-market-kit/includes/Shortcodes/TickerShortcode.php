<?php
/**
 * TickerShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

/**
 * Shortcode handler for the [xbo_ticker] shortcode.
 *
 * Renders live cryptocurrency price ticker cards with auto-refresh.
 */
class TickerShortcode extends AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	protected function get_tag(): string {
		return 'xbo_ticker';
	}

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	protected function get_defaults(): array {
		return array(
			'symbols' => 'BTC/USDT,ETH/USDT',
			'refresh' => '15',
			'columns' => '4',
		);
	}

	/**
	 * Enqueue ticker-specific frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-ticker', 'ticker.js' );
	}

	/**
	 * Render the ticker shortcode HTML output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	protected function render( array $atts ): string {
		$symbols = array_map( 'trim', explode( ',', $atts['symbols'] ) );
		$refresh = max( 5, (int) $atts['refresh'] );
		$columns = max( 1, min( 4, (int) $atts['columns'] ) );

		$context = array(
			'symbols' => $atts['symbols'],
			'refresh' => $refresh,
			'items'   => array(),
		);

		$icons_dir     = XBO_MARKET_KIT_DIR . 'assets/images/icons';
		$icons_url     = XBO_MARKET_KIT_URL . 'assets/images/icons';
		$icon_resolver = new \XboMarketKit\Icons\IconResolver( $icons_dir, $icons_url );

		$cards = '';
		foreach ( $symbols as $symbol ) {
			$parts = explode( '/', $symbol );
			$base  = $parts[0];
			$first = substr( $base, 0, 1 );
			$key   = sanitize_key( $symbol );

			$icon_url = $icon_resolver->url( $base );

			$cards .= '<div class="xbo-mk-ticker__card">';
			$cards .= '<div class="xbo-mk-ticker__header">';
			$cards .= '<div class="xbo-mk-ticker__icon">';
			$cards .= '<span class="xbo-mk-ticker__icon-text">' . esc_html( $first ) . '</span>';
			$cards .= '<img class="xbo-mk-ticker__icon-img" src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $base ) . '"'
				. ' width="40" height="40" loading="lazy" decoding="async">';
			$cards .= '</div>';
			$cards .= '<div class="xbo-mk-ticker__pair">';
			$cards .= '<div class="xbo-mk-ticker__symbol">' . esc_html( $base ) . '</div>';
			$cards .= '<div class="xbo-mk-ticker__name">' . esc_html( $symbol ) . '</div>';
			$cards .= '</div></div>';
			$cards .= '<div class="xbo-mk-ticker__price" data-wp-text="state.tickerPrice_' . esc_attr( $key ) . '">--</div>';
			$cards .= '<div class="xbo-mk-ticker__change"'
				. ' data-wp-class--xbo-mk-ticker__change--positive="state.tickerUp_' . esc_attr( $key ) . '"'
				. ' data-wp-class--xbo-mk-ticker__change--negative="state.tickerDown_' . esc_attr( $key ) . '"'
				. ' data-wp-text="state.tickerChange_' . esc_attr( $key ) . '">0.00%</div>';
			$cards .= '<svg class="xbo-mk-ticker__sparkline" viewBox="0 0 100 30" preserveAspectRatio="none">';
			$cards .= '<polyline fill="none" stroke="currentColor" stroke-width="1.5" points="0,25 15,20 30,22 45,15 60,18 75,10 100,5"/>';
			$cards .= '</svg>';
			$cards .= '</div>';
		}

		$html = '<div class="xbo-mk-ticker xbo-mk-ticker--cols-' . esc_attr( (string) $columns ) . '"'
			. ' data-wp-init="actions.initTicker">'
			. $cards . '</div>';

		return $this->render_wrapper( $html, $context );
	}
}
