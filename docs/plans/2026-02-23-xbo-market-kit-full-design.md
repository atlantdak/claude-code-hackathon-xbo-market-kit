# XBO Market Kit — Full Plugin Design

**Date:** 2026-02-23
**Status:** Approved
**Author:** Dmytro Kishkin (atlantdak@gmail.com)

## Overview

Complete design for the XBO Market Kit WordPress plugin: 5 cryptocurrency market data widgets (Live Ticker, Top Movers, Order Book, Recent Trades, Slippage Calculator) + Hero demo page. Delivered via Shortcodes + Gutenberg Blocks. Frontend powered by Tailwind CSS CDN and WP Interactivity API with polling.

## Decisions Summary

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Scope | 5 widgets + Hero demo page | Full spec + mock coverage |
| UI Delivery | Shortcodes + Gutenberg Blocks | Optimal for MVP, no Elementor |
| Sparklines | Decorative SVG (trend-based) | No historical API data available |
| Live Updates | WP Interactivity API + polling | Native WP, modern, reactive |
| Admin Panel | Minimal settings page | Cache TTL, default pairs, Tailwind toggle |
| Demo Page | Auto-created on activation | wp_insert_post with all widgets |
| Architecture | Monolithic Controller | Simple, testable, right-sized for 5 widgets |
| CSS | Tailwind CDN (ADR-003) | No npm build step needed |
| API Calls | Server-side only (ADR-001) | No CORS from XBO API |

## Architecture

```
Plugin (bootstrap) → registers:
├── ApiClient (HTTP to api.xbo.com via wp_remote_get)
├── CacheManager (WordPress Transients, variable TTL)
├── REST Controllers (WC-style, extends WP_REST_Controller)
│   ├── TickerController   → GET /xbo/v1/ticker
│   ├── MoversController   → GET /xbo/v1/movers
│   ├── OrderbookController → GET /xbo/v1/orderbook
│   ├── TradesController   → GET /xbo/v1/trades
│   └── SlippageController → GET /xbo/v1/slippage
├── Shortcodes
│   ├── TickerShortcode    → [xbo_ticker]
│   ├── MoversShortcode    → [xbo_movers]
│   ├── OrderbookShortcode → [xbo_orderbook]
│   ├── TradesShortcode    → [xbo_trades]
│   └── SlippageShortcode  → [xbo_slippage]
├── BlockRegistrar → 5 Gutenberg dynamic blocks
├── AdminSettings → Settings page (Settings API)
└── DemoPage → Auto-created showcase page
```

## Data Flow

```
Browser (widgets with Interactivity API)
    ↓ fetch (polling every N seconds)
WP REST API (/xbo/v1/*)
    ↓ check cache
CacheManager (WordPress Transients)
    ↓ cache miss → HTTP request
ApiClient → https://api.xbo.com/*
    ↓ response
CacheManager ← store with TTL
    ↓
WP REST API ← format response (WC-style: _links, schema, normalization)
    ↓
Browser ← update DOM via Interactivity API (reactive bindings)
```

## Component Details

### Plugin Bootstrap (`XboMarketKit\Plugin`)

Central class that initializes all components on `plugins_loaded` hook:
- Instantiates ApiClient, CacheManager
- Registers REST routes on `rest_api_init`
- Registers shortcodes on `init`
- Registers blocks on `init`
- Registers admin settings on `admin_menu`
- Creates demo page on `register_activation_hook`

### API Client (`XboMarketKit\Api\ApiClient`)

| Method | XBO Endpoint | Notes |
|--------|-------------|-------|
| `get_currencies()` | GET /currencies | 205 records |
| `get_trading_pairs()` | GET /trading-pairs | 280 records |
| `get_stats()` | GET /trading-pairs/stats | 280 records, 24h change |
| `get_trades(symbol)` | GET /trades?symbol={BTC/USDT} | Slash format |
| `get_orderbook(symbol, depth)` | GET /orderbook/{BTC_USDT}?depth={N} | Underscore format, max 250 |

Implementation:
- Uses `wp_remote_get()` with 10s timeout
- Returns `ApiResponse` DTO or `WP_Error`
- Handles symbol format conversion internally
- Base URL configurable via filter `xbo_market_kit/api_base_url`

### API Response DTO (`XboMarketKit\Api\ApiResponse`)

```php
class ApiResponse {
    public bool $success;
    public array $data;
    public string $error_message;
    public int $status_code;
}
```

### Cache Manager (`XboMarketKit\Cache\CacheManager`)

| Data Type | Key Pattern | Default TTL |
|-----------|-------------|-------------|
| Currencies | `xbo_mk_currencies` | 6 hours |
| Trading pairs | `xbo_mk_pairs` | 6 hours |
| Ticker/Stats | `xbo_mk_stats` | 30 seconds |
| Orderbook | `xbo_mk_ob_{symbol}_{depth}` | 5 seconds |
| Trades | `xbo_mk_trades_{symbol}` | 10 seconds |
| Slippage | `xbo_mk_slip_{symbol}_{side}_{amount}` | 5 seconds |

Methods:
- `get(string $key): mixed`
- `set(string $key, mixed $data, int $ttl): void`
- `delete(string $key): void`
- `flush_all(): void` — deletes all `xbo_mk_*` transients
- TTL multiplier from admin settings (Fast=0.5x, Normal=1x, Slow=2x)

### REST API Endpoints (WC-style)

All controllers extend `AbstractController` which extends `WP_REST_Controller`:
- Schema validation for all parameters
- Sanitize/validate callbacks
- `_links` in response for navigation
- Standard HTTP codes (200, 400, 500)
- Snake_case normalization

#### GET /xbo/v1/ticker

```
Parameters:
  symbols (string, default: "BTC/USDT,ETH/USDT")
  refresh (int, default: 15, min: 5, max: 60)

Response: [{
  symbol, base, quote, last_price, change_24h,
  change_pct_24h, volume_24h, high_24h, low_24h
}]

Source: /trading-pairs/stats → filter by symbols
Cache TTL: 30s
```

#### GET /xbo/v1/movers

```
Parameters:
  mode (enum: gainers|losers, default: gainers)
  limit (int, default: 10, min: 1, max: 50)

Response: [{
  symbol, base, quote, last_price, change_pct_24h, volume_24h
}]

Source: /trading-pairs/stats → sort by change_pct_24h
Cache TTL: 30s
```

#### GET /xbo/v1/orderbook

```
Parameters:
  symbol (string, required, format: BTC_USDT)
  depth (int, default: 20, min: 1, max: 250)

Response: {
  symbol, bids: [{price, amount}], asks: [{price, amount}],
  spread, spread_pct, timestamp
}

Source: /orderbook/{symbol}?depth={depth}
Cache TTL: 5s
```

#### GET /xbo/v1/trades

```
Parameters:
  symbol (string, required, format: BTC/USDT)
  limit (int, default: 20, min: 1, max: 100)

Response: [{
  id, symbol, side, price, amount, total, timestamp
}]

Source: /trades?symbol={symbol} → limit
Cache TTL: 10s
```

#### GET /xbo/v1/slippage

```
Parameters:
  symbol (string, required, format: BTC_USDT)
  side (enum: buy|sell, default: buy)
  amount (float, required, min: 0.001)
  depth (int, default: 250)

Response: {
  symbol, side, amount, avg_price, best_price,
  slippage_pct, spread, spread_pct, depth_used, total_cost
}

Source: /orderbook/{symbol}?depth={depth} → calculation
Cache TTL: 5s
```

### Shortcodes

Each shortcode renders a self-contained widget with Tailwind CSS classes and Interactivity API directives.

| Shortcode | Attributes | Default |
|-----------|-----------|---------|
| `[xbo_ticker]` | symbols, refresh, columns | "BTC/USDT,ETH/USDT", 15, 4 |
| `[xbo_movers]` | mode, limit | "gainers", 10 |
| `[xbo_orderbook]` | symbol, depth, refresh | "BTC_USDT", 20, 5 |
| `[xbo_trades]` | symbol, limit, refresh | "BTC/USDT", 20, 10 |
| `[xbo_slippage]` | symbol, side, amount | "BTC_USDT", "buy", "" |

All shortcodes:
- Use `data-wp-interactive="xbo-market-kit"` for reactivity
- Enqueue Tailwind CDN and widget-specific JS viewScriptModule
- Render server-side initial HTML (no flash of empty content)
- CSS prefix: `.xbo-mk-*`

### Frontend Widgets

**Tailwind CSS CDN:** Loaded once via `wp_enqueue_script` when any widget is on the page. Configured with custom prefix to avoid conflicts.

**WP Interactivity API:**
- Each widget has its own store namespace under `@xbo-market-kit`
- Polling via `setInterval` inside Interactivity API actions
- Reactive DOM updates via `data-wp-bind`, `data-wp-text`, `data-wp-class`
- Price change animations: CSS transitions (green flash on rise, red on fall)

#### 1. Live Ticker
- Horizontal card layout (1-4 columns, responsive)
- Crypto icon: CSS circle with first letter of ticker
- Price + 24h change (green/red)
- Decorative SVG sparkline (trend direction based on change sign)
- Auto-refresh every 15s

#### 2. Top Movers
- Tabbed interface: Gainers / Losers
- Table: Pair, Price, 24h Change
- Crypto icons
- Auto-refresh every 30s

#### 3. Order Book
- Split layout: Bids (green) | Asks (red)
- Depth bars (visual volume indicators)
- Spread indicator at bottom
- Auto-refresh every 5s

#### 4. Recent Trades
- Table: Time, Side (Buy/Sell), Price, Amount
- Color-coded by side (green=buy, red=sell)
- Auto-refresh every 10s

#### 5. Slippage Calculator
- Form: Pair dropdown, Buy/Sell toggle, Amount input
- Results panel: Avg Price, Slippage %, Spread, Depth Used
- Calculation on input change (debounce 300ms)
- No auto-refresh — user-triggered calculation

### Gutenberg Blocks

All blocks are **dynamic blocks** with PHP render — same rendering code as shortcodes.

| Block | Slug | Category |
|-------|------|----------|
| Ticker | `xbo-market-kit/ticker` | xbo-market-kit |
| Top Movers | `xbo-market-kit/movers` | xbo-market-kit |
| Order Book | `xbo-market-kit/orderbook` | xbo-market-kit |
| Recent Trades | `xbo-market-kit/trades` | xbo-market-kit |
| Slippage Calculator | `xbo-market-kit/slippage` | xbo-market-kit |

Each block:
- `block.json` metadata with attributes matching shortcode params
- `render.php` calls shared rendering function
- `edit.js` with `InspectorControls` for attribute editing + `ServerSideRender` for preview
- Custom block category `xbo-market-kit` with icon

### Admin Settings

**Menu location:** Settings → XBO Market Kit

**Fields (WordPress Settings API):**
- Default trading pairs (text, comma-separated, default: "BTC/USDT,ETH/USDT")
- Cache TTL mode (select: Fast/Normal/Slow, default: Normal)
- Enable Tailwind CDN (checkbox, default: on)
- Clear cache (button, AJAX call to flush_all)

### Demo Page

Created automatically on plugin activation via `register_activation_hook`:
- Post type: `page`
- Title: "XBO Market Kit Demo"
- Status: `draft` (user publishes when ready)
- Content: Hero section + all 5 widgets + embed examples
- Stored page ID in option `xbo_market_kit_demo_page_id`
- On deactivation: page moved to trash

## Error Handling

| Scenario | Behavior |
|----------|----------|
| API timeout (10s) | Return stale cache if available + WP_Error |
| API 4xx/5xx | WP_Error with localized message |
| Invalid parameters | HTTP 400 with validation details |
| Rate limiting | Simple counter in transient, exponential backoff |
| Frontend error | Widget shows "Loading..." or "Data unavailable" state |
| Empty API response | Treated as valid (no data for pair) |

## Testing

**PHPUnit tests:**
- `ApiClientTest` — mock wp_remote_get, verify parsing, error handling
- `CacheManagerTest` — set/get/delete/flush operations
- `SlippageCalculatorTest` — slippage calculation from orderbook data
- `RestEndpointTests` — parameter validation, response schema
- `ShortcodeTests` — rendering with various parameters

**Quality gates (existing):**
- PHPCS: WordPress Coding Standards
- PHPStan: Level 6
- PreToolUse hook: PHP syntax check
- PostToolUse hook: Auto-format with phpcbf

## File Structure (Final)

```
xbo-market-kit/
├── xbo-market-kit.php                    # Main plugin bootstrap
├── includes/
│   ├── Plugin.php                        # Central orchestrator
│   ├── Api/
│   │   ├── ApiClient.php                 # HTTP client for api.xbo.com
│   │   └── ApiResponse.php               # Response DTO
│   ├── Cache/
│   │   └── CacheManager.php              # Transient-based cache
│   ├── Rest/
│   │   ├── AbstractController.php        # Base WC-style controller
│   │   ├── TickerController.php
│   │   ├── MoversController.php
│   │   ├── OrderbookController.php
│   │   ├── TradesController.php
│   │   └── SlippageController.php
│   ├── Shortcodes/
│   │   ├── AbstractShortcode.php         # Base shortcode class
│   │   ├── TickerShortcode.php
│   │   ├── MoversShortcode.php
│   │   ├── OrderbookShortcode.php
│   │   ├── TradesShortcode.php
│   │   └── SlippageShortcode.php
│   ├── Blocks/
│   │   ├── BlockRegistrar.php            # Block registration
│   │   ├── ticker/
│   │   │   ├── block.json
│   │   │   ├── render.php
│   │   │   └── edit.js
│   │   ├── movers/
│   │   │   ├── block.json
│   │   │   ├── render.php
│   │   │   └── edit.js
│   │   ├── orderbook/
│   │   │   ├── block.json
│   │   │   ├── render.php
│   │   │   └── edit.js
│   │   ├── trades/
│   │   │   ├── block.json
│   │   │   ├── render.php
│   │   │   └── edit.js
│   │   └── slippage/
│   │       ├── block.json
│   │       ├── render.php
│   │       └── edit.js
│   └── Admin/
│       ├── AdminSettings.php             # Settings page
│       └── DemoPage.php                  # Demo page creator
├── assets/
│   ├── css/
│   │   └── widgets.css                   # Widget-specific styles (animations, overrides)
│   └── js/
│       ├── interactivity/
│       │   ├── ticker.js                 # Ticker store + polling
│       │   ├── movers.js                 # Movers store + tab switching
│       │   ├── orderbook.js              # Orderbook store + polling
│       │   ├── trades.js                 # Trades store + polling
│       │   └── slippage.js               # Slippage store + calculation
│       └── admin/
│           └── settings.js               # Cache clear AJAX
├── tests/
│   ├── bootstrap.php
│   ├── Unit/
│   │   ├── Api/
│   │   │   └── ApiClientTest.php
│   │   ├── Cache/
│   │   │   └── CacheManagerTest.php
│   │   ├── Rest/
│   │   │   ├── TickerControllerTest.php
│   │   │   ├── MoversControllerTest.php
│   │   │   ├── OrderbookControllerTest.php
│   │   │   ├── TradesControllerTest.php
│   │   │   └── SlippageControllerTest.php
│   │   └── Shortcodes/
│   │       └── SlippageCalculatorTest.php
│   └── bootstrap.php
├── composer.json
├── phpstan.neon
├── phpcs.xml
└── phpunit.xml
```
