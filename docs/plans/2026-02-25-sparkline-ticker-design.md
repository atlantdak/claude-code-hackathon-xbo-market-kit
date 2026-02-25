# Sparkline Ticker Design

**Date:** 2026-02-25
**Status:** Approved
**Author:** Dmytro Kishkin, Claude Opus 4.6, OpenAI Codex (reviewer)

## Problem

The Live Ticker widget displays a hardcoded static SVG polyline as a sparkline — identical fake curve for every trading pair, never updates. Users see no visual representation of price trends.

## Goal

Replace the fake sparkline with an algorithmically generated trend visualization that:
- Reflects real market indicators (24h change, price position in range)
- Produces unique curves per symbol
- Progressively replaces synthetic data with real polled prices over time
- Requires zero additional API calls to XBO

## Approach: Algorithmic Sparkline (Constrained Random Walk)

Generate sparkline curves programmatically from available snapshot indicators. Not real historical data — a synthetic trend visualization shaped by real market parameters.

### Why This Approach

| Alternative | Why rejected |
|-------------|-------------|
| Static template library | Limited variety, manual work creating 30+ SVG paths, no real data progression |
| Trades API real data | Separate request per symbol (4-8 extra API calls), 145KB per response, uneven time intervals, high API load |
| Pure Perlin/sine waves | Looks periodic and artificial |

## Architecture & Data Flow

```
Page Load (PHP):
  1. get_stats() → snapshot per symbol
  2. SparklineGenerator::generatePrices(indicators, seed)
     → 40 raw price values via constrained random walk
  3. Render SVG with points normalized by buffer min/max
     + gradient with unique ID per widget instance
  4. Inject into data-wp-context:
     { sparklineData: { btcusdt: { prices, updatedAt, trend } }, widgetId }

JS Hydration (Interactivity API):
  1. Read sparklineData from context → initialize ring buffers
  2. Start polling /xbo/v1/ticker every 15s

Poll Update Cycle:
  1. Fetch new snapshot from REST API
  2. Compare updatedAt per symbol with stored value
     - Same updatedAt → stale cache, skip
     - New updatedAt → push last_price into ring buffer (even if price unchanged)
  3. Normalize buffer by its own min/max + 10% padding
  4. Update SVG polyline points + polygon fill
  5. Update gradient color if change_pct_24h sign flipped
  6. Flash price animation (existing behavior)
```

### Key Decisions

- **Single REST endpoint** — `/xbo/v1/ticker` (no new endpoints, no API changes)
- **No additional XBO API calls** — uses only existing stats data
- **PHP pre-renders initial sparkline** — no empty flash on page load
- **JS takes over on hydration** — single source of truth for runtime updates
- **Algorithm duplicated in PHP and JS** — conscious tradeoff for SSR; algorithm is ~30 lines, tested for contract parity

### Data Replacement Timeline

With polling at 15s and cache TTL at 30s, a genuinely new snapshot arrives approximately every 30s. Full replacement of 40 synthetic points with real polled data takes **~20 minutes**.

## Algorithm: Constrained Random Walk

### Inputs (all explicit parameters)

```
last_price      — current price from stats
high_24h        — 24h high from stats
low_24h         — 24h low from stats
change_pct_24h  — 24h percent change from stats
symbol          — trading pair identifier
count           — number of points (default: 40)
```

### Generation (pseudocode)

```
function generateSyntheticPrices(last_price, high_24h, low_24h,
                                  change_pct_24h, symbol, count=40):
    seed = hash(symbol + floor(Date/86400) + round(change_pct_24h))
    rng = SeededUniformRandom(seed)

    priceRange = max(high_24h - low_24h, last_price * 0.001)
    trend24 = tanh(change_pct_24h / 10)

    noiseScale = priceRange * 0.012
    drift = -trend24 * priceRange * 0.005   // negative: generating backwards
    maxStep = priceRange * 0.05

    price = last_price
    prices[count-1] = price

    for i = count-2 downto 0:
        noise = (rng.next() - 0.5) * 2 * noiseScale
        step = clamp(drift + noise, -maxStep, maxStep)
        price = max(price + step, 1e-9)
        prices[i] = price

    return prices
```

### Rendering (pseudocode)

```
function renderToSVG(prices, viewBoxWidth=100, viewBoxHeight=30):
    bufMin = min(prices)
    bufMax = max(prices)
    range = bufMax - bufMin

    if range < epsilon:
        return horizontal line at viewBoxHeight / 2

    padding = range * 0.1
    yMin = bufMin - padding
    yMax = bufMax + padding

    points = []
    for i, price in prices:
        x = i * viewBoxWidth / (len(prices) - 1)
        y = viewBoxHeight - (price - yMin) / (yMax - yMin) * viewBoxHeight
        points.push(x + "," + round(y, 1))

    polylinePoints = points.join(" ")
    polygonPoints = polylinePoints + " " + viewBoxWidth + "," + viewBoxHeight + " 0," + viewBoxHeight

    return { polylinePoints, polygonPoints }
```

### Seed Design

- `hash(symbol + day + round(change_pct_24h))` — stable within a day for a given trend
- Day boundary provides subtle day-to-day variation
- Trend bucket (`round()`) provides variety when market regime changes
- Same seed → same curve (deterministic)

### Noise Choice: Uniform (not Gaussian)

Uniform noise chosen for PHP/JS parity safety. Gaussian requires Box-Muller or similar transform — risk of divergent implementations across languages. Uniform RNG is trivial to keep identical.

The exact RNG algorithm and constants must be defined identically in both PHP and JS (or accept non-identical curves and test only invariants).

## Visual Design

### SVG Structure (per card)

```html
<svg class="xbo-mk-ticker__sparkline" viewBox="0 0 100 30"
     preserveAspectRatio="none">
  <defs>
    <linearGradient id="spark-grad-btcusdt-xbo-ticker-1"
                    x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="var(--xbo-mk--color-positive)"
            stop-opacity="0.3"/>
      <stop offset="100%" stop-color="var(--xbo-mk--color-positive)"
            stop-opacity="0"/>
    </linearGradient>
  </defs>
  <polygon fill="url(#spark-grad-btcusdt-xbo-ticker-1)"
           points="0,25 2.6,23 ... 100,15 100,30 0,30"/>
  <polyline fill="none" stroke="var(--xbo-mk--color-positive)"
            stroke-width="1.5"
            points="0,25 2.6,23 ... 100,15"/>
</svg>
```

### Color Scheme

| Condition | Stroke | Gradient |
|-----------|--------|----------|
| `change_pct_24h >= 0` | `--xbo-mk--color-positive` (green) | green 0.3 → 0 opacity |
| `change_pct_24h < 0` | `--xbo-mk--color-negative` (red) | red 0.3 → 0 opacity |

### Specifications

- No animation on point updates
- No curve smoothing (plain polyline)
- Gradient: linear top-to-bottom, 0.3 → 0 opacity
- Unique gradient ID per widget instance: `spark-grad-{symbol}-{widgetId}`

## Data Contracts

### `data-wp-context` Shape

```json
{
  "symbols": "BTC/USDT,ETH/USDT",
  "refresh": 15,
  "sparklineData": {
    "btcusdt": {
      "prices": [68500.0, 68520.3, 68490.1, "... (40 floats)"],
      "updatedAt": 1772051460,
      "trend": "positive"
    },
    "ethusdt": {
      "prices": [3820.5, 3818.2, 3825.0, "... (40 floats)"],
      "updatedAt": 1772051460,
      "trend": "negative"
    }
  },
  "widgetId": "xbo-ticker-1"
}
```

### `updatedAt` Contract

- Per-symbol, not global
- Sourced from PHP `time()` at fetch moment (or API timestamp if available)
- Monotonically increasing for a given symbol
- JS compares per-symbol: `sparklineData[key].updatedAt`

### JS Ring Buffer (per symbol)

```js
{
    prices: Array(40),         // raw prices, plain Array (not typed)
    head: 0,                   // ring buffer pointer
    realCount: 0,              // how many real (vs synthetic) points
    lastUpdatedAt: 1772051460  // last processed snapshot timestamp
}
```

### Invalid Snapshot Fallback

If snapshot contains `null`, `NaN`, or non-finite values → skip push, reuse previous buffer. Do not break sparkline because of one bad response.

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `includes/Sparkline/SparklineGenerator.php` | **Create** | `generatePrices()` + `renderSvgPoints()` + seeded RNG |
| `includes/Shortcodes/TickerShortcode.php` | Modify | Replace hardcoded SVG with generator call; inject sparklineData + widgetId into context; gradient ID + trend helpers |
| `assets/js/interactivity/ticker.js` | Modify | Ring buffer init, poll update with push/shift, SVG re-render, seeded RNG, updatedAt comparison |
| `assets/css/src/_ticker.css` | Modify | Gradient fill styles, positive/negative color variables for sparkline |
| `tests/Unit/SparklineGeneratorTest.php` | **Create** | 22 tests (see Testing section) |
| `composer.json` | Verify | Confirm `includes/Sparkline/` covered by PSR-4 autoload |

### Files NOT Changed

- `includes/Api/ApiClient.php` — no new API calls
- `includes/Rest/TickerController.php` — no response format changes
- `includes/Cache/CacheManager.php` — no caching changes

## Edge Cases

| Case | Handling |
|------|---------|
| `high_24h == low_24h` | `priceRange = last_price * 0.001` |
| `high_24h < low_24h` (bad API data) | Swap values: `[low, high] = [min(low,high), max(low,high)]` |
| `last_price == 0` | Skip sparkline render entirely |
| All buffer prices equal | Horizontal line at `viewBoxHeight / 2` |
| `change_pct_24h == 0` | `trend24 = 0`, drift = 0, pure noise; color = green (default) |
| `change_pct_24h > 500%` | `tanh()` saturates near 1.0 — safe |
| Stale snapshot (same updatedAt) | Skip buffer push |
| New snapshot, same price | Push point (flat market is valid data) |
| Invalid snapshot values (NaN/null) | Skip push, reuse previous buffer |
| `count < 2` | Return `[last_price]` or empty array |
| Multiple widgets on same page | Unique gradient IDs via `widgetId` (`wp_unique_id()`) |

## Testing

### PHPUnit Tests (22 total)

**Unit — SparklineGenerator:**
1. `test_generates_correct_count` — 40 floats
2. `test_last_point_equals_last_price` — prices[39] === last_price
3. `test_deterministic_output` — same seed → identical
4. `test_different_seeds_different_curves` — BTC vs ETH
5. `test_all_prices_positive` — all > 0
6. `test_all_prices_finite` — no NaN/Infinity
7. `test_handles_zero_range` — high == low
8. `test_handles_zero_price` — returns empty
9. `test_handles_negative_change` — change=-50
10. `test_handles_extreme_change` — change=+500
11. `test_handles_inverted_range` — high < low
12. `test_handles_tiny_range` — near zero
13. `test_handles_nan_input` — NaN/null → safe default
14. `test_count_minimum` — count < 2
15. `test_svg_points_within_viewbox` — x∈[0,100], y∈[0,30]
16. `test_svg_polygon_closes_path` — ends with "100,30 0,30"
17. `test_renderer_handles_empty_prices` — no crash
18. `test_trend_direction` — parameterized (positive/negative/zero)

**Integration — Shortcode:**
19. `test_shortcode_renders_valid_svg` — valid SVG output
20. `test_context_contains_sparkline_data` — correct JSON structure
21. `test_multiple_widgets_unique_gradient_ids` — no ID collisions
22. `test_context_json_encoding_valid` — parseable, correct escaping

### Parity Check PHP ↔ JS (fixture-based)

Contract verification, not byte-for-byte:
- Count: 40
- Last point anchored to last_price
- All positive, all finite
- Overall trend direction matches (first < last for positive)

### Browser Smoke (manual)

- Page with 8 tickers simultaneously
- DevTools Console: 0 errors
- All SVGs contain polyline with ~40 coordinate pairs
- Gradient IDs unique across all cards
- Network tab: poll requests fire every ~15s
- Two `[xbo_ticker]` blocks on same page: independent operation

## Future Enhancements (out of scope)

- **Trades API enrichment:** for top pairs (BTC, ETH), optionally fetch real trade history from `/trades?symbol=X` to seed sparkline with actual data instead of synthetic
- **Catmull-Rom smoothing:** smooth polyline with spline interpolation for premium look
- **Animated transitions:** slide curve left on new data point via requestAnimationFrame
- **Y-scale damping:** smooth vertical range changes to reduce "breathing" effect
- **Configurable point count:** allow users to set sparkline resolution via shortcode attribute
