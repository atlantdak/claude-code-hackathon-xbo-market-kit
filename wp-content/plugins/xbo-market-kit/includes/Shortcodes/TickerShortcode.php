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

		$widget_id = 'xbo-ticker-' . wp_unique_id();
		$generator = new \XboMarketKit\Sparkline\SparklineGenerator();

		// Fetch current stats for sparkline generation.
		$api_client   = new \XboMarketKit\Api\ApiClient(
			new \XboMarketKit\Cache\CacheManager()
		);
		$api_response = $api_client->get_stats();
		$stats_map    = array();
		if ( $api_response->success ) {
			foreach ( $api_response->data as $item ) {
				$stats_map[ $item['symbol'] ?? '' ] = $item;
			}
		}

		$sparkline_data = array();
		$context        = array(
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
			$key   = sanitize_key( $symbol );

			// Get stats for this symbol.
			$stat        = $stats_map[ $symbol ] ?? array();
			$last_price  = (float) ( $stat['lastPrice'] ?? 0 );
			$high_24h    = (float) ( $stat['highestPrice24H'] ?? 0 );
			$low_24h     = (float) ( $stat['lowestPrice24H'] ?? 0 );
			$change_pct  = (float) ( $stat['priceChangePercent24H'] ?? 0 );
			$trend       = $generator->get_trend_direction( $change_pct );
			$gradient_id = 'spark-grad-' . $key . '-' . $widget_id;

			// Generate sparkline data.
			$prices    = array();
			$svg_attrs = array(
				'polyline_points' => '',
				'polygon_points'  => '',
			);
			if ( $last_price > 0 ) {
				$prices    = $generator->generate_prices( $last_price, $high_24h, $low_24h, $change_pct, $symbol );
				$svg_attrs = $generator->render_svg_points( $prices );
			}

			$sparkline_data[ $key ] = array(
				'prices'    => $prices,
				'updatedAt' => time(),
				'trend'     => $trend,
			);

			$icon_url = $icon_resolver->url( $base );

			$cards .= '<div class="xbo-mk-ticker__card">';
			$cards .= '<div class="xbo-mk-ticker__header">';
			$cards .= '<div class="xbo-mk-ticker__icon">';
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

			// Sparkline SVG with gradient fill.
			$cards .= '<svg class="xbo-mk-ticker__sparkline xbo-mk-ticker__sparkline--' . esc_attr( $trend ) . '"'
				. ' viewBox="0 0 100 30" preserveAspectRatio="none" data-symbol="' . esc_attr( $key ) . '">';
			$cards .= '<defs>';
			$cards .= '<linearGradient id="' . esc_attr( $gradient_id ) . '" x1="0" y1="0" x2="0" y2="1">';
			$cards .= '<stop offset="0%" stop-opacity="0.3"/>';
			$cards .= '<stop offset="100%" stop-opacity="0"/>';
			$cards .= '</linearGradient>';
			$cards .= '</defs>';
			if ( ! empty( $svg_attrs['polygon_points'] ) ) {
				$cards .= '<polygon class="xbo-mk-ticker__sparkline-fill" fill="url(#' . esc_attr( $gradient_id ) . ')"'
					. ' points="' . esc_attr( $svg_attrs['polygon_points'] ) . '"/>';
			}
			if ( ! empty( $svg_attrs['polyline_points'] ) ) {
				$cards .= '<polyline class="xbo-mk-ticker__sparkline-line" fill="none" stroke-width="1.5"'
					. ' points="' . esc_attr( $svg_attrs['polyline_points'] ) . '"/>';
			}
			$cards .= '</svg>';
			$cards .= '</div>';
		}

		$context['sparklineData'] = $sparkline_data;
		$context['widgetId']      = $widget_id;

		$html = '<div class="xbo-mk-ticker xbo-mk-ticker--cols-' . esc_attr( (string) $columns ) . '"'
			. ' data-wp-init="actions.initTicker">'
			. $cards . '</div>';

		return $this->render_wrapper( $html, $context );
	}
}
