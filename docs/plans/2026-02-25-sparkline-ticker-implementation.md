# Sparkline Ticker Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace hardcoded fake SVG sparklines with algorithmically generated trend visualizations using constrained random walk.

**Architecture:** PHP `SparklineGenerator` class produces 40 synthetic price points from market snapshot indicators (change_pct_24h, high/low/last). Points are rendered as SVG polyline + gradient fill in the shortcode, then injected into Interactivity API context. JS hydrates the ring buffer and pushes real polled prices on each 15s cycle, progressively replacing synthetic data over ~20 minutes.

**Tech Stack:** PHP 8.1+, WordPress Interactivity API, SVG, CSS Custom Properties, PHPUnit 11 + Brain\Monkey

**Design doc:** `docs/plans/2026-02-25-sparkline-ticker-design.md`

---

### Task 1: SparklineGenerator — Seeded RNG and Price Generation

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/includes/Sparkline/SparklineGenerator.php`
- Create: `wp-content/plugins/xbo-market-kit/tests/Unit/Sparkline/SparklineGeneratorTest.php`

**Step 1: Write failing tests for generatePrices()**

Create `tests/Unit/Sparkline/SparklineGeneratorTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Sparkline;

use PHPUnit\Framework\TestCase;
use XboMarketKit\Sparkline\SparklineGenerator;

class SparklineGeneratorTest extends TestCase {

	private SparklineGenerator $generator;

	protected function setUp(): void {
		parent::setUp();
		$this->generator = new SparklineGenerator();
	}

	public function test_generates_correct_count(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$this->assertCount( 40, $prices );
	}

	public function test_last_point_equals_last_price(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$this->assertSame( 68900.0, $prices[39] );
	}

	public function test_deterministic_output(): void {
		$a = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$b = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$this->assertSame( $a, $b );
	}

	public function test_different_seeds_different_curves(): void {
		$btc = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$eth = $this->generator->generate_prices( 3800.0, 3900.0, 3700.0, -1.2, 'ETH/USDT' );
		$this->assertNotSame( $btc, $eth );
	}

	public function test_all_prices_positive(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, -50.0, 'BTC/USDT' );
		foreach ( $prices as $price ) {
			$this->assertGreaterThan( 0, $price );
		}
	}

	public function test_all_prices_finite(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		foreach ( $prices as $price ) {
			$this->assertIsFloat( $price );
			$this->assertTrue( is_finite( $price ) );
		}
	}
}
```

**Step 2: Run tests to verify they fail**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=SparklineGeneratorTest`

Expected: FAIL — class `SparklineGenerator` not found.

**Step 3: Implement SparklineGenerator with generatePrices()**

Create `includes/Sparkline/SparklineGenerator.php`:

```php
<?php
/**
 * SparklineGenerator class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Sparkline;

/**
 * Generates synthetic sparkline price data from market snapshot indicators.
 *
 * Uses a constrained random walk algorithm seeded deterministically
 * per symbol to produce realistic trend visualizations.
 */
class SparklineGenerator {

	/**
	 * Default number of sparkline data points.
	 */
	private const DEFAULT_COUNT = 40;

	/**
	 * Noise amplitude as fraction of price range.
	 */
	private const NOISE_SCALE = 0.012;

	/**
	 * Drift amplitude as fraction of price range.
	 */
	private const DRIFT_SCALE = 0.005;

	/**
	 * Maximum step as fraction of price range.
	 */
	private const MAX_STEP_SCALE = 0.05;

	/**
	 * Minimum price floor to prevent zero/negative values.
	 */
	private const PRICE_FLOOR = 1e-9;

	/**
	 * Generate synthetic price points for a sparkline.
	 *
	 * @param float  $last_price     Current price.
	 * @param float  $high_24h       24-hour high price.
	 * @param float  $low_24h        24-hour low price.
	 * @param float  $change_pct_24h 24-hour percent change.
	 * @param string $symbol         Trading pair symbol (e.g. 'BTC/USDT').
	 * @param int    $count          Number of points to generate.
	 * @return array<int, float> Array of price points. Empty if last_price <= 0.
	 */
	public function generate_prices(
		float $last_price,
		float $high_24h,
		float $low_24h,
		float $change_pct_24h,
		string $symbol,
		int $count = self::DEFAULT_COUNT
	): array {
		if ( $last_price <= 0.0 || $count < 2 ) {
			return array();
		}

		// Ensure high >= low.
		if ( $high_24h < $low_24h ) {
			[ $low_24h, $high_24h ] = [ $high_24h, $low_24h ];
		}

		$price_range = max( $high_24h - $low_24h, $last_price * 0.001 );
		$trend24     = tanh( $change_pct_24h / 10.0 );
		$noise_scale = $price_range * self::NOISE_SCALE;
		$drift       = -$trend24 * $price_range * self::DRIFT_SCALE;
		$max_step    = $price_range * self::MAX_STEP_SCALE;

		$seed = $this->compute_seed( $symbol, $change_pct_24h );
		$rng  = $seed;

		$prices              = array_fill( 0, $count, 0.0 );
		$prices[ $count - 1 ] = $last_price;
		$price               = $last_price;

		for ( $i = $count - 2; $i >= 0; $i-- ) {
			$rng   = $this->next_random( $rng );
			$rand  = ( $this->random_float( $rng ) - 0.5 ) * 2.0 * $noise_scale;
			$step  = max( -$max_step, min( $max_step, $drift + $rand ) );
			$price = max( self::PRICE_FLOOR, $price + $step );
			$prices[ $i ] = $price;
		}

		return $prices;
	}

	/**
	 * Compute a deterministic seed from symbol and trend bucket.
	 *
	 * @param string $symbol         Trading pair symbol.
	 * @param float  $change_pct_24h 24-hour percent change.
	 * @return int Seed value.
	 */
	private function compute_seed( string $symbol, float $change_pct_24h ): int {
		$day_stamp    = (int) floor( time() / 86400 );
		$trend_bucket = (int) round( $change_pct_24h );
		return crc32( $symbol . $day_stamp . $trend_bucket );
	}

	/**
	 * Advance the seeded RNG state (xorshift32).
	 *
	 * @param int $state Current RNG state.
	 * @return int Next RNG state.
	 */
	private function next_random( int $state ): int {
		$state ^= ( $state << 13 ) & 0xFFFFFFFF;
		$state ^= ( $state >> 17 );
		$state ^= ( $state << 5 ) & 0xFFFFFFFF;
		return $state & 0xFFFFFFFF;
	}

	/**
	 * Convert RNG state to a float in [0, 1).
	 *
	 * @param int $state Current RNG state.
	 * @return float Random float in [0, 1).
	 */
	private function random_float( int $state ): float {
		return ( $state & 0x7FFFFFFF ) / 2147483648.0;
	}
}
```

**Step 4: Run tests to verify they pass**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=SparklineGeneratorTest`

Expected: 6 tests, 6 assertions — all PASS.

**Step 5: Commit**

```bash
cd wp-content/plugins/xbo-market-kit
git add includes/Sparkline/SparklineGenerator.php tests/Unit/Sparkline/SparklineGeneratorTest.php
git commit -m "feat(sparkline): add SparklineGenerator with seeded random walk algorithm"
```

---

### Task 2: SparklineGenerator — SVG Point Rendering

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Sparkline/SparklineGenerator.php`
- Modify: `wp-content/plugins/xbo-market-kit/tests/Unit/Sparkline/SparklineGeneratorTest.php`

**Step 1: Write failing tests for renderSvgPoints()**

Append to `SparklineGeneratorTest.php`:

```php
public function test_svg_points_within_viewbox(): void {
	$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
	$result = $this->generator->render_svg_points( $prices );

	$pairs = explode( ' ', $result['polyline_points'] );
	foreach ( $pairs as $pair ) {
		[ $x, $y ] = explode( ',', $pair );
		$this->assertGreaterThanOrEqual( 0.0, (float) $x );
		$this->assertLessThanOrEqual( 100.0, (float) $x );
		$this->assertGreaterThanOrEqual( 0.0, (float) $y );
		$this->assertLessThanOrEqual( 30.0, (float) $y );
	}
}

public function test_svg_polygon_closes_path(): void {
	$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
	$result = $this->generator->render_svg_points( $prices );

	$this->assertStringEndsWith( '100,30 0,30', $result['polygon_points'] );
}

public function test_renderer_handles_empty_prices(): void {
	$result = $this->generator->render_svg_points( array() );

	$this->assertSame( '', $result['polyline_points'] );
	$this->assertSame( '', $result['polygon_points'] );
}

public function test_renderer_handles_equal_prices(): void {
	$prices = array_fill( 0, 40, 100.0 );
	$result = $this->generator->render_svg_points( $prices );

	// All y values should be at midpoint (15.0) for flat line.
	$pairs = explode( ' ', $result['polyline_points'] );
	foreach ( $pairs as $pair ) {
		[ , $y ] = explode( ',', $pair );
		$this->assertEqualsWithDelta( 15.0, (float) $y, 0.1 );
	}
}
```

**Step 2: Run tests to verify they fail**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=SparklineGeneratorTest`

Expected: FAIL — method `render_svg_points` not found.

**Step 3: Add renderSvgPoints() to SparklineGenerator**

Add to `SparklineGenerator.php` after `generate_prices()`:

```php
/**
 * Render price array to SVG polyline and polygon points strings.
 *
 * @param array<int, float> $prices     Array of price values.
 * @param float             $view_width  SVG viewBox width.
 * @param float             $view_height SVG viewBox height.
 * @return array{polyline_points: string, polygon_points: string}
 */
public function render_svg_points(
	array $prices,
	float $view_width = 100.0,
	float $view_height = 30.0
): array {
	$empty = array(
		'polyline_points' => '',
		'polygon_points'  => '',
	);

	$count = count( $prices );
	if ( $count < 2 ) {
		return $empty;
	}

	$buf_min = min( $prices );
	$buf_max = max( $prices );
	$range   = $buf_max - $buf_min;

	// Flat line: all prices equal.
	if ( $range < 1e-12 ) {
		$mid    = $view_height / 2.0;
		$points = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$x        = $i * $view_width / ( $count - 1 );
			$points[] = round( $x, 1 ) . ',' . round( $mid, 1 );
		}
		$polyline = implode( ' ', $points );
		$polygon  = $polyline . ' ' . (int) $view_width . ',' . (int) $view_height . ' 0,' . (int) $view_height;
		return array(
			'polyline_points' => $polyline,
			'polygon_points'  => $polygon,
		);
	}

	$padding = $range * 0.1;
	$y_min   = $buf_min - $padding;
	$y_max   = $buf_max + $padding;
	$y_range = $y_max - $y_min;

	$points = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$x = $i * $view_width / ( $count - 1 );
		$y = $view_height - ( ( $prices[ $i ] - $y_min ) / $y_range ) * $view_height;
		$y = max( 0.0, min( $view_height, $y ) );
		$points[] = round( $x, 1 ) . ',' . round( $y, 1 );
	}

	$polyline = implode( ' ', $points );
	$polygon  = $polyline . ' ' . (int) $view_width . ',' . (int) $view_height . ' 0,' . (int) $view_height;

	return array(
		'polyline_points' => $polyline,
		'polygon_points'  => $polygon,
	);
}
```

**Step 4: Run tests to verify they pass**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=SparklineGeneratorTest`

Expected: 10 tests, all PASS.

**Step 5: Commit**

```bash
git add includes/Sparkline/SparklineGenerator.php tests/Unit/Sparkline/SparklineGeneratorTest.php
git commit -m "feat(sparkline): add SVG point rendering to SparklineGenerator"
```

---

### Task 3: SparklineGenerator — Edge Cases

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/tests/Unit/Sparkline/SparklineGeneratorTest.php`

**Step 1: Write edge case tests**

Append to `SparklineGeneratorTest.php`:

```php
public function test_handles_zero_range(): void {
	$prices = $this->generator->generate_prices( 100.0, 100.0, 100.0, 0.0, 'STABLE/USDT' );
	$this->assertCount( 40, $prices );
	foreach ( $prices as $price ) {
		$this->assertGreaterThan( 0, $price );
		$this->assertTrue( is_finite( $price ) );
	}
}

public function test_handles_zero_price(): void {
	$prices = $this->generator->generate_prices( 0.0, 100.0, 50.0, 5.0, 'ZERO/USDT' );
	$this->assertSame( array(), $prices );
}

public function test_handles_negative_change(): void {
	$prices = $this->generator->generate_prices( 68900.0, 72000.0, 65000.0, -50.0, 'BTC/USDT' );
	$this->assertCount( 40, $prices );
	foreach ( $prices as $price ) {
		$this->assertGreaterThan( 0, $price );
	}
}

public function test_handles_extreme_change(): void {
	$prices = $this->generator->generate_prices( 0.001, 0.005, 0.0001, 500.0, 'MEME/USDT' );
	$this->assertCount( 40, $prices );
	foreach ( $prices as $price ) {
		$this->assertGreaterThan( 0, $price );
		$this->assertTrue( is_finite( $price ) );
	}
}

public function test_handles_inverted_range(): void {
	// high < low — bad API data.
	$prices = $this->generator->generate_prices( 68900.0, 65000.0, 72000.0, 2.5, 'BTC/USDT' );
	$this->assertCount( 40, $prices );
	$this->assertSame( 68900.0, $prices[39] );
}

public function test_handles_tiny_range(): void {
	$prices = $this->generator->generate_prices( 1.00001, 1.00002, 1.00000, 0.01, 'PEG/USDT' );
	$this->assertCount( 40, $prices );
	foreach ( $prices as $price ) {
		$this->assertGreaterThan( 0, $price );
	}
}

public function test_count_minimum(): void {
	$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT', 1 );
	$this->assertSame( array(), $prices );
}

/** @dataProvider trend_direction_provider */
public function test_trend_direction( float $change, string $expected ): void {
	$this->assertSame( $expected, $this->generator->get_trend_direction( $change ) );
}

public static function trend_direction_provider(): array {
	return array(
		'positive' => array( 5.0, 'positive' ),
		'negative' => array( -3.0, 'negative' ),
		'zero'     => array( 0.0, 'positive' ),
	);
}
```

**Step 2: Run tests to verify they fail**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=SparklineGeneratorTest`

Expected: FAIL — method `get_trend_direction` not found.

**Step 3: Add get_trend_direction() to SparklineGenerator**

Add to `SparklineGenerator.php`:

```php
/**
 * Get trend direction string based on 24h percent change.
 *
 * @param float $change_pct_24h 24-hour percent change.
 * @return string 'positive' or 'negative'.
 */
public function get_trend_direction( float $change_pct_24h ): string {
	return $change_pct_24h >= 0 ? 'positive' : 'negative';
}
```

**Step 4: Run tests to verify they pass**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=SparklineGeneratorTest`

Expected: 18 tests, all PASS.

**Step 5: Run full test suite + static analysis**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test && composer run phpstan`

Expected: All tests pass, no PHPStan errors.

**Step 6: Commit**

```bash
git add tests/Unit/Sparkline/SparklineGeneratorTest.php includes/Sparkline/SparklineGenerator.php
git commit -m "test(sparkline): add edge case tests and trend direction method"
```

---

### Task 4: TickerShortcode — Integrate SparklineGenerator

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/TickerShortcode.php`

This task replaces the hardcoded SVG sparkline (lines 95-97) with SparklineGenerator output and injects sparkline data into the Interactivity API context.

**Step 1: Read the current TickerShortcode.php to confirm line numbers**

Verify lines 62-106 match the expected structure before editing.

**Step 2: Modify render() to use SparklineGenerator**

Replace the `render()` method body in `TickerShortcode.php`. The full updated method:

```php
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
		$stat         = $stats_map[ $symbol ] ?? array();
		$last_price   = (float) ( $stat['lastPrice'] ?? 0 );
		$high_24h     = (float) ( $stat['highestPrice24H'] ?? 0 );
		$low_24h      = (float) ( $stat['lowestPrice24H'] ?? 0 );
		$change_pct   = (float) ( $stat['priceChangePercent24H'] ?? 0 );
		$trend        = $generator->get_trend_direction( $change_pct );
		$gradient_id  = 'spark-grad-' . $key . '-' . $widget_id;

		// Generate sparkline data.
		$prices    = array();
		$svg_attrs = array( 'polyline_points' => '', 'polygon_points' => '' );
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
```

**Step 3: Run PHPCS and PHPStan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs -- includes/Shortcodes/TickerShortcode.php && composer run phpstan`

Fix any code style issues reported.

**Step 4: Run full test suite**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test`

Expected: All tests pass (existing tests should not break).

**Step 5: Commit**

```bash
git add includes/Shortcodes/TickerShortcode.php
git commit -m "feat(sparkline): integrate SparklineGenerator into ticker shortcode"
```

---

### Task 5: CSS — Sparkline Gradient and Trend Colors

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_ticker.css`

**Step 1: Replace current sparkline CSS**

Replace lines 92-100 of `_ticker.css` (the `.xbo-mk-ticker__sparkline` block and polyline rule) with:

```css
/* Sparkline container */
.xbo-mk-ticker__sparkline {
  margin-top: var(--xbo-mk--space-sm);
  width: 100%;
  height: 32px;
}

/* Sparkline stroke color by trend */
.xbo-mk-ticker__sparkline--positive .xbo-mk-ticker__sparkline-line {
  stroke: var(--xbo-mk--color-positive);
}

.xbo-mk-ticker__sparkline--negative .xbo-mk-ticker__sparkline-line {
  stroke: var(--xbo-mk--color-negative);
}

/* Gradient fill inherits trend color via stop-color */
.xbo-mk-ticker__sparkline--positive stop {
  stop-color: var(--xbo-mk--color-positive);
}

.xbo-mk-ticker__sparkline--negative stop {
  stop-color: var(--xbo-mk--color-negative);
}
```

**Step 2: Rebuild CSS bundle**

Check if there is a CSS build step. Look for a build script:

Run: `ls wp-content/plugins/xbo-market-kit/assets/css/dist/`

If minified CSS exists, check how it's built (likely just concatenation). Rebuild as needed.

**Step 3: Visual verification**

Open `http://claude-code-hackathon-xbo-market-kit.local/` in browser. Verify:
- Sparklines render with colored lines (green for positive, red for negative)
- Gradient fill visible under the line
- Different curves per symbol

**Step 4: Commit**

```bash
git add assets/css/src/_ticker.css
git commit -m "feat(sparkline): add trend-based gradient and stroke colors"
```

---

### Task 6: JavaScript — Ring Buffer and Live SVG Updates

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/js/interactivity/ticker.js`

**Step 1: Rewrite ticker.js with sparkline ring buffer**

Replace full contents of `ticker.js`:

```js
/**
 * XBO Market Kit — Ticker widget Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

/**
 * Seeded xorshift32 RNG — must match PHP implementation.
 *
 * @param {number} state Current RNG state.
 * @return {number} Next state.
 */
function nextRandom( state ) {
	state ^= ( state << 13 ) & 0xFFFFFFFF;
	state ^= ( state >> 17 );
	state ^= ( state << 5 ) & 0xFFFFFFFF;
	return state & 0xFFFFFFFF;
}

/**
 * Convert RNG state to float in [0, 1).
 *
 * @param {number} state RNG state.
 * @return {number} Float in [0, 1).
 */
function randomFloat( state ) {
	return ( state & 0x7FFFFFFF ) / 2147483648.0;
}

/**
 * Render price array to SVG points strings.
 *
 * @param {number[]} prices     Array of price values.
 * @param {number}   viewWidth  SVG viewBox width.
 * @param {number}   viewHeight SVG viewBox height.
 * @return {{ polyline: string, polygon: string }}
 */
function renderSvgPoints( prices, viewWidth = 100, viewHeight = 30 ) {
	const count = prices.length;
	if ( count < 2 ) {
		return { polyline: '', polygon: '' };
	}

	let bufMin = prices[ 0 ];
	let bufMax = prices[ 0 ];
	for ( let i = 1; i < count; i++ ) {
		if ( prices[ i ] < bufMin ) bufMin = prices[ i ];
		if ( prices[ i ] > bufMax ) bufMax = prices[ i ];
	}

	const range = bufMax - bufMin;
	const points = [];

	if ( range < 1e-12 ) {
		const mid = viewHeight / 2;
		for ( let i = 0; i < count; i++ ) {
			const x = ( i * viewWidth ) / ( count - 1 );
			points.push( `${ x.toFixed( 1 ) },${ mid.toFixed( 1 ) }` );
		}
	} else {
		const padding = range * 0.1;
		const yMin = bufMin - padding;
		const yRange = ( bufMax + padding ) - yMin;

		for ( let i = 0; i < count; i++ ) {
			const x = ( i * viewWidth ) / ( count - 1 );
			let y = viewHeight - ( ( prices[ i ] - yMin ) / yRange ) * viewHeight;
			y = Math.max( 0, Math.min( viewHeight, y ) );
			points.push( `${ x.toFixed( 1 ) },${ y.toFixed( 1 ) }` );
		}
	}

	const polyline = points.join( ' ' );
	const polygon = `${ polyline } ${ viewWidth },${viewHeight} 0,${ viewHeight }`;
	return { polyline, polygon };
}

// Sparkline buffers keyed by symbol key.
const sparklineBuffers = {};

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		get tickerItems() {
			return state._tickerItems || [];
		},
	},
	actions: {
		initTicker() {
			const ctx = getContext();
			const symbols = ctx.symbols || 'BTC/USDT,ETH/USDT';
			const refresh = ( ctx.refresh || 15 ) * 1000;
			const sparklineData = ctx.sparklineData || {};

			// Initialize ring buffers from server-rendered data.
			Object.entries( sparklineData ).forEach( ( [ key, data ] ) => {
				sparklineBuffers[ key ] = {
					prices: [ ...( data.prices || [] ) ],
					realCount: 0,
					lastUpdatedAt: data.updatedAt || 0,
				};
			} );

			const fetchTicker = async () => {
				try {
					const res = await fetch(
						`${ window.location.origin }/wp-json/xbo/v1/ticker?symbols=${ encodeURIComponent( symbols ) }`
					);
					const data = await res.json();
					if ( ! Array.isArray( data ) ) return;

					state._tickerItems = data;
					const now = Math.floor( Date.now() / 1000 );

					data.forEach( ( item ) => {
						const key = item.symbol.replace( /[^a-zA-Z0-9]/g, '' ).toLowerCase();
						const prevPrice = state[ `tickerPrice_${ key }` ];
						const newPrice = parseFloat( item.last_price ).toLocaleString( 'en-US', {
							minimumFractionDigits: 2,
							maximumFractionDigits: 8,
						} );
						state[ `tickerPrice_${ key }` ] = `$${ newPrice }`;
						state[ `tickerChange_${ key }` ] = `${ item.change_pct_24h >= 0 ? '+' : '' }${ parseFloat( item.change_pct_24h ).toFixed( 2 ) }%`;
						state[ `tickerUp_${ key }` ] = item.change_pct_24h >= 0;
						state[ `tickerDown_${ key }` ] = item.change_pct_24h < 0;

						// Flash animation.
						if ( prevPrice && prevPrice !== `$${ newPrice }` ) {
							const cards = document.querySelectorAll( '.xbo-mk-ticker__card' );
							cards.forEach( ( card ) => {
								if ( card.textContent.includes( item.symbol ) ) {
									const cls = item.change_pct_24h >= 0 ? 'xbo-mk-price-up' : 'xbo-mk-price-down';
									card.classList.add( cls );
									setTimeout( () => card.classList.remove( cls ), 2000 );
								}
							} );
						}

						// Update sparkline buffer.
						const buf = sparklineBuffers[ key ];
						if ( ! buf ) return;

						const lastPrice = parseFloat( item.last_price );
						if ( ! isFinite( lastPrice ) || lastPrice <= 0 ) return;

						// Skip stale snapshots (same cached response).
						if ( buf.lastUpdatedAt === now ) return;
						buf.lastUpdatedAt = now;

						// Push real point, shift left.
						buf.prices.push( lastPrice );
						if ( buf.prices.length > 40 ) {
							buf.prices.shift();
						}
						buf.realCount = Math.min( buf.realCount + 1, 40 );

						// Re-render SVG.
						const svg = document.querySelector(
							`.xbo-mk-ticker__sparkline[data-symbol="${ key }"]`
						);
						if ( ! svg ) return;

						const { polyline, polygon } = renderSvgPoints( buf.prices );
						const polylineEl = svg.querySelector( '.xbo-mk-ticker__sparkline-line' );
						const polygonEl = svg.querySelector( '.xbo-mk-ticker__sparkline-fill' );
						if ( polylineEl ) polylineEl.setAttribute( 'points', polyline );
						if ( polygonEl ) polygonEl.setAttribute( 'points', polygon );

						// Update trend color class.
						const trend = item.change_pct_24h >= 0 ? 'positive' : 'negative';
						svg.classList.remove( 'xbo-mk-ticker__sparkline--positive', 'xbo-mk-ticker__sparkline--negative' );
						svg.classList.add( `xbo-mk-ticker__sparkline--${ trend }` );
					} );
				} catch ( e ) {
					// Silently fail, will retry on next interval.
				}
			};

			fetchTicker();
			setInterval( fetchTicker, refresh );
		},
	},
} );
```

**Step 2: Verify JS syntax**

Run: `node --check wp-content/plugins/xbo-market-kit/assets/js/interactivity/ticker.js`

Expected: No syntax errors (exit code 0). Note: import statement will show an error in plain Node since it's an ES module intended for WP bundler — this is expected. Verify no other syntax issues.

**Step 3: Visual verification in browser**

Open `http://claude-code-hackathon-xbo-market-kit.local/` and verify:
- Sparklines render on page load (PHP pre-rendered)
- After ~15s, sparkline points update (check in DevTools by inspecting the `points` attribute)
- Console: no errors
- Gradient color matches trend direction

**Step 4: Commit**

```bash
git add assets/js/interactivity/ticker.js
git commit -m "feat(sparkline): add ring buffer and live SVG updates to ticker JS"
```

---

### Task 7: CSS Build — Rebuild Minified Bundle

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/dist/widgets.min.css` (or however the build works)

**Step 1: Check how CSS is built**

Run: `ls wp-content/plugins/xbo-market-kit/assets/css/dist/`

Look for a build script or Makefile. Check if there's a concat/minify step.

**Step 2: Rebuild CSS**

If there's a build script, run it. If CSS is manually concatenated, concatenate all `_*.css` source files and minify.

Check `scripts/` directory or plugin root for any build commands.

**Step 3: Verify the built CSS includes the new sparkline rules**

Run: `grep 'sparkline--positive' wp-content/plugins/xbo-market-kit/assets/css/dist/widgets.min.css`

Expected: Match found.

**Step 4: Commit**

```bash
git add assets/css/dist/
git commit -m "chore(sparkline): rebuild CSS bundle with sparkline trend styles"
```

---

### Task 8: Integration Tests

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/tests/Unit/Sparkline/SparklineGeneratorTest.php`

**Step 1: Add context serialization test**

Append to `SparklineGeneratorTest.php`:

```php
public function test_sparkline_data_is_json_serializable(): void {
	$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
	$data = array(
		'prices'    => $prices,
		'updatedAt' => 1772051460,
		'trend'     => $this->generator->get_trend_direction( 2.5 ),
	);

	$json = json_encode( $data );
	$this->assertNotFalse( $json );

	$decoded = json_decode( $json, true );
	$this->assertIsArray( $decoded );
	$this->assertCount( 40, $decoded['prices'] );
	$this->assertSame( 'positive', $decoded['trend'] );
}

public function test_multiple_symbols_produce_unique_gradients(): void {
	$symbols = array( 'BTC/USDT', 'ETH/USDT', 'SOL/USDT', 'BTC/USDT' );
	$ids = array();
	foreach ( $symbols as $symbol ) {
		$key = preg_replace( '/[^a-zA-Z0-9]/', '', strtolower( $symbol ) );
		$ids[] = 'spark-grad-' . $key . '-xbo-ticker-1';
	}
	// BTC appears twice — same widget, same key, same ID (expected).
	// But different symbols must produce different IDs.
	$unique = array_unique( $ids );
	$this->assertCount( 3, $unique ); // BTC, ETH, SOL — 3 unique.
}
```

**Step 2: Run all tests**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test`

Expected: All tests pass (20 total).

**Step 3: Run full quality checks**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs && composer run phpstan`

Expected: No errors.

**Step 4: Commit**

```bash
git add tests/Unit/Sparkline/SparklineGeneratorTest.php
git commit -m "test(sparkline): add JSON serialization and gradient ID uniqueness tests"
```

---

### Task 9: Browser Smoke Test

**Files:** None — manual verification only.

**Step 1: Open ticker page with single widget**

Navigate to a page with `[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT"]` and verify:
- All 4 sparklines render immediately (PHP pre-rendered)
- Each sparkline has a different curve shape
- Green sparklines for positive change, red for negative
- Gradient fill visible under each line

**Step 2: Wait for live updates**

Wait 30 seconds and verify in DevTools:
- Network: `/wp-json/xbo/v1/ticker` requests every ~15s
- Elements: SVG `points` attribute changes on at least one polyline
- Console: 0 errors

**Step 3: Test two widgets on same page**

Create a test page with two `[xbo_ticker]` blocks:
```
[xbo_ticker symbols="BTC/USDT,ETH/USDT"]
[xbo_ticker symbols="SOL/USDT,XRP/USDT"]
```

Verify:
- Both widgets render independently
- Gradient IDs are unique (inspect in DevTools — no shared IDs)
- Both widgets update independently

**Step 4: Test edge case — unknown symbol**

Add `[xbo_ticker symbols="FAKE/USDT"]` — verify no crash, sparkline simply doesn't render (no data).

---

## Summary

| Task | Description | Est. Steps |
|------|-------------|-----------|
| 1 | SparklineGenerator — seeded RNG + generatePrices() | 5 |
| 2 | SparklineGenerator — SVG point rendering | 5 |
| 3 | SparklineGenerator — edge case tests + trend direction | 6 |
| 4 | TickerShortcode — integrate generator | 5 |
| 5 | CSS — gradient and trend colors | 4 |
| 6 | JS — ring buffer and live SVG updates | 4 |
| 7 | CSS build — rebuild minified bundle | 4 |
| 8 | Integration tests | 4 |
| 9 | Browser smoke test | 4 |

**Total: 9 tasks, ~41 steps, 9 commits**
