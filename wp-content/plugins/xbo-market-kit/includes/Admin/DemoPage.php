<?php
/**
 * DemoPage class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Admin;

/**
 * Manages the demo page created on plugin activation.
 *
 * Creates a draft WordPress page showcasing all plugin shortcodes
 * and removes it on deactivation.
 *
 * @deprecated Use PageManager instead.
 */
class DemoPage {

	/**
	 * Option key storing the demo page post ID.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'xbo_market_kit_demo_page_id';

	/**
	 * Create the demo page on plugin activation.
	 */
	public static function create(): void {
		$existing_id = get_option( self::OPTION_KEY );
		if ( $existing_id && get_post( $existing_id ) ) {
			return;
		}

		$content = self::get_demo_content();
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'XBO Market Kit Demo',
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'page',
				'post_author'  => 1,
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( self::OPTION_KEY, $page_id );
		}
	}

	/**
	 * Trash the demo page on plugin deactivation.
	 */
	public static function delete(): void {
		$page_id = get_option( self::OPTION_KEY );
		if ( $page_id ) {
			wp_trash_post( (int) $page_id );
			delete_option( self::OPTION_KEY );
		}
	}

	/**
	 * Build the demo page content with all plugin shortcode examples.
	 *
	 * @return string Block editor content string.
	 */
	private static function get_demo_content(): string {
		$content = '';

		// Hero section.
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">XBO Market Kit Demo</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Live cryptocurrency market data powered by the XBO Public API.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";

		// Ticker.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Live Ticker</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT" columns="4" refresh="15"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Top Movers.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Top Movers</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_movers mode="gainers" limit="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Order Book.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Order Book — BTC/USDT</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_orderbook symbol="BTC_USDT" depth="15" refresh="5"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Recent Trades.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Recent Trades — BTC/USDT</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_trades symbol="BTC/USDT" limit="15" refresh="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Slippage Calculator.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Slippage Calculator</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_slippage symbol="BTC_USDT" side="buy"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		return $content;
	}
}
