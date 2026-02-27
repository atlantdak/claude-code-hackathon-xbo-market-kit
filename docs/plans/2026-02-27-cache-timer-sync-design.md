# Cache and Timer Synchronization — Design Document

**Date:** 2026-02-27
**Author:** Claude Sonnet 4.5 + Dmytro Kishkin
**Status:** Approved
**Related:** XBO Market Kit plugin

## Problem Statement

Current implementation has mismatched cache TTL and frontend refresh intervals, causing unnecessary REST API requests and inconsistent user experience:

- **Backend cache:** Stats (30s), Orderbook (5s), Trades (10s)
- **Frontend refresh:** Ticker (15s), Orderbook (5s), Trades (10s), Movers (30s hardcoded)
- **Issue:** Ticker refreshes every 15s but cache is 30s → fetches stale data for first 15s
- **Complexity:** CacheManager has TTL multiplier (fast/normal/slow) adding unnecessary flexibility
- **Inconsistency:** No centralized constant for managing sync

## Goals

1. **Synchronize** backend cache TTL with frontend refresh intervals
2. **Centralize** refresh interval management via single constant
3. **Simplify** CacheManager by removing TTL multiplier modes
4. **Standardize** all live widgets to 15-second refresh interval
5. **Maintain** extensibility via WordPress filters
6. **Preserve** long-lived metadata caches (currencies, trading pairs)

## Non-Goals

- Implement force-refresh parameter for widgets
- Create advanced cache configuration UI
- Change refresh behavior of static widgets (Slippage Calculator)
- Modify metadata cache behavior (currencies/trading pairs remain 6 hours)

## Design Decision: Approach 2 with Codex Refinements

After evaluating three approaches (strictly fixed, with flexibility, centralized config class), we selected **Approach 2** with Codex recommendations:

### Why This Approach

- ✅ WordPress conventions: constants + `apply_filters()` extensibility
- ✅ Fixed core behavior (15s) with safe override capability
- ✅ Avoids over-engineering (no Config class for MVP)
- ✅ Improves maintainability vs hard-coded values
- ✅ Preserves separation: live data (15s) vs metadata (6h)

### Key Principles

1. **Single source of truth:** `XBO_MARKET_KIT_REFRESH_INTERVAL` constant
2. **Extensibility:** `xbo_market_kit/refresh_interval` filter for advanced users
3. **Selective application:** Only widget-related caches use 15s
4. **Metadata preservation:** Currencies/trading pairs keep 6-hour cache
5. **Frontend sync:** JS receives same value via localization

## Architecture Overview

### Data Flow

```
PHP Bootstrap
    ↓
define( XBO_MARKET_KIT_REFRESH_INTERVAL, 15 )
    ↓
xbo_market_kit_get_refresh_interval()
apply_filters( 'xbo_market_kit/refresh_interval', 15 )
    ↓
┌─────────────┴─────────────┐
↓                           ↓
ApiClient                   wp_localize_script
(cache TTL)                 (to frontend)
    ↓                           ↓
WordPress Transients        JS Widgets
set_transient(..., 15)      setInterval(..., 15000)
    ↓                           ↓
REST API ←── fetch ───── Blocks/Shortcodes
(cached for 15s)         (refresh every 15s)
```

### Component Responsibilities

**Backend (PHP):**
- Bootstrap: Define constant and helper function
- CacheManager: Store/retrieve data with direct TTL (no multiplier)
- ApiClient: Use helper function for widget caches, fixed TTL for metadata
- AdminSettings: Remove cache_mode UI

**Frontend (JS/Blocks):**
- Block.json: Default refresh = 15 for all widgets
- Interactivity stores: Read refresh from context, fallback to 15
- Refresh Timer: Auto-detect from data-xbo-refresh attributes

## Detailed Design

### 1. Backend Changes (PHP)

#### 1.1. Bootstrap File (`xbo-market-kit.php`)

Add constant and helper function:

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

#### 1.2. CacheManager (`includes/Cache/CacheManager.php`)

**Remove:**
- `$ttl_multiplier` property
- Constructor logic reading `cache_mode` option
- `match()` expression for fast/normal/slow modes

**Simplify `set()` method:**

```php
public function set( string $key, mixed $data, int $ttl ): void {
    set_transient( $key, $data, max( $ttl, 1 ) );
}
```

No multiplier applied — direct TTL passthrough.

#### 1.3. ApiClient (`includes/Api/ApiClient.php`)

**Update widget-related methods:**

```php
public function get_stats(): ApiResponse {
    return $this->cached_request(
        'xbo_mk_stats',
        '/trading-pairs/stats',
        xbo_market_kit_get_refresh_interval()
    );
}

public function get_orderbook( string $symbol, int $depth = 20 ): ApiResponse {
    $symbol = $this->to_underscore_format( $symbol );
    $depth  = min( max( $depth, 1 ), 250 );
    $key    = sprintf( 'xbo_mk_ob_%s_%d', $symbol, $depth );
    $url    = sprintf( '/orderbook/%s?depth=%d', $symbol, $depth );
    return $this->cached_request( $key, $url, xbo_market_kit_get_refresh_interval() );
}

public function get_trades( string $symbol ): ApiResponse {
    $symbol = $this->to_slash_format( $symbol );
    $key    = sprintf( 'xbo_mk_trades_%s', sanitize_key( $symbol ) );
    $url    = sprintf( '/trades?symbol=%s', rawurlencode( $symbol ) );
    return $this->cached_request( $key, $url, xbo_market_kit_get_refresh_interval() );
}
```

**Keep metadata caches unchanged:**

```php
public function get_currencies(): ApiResponse {
    return $this->cached_request( 'xbo_mk_currencies', '/currencies', 21600 );
}

public function get_trading_pairs(): ApiResponse {
    return $this->cached_request( 'xbo_mk_pairs', '/trading-pairs', 21600 );
}
```

#### 1.4. Admin Settings (`includes/Admin/AdminSettings.php`)

**Remove:**
- `cache_mode` field from settings registration
- All UI references to fast/normal/slow modes
- Related option handling

### 2. Frontend Changes (JS/Blocks)

#### 2.1. Script Localization

In block registration (e.g., `BlockRegistrar.php` or `Plugin.php`):

```php
wp_localize_script(
    'xbo-market-kit-interactivity',
    'xboMarketKitConfig',
    array(
        'refreshInterval' => xbo_market_kit_get_refresh_interval() * 1000,
    )
);
```

#### 2.2. Block.json Updates

**All interactive blocks** (`ticker`, `orderbook`, `trades`, `movers`):

```json
{
    "attributes": {
        "refresh": { "type": "string", "default": "15" }
    }
}
```

**Movers block** (`src/blocks/movers/block.json`):
Add missing `refresh` attribute:

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

#### 2.3. JavaScript Interactivity Stores

**`assets/js/interactivity/movers.js`:**

Before:
```javascript
setInterval( actions.fetchMovers, 30000 ); // hardcoded
```

After:
```javascript
const ctx = getContext();
const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
setInterval( actions.fetchMovers, refresh );
```

**All other stores** (`ticker.js`, `orderbook.js`, `trades.js`):
Ensure fallback is 15:

```javascript
const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
```

#### 2.4. Shortcodes

Update default `refresh` attribute to `15` in `shortcode_atts()` calls:

```php
$atts = shortcode_atts(
    array(
        'symbols' => 'BTC/USDT,ETH/USDT',
        'refresh' => '15', // updated
        'columns' => '4',
    ),
    $atts,
    'xbo_ticker'
);
```

### 3. Content Updates

#### 3.1. Pages Requiring Refresh Timer Block

Add `<!-- wp:xbo-market-kit/refresh-timer /-->` to:

1. **Showcase** page
2. **Ticker Documentation** page
3. **Top Movers Documentation** page
4. **Order Book Documentation** page
5. **Recent Trades Documentation** page

**Placement strategy:**
- One timer per page
- Position before first XBO widget or in prominent location
- Use `interval: 0` for auto-detection

#### 3.2. Documentation Updates

In `includes/Admin/PageManager.php`, update parameter tables:

**All widgets:**
```html
<tr>
    <td><code>refresh</code></td>
    <td>number</td>
    <td>15</td>
    <td>Auto-refresh interval in seconds</td>
</tr>
```

**Movers widget:**
Add refresh parameter row (currently missing).

### 4. Testing Strategy

#### 4.1. Unit Tests

**CacheManagerTest:**
- Remove TTL multiplier tests
- Add test: `set()` stores data with exact TTL
- Add test: `get()` retrieves data within TTL window

**ApiClientTest:**
- Mock `xbo_market_kit_get_refresh_interval()` → return 15
- Assert `get_stats()`, `get_orderbook()`, `get_trades()` use 15s TTL
- Assert `get_currencies()`, `get_trading_pairs()` use 21600s TTL

#### 4.2. Integration Tests (Manual)

**Test 1: Backend Cache TTL**
```sql
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE '_transient_timeout_xbo_mk_stats';
-- Verify timeout is ~15 seconds from now
```

**Test 2: Frontend Refresh**
```javascript
// DevTools Console
document.querySelectorAll('[data-xbo-refresh]').forEach(el => {
    console.log(el.getAttribute('data-xbo-refresh')); // Should be "15000"
});
```

**Test 3: Refresh Timer Sync**
- Add multiple XBO widgets to page
- Add Refresh Timer block (interval=0)
- Verify timer shows "15s" and pulses on widget refresh

**Test 4: Cross-Widget Sync**
- Open DevTools Network tab
- Filter: `/wp-json/xbo/v1/`
- Verify requests every ~15s
- Verify first request served from cache (fast)
- Verify request after 15s fetches from API (slower)

#### 4.3. Regression Tests

- ✅ Slippage Calculator still works (no refresh)
- ✅ REST API endpoints return correct data
- ✅ Existing pages render without errors
- ✅ Admin settings save correctly (after cache_mode removal)

#### 4.4. Performance Test

- Open page with 5 widgets for 5 minutes
- Check DevTools Performance:
  - No memory leaks
  - setInterval not accumulating
  - REST API requests on schedule
- Verify reduced requests to api.xbo.com (due to sync)

## Migration Strategy

### Backward Compatibility

**Breaking changes:**
- ❌ Removes `cache_mode` setting (users who configured this will revert to default)
- ✅ No API changes (REST endpoints unchanged)
- ✅ No block markup changes (existing pages work)
- ✅ No database migrations needed (transients auto-expire)

**Recommendation:**
- Document cache_mode removal in changelog
- Clear all transients on plugin update (via activation hook)

### Rollout Plan

1. **Phase 1:** Backend changes (constant, CacheManager, ApiClient)
2. **Phase 2:** Frontend changes (block.json, JS files)
3. **Phase 3:** Content updates (add timers to pages)
4. **Phase 4:** Testing (unit + integration + performance)
5. **Phase 5:** Documentation (README, changelog)

## Future Enhancements

Not in scope for MVP, but potential future work:

1. **Admin UI for refresh interval:** Add setting to override default 15s
2. **Per-widget cache control:** Allow different TTLs for different widgets
3. **Cache warming:** Pre-fetch data before expiry to reduce user wait time
4. **WebSocket support:** Real-time updates without polling
5. **Service Worker caching:** Offline support for cached data

## References

- WordPress Transients API: https://developer.wordpress.org/apis/transients/
- WordPress Plugin Best Practices: https://developer.wordpress.org/plugins/plugin-basics/best-practices/
- Interactivity API: https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/
- XBO Market Kit Spec: `docs/plans/2026-02-22-xbo-market-kit-spec.md`

## Approval

**Reviewed by:** User (atlantdak)
**Approved:** 2026-02-27
**Next step:** Create implementation plan via writing-plans skill
