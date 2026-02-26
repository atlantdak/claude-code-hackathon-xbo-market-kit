# Block Settings: Trading Pair Selector — Design

**Date:** 2026-02-26
**Author:** Dmytro Kishkin
**Status:** Approved

## Problem

All 5 Gutenberg blocks currently use `TextControl` for trading pair input, requiring users to type pair symbols manually in inconsistent formats (`BTC/USDT` in Trades, `BTC_USDT` in OrderBook and Slippage). There is no pair validation or autocomplete.

## Goals

1. Replace manual text input with searchable dropdowns backed by the live XBO API pair list
2. Normalize all block attribute storage to SLASH format (`BTC/USDT`)
3. Create reusable shared components to avoid duplication
4. Do not break existing saved content

## Architecture

### REST Endpoint

`GET /wp-json/xbo/v1/trading-pairs`

Returns a flat array of all available trading pairs in SLASH format:

```json
["BTC/USDT", "ETH/USDT", "BNB/USDT", "SOL/USDT", ...]
```

- Controller: `includes/Rest/TradingPairsController.php`
- Reuses `ApiClient::get_trading_pairs()` with existing transient caching
- Public endpoint (no auth required, read-only market data)

### Shared Hook

`src/blocks/shared/useTradingPairs.js`

```js
function useTradingPairs() {
  // Uses @wordpress/api-fetch to load /xbo/v1/trading-pairs
  // Returns { pairs: string[], loading: bool, error: string|null }
}
```

Single hook shared across all block editors. Results cached in component state for the editor session.

### Shared Components

`src/blocks/shared/PairSelector.js`
- Uses `ComboboxControl` from `@wordpress/components`
- Searchable/filterable dropdown for a single trading pair
- Used in: OrderBook, Trades, Slippage

`src/blocks/shared/PairsSelector.js`
- Uses `FormTokenField` from `@wordpress/components`
- Multi-pair input with autocomplete suggestions
- Used in: Ticker (`symbols` attribute)

## Block Changes

| Block | Old Control | New Control | Attribute Change |
|-------|-------------|-------------|-----------------|
| Ticker | TextControl | PairsSelector (FormTokenField) | `symbols` — no change (already SLASH) |
| OrderBook | TextControl | PairSelector (ComboboxControl) | `symbol` default: `BTC_USDT` → `BTC/USDT` |
| Trades | TextControl | PairSelector (ComboboxControl) | `symbol` — no change (already SLASH) |
| Slippage | TextControl | PairSelector (ComboboxControl) | `symbol` default: `BTC_USDT` → `BTC/USDT` |
| Movers | — | — | No change |

## Format Normalization

**Canonical storage format:** SLASH (`BTC/USDT`) for all block attributes.

PHP render callbacks that use underscore format (OrderBook, Slippage) call
`ApiClient::to_underscore_format()` before passing to the API. This conversion
is already implemented and tested. Existing saved content with `BTC_USDT` will
continue to work because the render PHP already normalizes.

## Backward Compatibility

- Existing pages with `symbol="BTC_USDT"` continue to render correctly — PHP
  render callbacks accept both formats (conversion is idempotent).
- No database migration needed.
- Default values in `block.json` updated to SLASH format for new block
  instances only.

## Files to Create

```
includes/Rest/TradingPairsController.php   (new)
src/blocks/shared/useTradingPairs.js       (new)
src/blocks/shared/PairSelector.js          (new)
src/blocks/shared/PairsSelector.js         (new)
```

## Files to Modify

```
includes/Rest/RestRegistrar.php            (register new route)
src/blocks/ticker/index.js                 (use PairsSelector)
src/blocks/orderbook/index.js              (use PairSelector)
src/blocks/trades/index.js                 (use PairSelector)
src/blocks/slippage/index.js              (use PairSelector)
src/blocks/orderbook/block.json            (default: BTC/USDT)
src/blocks/slippage/block.json             (default: BTC/USDT)
```

## Out of Scope

- Frontend (post/page) UI changes — Slippage already has interactive pair selector
- Icons in editor dropdowns — standard WordPress ComboboxControl/FormTokenField
- Pairs map / hierarchical base→quote filtering (YAGNI for admin context)
