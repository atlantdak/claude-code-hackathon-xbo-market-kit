# Slippage Calculator: Default Amount & Always-Visible Results

**Date:** 2026-02-27
**Status:** Approved

## Problem

1. **No default amount in block:** `block.json` has `amount.default = ""`, so blocks placed on pages render with empty amount field. The results table is completely hidden until user inputs a value.
2. **Table hidden when empty:** Results section uses `data-wp-class--xbo-mk-hidden="!state.slippageHasResult"` — hidden until API returns data. Users see nothing until they interact.
3. **Amount field height mismatch:** The `<input type="number">` (`.xbo-mk-slippage__input`) may not perfectly match the visual height of the pair selector triggers (`.xbo-mk-slippage__selector-trigger`).

## Solution

### 1. Default Amount Value

- `block.json` (both source and built): `amount.default` → `"1"`
- `render.php`: fallback — if `$attributes['amount']` is empty, use `'1'`
- Shortcode defaults already use `'1'` — no change needed
- Existing blocks with `amount=""` in post content automatically get `'1'` via render.php fallback

### 2. Always-Visible Results Table

- Remove `data-wp-class--xbo-mk-hidden="!state.slippageHasResult"` from results div
- Results table always rendered and visible
- When no API result yet: metric values show `—` (em dash)
- When API result available: show real formatted values
- JS getters updated: each `slippageResult_*` getter returns `'—'` when `context.result` is null/undefined

### 3. Auto-Calculate on Load

- `initSlippage()` already calls `slippageDebounceCalc()` when amount is present
- With default amount `'1'` always set, calculation triggers automatically on page load
- Result: page loads → amount=1 visible → API fires → real data replaces dashes

### 4. Field Height Alignment

- Add explicit `height: 40px` to both `.xbo-mk-slippage__input` and `.xbo-mk-slippage__selector-trigger`
- Use `box-sizing: border-box` (already set globally) to ensure consistent sizing including borders

## Files Changed

| File | Change |
|------|--------|
| `src/blocks/slippage/block.json` | `amount.default`: `""` → `"1"` |
| `includes/Blocks/slippage/block.json` | `amount.default`: `""` → `"1"` |
| `includes/Blocks/slippage/render.php` | Add fallback: empty amount → `'1'` |
| `includes/Shortcodes/SlippageShortcode.php` | Remove `data-wp-class--xbo-mk-hidden` from results div |
| `assets/js/interactivity/slippage.js` | Getters return `'—'` when no result |
| `assets/css/src/_slippage.css` | Add explicit `height: 40px` to input and selector trigger |

## Backward Compatibility

Existing blocks in post content have `amount=""` saved. The render.php fallback ensures these blocks render with amount=1 without requiring content migration.
