# Slippage Block Redesign

**Date:** 2026-02-26
**Status:** Approved

## Problem

1. **Narrow fields:** 3-column equal grid (`repeat(3, 1fr)`) causes pair selectors to be too narrow for ticker names.
2. **Icon bug:** When changing currency via dropdown, the selector text updates but the icon does not — the `<img>` src is static (no `data-wp-bind--src` directive).

## Solution: CSS-only layout change + icon binding fix

### Layout (Approach A — CSS-only)

**Desktop (> 768px):**

```
Row 1: [Pair (base / quote) ~60%]  [Amount input ~40%]
Row 2: [Buy | Sell toggle — full width]
```

- `grid-template-columns: 3fr 2fr` (was `repeat(3, 1fr)`)
- Toggle field: `grid-column: 1 / -1`
- Remove "Side" label — toggle is self-explanatory

**Mobile (< 768px):**

- Single column (`1fr`), order: Pair → Amount → Toggle
- No change from current mobile behavior

### Icon fix

Add `data-wp-bind--src` to the `<img>` in `render_selector()` trigger button:

- Base selector: `data-wp-bind--src="state.slippageBaseIcon"`
- Quote selector: `data-wp-bind--src="state.slippageQuoteIcon"`

JS getters `state.slippageBaseIcon` and `state.slippageQuoteIcon` already exist and resolve icons from the pair catalog.

## Files changed

| File | Change |
|------|--------|
| `assets/css/src/_slippage.css` | Grid columns `3fr 2fr`, toggle `grid-column: 1/-1` |
| `includes/Shortcodes/SlippageShortcode.php` | Add `data-wp-bind--src` on trigger img, remove "Side" label |
| `assets/js/interactivity/slippage.js` | No changes |

## Test plan

- Select different base currency → icon updates in trigger
- Select different quote currency → icon updates in trigger
- Long ticker names (e.g. MATIC) display without truncation
- Buy/Sell toggle spans full width, functions correctly
- Mobile view: single column, order Pair → Amount → Toggle
- Dark mode not broken
