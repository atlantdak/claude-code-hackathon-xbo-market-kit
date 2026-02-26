<?php
/**
 * PageManager class file.
 *
 * Creates demo pages, navigation menu, and sets the static front page
 * on plugin activation; cleans up on deactivation.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Admin;

/**
 * Manages the full set of demo/showcase pages created on plugin activation.
 *
 * Replaces the old DemoPage class with a multi-page system:
 * 9 pages total + navigation menu + static front page setting.
 */
class PageManager {

	/**
	 * Option key storing all created page IDs.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'xbo_market_kit_pages';

	/**
	 * Option key from the old DemoPage class (for migration).
	 *
	 * @var string
	 */
	private const OLD_OPTION_KEY = 'xbo_market_kit_demo_page_id';

	/**
	 * Navigation menu name.
	 *
	 * @var string
	 */
	private const MENU_NAME = 'XBO Market Kit';

	/**
	 * Create all pages, navigation menu, and set static front page.
	 */
	public static function create(): void {
		// Clean up old DemoPage if it exists.
		self::cleanup_old_demo_page();

		$existing = get_option( self::OPTION_KEY );
		if ( is_array( $existing ) && ! empty( $existing ) ) {
			// Check if at least the first page still exists.
			$first_value = reset( $existing );
			if ( $first_value && get_post( (int) $first_value ) ) {
				return;
			}
		}

		$pages = self::create_pages();

		if ( empty( $pages ) ) {
			return;
		}

		update_option( self::OPTION_KEY, $pages );

		self::create_menu( $pages );
		self::set_static_front_page( $pages );
	}

	/**
	 * Delete all pages, remove menu, and restore blog front page.
	 */
	public static function delete(): void {
		$pages = get_option( self::OPTION_KEY );

		if ( is_array( $pages ) ) {
			foreach ( $pages as $page_id ) {
				wp_trash_post( (int) $page_id );
			}
		}

		delete_option( self::OPTION_KEY );

		self::delete_menu();
		self::restore_blog_front_page();
	}

	/**
	 * Remove the old DemoPage if it exists.
	 */
	private static function cleanup_old_demo_page(): void {
		$old_page_id = get_option( self::OLD_OPTION_KEY );
		if ( $old_page_id ) {
			wp_trash_post( (int) $old_page_id );
			delete_option( self::OLD_OPTION_KEY );
		}
	}

	/**
	 * Create all 9 pages and return an associative array of slug => post ID.
	 *
	 * @return array<string, int> Map of page slug to post ID.
	 */
	private static function create_pages(): array {
		$pages = array();

		$page_definitions = self::get_page_definitions();

		// First pass: create pages without parents (to get IDs for child pages).
		foreach ( $page_definitions as $slug => $definition ) {
			if ( ! empty( $definition['parent'] ) ) {
				continue;
			}

			$page_id = self::insert_page( $slug, $definition['title'], $definition['content'] );
			if ( $page_id ) {
				$pages[ $slug ] = $page_id;
			}
		}

		// Second pass: create child pages.
		foreach ( $page_definitions as $slug => $definition ) {
			if ( empty( $definition['parent'] ) ) {
				continue;
			}

			$parent_id = $pages[ $definition['parent'] ] ?? 0;
			$page_id   = self::insert_page( $slug, $definition['title'], $definition['content'], $parent_id );
			if ( $page_id ) {
				$pages[ $slug ] = $page_id;
			}
		}

		return $pages;
	}

	/**
	 * Insert a single page.
	 *
	 * @param string $slug    Page slug.
	 * @param string $title   Page title.
	 * @param string $content Page content (Gutenberg block markup).
	 * @param int    $parent_id Parent page ID (0 for top-level).
	 * @return int Post ID on success, 0 on failure.
	 */
	private static function insert_page( string $slug, string $title, string $content, int $parent_id = 0 ): int {
		$page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
				'post_parent'  => $parent_id,
			)
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		return $page_id;
	}

	/**
	 * Get all page definitions.
	 *
	 * @return array<string, array{title: string, content: string, parent?: string}> Page definitions.
	 */
	private static function get_page_definitions(): array {
		return array(
			'xbo-home'           => array(
				'title'   => 'XBO Market Kit',
				'content' => self::get_home_content(),
			),
			'xbo-showcase'       => array(
				'title'   => 'Showcase',
				'content' => self::get_showcase_content(),
			),
			'xbo-demos'          => array(
				'title'   => 'Block Demos',
				'content' => self::get_demos_index_content(),
			),
			'xbo-demo-ticker'    => array(
				'title'   => 'Ticker Demo',
				'content' => self::get_ticker_demo_content(),
				'parent'  => 'xbo-demos',
			),
			'xbo-demo-movers'    => array(
				'title'   => 'Top Movers Demo',
				'content' => self::get_movers_demo_content(),
				'parent'  => 'xbo-demos',
			),
			'xbo-demo-orderbook' => array(
				'title'   => 'Order Book Demo',
				'content' => self::get_orderbook_demo_content(),
				'parent'  => 'xbo-demos',
			),
			'xbo-demo-trades'    => array(
				'title'   => 'Recent Trades Demo',
				'content' => self::get_trades_demo_content(),
				'parent'  => 'xbo-demos',
			),
			'xbo-demo-slippage'  => array(
				'title'   => 'Slippage Demo',
				'content' => self::get_slippage_demo_content(),
				'parent'  => 'xbo-demos',
			),
			'xbo-api-docs'       => array(
				'title'   => 'API Documentation',
				'content' => self::get_api_docs_content(),
			),
		);
	}

	/**
	 * Create the navigation menu with all page links.
	 *
	 * @param array<string, int> $pages Map of page slug to post ID.
	 */
	private static function create_menu( array $pages ): void {
		// Remove existing menu if present.
		$existing_menu = wp_get_nav_menu_object( self::MENU_NAME );
		if ( $existing_menu ) {
			wp_delete_nav_menu( $existing_menu->term_id );
		}

		$menu_id = wp_create_nav_menu( self::MENU_NAME );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}

		// Home.
		if ( isset( $pages['xbo-home'] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'Home',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages['xbo-home'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		// Showcase.
		if ( isset( $pages['xbo-showcase'] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'Showcase',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages['xbo-showcase'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		// Block Demos parent.
		$demos_menu_item_id = 0;
		if ( isset( $pages['xbo-demos'] ) ) {
			$demos_menu_item_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'Block Demos',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages['xbo-demos'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);

			if ( is_wp_error( $demos_menu_item_id ) ) {
				$demos_menu_item_id = 0;
			}
		}

		// Child demo pages.
		$demo_children = array(
			'xbo-demo-ticker'    => 'Ticker Demo',
			'xbo-demo-movers'    => 'Top Movers Demo',
			'xbo-demo-orderbook' => 'Order Book Demo',
			'xbo-demo-trades'    => 'Recent Trades Demo',
			'xbo-demo-slippage'  => 'Slippage Demo',
		);

		foreach ( $demo_children as $slug => $title ) {
			if ( ! isset( $pages[ $slug ] ) ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $title,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages[ $slug ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $demos_menu_item_id,
				)
			);
		}

		// API Docs.
		if ( isset( $pages['xbo-api-docs'] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'API Documentation',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages['xbo-api-docs'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		// Assign menu to theme location if available.
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( is_array( $locations ) ) {
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
	}

	/**
	 * Delete the navigation menu.
	 */
	private static function delete_menu(): void {
		$menu = wp_get_nav_menu_object( self::MENU_NAME );
		if ( $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}
	}

	/**
	 * Set the Home page as the static front page.
	 *
	 * @param array<string, int> $pages Map of page slug to post ID.
	 */
	private static function set_static_front_page( array $pages ): void {
		if ( ! isset( $pages['xbo-home'] ) ) {
			return;
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $pages['xbo-home'] );
	}

	/**
	 * Restore the blog front page setting.
	 */
	private static function restore_blog_front_page(): void {
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_on_front', 0 );
	}

	/**
	 * Get Home page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_home_content(): string {
		$content = '';

		// Hero Section.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-hero"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-hero">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Real-Time Crypto Market Data for WordPress</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>5 live trading widgets powered by XBO API. Built entirely by AI during Claude Code Hackathon 2026.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT" columns="4" refresh="15"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Features Grid.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-features"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-features">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Plugin Features</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<div class="xbo-mk-page-features-grid">' . "\n";
		$content .= '<div class="xbo-mk-page-feature-card">' . "\n";
		$content .= '<h3>Live Ticker</h3>' . "\n";
		$content .= '<p>Real-time prices with 24h change and sparkline charts</p>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<div class="xbo-mk-page-feature-card">' . "\n";
		$content .= '<h3>Top Movers</h3>' . "\n";
		$content .= '<p>Biggest gainers and losers by 24h percentage</p>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<div class="xbo-mk-page-feature-card">' . "\n";
		$content .= '<h3>Order Book</h3>' . "\n";
		$content .= '<p>Live bid/ask depth with spread indicator</p>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<div class="xbo-mk-page-feature-card">' . "\n";
		$content .= '<h3>Recent Trades</h3>' . "\n";
		$content .= '<p>Trade feed with color-coded buy/sell</p>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<div class="xbo-mk-page-feature-card">' . "\n";
		$content .= '<h3>Slippage Calculator</h3>' . "\n";
		$content .= '<p>Avg execution price from order book analysis</p>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:html -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Live Showcase.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section--gradient"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section--gradient">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Live Market Data</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_movers mode="gainers" limit="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";
		$content .= '<!-- wp:columns -->' . "\n";
		$content .= '<div class="wp-block-columns">';
		$content .= '<!-- wp:column -->' . "\n";
		$content .= '<div class="wp-block-column">';
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_orderbook symbol="BTC_USDT" depth="15" refresh="5"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:column -->' . "\n\n";
		$content .= '<!-- wp:column -->' . "\n";
		$content .= '<div class="wp-block-column">';
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_trades symbol="BTC/USDT" limit="15" refresh="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:column -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:columns -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Stats Section.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-stats"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-stats">';
		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<div class="xbo-mk-page-stats-grid">' . "\n";
		$content .= '<div class="xbo-mk-page-stat-item"><span class="xbo-mk-page-stat-number">5</span><span class="xbo-mk-page-stat-label">Widgets</span></div>' . "\n";
		$content .= '<div class="xbo-mk-page-stat-item"><span class="xbo-mk-page-stat-number">280+</span><span class="xbo-mk-page-stat-label">Trading Pairs</span></div>' . "\n";
		$content .= '<div class="xbo-mk-page-stat-item"><span class="xbo-mk-page-stat-number">205</span><span class="xbo-mk-page-stat-label">Crypto Icons</span></div>' . "\n";
		$content .= '<div class="xbo-mk-page-stat-item"><span class="xbo-mk-page-stat-number">6</span><span class="xbo-mk-page-stat-label">API Endpoints</span></div>' . "\n";
		$content .= '<div class="xbo-mk-page-stat-item"><span class="xbo-mk-page-stat-number">100%</span><span class="xbo-mk-page-stat-label">AI-Built</span></div>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:html -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Slippage Section.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section--glass"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section--glass">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Advanced Slippage Calculator</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Calculate estimated execution price and slippage for any trade size.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_slippage symbol="BTC_USDT" side="buy"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// CTA Section.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-cta"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-cta">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Get Started with XBO Market Kit</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n";
		$content .= '<div class="wp-block-buttons">';
		$content .= '<!-- wp:button {"className":"is-style-fill"} -->' . "\n";
		$content .= '<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit">GitHub</a></div>' . "\n";
		$content .= '<!-- /wp:button -->' . "\n\n";
		$content .= '<!-- wp:button {"className":"is-style-outline"} -->' . "\n";
		$content .= '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/xbo-api-docs/">API Docs</a></div>' . "\n";
		$content .= '<!-- /wp:button -->' . "\n\n";
		$content .= '<!-- wp:button {"className":"is-style-outline"} -->' . "\n";
		$content .= '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/xbo-showcase/">Showcase</a></div>' . "\n";
		$content .= '<!-- /wp:button -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:buttons -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n";

		return $content;
	}

	/**
	 * Get Showcase page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_showcase_content(): string {
		$content = '';

		// Mini hero.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-hero xbo-mk-page-hero--mini"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-hero xbo-mk-page-hero--mini">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Showcase</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>All 5 widgets in action. Every block auto-refreshes with live data from XBO API.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Ticker.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Live Ticker</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Real-time cryptocurrency prices with 24h change percentage and sparkline charts.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT" columns="4" refresh="15"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Top Movers.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Top Movers</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Biggest gainers by 24-hour percentage change across all trading pairs.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_movers mode="gainers" limit="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Order Book.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Order Book</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Live bid/ask depth visualization for BTC/USDT with spread indicator.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Recent Trades.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Recent Trades</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Live trade feed for BTC/USDT with color-coded buy/sell indicators.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Slippage Calculator.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-section"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-section">';
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Slippage Calculator</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Calculate estimated execution price and slippage for any trade size using live order book data.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_slippage symbol="BTC_USDT" side="buy"]' . "\n";
		$content .= '<!-- /wp:shortcode -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n";

		return $content;
	}

	/**
	 * Get Block Demos index page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_demos_index_content(): string {
		$content = '';

		// Mini hero.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-hero xbo-mk-page-hero--mini"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-hero xbo-mk-page-hero--mini">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Block Demos</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Explore each widget individually with parameter documentation and shortcode examples.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Demo cards grid.
		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<div class="xbo-mk-page-features-grid">' . "\n";
		$content .= '<a href="/xbo-demos/xbo-demo-ticker/" class="xbo-mk-page-feature-card xbo-mk-page-feature-card--link">' . "\n";
		$content .= '<h3>Live Ticker</h3>' . "\n";
		$content .= '<p>Real-time prices with 24h change and sparkline charts</p>' . "\n";
		$content .= '</a>' . "\n";
		$content .= '<a href="/xbo-demos/xbo-demo-movers/" class="xbo-mk-page-feature-card xbo-mk-page-feature-card--link">' . "\n";
		$content .= '<h3>Top Movers</h3>' . "\n";
		$content .= '<p>Biggest gainers and losers by 24h percentage</p>' . "\n";
		$content .= '</a>' . "\n";
		$content .= '<a href="/xbo-demos/xbo-demo-orderbook/" class="xbo-mk-page-feature-card xbo-mk-page-feature-card--link">' . "\n";
		$content .= '<h3>Order Book</h3>' . "\n";
		$content .= '<p>Live bid/ask depth with spread indicator</p>' . "\n";
		$content .= '</a>' . "\n";
		$content .= '<a href="/xbo-demos/xbo-demo-trades/" class="xbo-mk-page-feature-card xbo-mk-page-feature-card--link">' . "\n";
		$content .= '<h3>Recent Trades</h3>' . "\n";
		$content .= '<p>Trade feed with color-coded buy/sell</p>' . "\n";
		$content .= '</a>' . "\n";
		$content .= '<a href="/xbo-demos/xbo-demo-slippage/" class="xbo-mk-page-feature-card xbo-mk-page-feature-card--link">' . "\n";
		$content .= '<h3>Slippage Calculator</h3>' . "\n";
		$content .= '<p>Avg execution price from order book analysis</p>' . "\n";
		$content .= '</a>' . "\n";
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n";

		return $content;
	}

	/**
	 * Get Ticker Demo page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_ticker_demo_content(): string {
		$content = '';

		// Demo header.
		$content .= '<!-- wp:group {"className":"xbo-mk-demo-header"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-demo-header">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Live Ticker</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Display real-time cryptocurrency prices with 24-hour change percentage and sparkline charts. Supports multiple trading pairs in a responsive grid layout.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Live block.
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT" columns="4" refresh="15"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Parameters table.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Parameters</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<table class="xbo-mk-param-table">' . "\n";
		$content .= '<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>' . "\n";
		$content .= '<tbody>' . "\n";
		$content .= '<tr><td><code>symbols</code></td><td>string</td><td>BTC/USDT,ETH/USDT</td><td>Comma-separated list of trading pairs in SLASH format</td></tr>' . "\n";
		$content .= '<tr><td><code>refresh</code></td><td>number</td><td>15</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
		$content .= '<tr><td><code>columns</code></td><td>number</td><td>4</td><td>Number of columns in the grid layout</td></tr>' . "\n";
		$content .= '</tbody>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n\n";

		// Shortcode example.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Shortcode Usage</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:code -->' . "\n";
		$content .= '<pre class="wp-block-code"><code>[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT" columns="4" refresh="15"]</code></pre>' . "\n";
		$content .= '<!-- /wp:code -->' . "\n";

		return $content;
	}

	/**
	 * Get Top Movers Demo page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_movers_demo_content(): string {
		$content = '';

		// Demo header.
		$content .= '<!-- wp:group {"className":"xbo-mk-demo-header"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-demo-header">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Top Movers</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Display the biggest gainers or losers by 24-hour percentage change across all available trading pairs.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Live block.
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_movers mode="gainers" limit="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Parameters table.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Parameters</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<table class="xbo-mk-param-table">' . "\n";
		$content .= '<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>' . "\n";
		$content .= '<tbody>' . "\n";
		$content .= '<tr><td><code>mode</code></td><td>string</td><td>gainers</td><td>Display mode: "gainers" or "losers"</td></tr>' . "\n";
		$content .= '<tr><td><code>limit</code></td><td>number</td><td>10</td><td>Number of pairs to display</td></tr>' . "\n";
		$content .= '</tbody>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n\n";

		// Shortcode example.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Shortcode Usage</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:code -->' . "\n";
		$content .= '<pre class="wp-block-code"><code>[xbo_movers mode="gainers" limit="10"]</code></pre>' . "\n";
		$content .= '<!-- /wp:code -->' . "\n";

		return $content;
	}

	/**
	 * Get Order Book Demo page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_orderbook_demo_content(): string {
		$content = '';

		// Demo header.
		$content .= '<!-- wp:group {"className":"xbo-mk-demo-header"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-demo-header">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Order Book</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Live bid/ask depth visualization with spread indicator. Shows real-time order book data for any trading pair.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Live block.
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Parameters table.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Parameters</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<table class="xbo-mk-param-table">' . "\n";
		$content .= '<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>' . "\n";
		$content .= '<tbody>' . "\n";
		$content .= '<tr><td><code>symbol</code></td><td>string</td><td>BTC_USDT</td><td>Trading pair in UNDERSCORE format</td></tr>' . "\n";
		$content .= '<tr><td><code>depth</code></td><td>number</td><td>20</td><td>Number of price levels to display (max 250)</td></tr>' . "\n";
		$content .= '<tr><td><code>refresh</code></td><td>number</td><td>5</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
		$content .= '</tbody>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n\n";

		// Shortcode example.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Shortcode Usage</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:code -->' . "\n";
		$content .= '<pre class="wp-block-code"><code>[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]</code></pre>' . "\n";
		$content .= '<!-- /wp:code -->' . "\n";

		return $content;
	}

	/**
	 * Get Recent Trades Demo page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_trades_demo_content(): string {
		$content = '';

		// Demo header.
		$content .= '<!-- wp:group {"className":"xbo-mk-demo-header"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-demo-header">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Recent Trades</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Live trade feed with color-coded buy/sell indicators. Shows the most recent trades for any trading pair in real time.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Live block.
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Parameters table.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Parameters</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<table class="xbo-mk-param-table">' . "\n";
		$content .= '<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>' . "\n";
		$content .= '<tbody>' . "\n";
		$content .= '<tr><td><code>symbol</code></td><td>string</td><td>BTC/USDT</td><td>Trading pair in SLASH format</td></tr>' . "\n";
		$content .= '<tr><td><code>limit</code></td><td>number</td><td>20</td><td>Number of trades to display</td></tr>' . "\n";
		$content .= '<tr><td><code>refresh</code></td><td>number</td><td>10</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
		$content .= '</tbody>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n\n";

		// Shortcode example.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Shortcode Usage</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:code -->' . "\n";
		$content .= '<pre class="wp-block-code"><code>[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]</code></pre>' . "\n";
		$content .= '<!-- /wp:code -->' . "\n";

		return $content;
	}

	/**
	 * Get Slippage Demo page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_slippage_demo_content(): string {
		$content = '';

		// Demo header.
		$content .= '<!-- wp:group {"className":"xbo-mk-demo-header"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-demo-header">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">Slippage Calculator</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>Calculate estimated execution price and slippage for any trade size using live order book data. Supports both buy and sell sides.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Live block.
		$content .= '<!-- wp:shortcode -->' . "\n";
		$content .= '[xbo_slippage symbol="BTC_USDT" side="buy"]' . "\n";
		$content .= '<!-- /wp:shortcode -->' . "\n\n";

		// Parameters table.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Parameters</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<table class="xbo-mk-param-table">' . "\n";
		$content .= '<thead><tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>' . "\n";
		$content .= '<tbody>' . "\n";
		$content .= '<tr><td><code>symbol</code></td><td>string</td><td>BTC_USDT</td><td>Trading pair in UNDERSCORE format</td></tr>' . "\n";
		$content .= '<tr><td><code>side</code></td><td>string</td><td>buy</td><td>Trade side: "buy" or "sell"</td></tr>' . "\n";
		$content .= '<tr><td><code>amount</code></td><td>number</td><td></td><td>Pre-filled trade amount (optional)</td></tr>' . "\n";
		$content .= '</tbody>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n\n";

		// Shortcode example.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Shortcode Usage</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:code -->' . "\n";
		$content .= '<pre class="wp-block-code"><code>[xbo_slippage symbol="BTC_USDT" side="buy"]</code></pre>' . "\n";
		$content .= '<!-- /wp:code -->' . "\n";

		return $content;
	}

	/**
	 * Get API Documentation page content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private static function get_api_docs_content(): string {
		$content = '';

		// Mini hero.
		$content .= '<!-- wp:group {"className":"xbo-mk-page-hero xbo-mk-page-hero--mini"} -->' . "\n";
		$content .= '<div class="wp-block-group xbo-mk-page-hero xbo-mk-page-hero--mini">';
		$content .= '<!-- wp:heading {"level":1} -->' . "\n";
		$content .= '<h1 class="wp-block-heading">API Documentation</h1>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";
		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>XBO Market Kit exposes a WordPress REST API that proxies data from the XBO Public API. All endpoints require no authentication.</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->';
		$content .= '</div>' . "\n";
		$content .= '<!-- /wp:group -->' . "\n\n";

		// Endpoints table.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">REST API Endpoints</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:html -->' . "\n";
		$content .= '<table class="xbo-mk-param-table">' . "\n";
		$content .= '<thead><tr><th>Endpoint</th><th>Method</th><th>Parameters</th><th>Description</th></tr></thead>' . "\n";
		$content .= '<tbody>' . "\n";
		$content .= '<tr><td><code>/wp-json/xbo/v1/ticker</code></td><td>GET</td><td><code>symbols</code> (comma-separated, SLASH format)</td><td>Returns price, 24h change, volume, and sparkline data for specified trading pairs</td></tr>' . "\n";
		$content .= '<tr><td><code>/wp-json/xbo/v1/movers</code></td><td>GET</td><td><code>mode</code> (gainers|losers), <code>limit</code></td><td>Returns top gaining or losing pairs by 24h percentage change</td></tr>' . "\n";
		$content .= '<tr><td><code>/wp-json/xbo/v1/orderbook</code></td><td>GET</td><td><code>symbol</code> (UNDERSCORE format), <code>depth</code></td><td>Returns order book bids and asks for a trading pair</td></tr>' . "\n";
		$content .= '<tr><td><code>/wp-json/xbo/v1/trades</code></td><td>GET</td><td><code>symbol</code> (SLASH format), <code>limit</code></td><td>Returns recent trades for a trading pair</td></tr>' . "\n";
		$content .= '<tr><td><code>/wp-json/xbo/v1/slippage</code></td><td>GET</td><td><code>symbol</code> (UNDERSCORE format), <code>side</code>, <code>amount</code></td><td>Calculates estimated execution price and slippage from order book</td></tr>' . "\n";
		$content .= '<tr><td><code>/wp-json/xbo/v1/trading-pairs</code></td><td>GET</td><td>none</td><td>Returns all available trading pairs with metadata</td></tr>' . "\n";
		$content .= '</tbody>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- /wp:html -->' . "\n\n";

		// Base URL info.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Base URL</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:paragraph -->' . "\n";
		$content .= '<p>All endpoints are available at your WordPress site URL. For example:</p>' . "\n";
		$content .= '<!-- /wp:paragraph -->' . "\n\n";

		$content .= '<!-- wp:code -->' . "\n";
		$content .= '<pre class="wp-block-code"><code>GET https://your-site.com/wp-json/xbo/v1/ticker?symbols=BTC/USDT,ETH/USDT</code></pre>' . "\n";
		$content .= '<!-- /wp:code -->' . "\n\n";

		// Notes.
		$content .= '<!-- wp:heading -->' . "\n";
		$content .= '<h2 class="wp-block-heading">Notes</h2>' . "\n";
		$content .= '<!-- /wp:heading -->' . "\n\n";

		$content .= '<!-- wp:list -->' . "\n";
		$content .= '<ul class="wp-block-list">';
		$content .= '<li>All data is proxied server-side from the XBO Public API (no CORS issues)</li>';
		$content .= '<li>Responses are cached using WordPress transients for optimal performance</li>';
		$content .= '<li>No API key or authentication is required</li>';
		$content .= '<li>Rate limiting is handled server-side by the caching layer</li>';
		$content .= '</ul>' . "\n";
		$content .= '<!-- /wp:list -->' . "\n";

		return $content;
	}
}
