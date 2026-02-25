# Slippage Calculator UX Redesign

**Date:** 2026-02-25
**Status:** Approved
**Scope:** Replace plain text pair input with cascading currency dropdowns, add defaults, fix state model

## Problem

The Slippage Calculator widget has three UX issues:

1. **Pair selector is a plain text input** — users must type exact format (`BTC_USDT`), no validation, no feedback on invalid pairs
2. **No default amount** — metrics area is empty on page load, widget looks broken
3. **Errors are silently swallowed** — invalid pair or API failure shows nothing

## Solution Overview

Replace the single text input with two cascading custom dropdown selectors (Base + Quote currency) with autocomplete, ticker icons, and server-rendered pair data. Set meaningful defaults and auto-calculate on load.

## Data Architecture

### Pair Catalog

PHP loads trading pairs from XBO API via `ApiClient::get_trading_pairs()` (6-hour cache TTL). Builds two structures:

```php
$pairs_map = [
    'BTC' => ['USDT', 'USD', 'EUR'],
    'ETH' => ['USDT', 'USD', 'EUR', 'BTC'],
    'ADA' => ['USDT', 'USD', 'EUR', 'BTC'],
    // ... 180 base currencies total
];

$icons_map = [
    'BTC' => 'https://.../icons/btc.svg',
    'ETH' => 'https://.../icons/eth.svg',
    // ... resolved via IconResolver (205 SVGs + placeholder fallback)
];
```

### Catalog Delivery

- **Small config** (REST URL, feature flags): `wp_interactivity_config('xbo-market-kit', [...])`
- **Large catalog** (pairs_map, icons_map): single `<script type="application/json" id="xbo-mk-pairs-catalog">` rendered via `wp_footer` hook, only when at least one slippage widget is on the page
- JS reads catalog once on first widget init, caches in module-level variable

### Mutual Exclusion

When base is selected (e.g., `BTC`), the quote dropdown shows only valid quotes for that base from `pairs_map[base]`. The selected base is hidden from the quote list and vice versa.

When quote changes, if the current base has no pair with the new quote, base resets to the first available option.

## Shortcode API

```
[xbo_slippage symbol="BTC_USDT" side="buy" amount="1"]
```

- `symbol` remains the canonical attribute (backward compatible)
- PHP parses `symbol` into base/quote internally (supports both `_` and `/` formats)
- `amount` default changes from `""` to `"1"`
- `side` default remains `"buy"`
- Auto-calculate on page load via `initSlippage()`
- Block.json `symbol` attribute unchanged

## UI Component: Custom Dropdown Selector

### HTML Structure (per dropdown)

```html
<div class="xbo-mk-slippage__selector" role="combobox"
     aria-expanded="false" aria-haspopup="listbox"
     aria-owns="xbo-mk-slippage-{instance}-{base|quote}-list">
  <button class="xbo-mk-slippage__selector-trigger"
          aria-label="Select base currency">
    <img class="xbo-mk-slippage__selector-icon"
         src=".../btc.svg" alt="" width="20" height="20" />
    <span class="xbo-mk-slippage__selector-text">BTC</span>
    <span class="xbo-mk-slippage__selector-chevron" aria-hidden="true">▾</span>
  </button>
  <div class="xbo-mk-slippage__selector-dropdown">
    <input class="xbo-mk-slippage__selector-search"
           type="text" placeholder="Search..."
           role="searchbox" aria-label="Filter currencies" />
    <ul class="xbo-mk-slippage__selector-list"
        role="listbox" id="xbo-mk-slippage-{instance}-{base|quote}-list">
      <li class="xbo-mk-slippage__selector-item" role="option"
          aria-selected="false" data-symbol="ETH">
        <img src=".../eth.svg" alt="" width="20" height="20" loading="lazy" />
        <span>ETH</span>
      </li>
      <!-- ... more items -->
      <li class="xbo-mk-slippage__selector-empty" role="option" aria-disabled="true">
        No results
      </li>
    </ul>
  </div>
</div>
```

### Form Layout

3-column grid (unchanged). First column contains both dropdowns with `/` separator:

```
┌─────────────────────────┐  ┌───────────┐  ┌───────────┐
│  [🪙 BTC ▾] / [🪙 USDT ▾]  │  │  Buy|Sell  │  │    1.0    │
│           Pair              │  │    Side    │  │   Amount  │
└─────────────────────────┘  └───────────┘  └───────────┘
```

Mobile (≤768px): fields stack vertically, pair selectors stack vertically within their column, dropdown panels become full-width.

### Dropdown Behavior

| Action | Result |
|--------|--------|
| Click trigger | Open dropdown, focus search input |
| Type in search | Filter list case-insensitively |
| Click item | Select, close dropdown, trigger calculation |
| Enter key | Select highlighted item |
| Escape key | Close without selection |
| ↑↓ arrows | Navigate list, update `aria-activedescendant` |
| Click outside | Close dropdown |
| Tab key | Close dropdown, move focus to next element |

Max dropdown height: ~200px with `overflow-y: auto`.

### Icon Lazy Loading

Items inside closed dropdowns use `loading="lazy"` on `<img>` tags. Only trigger icons (2 visible) load eagerly.

## State Model (Interactivity API)

### Per-Instance Context (via `getContext()`)

```js
{
  base: 'BTC',
  quote: 'USDT',
  side: 'buy',
  amount: '1',
  result: null,
  loading: false,
  error: '',
  baseOpen: false,
  quoteOpen: false,
  baseSearch: '',
  quoteSearch: '',
}
```

### Global State (derived getters only)

```js
state: {
  get slippageIsBuy() { return getContext().side === 'buy'; },
  get slippageIsSell() { return getContext().side === 'sell'; },
  get slippageHasResult() { return !!getContext().result; },
  get slippageHasError() { return !!getContext().error; },
  // ... formatted metric getters
}
```

### AbortController

Each context holds an `AbortController` reference. On new calculation:

```js
async slippageCalculate() {
  const ctx = getContext();
  if (ctx._abortController) ctx._abortController.abort();
  ctx._abortController = new AbortController();
  const { signal } = ctx._abortController;

  try {
    const res = await fetch(url, { signal });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    if (signal.aborted) return;
    const data = await res.json();
    ctx.result = data;
    ctx.error = '';
  } catch (e) {
    if (e.name === 'AbortError') return;
    ctx.error = 'Failed to calculate slippage';
  } finally {
    if (!signal.aborted) ctx.loading = false;
  }
}
```

### Debounce with `withScope`

```js
import { store, getContext, withScope } from '@wordpress/interactivity';

// Per-instance debounce via context
slippageDebounceCalc() {
  const ctx = getContext();
  if (ctx._debounceTimer) clearTimeout(ctx._debounceTimer);
  const scopedCalc = withScope(actions.slippageCalculate);
  ctx._debounceTimer = setTimeout(scopedCalc, 300);
}
```

### REST URL

Passed via `wp_interactivity_config()`:

```php
wp_interactivity_config('xbo-market-kit', [
    'restUrl' => rest_url('xbo/v1/'),
]);
```

JS reads via `getConfig()`:

```js
import { getConfig } from '@wordpress/interactivity';
const { restUrl } = getConfig('xbo-market-kit');
```

## Partial Fill Warning

Client-side detection: `depth_used < amount`. No REST API changes needed.

```html
<div class="xbo-mk-slippage__warning" data-wp-class--xbo-mk-hidden="!state.slippageIsPartialFill">
  ⚠ Only {depth_used} of {amount} filled — insufficient liquidity
</div>
```

Styled with yellow/amber background, visible only when partial fill detected.

## Error Handling

| Scenario | UI Behavior |
|----------|-------------|
| API returns HTTP error | Inline error message below form |
| Invalid pair (removed from API) | Pair not in dropdown list — impossible to select |
| Empty search results | "No results" shown in dropdown |
| Network failure | "Failed to calculate slippage" inline error |
| Partial fill | Yellow warning with filled/unfilled amounts |

## CSS Changes

New classes (BEM, `.xbo-mk-slippage__` prefix):

- `__selector`, `__selector-trigger`, `__selector-icon`, `__selector-text`, `__selector-chevron`
- `__selector-dropdown`, `__selector-search`, `__selector-list`, `__selector-item`, `__selector-empty`
- `__selector--open` (modifier for open state)
- `__selector-item--highlighted` (keyboard navigation)
- `__selector-item--selected` (current selection)
- `__pair` (container for base + separator + quote)
- `__pair-separator` (the "/" between dropdowns)
- `__warning` (partial fill warning)
- `__error` (inline error message)

Dropdown uses `position: absolute`, `z-index`, card shadow. Smooth open/close via opacity + transform transition.

## Files Changed

| File | Change |
|------|--------|
| `includes/Shortcodes/SlippageShortcode.php` | New render method with dropdown HTML, pair catalog injection, `amount` default `"1"` |
| `assets/js/interactivity/slippage.js` | Rewrite: context-based state, dropdown logic, AbortController, withScope debounce, getConfig for REST URL |
| `assets/css/src/_slippage.css` | New dropdown styles, pair layout, warning/error styles, mobile breakpoints |
| `includes/Shortcodes/AbstractShortcode.php` | Add `wp_interactivity_config()` call for REST URL (once) |
| `includes/Blocks/slippage/render.php` | No changes (delegates to shortcode) |
| `includes/Blocks/slippage/block.json` | No changes (symbol attribute unchanged) |
| `tests/Unit/Shortcodes/SlippageShortcodeTest.php` | New/updated tests for defaults, symbol parsing |

## Out of Scope

- Bottom-sheet mobile pattern (future iteration)
- Fixing REST URL in other widgets (separate task)
- New REST API fields for partial fill (client-side detection sufficient)
- Elementor/Gutenberg block editor UI for pair selection (future)
- `base`/`quote` as separate shortcode attributes (unnecessary — UI handles selection)

## Testing Checklist

- [ ] Default BTC/USDT renders with calculated metrics on load
- [ ] Base dropdown shows 180 currencies with icons
- [ ] Quote dropdown filters based on selected base
- [ ] Mutual exclusion works (same currency hidden from other dropdown)
- [ ] Autocomplete filters case-insensitively
- [ ] Keyboard navigation (arrows, Enter, Escape, Tab)
- [ ] Click outside closes dropdown
- [ ] Two widgets on same page work independently
- [ ] REST URL works on subdirectory WP install
- [ ] Rapid pair switching doesn't show stale results
- [ ] Partial fill shows warning
- [ ] API error shows inline error message
- [ ] Mobile layout stacks correctly at ≤768px
- [ ] `[xbo_slippage symbol="ETH_USDT"]` renders with ETH selected
- [ ] Icons fallback to placeholder for unknown currencies
