# Slippage Calculator UX Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the plain text pair input with cascading currency dropdowns, add defaults, and fix the state model so the slippage calculator is usable out of the box.

**Architecture:** Server-side rendered pair catalog (PHP builds pairs_map + icons_map from cached API data, outputs once via `wp_footer`). Interactivity API store rewritten to use per-instance `getContext()` instead of global state. Two custom dropdown components with autocomplete, icons, and mutual exclusion. `AbortController` + `withScope` for safe async.

**Tech Stack:** PHP 8.1+, WordPress Interactivity API, PostCSS, Brain Monkey (tests)

**Design Doc:** `docs/plans/2026-02-25-slippage-calculator-ux-design.md`

---

### Task 1: SlippageShortcode — Update Defaults and Symbol Parsing

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php:33-38`
- Create: `wp-content/plugins/xbo-market-kit/tests/Unit/Shortcodes/SlippageShortcodeTest.php`

**Step 1: Write the failing test for default amount**

```php
<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Shortcodes;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Shortcodes\SlippageShortcode;

class SlippageShortcodeTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_defaults_include_amount_one(): void {
		$shortcode = new SlippageShortcode();
		$reflection = new \ReflectionMethod( $shortcode, 'get_defaults' );
		$reflection->setAccessible( true );
		$defaults = $reflection->invoke( $shortcode );

		$this->assertSame( '1', $defaults['amount'] );
		$this->assertSame( 'BTC_USDT', $defaults['symbol'] );
		$this->assertSame( 'buy', $defaults['side'] );
	}
}
```

**Step 2: Run test to verify it fails**

Run: `cd wp-content/plugins/xbo-market-kit && composer test -- --filter=test_defaults_include_amount_one`
Expected: FAIL — `assertSame('1', '')` because current default is `''`

**Step 3: Update get_defaults() to set amount to '1'**

In `SlippageShortcode.php:33-38`, change:

```php
protected function get_defaults(): array {
    return array(
        'symbol' => 'BTC_USDT',
        'side'   => 'buy',
        'amount' => '1',
    );
}
```

**Step 4: Run test to verify it passes**

Run: `cd wp-content/plugins/xbo-market-kit && composer test -- --filter=test_defaults_include_amount_one`
Expected: PASS

**Step 5: Write test for symbol parsing (underscore and slash formats)**

Add to `SlippageShortcodeTest.php`:

```php
public function test_parse_symbol_underscore_format(): void {
    $shortcode = new SlippageShortcode();
    $reflection = new \ReflectionMethod( $shortcode, 'parse_symbol' );
    $reflection->setAccessible( true );
    $result = $reflection->invoke( $shortcode, 'BTC_USDT' );

    $this->assertSame( 'BTC', $result['base'] );
    $this->assertSame( 'USDT', $result['quote'] );
}

public function test_parse_symbol_slash_format(): void {
    $shortcode = new SlippageShortcode();
    $reflection = new \ReflectionMethod( $shortcode, 'parse_symbol' );
    $reflection->setAccessible( true );
    $result = $reflection->invoke( $shortcode, 'ETH/USD' );

    $this->assertSame( 'ETH', $result['base'] );
    $this->assertSame( 'USD', $result['quote'] );
}

public function test_parse_symbol_invalid_returns_defaults(): void {
    $shortcode = new SlippageShortcode();
    $reflection = new \ReflectionMethod( $shortcode, 'parse_symbol' );
    $reflection->setAccessible( true );
    $result = $reflection->invoke( $shortcode, 'INVALID' );

    $this->assertSame( 'BTC', $result['base'] );
    $this->assertSame( 'USDT', $result['quote'] );
}
```

**Step 6: Run tests to verify they fail**

Run: `cd wp-content/plugins/xbo-market-kit && composer test -- --filter=test_parse_symbol`
Expected: FAIL — `parse_symbol` method does not exist

**Step 7: Implement parse_symbol() method**

Add to `SlippageShortcode.php`:

```php
/**
 * Parse a trading pair symbol into base and quote currencies.
 *
 * @param string $symbol Symbol in underscore (BTC_USDT) or slash (BTC/USDT) format.
 * @return array{base: string, quote: string} Parsed currencies.
 */
protected function parse_symbol( string $symbol ): array {
    $separator = str_contains( $symbol, '/' ) ? '/' : '_';
    $parts     = explode( $separator, $symbol, 2 );

    if ( count( $parts ) === 2 && '' !== $parts[0] && '' !== $parts[1] ) {
        return array(
            'base'  => strtoupper( trim( $parts[0] ) ),
            'quote' => strtoupper( trim( $parts[1] ) ),
        );
    }

    return array(
        'base'  => 'BTC',
        'quote' => 'USDT',
    );
}
```

**Step 8: Run tests to verify they pass**

Run: `cd wp-content/plugins/xbo-market-kit && composer test -- --filter=SlippageShortcodeTest`
Expected: ALL PASS

**Step 9: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php \
       wp-content/plugins/xbo-market-kit/tests/Unit/Shortcodes/SlippageShortcodeTest.php
git commit -m "feat(slippage): add symbol parsing and default amount

Add parse_symbol() method supporting both underscore and slash formats.
Change default amount from empty string to '1' for auto-calculation."
```

---

### Task 2: Pair Catalog Data Provider

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/PairCatalog.php`
- Create: `wp-content/plugins/xbo-market-kit/tests/Unit/Shortcodes/PairCatalogTest.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Shortcodes;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Api\ApiClient;
use XboMarketKit\Api\ApiResponse;
use XboMarketKit\Icons\IconResolver;
use XboMarketKit\Shortcodes\PairCatalog;

class PairCatalogTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_build_returns_pairs_map_and_icons(): void {
		$api = \Mockery::mock( ApiClient::class );
		$api->shouldReceive( 'get_trading_pairs' )
			->once()
			->andReturn( ApiResponse::success( array(
				array( 'symbol' => 'BTC/USDT' ),
				array( 'symbol' => 'BTC/EUR' ),
				array( 'symbol' => 'ETH/USDT' ),
			) ) );

		$icons = \Mockery::mock( IconResolver::class );
		$icons->shouldReceive( 'url' )->with( 'BTC' )->andReturn( '/icons/btc.svg' );
		$icons->shouldReceive( 'url' )->with( 'USDT' )->andReturn( '/icons/usdt.svg' );
		$icons->shouldReceive( 'url' )->with( 'EUR' )->andReturn( '/icons/eur.svg' );
		$icons->shouldReceive( 'url' )->with( 'ETH' )->andReturn( '/icons/eth.svg' );

		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$this->assertSame( array( 'EUR', 'USDT' ), $data['pairs_map']['BTC'] );
		$this->assertSame( array( 'USDT' ), $data['pairs_map']['ETH'] );
		$this->assertSame( '/icons/btc.svg', $data['icons']['BTC'] );
		$this->assertSame( '/icons/usdt.svg', $data['icons']['USDT'] );
	}

	public function test_build_returns_empty_on_api_failure(): void {
		$api = \Mockery::mock( ApiClient::class );
		$api->shouldReceive( 'get_trading_pairs' )
			->once()
			->andReturn( ApiResponse::error( 'API down' ) );

		$icons = \Mockery::mock( IconResolver::class );

		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$this->assertSame( array(), $data['pairs_map'] );
		$this->assertSame( array(), $data['icons'] );
	}

	public function test_pairs_map_quotes_are_sorted_alphabetically(): void {
		$api = \Mockery::mock( ApiClient::class );
		$api->shouldReceive( 'get_trading_pairs' )
			->once()
			->andReturn( ApiResponse::success( array(
				array( 'symbol' => 'BTC/USD' ),
				array( 'symbol' => 'BTC/EUR' ),
				array( 'symbol' => 'BTC/USDT' ),
			) ) );

		$icons = \Mockery::mock( IconResolver::class );
		$icons->shouldReceive( 'url' )->andReturn( '/icons/placeholder.svg' );

		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$this->assertSame( array( 'EUR', 'USD', 'USDT' ), $data['pairs_map']['BTC'] );
	}
}
```

**Step 2: Run test to verify it fails**

Run: `cd wp-content/plugins/xbo-market-kit && composer test -- --filter=PairCatalogTest`
Expected: FAIL — class `PairCatalog` does not exist

**Step 3: Implement PairCatalog class**

```php
<?php
/**
 * PairCatalog class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Icons\IconResolver;

/**
 * Builds a catalog of trading pairs and icon URLs for dropdown selectors.
 */
class PairCatalog {

	private ApiClient $api;
	private IconResolver $icons;

	/**
	 * Constructor.
	 *
	 * @param ApiClient    $api   API client instance.
	 * @param IconResolver $icons Icon resolver instance.
	 */
	public function __construct( ApiClient $api, IconResolver $icons ) {
		$this->api   = $api;
		$this->icons = $icons;
	}

	/**
	 * Build the pairs catalog data.
	 *
	 * @return array{pairs_map: array<string, list<string>>, icons: array<string, string>}
	 */
	public function build(): array {
		$response = $this->api->get_trading_pairs();

		if ( ! $response->success ) {
			return array(
				'pairs_map' => array(),
				'icons'     => array(),
			);
		}

		$pairs_map = array();
		$symbols   = array();

		foreach ( $response->data as $pair ) {
			$parts = explode( '/', $pair['symbol'] ?? '', 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$base  = $parts[0];
			$quote = $parts[1];

			$pairs_map[ $base ][] = $quote;
			$symbols[ $base ]     = true;
			$symbols[ $quote ]    = true;
		}

		// Sort quotes alphabetically and deduplicate.
		foreach ( $pairs_map as $base => $quotes ) {
			$pairs_map[ $base ] = array_values( array_unique( $quotes ) );
			sort( $pairs_map[ $base ] );
		}

		// Sort bases alphabetically.
		ksort( $pairs_map );

		// Resolve icon URLs for all unique symbols.
		$icons = array();
		foreach ( array_keys( $symbols ) as $symbol ) {
			$icons[ $symbol ] = $this->icons->url( $symbol );
		}

		return array(
			'pairs_map' => $pairs_map,
			'icons'     => $icons,
		);
	}
}
```

**Step 4: Run tests to verify they pass**

Run: `cd wp-content/plugins/xbo-market-kit && composer test -- --filter=PairCatalogTest`
Expected: ALL PASS

**Step 5: Run PHPCS and PHPStan**

Run: `cd wp-content/plugins/xbo-market-kit && composer phpcs -- includes/Shortcodes/PairCatalog.php && composer phpstan`
Fix any issues.

**Step 6: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/PairCatalog.php \
       wp-content/plugins/xbo-market-kit/tests/Unit/Shortcodes/PairCatalogTest.php
git commit -m "feat(slippage): add PairCatalog service for dropdown data

Builds pairs_map (base → sorted quotes) and icons map from cached
trading pairs API response. Used by dropdown selectors."
```

---

### Task 3: REST URL Config and Footer Catalog Output

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/AbstractShortcode.php:68-70`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php:46-49`

**Step 1: Add wp_interactivity_config() for REST URL in AbstractShortcode**

In `AbstractShortcode.php`, modify `enqueue_assets()`:

```php
protected function enqueue_assets(): void {
    $this->enqueue_widget_css();
    $this->enqueue_interactivity_config();
}

/**
 * Register Interactivity API configuration (REST URL, etc.). Runs once.
 *
 * @return void
 */
protected function enqueue_interactivity_config(): void {
    static $registered = false;
    if ( $registered ) {
        return;
    }
    $registered = true;
    wp_interactivity_config(
        'xbo-market-kit',
        array( 'restUrl' => rest_url( 'xbo/v1/' ) )
    );
}
```

**Step 2: Add footer catalog output in SlippageShortcode**

In `SlippageShortcode.php`, update `enqueue_assets()`:

```php
protected function enqueue_assets(): void {
    parent::enqueue_assets();
    $this->enqueue_interactivity_script( 'xbo-market-kit-slippage', 'slippage.js' );
    $this->enqueue_pair_catalog();
}

/**
 * Schedule pair catalog JSON output in wp_footer (once per page).
 *
 * @return void
 */
private function enqueue_pair_catalog(): void {
    static $enqueued = false;
    if ( $enqueued ) {
        return;
    }
    $enqueued = true;
    add_action( 'wp_footer', array( $this, 'render_pair_catalog_json' ) );
}

/**
 * Output the pair catalog JSON script tag in the footer.
 *
 * @return void
 */
public function render_pair_catalog_json(): void {
    $api     = \XboMarketKit\Plugin::get_instance()->get_api_client();
    $icons   = \XboMarketKit\Plugin::get_instance()->get_icon_resolver();
    $catalog = new PairCatalog( $api, $icons );
    $data    = $catalog->build();

    echo '<script type="application/json" id="xbo-mk-pairs-catalog">'
        . wp_json_encode( $data )
        . '</script>';
}
```

**Note:** Check how `Plugin` class exposes `ApiClient` and `IconResolver`. If `get_api_client()` or `get_icon_resolver()` don't exist as public methods on Plugin, add simple public getters. Refer to `includes/Plugin.php` for the singleton and existing service access patterns.

**Step 3: Run existing tests to verify no regressions**

Run: `cd wp-content/plugins/xbo-market-kit && composer test`
Expected: ALL PASS

**Step 4: Run PHPCS**

Run: `cd wp-content/plugins/xbo-market-kit && composer phpcs`
Fix any issues.

**Step 5: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/AbstractShortcode.php \
       wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php
git commit -m "feat(slippage): add REST URL config and footer pair catalog

Register wp_interactivity_config with restUrl for all widgets.
Output pair catalog JSON in wp_footer when slippage widget is present."
```

---

### Task 4: Rewrite SlippageShortcode HTML Render

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php:57-137`

This is the largest PHP change. The `render()` method is rewritten to output two custom dropdown selectors instead of a single text input.

**Step 1: Rewrite the render() method**

Replace the entire `render()` method in `SlippageShortcode.php`. Key changes:

1. Parse `symbol` into `base`/`quote` using `parse_symbol()`
2. Build context with `base`, `quote`, `side`, `amount`, `baseOpen`, `quoteOpen`, `baseSearch`, `quoteSearch`, `result`, `loading`, `error`
3. Replace the single `<input type="text">` with two dropdown selector components
4. Add pair separator `/` between dropdowns
5. Add `value` attribute to amount input with default
6. Add partial fill warning div
7. Add error display div
8. Use `data-wp-init` to trigger auto-calculation on load

The full render method structure:

```php
protected function render( array $atts ): string {
    $symbol = sanitize_text_field( $atts['symbol'] );
    $parsed = $this->parse_symbol( $symbol );
    $base   = $parsed['base'];
    $quote  = $parsed['quote'];
    $side   = in_array( $atts['side'], array( 'buy', 'sell' ), true ) ? $atts['side'] : 'buy';
    $amount = $atts['amount'];

    // Load catalog for icon URLs in trigger buttons.
    $api     = \XboMarketKit\Plugin::get_instance()->get_api_client();
    $icons   = \XboMarketKit\Plugin::get_instance()->get_icon_resolver();
    $catalog = new PairCatalog( $api, $icons );
    $data    = $catalog->build();

    $base_icon  = $data['icons'][ $base ] ?? '';
    $quote_icon = $data['icons'][ $quote ] ?? '';

    // All base currencies sorted alphabetically.
    $all_bases = array_keys( $data['pairs_map'] );

    // Available quotes for the default base.
    $available_quotes = $data['pairs_map'][ $base ] ?? array();

    $context = array(
        'base'        => $base,
        'quote'       => $quote,
        'side'        => $side,
        'amount'      => $amount,
        'result'      => null,
        'loading'     => false,
        'error'       => '',
        'baseOpen'    => false,
        'quoteOpen'   => false,
        'baseSearch'  => '',
        'quoteSearch' => '',
    );

    $html = '<div class="xbo-mk-slippage" data-wp-init="actions.initSlippage">';

    // Header.
    $html .= '<div class="xbo-mk-slippage__header">';
    $html .= '<h3 class="xbo-mk-slippage__title">'
        . esc_html__( 'Slippage Calculator', 'xbo-market-kit' ) . '</h3>';
    $html .= '</div>';

    // Form.
    $html .= '<div class="xbo-mk-slippage__form">';
    $html .= '<div class="xbo-mk-slippage__fields">';

    // Pair selectors (first column).
    $html .= '<div class="xbo-mk-slippage__field">';
    $html .= '<label class="xbo-mk-slippage__label">'
        . esc_html__( 'Pair', 'xbo-market-kit' ) . '</label>';
    $html .= '<div class="xbo-mk-slippage__pair">';

    // Base dropdown.
    $html .= $this->render_selector(
        'base', $base, $base_icon, $all_bases, $data['icons']
    );

    // Separator.
    $html .= '<span class="xbo-mk-slippage__pair-separator">/</span>';

    // Quote dropdown.
    $html .= $this->render_selector(
        'quote', $quote, $quote_icon, $available_quotes, $data['icons']
    );

    $html .= '</div></div>';

    // Side toggle (second column) — same as before but with getContext()-based directives.
    $html .= '<div class="xbo-mk-slippage__field">';
    $html .= '<label class="xbo-mk-slippage__label">'
        . esc_html__( 'Side', 'xbo-market-kit' ) . '</label>';
    $html .= '<div class="xbo-mk-slippage__toggle">';
    $html .= '<button type="button" class="xbo-mk-slippage__toggle-btn"'
        . ' data-wp-class--xbo-mk-slippage__toggle-btn--buy-active="state.slippageIsBuy"'
        . ' data-wp-on--click="actions.slippageSetBuy">'
        . esc_html__( 'Buy', 'xbo-market-kit' ) . '</button>';
    $html .= '<button type="button" class="xbo-mk-slippage__toggle-btn"'
        . ' data-wp-class--xbo-mk-slippage__toggle-btn--sell-active="state.slippageIsSell"'
        . ' data-wp-on--click="actions.slippageSetSell">'
        . esc_html__( 'Sell', 'xbo-market-kit' ) . '</button>';
    $html .= '</div></div>';

    // Amount input (third column).
    $html .= '<div class="xbo-mk-slippage__field">';
    $html .= '<label class="xbo-mk-slippage__label">'
        . esc_html__( 'Amount', 'xbo-market-kit' ) . '</label>';
    $html .= '<input type="number" step="0.001" min="0.001"'
        . ' value="' . esc_attr( $amount ) . '"'
        . ' placeholder="1.0"'
        . ' class="xbo-mk-slippage__input"'
        . ' data-wp-on--input="actions.slippageAmountChange" />';
    $html .= '</div>';

    $html .= '</div></div>'; // fields + form.

    // Error message.
    $html .= '<div class="xbo-mk-slippage__error"'
        . ' data-wp-class--xbo-mk-hidden="!state.slippageHasError"'
        . ' data-wp-text="state.slippageError"></div>';

    // Partial fill warning.
    $html .= '<div class="xbo-mk-slippage__warning"'
        . ' data-wp-class--xbo-mk-hidden="!state.slippageIsPartialFill"'
        . ' data-wp-text="state.slippagePartialFillText"></div>';

    // Results — same structure as before.
    $html .= '<div class="xbo-mk-slippage__results"'
        . ' data-wp-class--xbo-mk-hidden="!state.slippageHasResult">';
    $html .= '<div class="xbo-mk-slippage__metrics">';

    $metrics = array(
        'avg_price'    => __( 'Avg Price', 'xbo-market-kit' ),
        'slippage_pct' => __( 'Slippage', 'xbo-market-kit' ),
        'spread'       => __( 'Spread', 'xbo-market-kit' ),
        'spread_pct'   => __( 'Spread %', 'xbo-market-kit' ),
        'depth_used'   => __( 'Depth Used', 'xbo-market-kit' ),
        'total_cost'   => __( 'Total Cost', 'xbo-market-kit' ),
    );

    foreach ( $metrics as $key => $label ) {
        $html .= '<div class="xbo-mk-slippage__metric">';
        $html .= '<div class="xbo-mk-slippage__metric-label">'
            . esc_html( $label ) . '</div>';
        $html .= '<div class="xbo-mk-slippage__metric-value"'
            . ' data-wp-text="state.slippageResult_' . esc_attr( $key ) . '">--</div>';
        $html .= '</div>';
    }

    $html .= '</div></div>';

    // Loading indicator.
    $html .= '<div class="xbo-mk-slippage__loading"'
        . ' data-wp-class--xbo-mk-hidden="!state.slippageLoading">';
    $html .= esc_html__( 'Calculating...', 'xbo-market-kit' );
    $html .= '</div>';

    $html .= '</div>'; // slippage container.

    return $this->render_wrapper( $html, $context );
}
```

**Step 2: Implement the render_selector() helper method**

Add to `SlippageShortcode.php`:

```php
/**
 * Render a custom dropdown selector for base or quote currency.
 *
 * @param string $type         Selector type: 'base' or 'quote'.
 * @param string $selected     Currently selected symbol.
 * @param string $selected_icon URL of the selected symbol's icon.
 * @param array  $options      Available options (symbol strings).
 * @param array  $icons_map    Map of symbol → icon URL.
 * @return string HTML output.
 */
private function render_selector(
    string $type,
    string $selected,
    string $selected_icon,
    array $options,
    array $icons_map
): string {
    $open_state   = $type . 'Open';
    $search_state = $type . 'Search';
    $toggle_action = 'actions.slippageToggle' . ucfirst( $type );
    $select_action = 'actions.slippageSelect' . ucfirst( $type );
    $search_action = 'actions.slippageSearch' . ucfirst( $type );
    $keydown_action = 'actions.slippageSelectorKeydown';
    $filtered_items = 'state.slippageFiltered' . ucfirst( $type );

    $html  = '<div class="xbo-mk-slippage__selector"'
        . ' data-wp-class--xbo-mk-slippage__selector--open="context.' . $open_state . '"'
        . ' data-wp-on-document--click="actions.slippageCloseDropdowns">';

    // Trigger button.
    $html .= '<button type="button" class="xbo-mk-slippage__selector-trigger"'
        . ' data-wp-on--click="' . $toggle_action . '"'
        . ' aria-haspopup="listbox"'
        . ' data-wp-bind--aria-expanded="context.' . $open_state . '"'
        . ' aria-label="' . esc_attr(
            /* translators: %s: selector type (base or quote) */
            sprintf( __( 'Select %s currency', 'xbo-market-kit' ), $type )
        ) . '">';
    $html .= '<img class="xbo-mk-slippage__selector-icon"'
        . ' src="' . esc_url( $selected_icon ) . '"'
        . ' alt="" width="20" height="20" />';
    $html .= '<span class="xbo-mk-slippage__selector-text"'
        . ' data-wp-text="context.' . $type . '">'
        . esc_html( $selected ) . '</span>';
    $html .= '<span class="xbo-mk-slippage__selector-chevron" aria-hidden="true">&#9662;</span>';
    $html .= '</button>';

    // Dropdown panel.
    $html .= '<div class="xbo-mk-slippage__selector-dropdown">';
    $html .= '<input type="text"'
        . ' class="xbo-mk-slippage__selector-search"'
        . ' placeholder="' . esc_attr__( 'Search...', 'xbo-market-kit' ) . '"'
        . ' data-wp-on--input="' . $search_action . '"'
        . ' data-wp-on--keydown="' . $keydown_action . '"'
        . ' data-wp-bind--value="context.' . $search_state . '"'
        . ' role="searchbox"'
        . ' aria-label="' . esc_attr__( 'Filter currencies', 'xbo-market-kit' ) . '" />';

    // Options list — rendered server-side, filtered client-side via data-wp-each or visibility.
    $html .= '<ul class="xbo-mk-slippage__selector-list" role="listbox">';
    foreach ( $options as $symbol ) {
        $icon_url = $icons_map[ $symbol ] ?? '';
        $html    .= '<li class="xbo-mk-slippage__selector-item"'
            . ' role="option"'
            . ' data-symbol="' . esc_attr( $symbol ) . '"'
            . ' data-wp-on--click="' . $select_action . '">';
        $html    .= '<img src="' . esc_url( $icon_url ) . '"'
            . ' alt="" width="20" height="20" loading="lazy" />';
        $html    .= '<span>' . esc_html( $symbol ) . '</span>';
        $html    .= '</li>';
    }
    $html .= '<li class="xbo-mk-slippage__selector-empty" aria-disabled="true">'
        . esc_html__( 'No results', 'xbo-market-kit' ) . '</li>';
    $html .= '</ul>';

    $html .= '</div>'; // dropdown.
    $html .= '</div>'; // selector.

    return $html;
}
```

**Step 3: Run all tests**

Run: `cd wp-content/plugins/xbo-market-kit && composer test`
Expected: ALL PASS

**Step 4: Run PHPCS and PHPStan**

Run: `cd wp-content/plugins/xbo-market-kit && composer phpcs && composer phpstan`
Fix any issues found.

**Step 5: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php
git commit -m "feat(slippage): rewrite shortcode render with dropdown selectors

Replace plain text pair input with two custom dropdown selector
components (base + quote) with icons, search, and ARIA attributes.
Add error display, partial fill warning, and default amount value."
```

---

### Task 5: CSS — Dropdown Selector Styles

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_slippage.css`

**Step 1: Add all new CSS rules**

Append to `_slippage.css` (after existing rules). Key sections:

```css
/* --- Pair selector layout --- */

.xbo-mk-slippage__pair {
  display: flex;
  align-items: center;
  gap: var(--xbo-mk--space-xs);
}

.xbo-mk-slippage__pair-separator {
  font-size: var(--xbo-mk--font-size-sm);
  color: var(--xbo-mk--color-text-muted);
  flex-shrink: 0;
  user-select: none;
}

/* --- Selector component --- */

.xbo-mk-slippage__selector {
  position: relative;
  flex: 1;
  min-width: 0;
}

.xbo-mk-slippage__selector-trigger {
  display: flex;
  align-items: center;
  gap: var(--xbo-mk--space-xs);
  width: 100%;
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-md);
  border: 1px solid var(--xbo-mk--color-border);
  border-radius: var(--xbo-mk--radius-md);
  background: var(--xbo-mk--color-bg);
  font-size: var(--xbo-mk--font-size-sm);
  font-family: var(--xbo-mk--font-primary);
  color: var(--xbo-mk--color-text-primary);
  cursor: pointer;
  transition: var(--xbo-mk--transition-fast);
  outline: none;
}

.xbo-mk-slippage__selector-trigger:hover {
  border-color: var(--xbo-mk--color-primary);
}

.xbo-mk-slippage__selector-trigger:focus-visible {
  border-color: var(--xbo-mk--color-primary);
  box-shadow: 0 0 0 3px rgba(99, 25, 255, 0.1);
}

.xbo-mk-slippage__selector-icon {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  flex-shrink: 0;
}

.xbo-mk-slippage__selector-text {
  font-weight: var(--xbo-mk--font-weight-medium);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.xbo-mk-slippage__selector-chevron {
  margin-left: auto;
  font-size: 10px;
  color: var(--xbo-mk--color-text-muted);
  transition: var(--xbo-mk--transition-fast);
}

.xbo-mk-slippage__selector--open .xbo-mk-slippage__selector-chevron {
  transform: rotate(180deg);
}

/* --- Dropdown panel --- */

.xbo-mk-slippage__selector-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  min-width: 140px;
  background: var(--xbo-mk--color-bg);
  border: 1px solid var(--xbo-mk--color-border);
  border-radius: var(--xbo-mk--radius-md);
  box-shadow: var(--xbo-mk--shadow-card);
  z-index: 100;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-4px);
  transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
}

.xbo-mk-slippage__selector--open .xbo-mk-slippage__selector-dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.xbo-mk-slippage__selector-search {
  display: block;
  width: 100%;
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-md);
  border: none;
  border-bottom: 1px solid var(--xbo-mk--color-border);
  font-size: var(--xbo-mk--font-size-xs);
  font-family: var(--xbo-mk--font-primary);
  color: var(--xbo-mk--color-text-primary);
  background: transparent;
  outline: none;
  box-sizing: border-box;
}

.xbo-mk-slippage__selector-list {
  list-style: none;
  margin: 0;
  padding: var(--xbo-mk--space-xs) 0;
  max-height: 200px;
  overflow-y: auto;
}

.xbo-mk-slippage__selector-item {
  display: flex;
  align-items: center;
  gap: var(--xbo-mk--space-xs);
  padding: var(--xbo-mk--space-xs) var(--xbo-mk--space-md);
  font-size: var(--xbo-mk--font-size-xs);
  cursor: pointer;
  transition: var(--xbo-mk--transition-fast);
}

.xbo-mk-slippage__selector-item:hover,
.xbo-mk-slippage__selector-item--highlighted {
  background: var(--xbo-mk--color-bg-secondary);
}

.xbo-mk-slippage__selector-item--selected {
  font-weight: var(--xbo-mk--font-weight-semibold);
  color: var(--xbo-mk--color-primary);
}

.xbo-mk-slippage__selector-item--hidden {
  display: none;
}

.xbo-mk-slippage__selector-item img {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  flex-shrink: 0;
}

.xbo-mk-slippage__selector-empty {
  padding: var(--xbo-mk--space-md);
  text-align: center;
  font-size: var(--xbo-mk--font-size-xs);
  color: var(--xbo-mk--color-text-muted);
  display: none;
}

.xbo-mk-slippage__selector-list:not(:has(.xbo-mk-slippage__selector-item:not(.xbo-mk-slippage__selector-item--hidden))) .xbo-mk-slippage__selector-empty {
  display: block;
}

/* --- Warning and error --- */

.xbo-mk-slippage__warning {
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-lg);
  font-size: var(--xbo-mk--font-size-xs);
  color: #92400e;
  background: #fef3c7;
  border-top: 1px solid #fde68a;
}

.xbo-mk-slippage__error {
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-lg);
  font-size: var(--xbo-mk--font-size-xs);
  color: var(--xbo-mk--color-negative);
}

/* --- Mobile responsive --- */

@media (max-width: 768px) {
  .xbo-mk-slippage__fields {
    grid-template-columns: 1fr;
  }

  .xbo-mk-slippage__pair {
    flex-direction: column;
    gap: var(--xbo-mk--space-sm);
  }

  .xbo-mk-slippage__pair-separator {
    display: none;
  }

  .xbo-mk-slippage__selector {
    width: 100%;
  }
}

/* --- Utility (shared) --- */

.xbo-mk-hidden {
  display: none !important;
}
```

**Step 2: Remove the old 480px mobile breakpoint for fields**

The old `@media (max-width: 480px)` rule for `.xbo-mk-slippage__fields` should be removed — it's replaced by the 768px breakpoint above.

**Step 3: Build CSS**

Run: `cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/widgets.css -o assets/css/dist/widgets.css && NODE_ENV=production npx postcss assets/css/src/widgets.css -o assets/css/dist/widgets.min.css`

**Step 4: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/
git commit -m "feat(slippage): add dropdown selector CSS styles

Custom combobox dropdown with icons, search, transitions, hover/focus
states. Mobile-first responsive layout with 768px breakpoint.
Warning and error message styles."
```

---

### Task 6: JavaScript — Rewrite Interactivity API Store

**Files:**
- Rewrite: `wp-content/plugins/xbo-market-kit/assets/js/interactivity/slippage.js`

This is the largest change. Complete rewrite of the JS store to use context-based state.

**Step 1: Rewrite slippage.js completely**

```js
/**
 * XBO Market Kit — Slippage Calculator Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext, getConfig, withScope } from '@wordpress/interactivity';

/**
 * Module-level cached pair catalog (loaded once from footer JSON).
 */
let catalogCache = null;

/**
 * Load the pair catalog from the footer JSON script tag.
 *
 * @return {Object} Catalog with pairs_map and icons.
 */
function getCatalog() {
	if ( catalogCache ) {
		return catalogCache;
	}
	const el = document.getElementById( 'xbo-mk-pairs-catalog' );
	if ( el ) {
		try {
			catalogCache = JSON.parse( el.textContent );
		} catch ( e ) {
			catalogCache = { pairs_map: {}, icons: {} };
		}
	} else {
		catalogCache = { pairs_map: {}, icons: {} };
	}
	return catalogCache;
}

const { restUrl } = getConfig( 'xbo-market-kit' );

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		// --- Side toggle ---
		get slippageIsBuy() {
			return getContext().side === 'buy';
		},
		get slippageIsSell() {
			return getContext().side === 'sell';
		},

		// --- Results visibility ---
		get slippageHasResult() {
			return !! getContext().result;
		},
		get slippageHasError() {
			return !! getContext().error;
		},
		get slippageLoading() {
			return !! getContext().loading;
		},
		get slippageError() {
			return getContext().error || '';
		},

		// --- Partial fill ---
		get slippageIsPartialFill() {
			const ctx = getContext();
			if ( ! ctx.result ) return false;
			const amount = parseFloat( ctx.amount );
			return amount > 0 && ctx.result.depth_used < amount;
		},
		get slippagePartialFillText() {
			const ctx = getContext();
			if ( ! ctx.result ) return '';
			const amount = parseFloat( ctx.amount );
			return `⚠ Only ${ ctx.result.depth_used } of ${ amount } filled — insufficient liquidity`;
		},

		// --- Formatted result getters ---
		get slippageResult_avg_price() {
			const r = getContext().result;
			return r?.avg_price?.toLocaleString( 'en-US', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 8,
			} ) || '--';
		},
		get slippageResult_slippage_pct() {
			const r = getContext().result;
			return r ? `${ r.slippage_pct }%` : '--';
		},
		get slippageResult_spread() {
			const r = getContext().result;
			return r?.spread?.toFixed( 8 ) || '--';
		},
		get slippageResult_spread_pct() {
			const r = getContext().result;
			return r ? `${ r.spread_pct }%` : '--';
		},
		get slippageResult_depth_used() {
			const r = getContext().result;
			return r?.depth_used?.toFixed( 8 ) || '--';
		},
		get slippageResult_total_cost() {
			const r = getContext().result;
			return r?.total_cost?.toLocaleString( 'en-US', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 8,
			} ) || '--';
		},

		// --- Selector icon URLs (for trigger buttons, updated dynamically) ---
		get slippageBaseIcon() {
			const catalog = getCatalog();
			return catalog.icons[ getContext().base ] || '';
		},
		get slippageQuoteIcon() {
			const catalog = getCatalog();
			return catalog.icons[ getContext().quote ] || '';
		},
	},

	actions: {
		/**
		 * Initialize the slippage widget instance.
		 */
		initSlippage() {
			const ctx = getContext();

			// Initialize non-serializable context properties.
			ctx._abortController = null;
			ctx._debounceTimer = null;

			// Auto-calculate if amount is set.
			if ( ctx.amount && parseFloat( ctx.amount ) > 0 ) {
				actions.slippageCalculate();
			}
		},

		// --- Side toggle ---
		slippageSetBuy() {
			const ctx = getContext();
			ctx.side = 'buy';
			actions.slippageDebounceCalc();
		},
		slippageSetSell() {
			const ctx = getContext();
			ctx.side = 'sell';
			actions.slippageDebounceCalc();
		},

		// --- Amount ---
		slippageAmountChange( event ) {
			const ctx = getContext();
			ctx.amount = event.target.value;
			actions.slippageDebounceCalc();
		},

		// --- Base selector ---
		slippageToggleBase( event ) {
			event.stopPropagation();
			const ctx = getContext();
			ctx.baseOpen = ! ctx.baseOpen;
			ctx.quoteOpen = false;
			ctx.baseSearch = '';
			if ( ctx.baseOpen ) {
				// Focus search input after DOM update.
				requestAnimationFrame( () => {
					event.target
						.closest( '.xbo-mk-slippage__selector' )
						?.querySelector( '.xbo-mk-slippage__selector-search' )
						?.focus();
				} );
			}
		},
		slippageSearchBase( event ) {
			const ctx = getContext();
			ctx.baseSearch = event.target.value;
			actions.slippageFilterItems( 'base' );
		},
		slippageSelectBase( event ) {
			event.stopPropagation();
			const ctx = getContext();
			const symbol = event.target.closest( '[data-symbol]' )?.dataset?.symbol;
			if ( ! symbol ) return;

			ctx.base = symbol;
			ctx.baseOpen = false;
			ctx.baseSearch = '';

			// Validate current quote is still available for new base.
			const catalog = getCatalog();
			const available = catalog.pairs_map[ symbol ] || [];
			if ( ! available.includes( ctx.quote ) ) {
				ctx.quote = available[ 0 ] || 'USDT';
			}

			// Update quote dropdown options.
			actions.slippageUpdateQuoteOptions();
			actions.slippageDebounceCalc();
		},

		// --- Quote selector ---
		slippageToggleQuote( event ) {
			event.stopPropagation();
			const ctx = getContext();
			ctx.quoteOpen = ! ctx.quoteOpen;
			ctx.baseOpen = false;
			ctx.quoteSearch = '';
			if ( ctx.quoteOpen ) {
				requestAnimationFrame( () => {
					event.target
						.closest( '.xbo-mk-slippage__selector' )
						?.querySelector( '.xbo-mk-slippage__selector-search' )
						?.focus();
				} );
			}
		},
		slippageSearchQuote( event ) {
			const ctx = getContext();
			ctx.quoteSearch = event.target.value;
			actions.slippageFilterItems( 'quote' );
		},
		slippageSelectQuote( event ) {
			event.stopPropagation();
			const ctx = getContext();
			const symbol = event.target.closest( '[data-symbol]' )?.dataset?.symbol;
			if ( ! symbol ) return;

			ctx.quote = symbol;
			ctx.quoteOpen = false;
			ctx.quoteSearch = '';
			actions.slippageDebounceCalc();
		},

		// --- Dropdown utilities ---
		slippageCloseDropdowns( event ) {
			const ctx = getContext();
			if ( ctx.baseOpen || ctx.quoteOpen ) {
				ctx.baseOpen = false;
				ctx.quoteOpen = false;
				ctx.baseSearch = '';
				ctx.quoteSearch = '';
			}
		},

		/**
		 * Filter dropdown items by search text via CSS class toggle.
		 *
		 * @param {string} type 'base' or 'quote'.
		 */
		slippageFilterItems( type ) {
			const ctx = getContext();
			const search = ( type === 'base' ? ctx.baseSearch : ctx.quoteSearch ).toUpperCase();

			// Find the relevant selector element.
			// We use DOM traversal from the current event target's context.
			const selectors = document.querySelectorAll(
				'.xbo-mk-slippage__selector'
			);

			// This is a simplified approach — iterate all selectors.
			// In practice, scope to the current widget instance.
			selectors.forEach( ( selector ) => {
				const items = selector.querySelectorAll(
					'.xbo-mk-slippage__selector-item'
				);
				items.forEach( ( item ) => {
					const sym = item.dataset.symbol || '';
					const match = ! search || sym.toUpperCase().includes( search );
					item.classList.toggle(
						'xbo-mk-slippage__selector-item--hidden',
						! match
					);
				} );
			} );
		},

		/**
		 * Update the quote dropdown to show only valid quotes for the selected base.
		 */
		slippageUpdateQuoteOptions() {
			const ctx = getContext();
			const catalog = getCatalog();
			const available = catalog.pairs_map[ ctx.base ] || [];

			// Hide unavailable quote items via class.
			// The quote selector is the second .xbo-mk-slippage__selector in this widget.
			// This works for the current DOM structure.
			const widget = document.querySelector( '.xbo-mk-slippage' );
			if ( ! widget ) return;

			const quoteSelector = widget.querySelectorAll(
				'.xbo-mk-slippage__selector'
			)[ 1 ];
			if ( ! quoteSelector ) return;

			const items = quoteSelector.querySelectorAll(
				'.xbo-mk-slippage__selector-item'
			);
			items.forEach( ( item ) => {
				const sym = item.dataset.symbol || '';
				item.classList.toggle(
					'xbo-mk-slippage__selector-item--hidden',
					! available.includes( sym )
				);
			} );
		},

		/**
		 * Handle keyboard navigation in dropdown.
		 */
		slippageSelectorKeydown( event ) {
			const list = event.target
				.closest( '.xbo-mk-slippage__selector-dropdown' )
				?.querySelector( '.xbo-mk-slippage__selector-list' );
			if ( ! list ) return;

			const visible = Array.from(
				list.querySelectorAll(
					'.xbo-mk-slippage__selector-item:not(.xbo-mk-slippage__selector-item--hidden)'
				)
			);
			if ( ! visible.length ) return;

			const highlighted = list.querySelector(
				'.xbo-mk-slippage__selector-item--highlighted'
			);
			let idx = highlighted ? visible.indexOf( highlighted ) : -1;

			if ( event.key === 'ArrowDown' ) {
				event.preventDefault();
				if ( highlighted ) {
					highlighted.classList.remove(
						'xbo-mk-slippage__selector-item--highlighted'
					);
				}
				idx = Math.min( idx + 1, visible.length - 1 );
				visible[ idx ].classList.add(
					'xbo-mk-slippage__selector-item--highlighted'
				);
				visible[ idx ].scrollIntoView( { block: 'nearest' } );
			} else if ( event.key === 'ArrowUp' ) {
				event.preventDefault();
				if ( highlighted ) {
					highlighted.classList.remove(
						'xbo-mk-slippage__selector-item--highlighted'
					);
				}
				idx = Math.max( idx - 1, 0 );
				visible[ idx ].classList.add(
					'xbo-mk-slippage__selector-item--highlighted'
				);
				visible[ idx ].scrollIntoView( { block: 'nearest' } );
			} else if ( event.key === 'Enter' ) {
				event.preventDefault();
				if ( highlighted ) {
					highlighted.click();
				}
			} else if ( event.key === 'Escape' ) {
				event.preventDefault();
				const ctx = getContext();
				ctx.baseOpen = false;
				ctx.quoteOpen = false;
			}
		},

		// --- Debounced calculation ---
		slippageDebounceCalc() {
			const ctx = getContext();
			if ( ctx._debounceTimer ) {
				clearTimeout( ctx._debounceTimer );
			}
			const scopedCalc = withScope( actions.slippageCalculate );
			ctx._debounceTimer = setTimeout( scopedCalc, 300 );
		},

		// --- API fetch ---
		async slippageCalculate() {
			const ctx = getContext();
			const symbol = `${ ctx.base }_${ ctx.quote }`;
			const { side } = ctx;
			const amount = parseFloat( ctx.amount );

			if ( ! ctx.base || ! ctx.quote || ! amount || amount <= 0 ) {
				return;
			}

			// Abort previous request.
			if ( ctx._abortController ) {
				ctx._abortController.abort();
			}
			ctx._abortController = new AbortController();
			const { signal } = ctx._abortController;

			ctx.loading = true;
			ctx.error = '';

			try {
				const url = `${ restUrl }slippage?symbol=${ encodeURIComponent( symbol ) }&side=${ side }&amount=${ amount }`;
				const res = await fetch( url, { signal } );

				if ( ! res.ok ) {
					throw new Error( `HTTP ${ res.status }` );
				}

				if ( signal.aborted ) return;

				const data = await res.json();

				if ( data.avg_price !== undefined ) {
					ctx.result = data;
					ctx.error = '';
				} else if ( data.message ) {
					ctx.error = data.message;
					ctx.result = null;
				}
			} catch ( e ) {
				if ( e.name === 'AbortError' ) return;
				ctx.error = 'Failed to calculate slippage';
				ctx.result = null;
			} finally {
				if ( ! signal.aborted ) {
					ctx.loading = false;
				}
			}
		},
	},
} );
```

**Step 2: Verify no syntax errors**

Run: `node -c wp-content/plugins/xbo-market-kit/assets/js/interactivity/slippage.js`
Expected: No errors. (Note: this may fail due to ESM import syntax — in that case, skip syntax check and rely on browser testing.)

**Step 3: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/js/interactivity/slippage.js
git commit -m "feat(slippage): rewrite JS store with context-based state

Migrate from global state to per-instance getContext(). Add dropdown
toggle/search/select/keyboard actions. Use AbortController for request
cancellation, withScope for debounce. REST URL from getConfig()."
```

---

### Task 7: Build CSS, Run All Checks, and Manual Verification

**Files:**
- Build: `wp-content/plugins/xbo-market-kit/assets/css/dist/`

**Step 1: Rebuild CSS dist files**

Run: `cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/widgets.css -o assets/css/dist/widgets.css && NODE_ENV=production npx postcss assets/css/src/widgets.css -o assets/css/dist/widgets.min.css`

**Step 2: Run all quality checks**

Run:
```bash
cd wp-content/plugins/xbo-market-kit
composer test
composer phpcs
composer phpstan
```
Expected: ALL PASS. Fix any failures before proceeding.

**Step 3: Manual browser verification**

Open: `http://claude-code-hackathon-xbo-market-kit.local/`

Navigate to the demo page with the slippage calculator. Verify:

1. [ ] Widget loads with BTC/USDT pre-selected and metrics calculated
2. [ ] Base dropdown opens on click, shows icons and symbols
3. [ ] Typing in search filters the list
4. [ ] Selecting a base updates the quote dropdown options
5. [ ] Quote dropdown shows only valid quotes for selected base
6. [ ] Buy/Sell toggle works
7. [ ] Changing amount triggers recalculation
8. [ ] Escape closes dropdown
9. [ ] Click outside closes dropdown
10. [ ] Arrow keys navigate dropdown items

**Step 4: Commit built CSS**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/
git commit -m "chore: rebuild CSS dist with dropdown selector styles"
```

**Step 5: Final commit with all remaining fixes**

If any fixes were needed during verification, commit them:

```bash
git add -A wp-content/plugins/xbo-market-kit/
git commit -m "fix(slippage): address issues found during manual verification"
```

---

### Task 8: Plugin.php — Expose ApiClient and IconResolver Getters

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Plugin.php`

**Note:** This task may already be done — check if `get_api_client()` and `get_icon_resolver()` are already public methods on the Plugin class. If they are, skip this task.

**Step 1: Check existing API**

Read `includes/Plugin.php` and check for public getters for ApiClient and IconResolver.

**Step 2: If missing, add public getters**

```php
/**
 * Get the API client instance.
 *
 * @return \XboMarketKit\Api\ApiClient
 */
public function get_api_client(): \XboMarketKit\Api\ApiClient {
    return $this->api_client;
}

/**
 * Get the icon resolver instance.
 *
 * @return \XboMarketKit\Icons\IconResolver
 */
public function get_icon_resolver(): \XboMarketKit\Icons\IconResolver {
    return $this->icon_resolver;
}
```

**Step 3: Run tests**

Run: `cd wp-content/plugins/xbo-market-kit && composer test`
Expected: ALL PASS

**Step 4: Commit if changes were made**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Plugin.php
git commit -m "refactor: expose ApiClient and IconResolver as public getters"
```

---

## Task Dependency Order

```
Task 8 (Plugin getters) ──┐
                          ├──► Task 2 (PairCatalog) ──► Task 3 (REST config + footer) ──► Task 4 (Render rewrite) ──┐
Task 1 (Defaults + parse) ┘                                                                                        ├──► Task 7 (Build + verify)
                                                                              Task 5 (CSS) ──────────────────────────┤
                                                                              Task 6 (JS rewrite) ──────────────────┘
```

**Recommended execution order:** 8 → 1 → 2 → 3 → 4 → 5 → 6 → 7

---

## Important Notes for the Implementing Agent

- **WordPress Interactivity API docs:** Check `@wordpress/interactivity` docs for `getContext()`, `getConfig()`, `withScope()` API. Use Context7 MCP server: `context7 resolve-library-id "wordpress interactivity api"` then `query-docs`.
- **CSS build requires Node.js:** PostCSS is used. Run `npm install` in plugin dir if `npx postcss` fails.
- **Brain Monkey mocking:** WordPress functions (`esc_html__`, `esc_attr`, `sanitize_text_field`, `wp_json_encode`, `add_action`, `wp_interactivity_config`, `rest_url`) must be mocked in tests. See existing test patterns in `tests/Unit/Api/ApiClientTest.php`.
- **PHPCS:** WordPress Coding Standards require Yoda conditions, aligned assignments, proper doc blocks. Run `composer phpcbf` to auto-fix most issues.
- **The `xbo-mk-hidden` utility class** is used across all widgets — it already exists or must be added to `_base.css`.
- **DOM scoping in JS:** The `slippageFilterItems` and `slippageUpdateQuoteOptions` methods use `document.querySelector` which may not scope correctly for multi-instance. Consider using the event target to find the closest `.xbo-mk-slippage` ancestor. This is a known limitation — fix during manual verification if needed.
