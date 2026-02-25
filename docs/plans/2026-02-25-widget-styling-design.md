# XBO Market Kit — Widget Styling Design

**Date:** 2026-02-25
**Status:** Approved
**Approach:** XBO-Inspired Design System (CSS Custom Properties + BEM + PostCSS)

## Problem

Current styling uses Tailwind CDN Play (`cdn.tailwindcss.com`) which:
- Adds ~300kB JS on every page (forbidden for production by Tailwind docs)
- CSS `@layer` conflicts with WordPress core in Gutenberg editor preview
- Utility classes in PHP strings create unreadable 300+ char lines
- No dark mode theming, no customization via block/widget controls
- Cannot work reliably with Elementor widget preview

## Decision

Replace Tailwind CDN with a custom design system inspired by xbo.com styles:
- **CSS Custom Properties** for design tokens (colors, typography, spacing, shadows)
- **BEM naming** with `xbo-mk-` prefix for full CSS isolation
- **PostCSS build pipeline** for file merging + minification
- **IBM Plex Sans** (Google Fonts) for financial data display

## Design Tokens (extracted from xbo.com)

### Colors

```
Brand:
  --xbo-mk--color-primary:        #6319ff  (purple accent, CTAs)
  --xbo-mk--color-primary-dark:   #3b0f99
  --xbo-mk--color-primary-light:  #e5ddf9
  --xbo-mk--color-primary-bg:     #f4f3f8

Financial:
  --xbo-mk--color-positive:       #49b47a  (green — price up, buy)
  --xbo-mk--color-positive-bg:    #e3f9f4
  --xbo-mk--color-negative:       #fd3b5e  (red — price down, sell)
  --xbo-mk--color-negative-bg:    #fdeeee

Neutral:
  --xbo-mk--color-bg:             #ffffff
  --xbo-mk--color-bg-secondary:   #f3f3f6
  --xbo-mk--color-bg-header:      #f9f9f9
  --xbo-mk--color-text-heading:   #270a66
  --xbo-mk--color-text-primary:   #140533
  --xbo-mk--color-text-secondary: #828282
  --xbo-mk--color-text-muted:     #98a9c4
  --xbo-mk--color-border:         #e5ddf9
  --xbo-mk--color-border-light:   #f0edf7

Dark mode overrides:
  --xbo-mk--color-bg:             #121418
  --xbo-mk--color-bg-secondary:   #1f2127
  --xbo-mk--color-bg-header:      #24242a
  --xbo-mk--color-text-heading:   #ffffff
  --xbo-mk--color-text-primary:   #e0e0e0
  --xbo-mk--color-text-secondary: #98a9c4
  --xbo-mk--color-border:         #2a2a35
```

### Typography

```
--xbo-mk--font-primary:  'IBM Plex Sans', system-ui, sans-serif  (data, tables)
--xbo-mk--font-heading:  system-ui, -apple-system, sans-serif    (headings, UI)

Sizes:
  --xbo-mk--font-size-xs:   12px
  --xbo-mk--font-size-sm:   14px
  --xbo-mk--font-size-base: 16px
  --xbo-mk--font-size-lg:   20px
  --xbo-mk--font-size-xl:   24px

Weights:
  --xbo-mk--font-weight-normal:   400
  --xbo-mk--font-weight-medium:   500
  --xbo-mk--font-weight-semibold: 600
  --xbo-mk--font-weight-bold:     700
```

### Spacing, Borders, Shadows

```
Spacing:
  --xbo-mk--space-xs:  4px
  --xbo-mk--space-sm:  8px
  --xbo-mk--space-md:  12px
  --xbo-mk--space-lg:  16px
  --xbo-mk--space-xl:  20px
  --xbo-mk--space-2xl: 32px

Border radius:
  --xbo-mk--radius-sm:  4px   (charts)
  --xbo-mk--radius-md:  12px  (table rows, coin cards)
  --xbo-mk--radius-lg:  16px  (widget cards)
  --xbo-mk--radius-xl:  32px  (pills, badges)

Shadows:
  --xbo-mk--shadow-card:  1px 25px 34px rgba(116, 124, 153, 0.1)
  --xbo-mk--shadow-chart: 1px 10px 24px rgba(29, 42, 68, 0.12)
  --xbo-mk--shadow-hover: 0 4px 16px rgba(99, 25, 255, 0.12)

Transitions:
  --xbo-mk--transition-fast:    all 0.15s ease-in-out
  --xbo-mk--transition-default: all 0.3s ease-in-out
  --xbo-mk--transition-slow:    all 0.45s ease-in-out
```

## BEM Component Structure

Each widget follows: `.xbo-mk-{widget}__{element}--{modifier}`

### Ticker Widget
```
.xbo-mk-ticker                    → grid container
  .xbo-mk-ticker__card            → individual pair card
    .xbo-mk-ticker__icon          → crypto icon circle
    .xbo-mk-ticker__pair          → pair name container
      .xbo-mk-ticker__symbol      → base currency (BTC)
      .xbo-mk-ticker__name        → full pair (BTC/USDT)
    .xbo-mk-ticker__price         → current price
    .xbo-mk-ticker__change        → 24h change percentage
      --positive / --negative     → modifiers
    .xbo-mk-ticker__sparkline     → mini chart SVG
```

### Movers Widget
```
.xbo-mk-movers                    → container
  .xbo-mk-movers__tabs            → tab bar
    .xbo-mk-movers__tab           → individual tab
      --active                    → active state modifier
  .xbo-mk-movers__table           → data table
    .xbo-mk-movers__header        → table header row
    .xbo-mk-movers__row           → data row
      .xbo-mk-movers__cell        → cell
        --pair / --price / --change
      .xbo-mk-movers__trend       → trend arrow (CSS border triangle)
```

### Orderbook Widget
```
.xbo-mk-orderbook                 → container
  .xbo-mk-orderbook__header       → title bar
  .xbo-mk-orderbook__grid         → bids/asks grid
    .xbo-mk-orderbook__side       → bid or ask column
      --bids / --asks
    .xbo-mk-orderbook__level      → single price level
      .xbo-mk-orderbook__depth    → depth bar (background fill)
      .xbo-mk-orderbook__price    → price value
      .xbo-mk-orderbook__amount   → amount value
  .xbo-mk-orderbook__spread       → spread indicator
```

### Trades Widget
```
.xbo-mk-trades                    → container
  .xbo-mk-trades__header          → title bar
  .xbo-mk-trades__table           → data table
    .xbo-mk-trades__row           → trade row
      .xbo-mk-trades__cell        → cell
        --time / --side / --price / --amount
      .xbo-mk-trades__badge       → buy/sell badge
        --buy / --sell
```

### Slippage Widget
```
.xbo-mk-slippage                  → container
  .xbo-mk-slippage__header        → title bar
  .xbo-mk-slippage__form          → calculator form
    .xbo-mk-slippage__field       → form field
    .xbo-mk-slippage__input       → input element
    .xbo-mk-slippage__toggle      → buy/sell toggle
      --active
    .xbo-mk-slippage__submit      → calculate button
  .xbo-mk-slippage__results       → results grid
    .xbo-mk-slippage__metric      → single metric card
      .xbo-mk-slippage__label     → metric label
      .xbo-mk-slippage__value     → metric value
  .xbo-mk-slippage__loading       → loading state
```

## CSS Architecture

```
assets/css/
  src/
    _variables.css        → Design tokens (CSS custom properties)
    _base.css             → Scoped reset, font loading, shared utilities
    _animations.css       → Flash animations for price updates
    _ticker.css           → Ticker widget styles
    _movers.css           → Movers widget styles
    _orderbook.css        → Orderbook widget styles
    _trades.css           → Trades widget styles
    _slippage.css         → Slippage widget styles
    widgets.css           → Entry point: @import all modules
  dist/
    widgets.css           → Concatenated (dev)
    widgets.min.css       → Minified (production)
```

## Build Pipeline

PostCSS with 3 plugins:
- `postcss-import` — merge @import files into single bundle
- `postcss-nesting` — CSS nesting for compact BEM (fallback for older browsers)
- `cssnano` — production minification

```json
{
  "scripts": {
    "css:dev": "postcss assets/css/src/widgets.css -o assets/css/dist/widgets.css --watch",
    "css:build": "postcss assets/css/src/widgets.css -o assets/css/dist/widgets.min.css --env production"
  }
}
```

## Customization Architecture

### Shortcode attributes (current)
Shortcode attributes control data/behavior, not styling. Styling comes from CSS.

### Gutenberg block controls (future)
Block controls override CSS variables via inline styles on the wrapper div:
```php
$style = sprintf(
    '--xbo-mk--color-positive: %s; --xbo-mk--color-negative: %s;',
    esc_attr($attributes['positiveColor']),
    esc_attr($attributes['negativeColor'])
);
echo '<div ' . get_block_wrapper_attributes(['style' => $style]) . '>';
```

### Elementor widget controls (future)
Same approach — Elementor controls output CSS variables on the widget wrapper.

### Theme customization
Themes can override any variable globally or per-widget:
```css
:root {
  --xbo-mk--color-primary: #your-color;
}
.xbo-mk-ticker {
  --xbo-mk--radius-lg: 8px;
}
```

## Asset Loading Strategy

### Frontend
```php
wp_enqueue_style('xbo-market-kit-fonts', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap');
wp_enqueue_style('xbo-market-kit-widgets', XBO_MARKET_KIT_URL . 'assets/css/dist/widgets.min.css');
```

### Gutenberg Editor
```php
add_action('enqueue_block_editor_assets', function() {
    wp_enqueue_style('xbo-market-kit-fonts', ...);
    wp_enqueue_style('xbo-market-kit-widgets', ...);
});
```

### Elementor (future)
Via `get_style_depends()` in each widget class.

## Price Update Animations (from xbo.com)

```css
@keyframes xbo-mk-flash-positive {
  0%   { color: var(--xbo-mk--color-positive); background: var(--xbo-mk--color-positive-bg); }
  100% { color: inherit; background: transparent; }
}
@keyframes xbo-mk-flash-negative {
  0%   { color: var(--xbo-mk--color-negative); background: var(--xbo-mk--color-negative-bg); }
  100% { color: inherit; background: transparent; }
}
```
Duration: 2s (matching xbo.com behavior)

## Success Criteria

1. All 5 widgets render identically to current Tailwind version (layout, spacing, colors)
2. No Tailwind CDN dependency — zero external JS for styling
3. CSS bundle < 25kB unminified, < 15kB minified
4. Dark mode toggle works via CSS class `.xbo-mk--dark`
5. Gutenberg editor preview shows correct styles
6. All existing unit tests pass
7. PHPCS and PHPStan pass
8. No CSS conflicts with default WordPress themes (Twenty Twenty-Five)
