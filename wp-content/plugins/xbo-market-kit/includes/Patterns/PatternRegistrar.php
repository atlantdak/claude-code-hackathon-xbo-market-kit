<?php
/**
 * PatternRegistrar class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Patterns;

/**
 * Registers all block patterns and the pattern category.
 */
class PatternRegistrar {

	/**
	 * Register pattern category and all plugin patterns.
	 *
	 * @return void
	 */
	public function register(): void {
		register_block_pattern_category(
			'xbo-market-kit',
			array( 'label' => __( 'XBO Market Kit', 'xbo-market-kit' ) )
		);

		$this->register_hero_pattern();
		$this->register_features_pattern();
		$this->register_live_showcase_pattern();
		$this->register_stats_counter_pattern();
		$this->register_glass_card_pattern();
		$this->register_demo_header_pattern();
	}

	/**
	 * Register the hero section pattern.
	 *
	 * @return void
	 */
	private function register_hero_pattern(): void {
		$content = '<!-- wp:group {"className":"xbo-mk-page-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group xbo-mk-page-hero">
<!-- wp:heading {"level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-color" style="color:#ffffff">Real-Time Crypto Market Data</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-color" style="color:#ffffffcc">Live trading widgets powered by XBO API.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[xbo_ticker symbols="BTC/USDT,ETH/USDT" columns="2" refresh="15"]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'xbo-market-kit/hero-section',
			array(
				'title'       => __( 'XBO Hero Section', 'xbo-market-kit' ),
				'description' => __( 'Full-width hero section with gradient background, heading, and ticker widget.', 'xbo-market-kit' ),
				'categories'  => array( 'xbo-market-kit' ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Register the features grid pattern.
	 *
	 * @return void
	 */
	private function register_features_pattern(): void {
		$content = '<!-- wp:group {"className":"xbo-mk-page-features","layout":{"type":"constrained"}} -->
<div class="wp-block-group xbo-mk-page-features">
<!-- wp:heading {"style":{"color":{"text":"#ffffff"}},"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Features</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<div class="xbo-mk-page-features-grid">
<div class="xbo-mk-page-feature-card">
<span class="dashicons dashicons-chart-line"></span>
<h3>Live Ticker</h3>
<p>Real-time price updates for any trading pair with auto-refresh.</p>
</div>
<div class="xbo-mk-page-feature-card">
<span class="dashicons dashicons-arrow-up-alt"></span>
<h3>Top Movers</h3>
<p>Track the biggest gainers and losers across the market.</p>
</div>
<div class="xbo-mk-page-feature-card">
<span class="dashicons dashicons-book"></span>
<h3>Order Book</h3>
<p>Visualize market depth with live bid and ask data.</p>
</div>
<div class="xbo-mk-page-feature-card">
<span class="dashicons dashicons-list-view"></span>
<h3>Recent Trades</h3>
<p>Stream the latest executed trades in real time.</p>
</div>
<div class="xbo-mk-page-feature-card">
<span class="dashicons dashicons-performance"></span>
<h3>Slippage Calculator</h3>
<p>Estimate price impact for any trade size.</p>
</div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'xbo-market-kit/features-grid',
			array(
				'title'       => __( 'XBO Features Grid', 'xbo-market-kit' ),
				'description' => __( 'Three-column grid of feature cards with hover effects.', 'xbo-market-kit' ),
				'categories'  => array( 'xbo-market-kit' ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Register the live showcase pattern.
	 *
	 * @return void
	 */
	private function register_live_showcase_pattern(): void {
		$content = '<!-- wp:group {"className":"xbo-mk-page-section--gradient","layout":{"type":"constrained"}} -->
<div class="wp-block-group xbo-mk-page-section--gradient">
<!-- wp:heading {"style":{"color":{"text":"#ffffff"}},"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Live Market Data</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[xbo_movers mode="gainers" limit="10"]
<!-- /wp:shortcode -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:shortcode -->
[xbo_orderbook]
<!-- /wp:shortcode -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:shortcode -->
[xbo_trades]
<!-- /wp:shortcode -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'xbo-market-kit/live-showcase',
			array(
				'title'       => __( 'XBO Live Showcase', 'xbo-market-kit' ),
				'description' => __( 'Live market data section with movers, orderbook, and trades.', 'xbo-market-kit' ),
				'categories'  => array( 'xbo-market-kit' ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Register the stats counter pattern.
	 *
	 * @return void
	 */
	private function register_stats_counter_pattern(): void {
		$content = '<!-- wp:html -->
<div class="xbo-mk-page-stats">
<div class="xbo-mk-page-stats-item">
<span class="xbo-mk-page-stats-number">5</span>
<span class="xbo-mk-page-stats-label">Widgets</span>
</div>
<div class="xbo-mk-page-stats-item">
<span class="xbo-mk-page-stats-number">280+</span>
<span class="xbo-mk-page-stats-label">Pairs</span>
</div>
<div class="xbo-mk-page-stats-item">
<span class="xbo-mk-page-stats-number">205</span>
<span class="xbo-mk-page-stats-label">Icons</span>
</div>
<div class="xbo-mk-page-stats-item">
<span class="xbo-mk-page-stats-number">6</span>
<span class="xbo-mk-page-stats-label">Endpoints</span>
</div>
<div class="xbo-mk-page-stats-item">
<span class="xbo-mk-page-stats-number">100%</span>
<span class="xbo-mk-page-stats-label">AI-Built</span>
</div>
</div>
<!-- /wp:html -->';

		register_block_pattern(
			'xbo-market-kit/stats-counter',
			array(
				'title'       => __( 'XBO Stats Counter', 'xbo-market-kit' ),
				'description' => __( 'Project statistics with large numbers.', 'xbo-market-kit' ),
				'categories'  => array( 'xbo-market-kit' ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Register the glass card pattern.
	 *
	 * @return void
	 */
	private function register_glass_card_pattern(): void {
		$content = '<!-- wp:group {"className":"xbo-mk-glass","layout":{"type":"constrained"}} -->
<div class="wp-block-group xbo-mk-glass">
<!-- wp:heading {"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-color" style="color:#ffffff">Featured Content</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-color" style="color:#ffffffcc">Add your content here. This card uses a glassmorphism effect for a modern look.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'xbo-market-kit/glass-card',
			array(
				'title'       => __( 'XBO Glass Card', 'xbo-market-kit' ),
				'description' => __( 'Glassmorphism card for featured content.', 'xbo-market-kit' ),
				'categories'  => array( 'xbo-market-kit' ),
				'content'     => $content,
			)
		);
	}

	/**
	 * Register the demo page header pattern.
	 *
	 * @return void
	 */
	private function register_demo_header_pattern(): void {
		$content = '<!-- wp:group {"className":"xbo-mk-demo-header","layout":{"type":"constrained"}} -->
<div class="wp-block-group xbo-mk-demo-header">
<!-- wp:heading {"level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-color" style="color:#ffffff">Demo Page</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-color" style="color:#ffffffcc">Explore the XBO Market Kit widgets in action.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';

		register_block_pattern(
			'xbo-market-kit/demo-page-header',
			array(
				'title'       => __( 'XBO Demo Page Header', 'xbo-market-kit' ),
				'description' => __( 'Mini hero header for demo pages.', 'xbo-market-kit' ),
				'categories'  => array( 'xbo-market-kit' ),
				'content'     => $content,
			)
		);
	}
}
