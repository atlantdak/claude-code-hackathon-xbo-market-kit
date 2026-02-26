# Block Design Consistency — Full-Width Layout with Inner Container

**Date:** 2026-02-26
**Status:** Approved
**Scope:** All 5 Gutenberg blocks (Ticker, Movers, Orderbook, Trades, Slippage)

## Problem

1. **Ticker cards narrower than other blocks on mobile** — `max-width: calc(25% - 12px)` from `cols-4` class doesn't adapt; `min-width: 200px` prevents collapse but cards (200px) don't fill the container (315px), leaving gaps due to `justify-content: center`.
2. **Theme padding constrains all blocks** — WordPress `is-layout-constrained` + `has-global-padding` limits blocks to `contentSize` (e.g., 645px on Twenty Twenty-Five) with 30–50px side padding.
3. **No alignment support** — blocks don't declare `align` in `block.json`, so users cannot choose wide/full in the editor.
4. **No responsive breakpoint system** — only Slippage has media queries (768px, 480px); Ticker, Movers, Orderbook, Trades have none.

## Decision

**Approach B: `alignfull` outer wrapper + inner `.xbo-mk-inner` container.**

- Outer block wrapper goes full viewport width via WordPress `alignfull`.
- Inner wrapper constrains content to a shared `--xbo-mk--content-max` (default 1200px).
- Defensive CSS ensures full-width even in classic themes without `align-wide` support.
- On mobile/tablet, blocks are effectively edge-to-edge with minimal padding.

### Why not other approaches

- **Approach A (`alignwide`):** Width depends on theme's `wideSize` — inconsistent across themes.
- **Approach C (CSS hacks):** `margin-left: calc(-50vw + 50%)` breaks with scrollbars, nested containers, and varies across themes.

## Architecture

### Container hierarchy

```
<div class="wp-block-xbo-market-kit-{name} alignfull">   ← WordPress alignfull (100vw)
  <div class="xbo-mk-inner">                             ← NEW shared wrapper
    <div class="xbo-mk-{name}">                          ← Existing block container
      ...block content...
    </div>
  </div>
</div>
```

### New CSS tokens

```css
:root {
  --xbo-mk--content-max: 1200px;
  --xbo-mk--content-padding: var(--xbo-mk--space-lg);  /* 16px */
}
```

### Inner wrapper styles

```css
.xbo-mk-inner {
  max-width: var(--xbo-mk--content-max, 1200px);
  margin-inline: auto;
  width: 100%;
  padding-inline: var(--xbo-mk--content-padding, 16px);
  box-sizing: border-box;
}

@media (max-width: 768px) {
  .xbo-mk-inner {
    padding-inline: var(--xbo-mk--space-md, 12px);
  }
}
```

### Defensive full-width CSS (theme-agnostic)

```css
.wp-block-xbo-market-kit-ticker.alignfull,
.wp-block-xbo-market-kit-movers.alignfull,
.wp-block-xbo-market-kit-orderbook.alignfull,
.wp-block-xbo-market-kit-trades.alignfull,
.wp-block-xbo-market-kit-slippage.alignfull {
  width: 100vw;
  max-width: 100vw;
  margin-left: calc(-50vw + 50%);
}
```

## block.json changes

All 5 block.json files get:

```json
"supports": {
  "html": false,
  "align": ["wide", "full"]
},
"attributes": {
  "align": {
    "type": "string",
    "default": "full"
  }
}
```

## Responsive strategy

### Breakpoints (mobile-first)

| Name | Value | Context |
|------|-------|---------|
| sm | 480px | Small phones |
| md | 768px | Tablets — aligns with Elementor mobile default (~767px) |
| lg | 1024px | Desktop — aligns with Elementor tablet default |

These are standard values that work across Gutenberg, Elementor, and any theme. CSS Custom Properties allow themes/users to override `--xbo-mk--content-max`.

### Ticker card responsive fix

```css
/* Mobile: always 1 column */
@media (max-width: 768px) {
  .xbo-mk-ticker__card {
    flex-basis: 100%;
    max-width: 100%;
  }
}

/* Tablet: max 2 columns for 3-4 col layouts */
@media (min-width: 769px) and (max-width: 1024px) {
  .xbo-mk-ticker[class*="--cols-3"] .xbo-mk-ticker__card,
  .xbo-mk-ticker[class*="--cols-4"] .xbo-mk-ticker__card {
    max-width: calc(50% - 8px);
  }
}
```

## Theme portability

| Context | Outer wrapper | Behavior |
|---------|--------------|----------|
| Block theme (Gutenberg) | `alignfull` from WP + defensive fallback CSS | 100vw → inner 1200px |
| Classic theme (Gutenberg) | Defensive CSS stretches to 100vw | 100vw → inner 1200px |
| Elementor / Shortcode | No alignfull; block inside builder container | Inner sets max-width, centers content |
| Elementor full-width section | Section already 100vw | Inner 1200px, works as expected |

**Key principle:** outer wrapper goes full-width *when possible*, while `.xbo-mk-inner` **always** guarantees consistent max-width and padding regardless of context.

## Design rationale: 1200px max-width

- Theme's `wideSize` (1340px on TT5) is too wide for financial data tables — hurts readability.
- 1200px is the industry standard for dashboard layouts (Binance, Coinbase use similar widths).
- Easily overridable via `--xbo-mk--content-max` CSS custom property.

## Files affected

- `assets/css/src/_variables.css` — new layout tokens
- `assets/css/src/_base.css` — `.xbo-mk-inner` styles + defensive alignfull CSS
- `assets/css/src/_ticker.css` — responsive card fixes
- `includes/Blocks/*/block.json` (5 files) — align support + default attribute
- `includes/Shortcodes/AbstractShortcode.php` (or equivalent) — add `.xbo-mk-inner` wrapper to render output
