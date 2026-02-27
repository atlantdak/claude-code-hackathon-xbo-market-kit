# Cache and Timer Synchronization Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Synchronize backend cache TTL with frontend refresh intervals using centralized 15-second constant.

**Architecture:** Single PHP constant (`XBO_MARKET_KIT_REFRESH_INTERVAL`) with filter extensibility (`xbo_market_kit/refresh_interval`) controls both backend cache TTL and frontend setInterval timers. CacheManager simplified by removing TTL multiplier. Live data widgets use 15s, metadata caches remain at 6h.

**Tech Stack:** WordPress 6.9.1, PHP 8.2, WordPress Transients API, Interactivity API, PHPUnit

**Design Document:** `docs/plans/2026-02-27-cache-timer-sync-design.md`

---

## Task 1: Add Refresh Interval Constant and Helper

**Goal:** Define centralized constant and helper function with filter support.

**Files:**
- Modify: `xbo-market-kit.php`

**Step 1: Add constant definition**

Open `xbo-market-kit.php` and add after plugin header comments, before any class includes:

```php
/**
 * Default refresh interval for live data widgets (seconds).
 * Applied to cache TTL and frontend auto-refresh timers.
 *
 * @since 1.0.0
 */
if ( ! defined( 'XBO_MARKET_KIT_REFRESH_INTERVAL' ) ) {
	define( 'XBO_MARKET_KIT_REFRESH_INTERVAL', 15 );
}
```

**Step 2: Add helper function**

Add after constant definition:

```php
/**
 * Get the refresh interval with filter support.
 *
 * Allows advanced users to override the default refresh interval
 * via the 'xbo_market_kit/refresh_interval' filter.
 *
 * @since 1.0.0
 * @return int Refresh interval in seconds.
 */
function xbo_market_kit_get_refresh_interval(): int {
	return (int) apply_filters(
		'xbo_market_kit/refresh_interval',
		XBO_MARKET_KIT_REFRESH_INTERVAL
	);
}
```

**Step 3: Verify syntax**

Run: `composer run phpcs -- xbo-market-kit.php`
Expected: No errors

**Step 4: Commit**

```bash
git add xbo-market-kit.php
git commit -m "feat: add centralized refresh interval constant and helper

Define XBO_MARKET_KIT_REFRESH_INTERVAL = 15 constant and
xbo_market_kit_get_refresh_interval() helper function with
filter support for extensibility.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 2: Simplify CacheManager

**Goal:** Remove TTL multiplier and cache mode logic.

**Files:**
- Modify: `includes/Cache/CacheManager.php`
- Modify: `tests/Unit/Cache/CacheManagerTest.php`

**Step 1: Write failing test for simplified behavior**

Open `tests/Unit/Cache/CacheManagerTest.php` and replace existing tests with:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Cache;

use XboMarketKit\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class CacheManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_set_stores_data_with_exact_ttl(): void {
		$cache = new CacheManager();
		$key   = 'test_key';
		$data  = array( 'foo' => 'bar' );
		$ttl   = 15;

		Functions\expect( 'set_transient' )
			->once()
			->with( $key, $data, 15 )
			->andReturn( true );

		$cache->set( $key, $data, $ttl );
	}

	public function test_set_enforces_minimum_ttl_of_one(): void {
		$cache = new CacheManager();

		Functions\expect( 'set_transient' )
			->once()
			->with( 'key', 'data', 1 )
			->andReturn( true );

		$cache->set( 'key', 'data', 0 );
	}

	public function test_get_returns_cached_value(): void {
		$cache = new CacheManager();

		Functions\expect( 'get_transient' )
			->once()
			->with( 'test_key' )
			->andReturn( array( 'cached' => 'data' ) );

		$result = $cache->get( 'test_key' );
		$this->assertSame( array( 'cached' => 'data' ), $result );
	}

	public function test_get_returns_null_when_not_found(): void {
		$cache = new CacheManager();

		Functions\expect( 'get_transient' )
			->once()
			->with( 'missing_key' )
			->andReturn( false );

		$result = $cache->get( 'missing_key' );
		$this->assertNull( $result );
	}

	public function test_delete_removes_transient(): void {
		$cache = new CacheManager();

		Functions\expect( 'delete_transient' )
			->once()
			->with( 'test_key' )
			->andReturn( true );

		$cache->delete( 'test_key' );
	}
}
```

**Step 2: Run test to verify it fails**

Run: `composer run test -- --filter=CacheManagerTest`
Expected: FAIL (set() method still has TTL multiplier logic)

**Step 3: Simplify CacheManager implementation**

Open `includes/Cache/CacheManager.php` and replace entire class:

```php
<?php
/**
 * CacheManager class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Cache;

/**
 * Caching layer using WordPress transients.
 *
 * Provides get/set/delete/flush operations with direct TTL control.
 */
class CacheManager {

	/**
	 * Transient key prefix for plugin cache entries.
	 *
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'xbo_mk_';

	/**
	 * Get a cached value by key.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value or null if not found.
	 */
	public function get( string $key ): mixed {
		$value = get_transient( $key );
		return false === $value ? null : $value;
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $key  Cache key.
	 * @param mixed  $data Data to cache.
	 * @param int    $ttl  Time-to-live in seconds.
	 * @return void
	 */
	public function set( string $key, mixed $data, int $ttl ): void {
		set_transient( $key, $data, max( $ttl, 1 ) );
	}

	/**
	 * Delete a cached value by key.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public function delete( string $key ): void {
		delete_transient( $key );
	}

	/**
	 * Flush all plugin cache entries from the database.
	 *
	 * @return int Number of deleted cache entries.
	 */
	public function flush_all(): int {
		global $wpdb;
		$prefix = '_transient_' . self::TRANSIENT_PREFIX;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$prefix . '%',
				'_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
			)
		);
		return (int) $deleted;
	}
}
```

**Step 4: Run tests to verify they pass**

Run: `composer run test -- --filter=CacheManagerTest`
Expected: PASS

**Step 5: Run code style check**

Run: `composer run phpcs -- includes/Cache/CacheManager.php`
Expected: No errors

**Step 6: Commit**

```bash
git add includes/Cache/CacheManager.php tests/Unit/Cache/CacheManagerTest.php
git commit -m "refactor: simplify CacheManager by removing TTL multiplier

Remove cache_mode multiplier logic from CacheManager. Now uses
direct TTL values without modification. Update tests to verify
exact TTL passthrough behavior.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 3: Update ApiClient Cache Methods

**Goal:** Use helper function for widget caches, keep metadata caches unchanged.

**Files:**
- Modify: `includes/Api/ApiClient.php`
- Modify: `tests/Unit/Api/ApiClientTest.php`

**Step 1: Write failing test**

Open `tests/Unit/Api/ApiClientTest.php` and add new test:

```php
public function test_get_stats_uses_refresh_interval(): void {
	Functions\expect( 'xbo_market_kit_get_refresh_interval' )
		->once()
		->andReturn( 15 );

	$this->cache->expects( $this->once() )
		->method( 'get' )
		->with( 'xbo_mk_stats' )
		->willReturn( null );

	$this->cache->expects( $this->once() )
		->method( 'set' )
		->with( 'xbo_mk_stats', $this->anything(), 15 );

	Functions\expect( 'wp_remote_get' )
		->once()
		->andReturn( array(
			'response' => array( 'code' => 200 ),
			'body'     => '[]',
		) );

	Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
	Functions\expect( 'wp_remote_retrieve_body' )->andReturn( '[]' );
	Functions\expect( 'is_wp_error' )->andReturn( false );

	$this->client->get_stats();
}

public function test_get_orderbook_uses_refresh_interval(): void {
	Functions\expect( 'xbo_market_kit_get_refresh_interval' )
		->once()
		->andReturn( 15 );

	$this->cache->expects( $this->once() )
		->method( 'get' )
		->willReturn( null );

	$this->cache->expects( $this->once() )
		->method( 'set' )
		->with( $this->anything(), $this->anything(), 15 );

	Functions\expect( 'wp_remote_get' )->andReturn( array(
		'response' => array( 'code' => 200 ),
		'body'     => '{"bids":[],"asks":[]}',
	) );

	Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
	Functions\expect( 'wp_remote_retrieve_body' )->andReturn( '{"bids":[],"asks":[]}' );
	Functions\expect( 'is_wp_error' )->andReturn( false );

	$this->client->get_orderbook( 'BTC/USDT', 20 );
}

public function test_get_trades_uses_refresh_interval(): void {
	Functions\expect( 'xbo_market_kit_get_refresh_interval' )
		->once()
		->andReturn( 15 );

	$this->cache->expects( $this->once() )
		->method( 'get' )
		->willReturn( null );

	$this->cache->expects( $this->once() )
		->method( 'set' )
		->with( $this->anything(), $this->anything(), 15 );

	Functions\expect( 'wp_remote_get' )->andReturn( array(
		'response' => array( 'code' => 200 ),
		'body'     => '[]',
	) );

	Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
	Functions\expect( 'wp_remote_retrieve_body' )->andReturn( '[]' );
	Functions\expect( 'is_wp_error' )->andReturn( false );

	$this->client->get_trades( 'BTC/USDT' );
}

public function test_get_currencies_uses_long_cache(): void {
	$this->cache->expects( $this->once() )
		->method( 'get' )
		->with( 'xbo_mk_currencies' )
		->willReturn( null );

	$this->cache->expects( $this->once() )
		->method( 'set' )
		->with( 'xbo_mk_currencies', $this->anything(), 21600 );

	Functions\expect( 'wp_remote_get' )->andReturn( array(
		'response' => array( 'code' => 200 ),
		'body'     => '[]',
	) );

	Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
	Functions\expect( 'wp_remote_retrieve_body' )->andReturn( '[]' );
	Functions\expect( 'is_wp_error' )->andReturn( false );

	$this->client->get_currencies();
}

public function test_get_trading_pairs_uses_long_cache(): void {
	$this->cache->expects( $this->once() )
		->method( 'get' )
		->willReturn( null );

	$this->cache->expects( $this->once() )
		->method( 'set' )
		->with( 'xbo_mk_pairs', $this->anything(), 21600 );

	Functions\expect( 'wp_remote_get' )->andReturn( array(
		'response' => array( 'code' => 200 ),
		'body'     => '[]',
	) );

	Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
	Functions\expect( 'wp_remote_retrieve_body' )->andReturn( '[]' );
	Functions\expect( 'is_wp_error' )->andReturn( false );

	$this->client->get_trading_pairs();
}
```

**Step 2: Run test to verify it fails**

Run: `composer run test -- --filter=ApiClientTest`
Expected: FAIL (methods still use hardcoded TTL values)

**Step 3: Update ApiClient methods**

Open `includes/Api/ApiClient.php` and update methods:

```php
/**
 * Get trading pair statistics.
 *
 * @return ApiResponse API response with trading pair stats data.
 */
public function get_stats(): ApiResponse {
	return $this->cached_request(
		'xbo_mk_stats',
		'/trading-pairs/stats',
		xbo_market_kit_get_refresh_interval()
	);
}

/**
 * Get the order book for a trading pair.
 *
 * @param string $symbol Trading pair symbol (e.g. BTC/USDT or BTC_USDT).
 * @param int    $depth  Order book depth (1-250).
 * @return ApiResponse API response with order book data.
 */
public function get_orderbook( string $symbol, int $depth = 20 ): ApiResponse {
	$symbol = $this->to_underscore_format( $symbol );
	$depth  = min( max( $depth, 1 ), 250 );
	$key    = sprintf( 'xbo_mk_ob_%s_%d', $symbol, $depth );
	$url    = sprintf( '/orderbook/%s?depth=%d', $symbol, $depth );
	return $this->cached_request( $key, $url, xbo_market_kit_get_refresh_interval() );
}

/**
 * Get recent trades for a trading pair.
 *
 * @param string $symbol Trading pair symbol (e.g. BTC/USDT).
 * @return ApiResponse API response with trades data.
 */
public function get_trades( string $symbol ): ApiResponse {
	$symbol = $this->to_slash_format( $symbol );
	$key    = sprintf( 'xbo_mk_trades_%s', sanitize_key( $symbol ) );
	$url    = sprintf( '/trades?symbol=%s', rawurlencode( $symbol ) );
	return $this->cached_request( $key, $url, xbo_market_kit_get_refresh_interval() );
}

// Keep these unchanged:
public function get_currencies(): ApiResponse {
	return $this->cached_request( 'xbo_mk_currencies', '/currencies', 21600 );
}

public function get_trading_pairs(): ApiResponse {
	return $this->cached_request( 'xbo_mk_pairs', '/trading-pairs', 21600 );
}
```

**Step 4: Run tests to verify they pass**

Run: `composer run test -- --filter=ApiClientTest`
Expected: PASS

**Step 5: Run code style check**

Run: `composer run phpcs -- includes/Api/ApiClient.php`
Expected: No errors

**Step 6: Commit**

```bash
git add includes/Api/ApiClient.php tests/Unit/Api/ApiClientTest.php
git commit -m "feat: use centralized refresh interval in ApiClient

Update get_stats(), get_orderbook(), get_trades() to use
xbo_market_kit_get_refresh_interval() for cache TTL.
Keep get_currencies() and get_trading_pairs() at 6 hours.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 4: Remove cache_mode from Admin Settings

**Goal:** Remove cache mode UI and option handling.

**Files:**
- Modify: `includes/Admin/AdminSettings.php`

**Step 1: Find and remove cache_mode field**

Open `includes/Admin/AdminSettings.php` and search for `cache_mode`. Remove:
- Field registration in `register_settings()`
- Field rendering callback
- Any validation/sanitization for cache_mode
- Help text about fast/normal/slow modes

**Step 2: Verify no references remain**

Run: `grep -r "cache_mode" wp-content/plugins/xbo-market-kit/includes/`
Expected: No matches (except possibly in comments/docs)

**Step 3: Run code style check**

Run: `composer run phpcs -- includes/Admin/AdminSettings.php`
Expected: No errors

**Step 4: Commit**

```bash
git add includes/Admin/AdminSettings.php
git commit -m "refactor: remove cache_mode setting from admin

Remove cache_mode (fast/normal/slow) setting from admin UI.
Refresh interval is now controlled by centralized constant.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 5: Update Block.json Files

**Goal:** Set default refresh to 15 for all interactive blocks.

**Files:**
- Modify: `src/blocks/ticker/block.json`
- Modify: `src/blocks/orderbook/block.json`
- Modify: `src/blocks/trades/block.json`
- Modify: `src/blocks/movers/block.json`

**Step 1: Update ticker block.json**

Open `src/blocks/ticker/block.json` and ensure:

```json
{
	"attributes": {
		"symbols": { "type": "string", "default": "BTC/USDT,ETH/USDT" },
		"refresh": { "type": "string", "default": "15" },
		"columns": { "type": "string", "default": "4" },
		"align": { "type": "string", "default": "full" }
	}
}
```

**Step 2: Update orderbook block.json**

Open `src/blocks/orderbook/block.json` and ensure:

```json
{
	"attributes": {
		"symbol": { "type": "string", "default": "BTC/USDT" },
		"depth": { "type": "string", "default": "20" },
		"refresh": { "type": "string", "default": "15" },
		"align": { "type": "string", "default": "full" }
	}
}
```

**Step 3: Update trades block.json**

Open `src/blocks/trades/block.json` and ensure:

```json
{
	"attributes": {
		"symbol": { "type": "string", "default": "BTC/USDT" },
		"limit": { "type": "string", "default": "20" },
		"refresh": { "type": "string", "default": "15" },
		"align": { "type": "string", "default": "full" }
	}
}
```

**Step 4: Update movers block.json - ADD refresh attribute**

Open `src/blocks/movers/block.json` and add refresh:

```json
{
	"attributes": {
		"mode": { "type": "string", "default": "gainers" },
		"limit": { "type": "string", "default": "10" },
		"refresh": { "type": "string", "default": "15" },
		"align": { "type": "string", "default": "full" }
	}
}
```

**Step 5: Rebuild blocks**

Run: `cd wp-content/plugins/xbo-market-kit && npm run build`
Expected: Build success, updated files in `build/blocks/`

**Step 6: Commit**

```bash
git add src/blocks/*/block.json build/blocks/
git commit -m "feat: standardize refresh interval to 15s in all blocks

Update block.json defaults for ticker, orderbook, trades to 15s.
Add missing refresh attribute to movers block.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 6: Update JavaScript Interactivity Stores

**Goal:** Ensure all stores use refresh from context with 15s fallback.

**Files:**
- Modify: `assets/js/interactivity/movers.js`
- Verify: `assets/js/interactivity/ticker.js`
- Verify: `assets/js/interactivity/orderbook.js`
- Verify: `assets/js/interactivity/trades.js`

**Step 1: Update movers.js**

Open `assets/js/interactivity/movers.js` and find the initMovers action. Change from:

```javascript
setInterval( actions.fetchMovers, 30000 );
```

To:

```javascript
const ctx = getContext();
const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
setInterval( actions.fetchMovers, refresh );
```

**Step 2: Verify ticker.js**

Open `assets/js/interactivity/ticker.js` and verify it has:

```javascript
const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
```

If not, update the fallback to 15.

**Step 3: Verify orderbook.js**

Open `assets/js/interactivity/orderbook.js` and verify fallback is 15:

```javascript
const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
```

**Step 4: Verify trades.js**

Open `assets/js/interactivity/trades.js` and verify fallback is 15:

```javascript
const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
```

**Step 5: Commit**

```bash
git add assets/js/interactivity/
git commit -m "fix: update movers.js to use configurable refresh interval

Change movers from hardcoded 30s to context-based refresh
with 15s fallback. Verify all other stores use 15s fallback.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 7: Update Shortcodes

**Goal:** Set refresh default to 15 in shortcode attributes.

**Files:**
- Modify: `includes/Shortcodes/TickerShortcode.php`
- Modify: `includes/Shortcodes/MoversShortcode.php` (if exists)

**Step 1: Update TickerShortcode**

Open `includes/Shortcodes/TickerShortcode.php` and find `shortcode_atts()` call. Update:

```php
$atts = shortcode_atts(
	array(
		'symbols' => 'BTC/USDT,ETH/USDT',
		'refresh' => '15',
		'columns' => '4',
	),
	$atts,
	'xbo_ticker'
);
```

**Step 2: Check for other shortcodes**

Run: `ls wp-content/plugins/xbo-market-kit/includes/Shortcodes/`

If MoversShortcode.php exists, update its refresh default to 15 as well.

**Step 3: Run code style check**

Run: `composer run phpcs -- includes/Shortcodes/`
Expected: No errors

**Step 4: Commit**

```bash
git add includes/Shortcodes/
git commit -m "feat: update shortcode refresh defaults to 15s

Standardize all shortcode refresh defaults to 15 seconds.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 8: Add Refresh Timer to Pages

**Goal:** Add Refresh Timer block to all documentation pages.

**Files:**
- Modify: `includes/Admin/PageManager.php`

**Step 1: Find page creation methods**

Open `includes/Admin/PageManager.php` and locate methods like:
- `create_showcase_page()`
- `create_ticker_docs_page()`
- `create_movers_docs_page()`
- `create_orderbook_docs_page()`
- `create_trades_docs_page()`

**Step 2: Add Refresh Timer block to Showcase**

In `create_showcase_page()`, add after the intro paragraph and before first widget:

```php
$content .= '<!-- wp:xbo-market-kit/refresh-timer /-->' . "\n\n";
```

**Step 3: Add Refresh Timer to each docs page**

Repeat for all documentation pages that contain live widgets. Place before first widget block.

Example position:
```php
// After heading and description
$content .= '<!-- /wp:paragraph -->' . "\n\n";
$content .= '<!-- wp:xbo-market-kit/refresh-timer /-->' . "\n\n";
// Before first widget
$content .= '<!-- wp:xbo-market-kit/ticker ... -->';
```

**Step 4: Verify page content**

Check that all pages with live widgets now have Refresh Timer block.

**Step 5: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "feat: add Refresh Timer block to all documentation pages

Add refresh timer to Showcase and all widget documentation pages
for visual feedback of auto-refresh intervals.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 9: Update Documentation Tables

**Goal:** Update parameter tables to show refresh = 15 default.

**Files:**
- Modify: `includes/Admin/PageManager.php`

**Step 1: Update Ticker parameters table**

In `create_ticker_docs_page()`, find the parameters table and update:

```php
$content .= '<tr><td><code>refresh</code></td><td>number</td><td>15</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
```

**Step 2: Update Orderbook parameters table**

In `create_orderbook_docs_page()`:

```php
$content .= '<tr><td><code>refresh</code></td><td>number</td><td>15</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
```

**Step 3: Update Trades parameters table**

In `create_trades_docs_page()`:

```php
$content .= '<tr><td><code>refresh</code></td><td>number</td><td>15</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
```

**Step 4: Add Movers parameters table**

In `create_movers_docs_page()`, add refresh parameter row:

```php
$content .= '<tr><td><code>refresh</code></td><td>number</td><td>15</td><td>Auto-refresh interval in seconds</td></tr>' . "\n";
```

**Step 5: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "docs: update parameter tables to reflect 15s refresh default

Update all widget documentation tables to show refresh=15 default.
Add missing refresh parameter to Movers documentation.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 10: Run All Tests

**Goal:** Verify all unit tests pass.

**Step 1: Run PHPUnit test suite**

Run: `composer run test`
Expected: All tests pass

**Step 2: Run PHPStan static analysis**

Run: `composer run phpstan`
Expected: No errors

**Step 3: Run code style check**

Run: `composer run phpcs`
Expected: No errors or auto-fixable issues only

**Step 4: Auto-fix code style if needed**

If PHPCS reports fixable issues:
Run: `composer run phpcbf`

**Step 5: Commit any fixes**

If phpcbf made changes:

```bash
git add .
git commit -m "style: fix code style issues

Auto-fix code style violations found by PHPCS.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 11: Manual Integration Testing

**Goal:** Verify cache and timer sync in browser.

**Files:**
- None (manual testing)

**Step 1: Activate plugin and flush cache**

1. Visit `http://claude-code-hackathon-xbo-market-kit.local/wp-admin/`
2. Login: admin / 1
3. Go to Plugins → Ensure XBO Market Kit is active
4. Go to XBO Market Kit → Settings
5. Click "Clear Cache" button (if exists)

**Step 2: Test backend cache TTL**

1. Open phpMyAdmin or run MySQL query:
```sql
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE '_transient_timeout_xbo_mk_stats'
ORDER BY option_id DESC
LIMIT 1;
```
2. Note the timeout value
3. Open Showcase page: `http://claude-code-hackathon-xbo-market-kit.local/showcase/`
4. Wait 5 seconds
5. Re-run query - timeout should be ~15 seconds from current time

**Step 3: Test frontend refresh**

1. Open Showcase page
2. Open DevTools → Console
3. Run:
```javascript
document.querySelectorAll('[data-xbo-refresh]').forEach(el => {
  console.log(el.getAttribute('data-xbo-refresh'));
});
```
4. Verify all show "15000" (milliseconds)

**Step 4: Test Refresh Timer sync**

1. On Showcase page, locate Refresh Timer widget
2. Verify it shows "15s"
3. Watch for 15 seconds - timer should count down and pulse
4. Network tab: verify XHR requests to `/wp-json/xbo/v1/` every ~15s

**Step 5: Test cross-widget sync**

1. Open DevTools → Network tab
2. Filter: XHR
3. Reload page
4. Observe requests to XBO REST endpoints
5. First requests should be fast (cached)
6. After 15 seconds, new requests should be slower (API fetch)
7. All widgets should refresh simultaneously

**Step 6: Document results**

Create `docs/testing/2026-02-27-cache-timer-integration-test.md`:

```markdown
# Cache-Timer Integration Test Results

**Date:** 2026-02-27
**Tester:** [Your name]

## Backend Cache TTL
- ✅/❌ Stats cache expires in 15s
- ✅/❌ Orderbook cache expires in 15s
- ✅/❌ Trades cache expires in 15s

## Frontend Refresh
- ✅/❌ All data-xbo-refresh attributes = 15000ms
- ✅/❌ Refresh Timer shows 15s interval
- ✅/❌ Timer pulses on widget refresh

## Cross-Widget Sync
- ✅/❌ All widgets refresh simultaneously
- ✅/❌ First 15s: fast (cached) responses
- ✅/❌ After 15s: slower (API) responses

## Issues Found
[List any issues]

## Notes
[Additional observations]
```

**Step 7: Commit test results**

```bash
git add docs/testing/
git commit -m "test: add cache-timer integration test results

Document manual integration testing of cache-timer sync.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 12: Update Worklog

**Goal:** Document work in worklog file.

**Files:**
- Modify: `docs/worklog/2026-02-frontend-redesign.md` (or create new)

**Step 1: Create worklog entry**

Create or append to `docs/worklog/2026-02-cache-timer-sync.md`:

```markdown
# Cache-Timer Synchronization Worklog

## 2026-02-27

### Goal
Synchronize backend cache TTL with frontend refresh intervals.

### Implementation Summary

**Backend Changes:**
- Added `XBO_MARKET_KIT_REFRESH_INTERVAL` constant (15 seconds)
- Added `xbo_market_kit_get_refresh_interval()` helper with filter support
- Simplified CacheManager by removing TTL multiplier logic
- Updated ApiClient to use refresh interval for widget caches
- Removed cache_mode setting from admin

**Frontend Changes:**
- Updated all block.json defaults to refresh=15
- Added refresh attribute to movers block
- Updated movers.js to use configurable refresh interval
- Verified all interactivity stores use 15s fallback

**Content Updates:**
- Added Refresh Timer block to all documentation pages
- Updated parameter tables to reflect 15s default

**Testing:**
- All unit tests pass
- PHPStan level 6 clean
- PHPCS clean
- Manual integration testing completed

### Files Modified
- `xbo-market-kit.php` - constant and helper
- `includes/Cache/CacheManager.php` - removed multiplier
- `includes/Api/ApiClient.php` - use refresh interval
- `includes/Admin/AdminSettings.php` - removed cache_mode
- `includes/Admin/PageManager.php` - added timers, updated docs
- `src/blocks/*/block.json` - updated defaults
- `assets/js/interactivity/*.js` - standardized refresh
- Test files updated

### Commits
- feat: add centralized refresh interval constant and helper
- refactor: simplify CacheManager by removing TTL multiplier
- feat: use centralized refresh interval in ApiClient
- refactor: remove cache_mode setting from admin
- feat: standardize refresh interval to 15s in all blocks
- fix: update movers.js to use configurable refresh interval
- feat: update shortcode refresh defaults to 15s
- feat: add Refresh Timer block to all documentation pages
- docs: update parameter tables to reflect 15s refresh default

### Next Steps
- Monitor performance in production
- Consider WebSocket alternative for real-time updates
- Evaluate user feedback on 15s interval
```

**Step 2: Commit worklog**

```bash
git add docs/worklog/
git commit -m "docs: add cache-timer sync worklog entry

Document implementation of cache-timer synchronization.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 13: Update README

**Goal:** Document new refresh interval constant and filter.

**Files:**
- Modify: `README.md`

**Step 1: Add to Features section**

Add under features:

```markdown
- **Synchronized Refresh:** Centralized 15-second refresh interval for all live widgets, synchronized between backend cache and frontend timers
```

**Step 2: Add to Developer Documentation**

Add new section:

```markdown
## Refresh Interval Configuration

All live data widgets (Ticker, Movers, Orderbook, Trades) refresh every 15 seconds by default.

### Customizing Refresh Interval

Advanced users can override the refresh interval via filter:

```php
add_filter( 'xbo_market_kit/refresh_interval', function( $interval ) {
    return 30; // 30 seconds
} );
```

This affects both backend cache TTL and frontend auto-refresh timers.

**Note:** Changing this value impacts API request frequency. Lower values increase load.
```

**Step 3: Update Architecture section**

Update cache documentation:

```markdown
### Caching Strategy

- **Live data:** 15-second cache (stats, orderbook, trades)
- **Metadata:** 6-hour cache (currencies, trading pairs)
- **Synchronization:** Frontend refresh matches backend cache expiry
```

**Step 4: Commit README**

```bash
git add README.md
git commit -m "docs: document refresh interval configuration

Add documentation for centralized refresh interval constant
and filter-based customization.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 14: Update Metrics

**Goal:** Update task metrics for hackathon tracking.

**Files:**
- Modify: `docs/metrics/tasks.json`

**Step 1: Read current metrics**

Open `docs/metrics/tasks.json` and note current totals.

**Step 2: Add cache-timer-sync task entry**

Add new entry:

```json
{
  "id": "cache-timer-sync",
  "name": "Cache and Timer Synchronization",
  "status": "completed",
  "category": "backend",
  "priority": "high",
  "startDate": "2026-02-27",
  "completedDate": "2026-02-27",
  "estimatedHours": 4,
  "actualHours": 3.5,
  "tags": ["performance", "ux", "refactoring"],
  "description": "Synchronize backend cache TTL with frontend refresh intervals using centralized constant",
  "files": [
    "xbo-market-kit.php",
    "includes/Cache/CacheManager.php",
    "includes/Api/ApiClient.php",
    "includes/Admin/AdminSettings.php",
    "includes/Admin/PageManager.php",
    "src/blocks/*/block.json",
    "assets/js/interactivity/*.js"
  ],
  "commits": 10,
  "testsAdded": 8,
  "linesChanged": 250
}
```

**Step 3: Update totals**

Recalculate:
- Total tasks completed
- Total commits
- Total hours

**Step 4: Commit metrics**

```bash
git add docs/metrics/tasks.json
git commit -m "docs: update metrics for cache-timer-sync task

Add completed task entry and update totals.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Task 15: Final Verification

**Goal:** Ensure all changes are committed and working.

**Step 1: Check git status**

Run: `git status`
Expected: Nothing to commit, working tree clean

**Step 2: Run full test suite**

Run:
```bash
composer run phpcs
composer run phpstan
composer run test
```
Expected: All pass

**Step 3: Build frontend assets**

Run: `npm run build`
Expected: Build success

**Step 4: Test on local site**

Visit: `http://claude-code-hackathon-xbo-market-kit.local/showcase/`
Expected:
- Page loads without errors
- All widgets display data
- Refresh Timer shows 15s
- Widgets refresh every 15 seconds

**Step 5: Create summary commit (if needed)**

If any final tweaks were made:

```bash
git add .
git commit -m "chore: final cleanup for cache-timer-sync

Final verification and cleanup.

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## Success Criteria

- ✅ All unit tests pass
- ✅ PHPStan level 6 clean
- ✅ PHPCS clean
- ✅ All widgets default to 15s refresh
- ✅ Backend cache TTL matches frontend refresh
- ✅ Refresh Timer displays and syncs correctly
- ✅ Documentation updated
- ✅ Worklog updated
- ✅ Metrics updated
- ✅ README updated

## Next Steps

After completing this plan:
1. Monitor performance on local site
2. Test with real XBO API data
3. Consider user feedback on 15s interval
4. Plan future enhancements (WebSocket, cache warming)

---

**Plan Duration:** ~3-4 hours
**Commits:** ~10-15
**Files Modified:** ~15-20
**Tests Added/Updated:** ~8-10
