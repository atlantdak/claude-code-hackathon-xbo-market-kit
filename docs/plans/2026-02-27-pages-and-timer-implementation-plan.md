# Pages Redesign & Refresh Timer Block — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a Refresh Timer Gutenberg block, redesign all site pages with gradient/glassmorphism visual style, build programmatic page generation with navigation menu, and register reusable block patterns.

**Architecture:** New `refresh-timer` block follows existing block pattern (block.json + render.php + editor JS + Interactivity API JS). PageManager replaces DemoPage to create 8 pages programmatically on activation. PatternRegistrar registers block patterns for reusable sections. Custom CSS in `_timer.css` and `_pages.css` imported via `widgets.css`.

**Tech Stack:** PHP 8.1+, WordPress 6.9.1 Block API, Interactivity API, PostCSS, wp-scripts (webpack)

**Design Doc:** `docs/plans/2026-02-27-pages-and-timer-design.md`

---

## Task 1: Refresh Timer — block.json + render.php

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/includes/Blocks/refresh-timer/block.json`
- Create: `wp-content/plugins/xbo-market-kit/includes/Blocks/refresh-timer/render.php`

**Step 1: Create block.json**

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "xbo-market-kit/refresh-timer",
  "version": "0.1.0",
  "title": "XBO Refresh Timer",
  "category": "xbo-market-kit",
  "icon": "clock",
  "description": "Circular countdown timer synced with XBO block refresh intervals.",
  "textdomain": "xbo-market-kit",
  "attributes": {
    "interval": {
      "type": "number",
      "default": 0
    },
    "label": {
      "type": "string",
      "default": "Next data refresh"
    },
    "size": {
      "type": "string",
      "default": "medium"
    },
    "showSeconds": {
      "type": "boolean",
      "default": true
    },
    "align": {
      "type": "string",
      "default": ""
    }
  },
  "supports": {
    "html": false,
    "align": ["wide", "full"]
  },
  "editorScript": "file:../../../build/blocks/refresh-timer/index.js",
  "render": "file:./render.php"
}
```

**Step 2: Create render.php**

The render.php creates a RefreshTimerShortcode-like output directly (no shortcode needed — this is a presentation-only block). It renders an SVG circle with Interactivity API directives.

```php
<?php
declare(strict_types=1);

$interval     = (int) ( $attributes['interval'] ?? 0 );
$label        = $attributes['label'] ?? 'Next data refresh';
$size         = $attributes['size'] ?? 'medium';
$show_seconds = $attributes['showSeconds'] ?? true;

$sizes = array(
    'small'  => 80,
    'medium' => 120,
    'large'  => 160,
);
$diameter    = $sizes[ $size ] ?? 120;
$radius      = ( $diameter - 8 ) / 2;
$circumf     = 2 * M_PI * $radius;
$center      = $diameter / 2;
$font_size   = $size === 'small' ? 16 : ( $size === 'large' ? 32 : 24 );
$stroke_w    = $size === 'small' ? 3 : ( $size === 'large' ? 5 : 4 );

$context = array(
    'interval'    => $interval,
    'remaining'   => $interval,
    'circumf'     => $circumf,
    'dashOffset'  => 0,
    'isPulsing'   => false,
    'displayText' => '--',
);

$wrapper_attrs = get_block_wrapper_attributes( array(
    'class' => 'xbo-mk-timer xbo-mk-timer--' . esc_attr( $size ),
) );

// Enqueue assets.
if ( ! wp_style_is( 'xbo-market-kit-widgets', 'enqueued' ) ) {
    $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    wp_enqueue_style(
        'xbo-market-kit-widgets',
        XBO_MARKET_KIT_URL . 'assets/css/dist/widgets' . $suffix . '.css',
        array(),
        XBO_MARKET_KIT_VERSION
    );
}

wp_enqueue_script_module(
    'xbo-market-kit-refresh-timer',
    XBO_MARKET_KIT_URL . 'assets/js/interactivity/refresh-timer.js',
    array( '@wordpress/interactivity' ),
    XBO_MARKET_KIT_VERSION
);
?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<div data-wp-interactive="xbo-market-kit"
     data-wp-context='<?php echo esc_attr( wp_json_encode( $context ) ); ?>'
     data-wp-init="actions.initRefreshTimer"
     data-wp-class--xbo-mk-timer--pulsing="context.isPulsing"
     class="xbo-mk-timer__wrap">

    <svg class="xbo-mk-timer__svg"
         width="<?php echo (int) $diameter; ?>"
         height="<?php echo (int) $diameter; ?>"
         viewBox="0 0 <?php echo (int) $diameter; ?> <?php echo (int) $diameter; ?>">
        <defs>
            <linearGradient id="xbo-timer-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="var(--xbo-mk--color-primary)" />
                <stop offset="100%" stop-color="var(--xbo-mk--color-positive)" />
            </linearGradient>
        </defs>
        <!-- Background track -->
        <circle class="xbo-mk-timer__track"
                cx="<?php echo (int) $center; ?>"
                cy="<?php echo (int) $center; ?>"
                r="<?php echo esc_attr( (string) round( $radius, 1 ) ); ?>"
                fill="none"
                stroke="var(--xbo-mk--color-border)"
                stroke-width="<?php echo (int) $stroke_w; ?>" />
        <!-- Progress arc -->
        <circle class="xbo-mk-timer__progress"
                cx="<?php echo (int) $center; ?>"
                cy="<?php echo (int) $center; ?>"
                r="<?php echo esc_attr( (string) round( $radius, 1 ) ); ?>"
                fill="none"
                stroke="url(#xbo-timer-gradient)"
                stroke-width="<?php echo (int) $stroke_w; ?>"
                stroke-linecap="round"
                stroke-dasharray="<?php echo esc_attr( (string) round( $circumf, 2 ) ); ?>"
                data-wp-style--stroke-dashoffset="context.dashOffset"
                transform="rotate(-90 <?php echo (int) $center; ?> <?php echo (int) $center; ?>)" />
    </svg>

    <?php if ( $show_seconds ) : ?>
    <div class="xbo-mk-timer__display"
         style="font-size: <?php echo (int) $font_size; ?>px"
         data-wp-text="context.displayText">--</div>
    <?php endif; ?>

    <div class="xbo-mk-timer__label"><?php echo esc_html( $label ); ?></div>
</div>
</div>
```

**Step 3: Register block in BlockRegistrar.php**

Modify `includes/Blocks/BlockRegistrar.php:22-28` — add `'refresh-timer'` to BLOCKS array:

```php
private const BLOCKS = array(
    'ticker',
    'movers',
    'orderbook',
    'trades',
    'slippage',
    'refresh-timer',
);
```

**Step 4: Verify block renders without errors**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpstan`
Expected: No new errors

**Step 5: Commit**

```bash
git add includes/Blocks/refresh-timer/ includes/Blocks/BlockRegistrar.php
git commit -m "feat(blocks): add refresh-timer block.json and server-side render"
```

---

## Task 2: Refresh Timer — Interactivity API JavaScript

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/assets/js/interactivity/refresh-timer.js`

**Step 1: Create refresh-timer.js**

```javascript
/**
 * XBO Market Kit — Refresh Timer Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

store( 'xbo-market-kit', {
  actions: {
    initRefreshTimer() {
      const ctx = getContext();
      let interval = ctx.interval;

      // Auto-detect: scan for other XBO blocks with data-xbo-refresh.
      if ( ! interval || interval <= 0 ) {
        const refreshEls = document.querySelectorAll( '[data-xbo-refresh]' );
        const intervals = [];
        refreshEls.forEach( ( el ) => {
          const val = parseInt( el.getAttribute( 'data-xbo-refresh' ), 10 );
          if ( val > 0 ) {
            intervals.push( val );
          }
        } );
        interval = intervals.length > 0 ? Math.min( ...intervals ) : 15;
      }

      ctx.interval = interval;
      ctx.remaining = interval;
      ctx.displayText = interval + 's';
      ctx.dashOffset = '0';

      const circumf = ctx.circumf;

      const tick = () => {
        ctx.remaining -= 1;

        if ( ctx.remaining <= 0 ) {
          // Pulse animation on reset.
          ctx.isPulsing = true;
          ctx.remaining = interval;
          ctx.dashOffset = '0';
          ctx.displayText = interval + 's';

          setTimeout( () => {
            ctx.isPulsing = false;
          }, 600 );
        } else {
          const progress = 1 - ( ctx.remaining / interval );
          ctx.dashOffset = ( progress * circumf ).toFixed( 2 );
          ctx.displayText = ctx.remaining + 's';
        }
      };

      setInterval( tick, 1000 );
    },
  },
} );
```

**Step 2: Verify file is loadable**

Open browser to any page with the refresh-timer block. Check DevTools console for errors.

**Step 3: Commit**

```bash
git add assets/js/interactivity/refresh-timer.js
git commit -m "feat(blocks): add refresh-timer Interactivity API JS with auto-sync"
```

---

## Task 3: Refresh Timer — Editor UI (React)

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/refresh-timer/index.js`

**Step 1: Create editor component**

```jsx
import { registerBlockType } from '@wordpress/blocks';
import { RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
  edit: ( { attributes, setAttributes } ) => {
    const { interval, label, size, showSeconds } = attributes;

    return (
      <XboBlockEdit
        blockName={ metadata.name }
        attributes={ attributes }
        setAttributes={ setAttributes }
        title={ __( 'XBO Refresh Timer', 'xbo-market-kit' ) }
        icon="clock"
        inspectorControls={
          <>
            <RangeControl
              label={ __( 'Interval (seconds, 0 = auto)', 'xbo-market-kit' ) }
              value={ interval }
              onChange={ ( val ) => setAttributes( { interval: val } ) }
              min={ 0 }
              max={ 60 }
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
            <TextControl
              label={ __( 'Label', 'xbo-market-kit' ) }
              value={ label }
              onChange={ ( val ) => setAttributes( { label: val } ) }
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
            <SelectControl
              label={ __( 'Size', 'xbo-market-kit' ) }
              value={ size }
              options={ [
                { label: __( 'Small (80px)', 'xbo-market-kit' ), value: 'small' },
                { label: __( 'Medium (120px)', 'xbo-market-kit' ), value: 'medium' },
                { label: __( 'Large (160px)', 'xbo-market-kit' ), value: 'large' },
              ] }
              onChange={ ( val ) => setAttributes( { size: val } ) }
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
            <ToggleControl
              label={ __( 'Show seconds', 'xbo-market-kit' ) }
              checked={ showSeconds }
              onChange={ ( val ) => setAttributes( { showSeconds: val } ) }
              __nextHasNoMarginBottom
            />
          </>
        }
      />
    );
  },
} );
```

**Step 2: Create symlink for block.json (needed by webpack)**

```bash
cd wp-content/plugins/xbo-market-kit/src/blocks/refresh-timer
ln -sf ../../../includes/Blocks/refresh-timer/block.json block.json
```

**Step 3: Build**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

Expected: `build/blocks/refresh-timer/index.js` created without errors.

**Step 4: Commit**

```bash
git add src/blocks/refresh-timer/ build/blocks/refresh-timer/
git commit -m "feat(blocks): add refresh-timer editor UI with interval/size/label controls"
```

---

## Task 4: Refresh Timer — CSS Styles

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/assets/css/src/_timer.css`
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/widgets.css` (line 10, add import)

**Step 1: Create _timer.css**

```css
/* XBO Market Kit — Refresh Timer Widget */

.xbo-mk-timer__wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--xbo-mk--space-sm);
  position: relative;
}

.xbo-mk-timer__svg {
  display: block;
  transform: scaleX(-1);
}

.xbo-mk-timer__progress {
  transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.xbo-mk-timer__display {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -70%);
  font-family: var(--xbo-mk--font-primary);
  font-weight: var(--xbo-mk--font-weight-bold);
  color: var(--xbo-mk--color-text-heading);
  font-variant-numeric: tabular-nums;
  line-height: 1;
}

.xbo-mk-timer__label {
  font-size: var(--xbo-mk--font-size-xs);
  color: var(--xbo-mk--color-text-secondary);
  text-align: center;
  font-weight: var(--xbo-mk--font-weight-medium);
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

/* Pulse animation on reset */
@keyframes xbo-mk-timer-pulse {
  0% {
    filter: drop-shadow(0 0 0 transparent);
  }
  50% {
    filter: drop-shadow(0 0 12px var(--xbo-mk--color-primary));
  }
  100% {
    filter: drop-shadow(0 0 0 transparent);
  }
}

.xbo-mk-timer--pulsing .xbo-mk-timer__svg {
  animation: xbo-mk-timer-pulse 0.6s ease-out;
}

.xbo-mk-timer--pulsing .xbo-mk-timer__display {
  color: var(--xbo-mk--color-positive);
  transition: color 0.3s ease;
}

/* Size variants */
.xbo-mk-timer--small .xbo-mk-timer__label {
  font-size: 10px;
}

.xbo-mk-timer--large .xbo-mk-timer__label {
  font-size: var(--xbo-mk--font-size-sm);
}
```

**Step 2: Add import to widgets.css**

Add `@import '_timer.css';` after line 9 (`@import '_slippage.css';`):

```css
@import '_slippage.css';
@import '_timer.css';
```

**Step 3: Build CSS**

```bash
cd wp-content/plugins/xbo-market-kit && npm run css:build
```

**Step 4: Commit**

```bash
git add assets/css/src/_timer.css assets/css/src/widgets.css assets/css/dist/
git commit -m "feat(css): add refresh-timer widget styles with pulse animation"
```

---

## Task 5: Add data-xbo-refresh attributes to existing blocks

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/TickerShortcode.php:158`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/TradesShortcode.php` (similar location)
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/OrderbookShortcode.php` (similar location)

**Step 1: Modify TickerShortcode.php**

In `render()` method around line 158, change the wrapper div to include `data-xbo-refresh`:

Find:
```php
$html = '<div class="xbo-mk-ticker xbo-mk-ticker--cols-' . esc_attr( (string) $columns ) . '"'
    . ' data-wp-init="actions.initTicker">'
```

Replace:
```php
$html = '<div class="xbo-mk-ticker xbo-mk-ticker--cols-' . esc_attr( (string) $columns ) . '"'
    . ' data-xbo-refresh="' . esc_attr( (string) $refresh ) . '"'
    . ' data-wp-init="actions.initTicker">'
```

**Step 2: Apply same pattern to TradesShortcode.php and OrderbookShortcode.php**

Find each block's main container div with `data-wp-init` and add `data-xbo-refresh` attribute with the block's refresh interval value. Read each file first to find exact line numbers.

**Step 3: Run phpstan**

```bash
cd wp-content/plugins/xbo-market-kit && composer run phpstan
```
Expected: No errors

**Step 4: Commit**

```bash
git add includes/Shortcodes/TickerShortcode.php includes/Shortcodes/TradesShortcode.php includes/Shortcodes/OrderbookShortcode.php
git commit -m "feat(blocks): add data-xbo-refresh attributes for timer sync"
```

---

## Task 6: Pages CSS — Gradient and Glassmorphism Styles

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_variables.css` (add new tokens)
- Create: `wp-content/plugins/xbo-market-kit/assets/css/src/_pages.css`
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/widgets.css` (add import)

**Step 1: Add new design tokens to _variables.css**

After line 72 (before dark mode section at line 75), add:

```css
  /* ===== Gradients ===== */
  --xbo-mk--gradient-hero: linear-gradient(135deg, #6319ff 0%, #00d2ff 100%);
  --xbo-mk--gradient-section: linear-gradient(180deg, #f4f3f8 0%, #ffffff 100%);
  --xbo-mk--gradient-dark: linear-gradient(135deg, #0D1117 0%, #1a1040 50%, #0D1117 100%);
  --xbo-mk--gradient-accent: linear-gradient(135deg, #6319ff 0%, #fd3b5e 100%);

  /* ===== Glassmorphism ===== */
  --xbo-mk--glass-bg: rgba(255, 255, 255, 0.08);
  --xbo-mk--glass-border: rgba(255, 255, 255, 0.15);
  --xbo-mk--glass-blur: 20px;
```

**Step 2: Create _pages.css**

```css
/* XBO Market Kit — Page Layout Styles */
/* Gradients, glassmorphism, page sections for landing and demo pages */

/* ===== Page Sections ===== */
.xbo-mk-page-section {
  padding: var(--xbo-mk--space-2xl) 0;
}

.xbo-mk-page-section__inner {
  max-width: var(--xbo-mk--content-max);
  margin-inline: auto;
  padding-inline: var(--xbo-mk--content-padding);
}

/* ===== Hero Section ===== */
.xbo-mk-page-hero {
  background: var(--xbo-mk--gradient-hero);
  color: #ffffff;
  padding: 80px 0 60px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.xbo-mk-page-hero h1,
.xbo-mk-page-hero h2 {
  color: #ffffff;
  margin: 0;
}

.xbo-mk-page-hero h1 {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 16px;
}

.xbo-mk-page-hero p {
  font-size: clamp(1rem, 2vw, 1.25rem);
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto 32px;
}

/* ===== Features Grid ===== */
.xbo-mk-page-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--xbo-mk--space-xl);
  padding: var(--xbo-mk--space-2xl) 0;
}

.xbo-mk-page-feature-card {
  background: var(--xbo-mk--color-bg);
  border: 1px solid var(--xbo-mk--color-border);
  border-radius: var(--xbo-mk--radius-lg);
  padding: var(--xbo-mk--space-xl);
  text-align: center;
  transition: var(--xbo-mk--transition-default);
}

.xbo-mk-page-feature-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--xbo-mk--shadow-hover);
}

.xbo-mk-page-feature-card__icon {
  font-size: 2rem;
  margin-bottom: var(--xbo-mk--space-md);
  color: var(--xbo-mk--color-primary);
}

.xbo-mk-page-feature-card h3 {
  font-size: var(--xbo-mk--font-size-lg);
  font-weight: var(--xbo-mk--font-weight-semibold);
  color: var(--xbo-mk--color-text-heading);
  margin: 0 0 var(--xbo-mk--space-sm);
}

.xbo-mk-page-feature-card p {
  font-size: var(--xbo-mk--font-size-sm);
  color: var(--xbo-mk--color-text-secondary);
  margin: 0;
}

/* ===== Glassmorphism Cards ===== */
.xbo-mk-glass {
  background: var(--xbo-mk--glass-bg);
  border: 1px solid var(--xbo-mk--glass-border);
  border-radius: var(--xbo-mk--radius-lg);
  backdrop-filter: blur(var(--xbo-mk--glass-blur));
  -webkit-backdrop-filter: blur(var(--xbo-mk--glass-blur));
}

/* ===== Stats Counter ===== */
.xbo-mk-page-stats {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--xbo-mk--space-2xl);
  padding: var(--xbo-mk--space-2xl) 0;
  text-align: center;
}

.xbo-mk-page-stat {
  min-width: 120px;
}

.xbo-mk-page-stat__number {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: var(--xbo-mk--font-weight-bold);
  color: var(--xbo-mk--color-primary);
  font-variant-numeric: tabular-nums;
  line-height: 1.1;
}

.xbo-mk-page-stat__label {
  font-size: var(--xbo-mk--font-size-sm);
  color: var(--xbo-mk--color-text-secondary);
  margin-top: var(--xbo-mk--space-xs);
}

/* ===== CTA Section ===== */
.xbo-mk-page-cta {
  background: var(--xbo-mk--gradient-accent);
  color: #ffffff;
  padding: 60px 0;
  text-align: center;
}

.xbo-mk-page-cta h2 {
  color: #ffffff;
  font-size: clamp(1.5rem, 3vw, 2.5rem);
  margin: 0 0 16px;
}

.xbo-mk-page-cta p {
  opacity: 0.9;
  margin: 0 0 32px;
}

.xbo-mk-page-cta__buttons {
  display: flex;
  gap: var(--xbo-mk--space-lg);
  justify-content: center;
  flex-wrap: wrap;
}

.xbo-mk-page-btn {
  display: inline-block;
  padding: 12px 32px;
  border-radius: var(--xbo-mk--radius-xl);
  font-weight: var(--xbo-mk--font-weight-semibold);
  font-size: var(--xbo-mk--font-size-base);
  text-decoration: none;
  transition: var(--xbo-mk--transition-default);
  cursor: pointer;
  border: none;
}

.xbo-mk-page-btn--primary {
  background: #ffffff;
  color: var(--xbo-mk--color-primary);
}

.xbo-mk-page-btn--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}

.xbo-mk-page-btn--outline {
  background: transparent;
  color: #ffffff;
  border: 2px solid rgba(255, 255, 255, 0.5);
}

.xbo-mk-page-btn--outline:hover {
  border-color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
}

/* ===== Showcase Grid (2-column) ===== */
.xbo-mk-page-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--xbo-mk--space-xl);
}

@media (max-width: 768px) {
  .xbo-mk-page-grid-2 {
    grid-template-columns: 1fr;
  }
}

/* ===== Demo Page Header (mini hero) ===== */
.xbo-mk-demo-header {
  background: var(--xbo-mk--gradient-hero);
  color: #ffffff;
  padding: 40px 0;
  text-align: center;
}

.xbo-mk-demo-header h1 {
  color: #ffffff;
  font-size: clamp(1.5rem, 3vw, 2.5rem);
  margin: 0 0 8px;
}

.xbo-mk-demo-header p {
  opacity: 0.85;
  margin: 0;
}

/* ===== Parameter Table ===== */
.xbo-mk-param-table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--xbo-mk--font-size-sm);
}

.xbo-mk-param-table th,
.xbo-mk-param-table td {
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-md);
  border-bottom: 1px solid var(--xbo-mk--color-border);
  text-align: left;
}

.xbo-mk-param-table th {
  font-weight: var(--xbo-mk--font-weight-semibold);
  color: var(--xbo-mk--color-text-heading);
  background: var(--xbo-mk--color-bg-header);
}

.xbo-mk-param-table code {
  background: var(--xbo-mk--color-bg-secondary);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.85em;
}

/* ===== Gradient Section (alternate bg) ===== */
.xbo-mk-page-section--gradient {
  background: var(--xbo-mk--gradient-section);
}

.xbo-mk-page-section--dark {
  background: var(--xbo-mk--gradient-dark);
  color: #ffffff;
}

.xbo-mk-page-section--dark h2,
.xbo-mk-page-section--dark h3 {
  color: #ffffff;
}
```

**Step 3: Add import to widgets.css**

After the timer import, add:

```css
@import '_timer.css';
@import '_pages.css';
```

**Step 4: Build CSS**

```bash
cd wp-content/plugins/xbo-market-kit && npm run css:build
```

**Step 5: Commit**

```bash
git add assets/css/src/_variables.css assets/css/src/_pages.css assets/css/src/widgets.css assets/css/dist/
git commit -m "feat(css): add page layout styles with gradients and glassmorphism"
```

---

## Task 7: PageManager — Replace DemoPage with Full Page Generator

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/includes/Admin/PageManager.php`
- Modify: `wp-content/plugins/xbo-market-kit/xbo-market-kit.php` (activation/deactivation hooks)
- Delete logic: `includes/Admin/DemoPage.php` (keep file, mark deprecated)

**Step 1: Create PageManager.php**

Create the PageManager class that generates all 8 pages and the navigation menu. This is a large file — the key methods are:

- `create()` — creates all pages + menu on activation
- `delete()` — trashes all pages + removes menu on deactivation
- `get_home_content()` — Gutenberg block markup for landing page
- `get_showcase_content()` — all 5 blocks with descriptions
- `get_demo_content(string $block)` — individual block demo page
- `get_api_docs_content()` — API docs page
- `create_navigation_menu()` — creates WP nav menu

Page content uses Gutenberg block markup strings (HTML comments + HTML). Each page uses `wp:group` blocks with custom CSS classes for the gradient/glassmorphism sections, plus our XBO shortcode blocks for live widgets.

The home page content should include:
1. Hero section with Group block (gradient class) + Heading + Paragraph + Refresh Timer shortcode + Ticker shortcode
2. Features grid with Columns block (3 cols) + feature descriptions
3. Live showcase section with Group block + Movers shortcode + Columns (Orderbook + Trades)
4. Stats section with Group block + stat numbers
5. Slippage section with Group block (glassmorphism) + Slippage shortcode
6. CTA section with Group block (gradient) + buttons

**Step 2: Update activation/deactivation hooks in xbo-market-kit.php**

Replace DemoPage references with PageManager:

```php
register_activation_hook( __FILE__, array( 'XboMarketKit\Admin\PageManager', 'create' ) );
register_deactivation_hook( __FILE__, array( 'XboMarketKit\Admin\PageManager', 'delete' ) );
```

**Step 3: Run phpstan**

```bash
cd wp-content/plugins/xbo-market-kit && composer run phpstan
```

**Step 4: Commit**

```bash
git add includes/Admin/PageManager.php xbo-market-kit.php
git commit -m "feat(admin): add PageManager for programmatic page generation"
```

---

## Task 8: PageManager — Home Page Content

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Admin/PageManager.php`

**Step 1: Implement get_home_content()**

This generates the Gutenberg block markup for the home/landing page. The content is built as a string of block comments + HTML. Uses:
- `<!-- wp:group -->` with className for gradient/glass sections
- `<!-- wp:heading -->` for titles
- `<!-- wp:paragraph -->` for descriptions
- `<!-- wp:shortcode -->` for XBO widget blocks
- `<!-- wp:columns -->` for grid layouts
- Custom HTML blocks for stats counters and CTA buttons

Include all 6 sections from the design doc: Hero, Features, Showcase, Stats, Slippage, CTA.

**Step 2: Test by running page creation**

```bash
cd /Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public
wp shell <<< "XboMarketKit\Admin\PageManager::create();"
```

Verify pages are created:
```bash
wp post list --post_type=page --post_status=draft --fields=ID,post_title,post_name
```

**Step 3: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "feat(pages): implement home page content with hero, features, showcase, stats, CTA"
```

---

## Task 9: PageManager — Showcase Page Content

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Admin/PageManager.php`

**Step 1: Implement get_showcase_content()**

All 5 blocks displayed together:
- Refresh Timer (medium) at top
- Brief description card + full-width Ticker
- Brief description card + full-width Movers
- Two-column: Orderbook + Trades (with description cards)
- Full-width Slippage Calculator

Each block section has a heading, one-line description, and the live shortcode.

**Step 2: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "feat(pages): implement showcase page with all 5 blocks"
```

---

## Task 10: PageManager — Individual Demo Pages (5 pages)

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Admin/PageManager.php`

**Step 1: Implement get_demo_content(string $block)**

Template for each demo page:
1. Mini hero (Group with gradient class + block name + description)
2. Live block (full-width shortcode)
3. Parameters table (HTML table with block attributes)
4. Shortcode usage example (Code block)
5. Gutenberg usage note (Paragraph block with placeholder text)

Block-specific content mapping:

| Block | Shortcode | Key Parameters |
|-------|-----------|----------------|
| ticker | `[xbo_ticker symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT" columns="4" refresh="15"]` | symbols, refresh, columns |
| movers | `[xbo_movers mode="gainers" limit="10"]` | mode, limit |
| orderbook | `[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]` | symbol, depth, refresh |
| trades | `[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]` | symbol, limit, refresh |
| slippage | `[xbo_slippage symbol="BTC_USDT" side="buy"]` | symbol, side, amount |

**Step 2: Create parent "Block Demos" page**

The parent page `/demo/` should list all 5 demos with links. Created by `get_demo_index_content()`.

**Step 3: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "feat(pages): implement 5 individual block demo pages with params and usage"
```

---

## Task 11: PageManager — API Docs Page + Navigation Menu

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Admin/PageManager.php`

**Step 1: Implement get_api_docs_content()**

Simple page with:
- Mini hero header
- API overview paragraph
- `[mp_api_docs]` shortcode (if mp-api-docs plugin is active) or manual endpoint table

**Step 2: Implement create_navigation_menu()**

```php
private static function create_navigation_menu( array $page_ids ): void {
    $menu_name = 'XBO Market Kit Navigation';
    $menu_id   = wp_create_nav_menu( $menu_name );

    if ( is_wp_error( $menu_id ) ) {
        // Menu might already exist.
        $existing = wp_get_nav_menu_object( $menu_name );
        $menu_id  = $existing ? $existing->term_id : 0;
    }

    if ( ! $menu_id ) {
        return;
    }

    // Add menu items: Home, Showcase, Block Demos (parent + children), API Docs
    wp_update_nav_menu_item( $menu_id, 0, array(
        'menu-item-title'     => 'Home',
        'menu-item-object-id' => $page_ids['home'],
        'menu-item-object'    => 'page',
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
    ) );
    // ... etc for Showcase, Block Demos parent, 5 demo children, API Docs

    // Assign to primary menu location
    $locations = get_theme_mod( 'nav_menu_locations', array() );
    $locations['primary'] = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );
}
```

**Step 3: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "feat(pages): add API docs page and navigation menu creation"
```

---

## Task 12: PatternRegistrar — Block Patterns

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/includes/Patterns/PatternRegistrar.php`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Plugin.php:84` (add registration)

**Step 1: Create PatternRegistrar.php**

Register 6 block patterns in the `xbo-market-kit` category:

1. `xbo-market-kit/hero-section` — Hero with gradient bg
2. `xbo-market-kit/features-grid` — 3-column feature cards
3. `xbo-market-kit/live-showcase` — Live blocks in curated layout
4. `xbo-market-kit/stats-counter` — Stats numbers row
5. `xbo-market-kit/glass-card` — Single glassmorphism card
6. `xbo-market-kit/demo-page-header` — Mini hero for demo pages

Each pattern uses `register_block_pattern()` with `content` (block markup) and `categories`.

```php
class PatternRegistrar {
    public function register(): void {
        register_block_pattern_category( 'xbo-market-kit', array(
            'label' => __( 'XBO Market Kit', 'xbo-market-kit' ),
        ) );

        $this->register_hero_pattern();
        $this->register_features_pattern();
        // ... etc
    }
}
```

**Step 2: Register in Plugin.php**

Add to `init()` method after line 84:

```php
add_action( 'init', array( $this, 'register_patterns' ) );
```

Add method:
```php
public function register_patterns(): void {
    $registrar = new Patterns\PatternRegistrar();
    $registrar->register();
}
```

**Step 3: Commit**

```bash
git add includes/Patterns/PatternRegistrar.php includes/Plugin.php
git commit -m "feat(patterns): register 6 reusable block patterns for page sections"
```

---

## Task 13: Publish Pages and Set Front Page

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Admin/PageManager.php`

**Step 1: Update create() to publish pages and set front page**

After creating all pages, publish them (not draft) and set the home page as the static front page:

```php
// Set WordPress to use static front page.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['home'] );
```

**Step 2: Test page creation end-to-end**

```bash
# Reset (trash old pages if any)
wp post list --post_type=page --post_status=any --format=ids | xargs -I{} wp post delete {} --force 2>/dev/null
# Re-activate plugin
wp plugin deactivate xbo-market-kit && wp plugin activate xbo-market-kit
# Check pages
wp post list --post_type=page --post_status=publish --fields=ID,post_title,post_name
# Check menu
wp menu list
wp menu item list "XBO Market Kit Navigation"
# Check front page
wp option get page_on_front
```

**Step 3: Open site in browser and verify**

Visit: `http://claude-code-hackathon-xbo-market-kit.local/`
Expected: Landing page with hero, timer, live blocks, features, stats, CTA

**Step 4: Commit**

```bash
git add includes/Admin/PageManager.php
git commit -m "feat(pages): publish pages on activation and set static front page"
```

---

## Task 14: Unit Tests

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/tests/Unit/Blocks/RefreshTimerRenderTest.php`
- Create: `wp-content/plugins/xbo-market-kit/tests/Unit/Admin/PageManagerTest.php`

**Step 1: Write RefreshTimerRenderTest**

Test that render.php produces correct SVG markup:
- Test default attributes produce medium-size circle (120px)
- Test small/large size variants
- Test interval attribute is passed to context
- Test showSeconds=false hides display element

**Step 2: Write PageManagerTest**

Test PageManager:
- Test create() generates correct number of pages
- Test page titles match expected
- Test delete() trashes all pages
- Test menu is created with correct items

**Step 3: Run tests**

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
```

**Step 4: Commit**

```bash
git add tests/Unit/
git commit -m "test: add unit tests for RefreshTimer render and PageManager"
```

---

## Task 15: Quality Checks and Final Build

**Step 1: Run all quality checks**

```bash
cd wp-content/plugins/xbo-market-kit
composer run phpcs
composer run phpstan
composer run test
npm run build
npm run css:build
```

Fix any issues that arise.

**Step 2: Take screenshots**

Visit each page in the browser and verify:
- Home page renders with all sections
- Showcase page shows all 5 blocks
- Each demo page shows the correct block + params
- Timer syncs with blocks on the page
- Navigation menu works correctly

**Step 3: Final commit**

```bash
git add -A
git commit -m "chore: final quality checks and build artifacts for pages redesign"
```

---

## Execution Dependencies

```
Task 1 (block.json + render.php)
  → Task 2 (Interactivity JS) — needs render.php to exist
  → Task 3 (Editor UI) — needs block.json
  → Task 4 (Timer CSS) — independent, can parallel with 2,3

Task 5 (data-xbo-refresh) — independent, can parallel with 1-4

Task 6 (Pages CSS) — independent, can parallel with 1-5

Task 7 (PageManager base) — depends on Task 4, 6 (CSS must exist)
  → Task 8 (Home content) — needs PageManager
  → Task 9 (Showcase content) — needs PageManager
  → Task 10 (Demo pages) — needs PageManager
  → Task 11 (API docs + menu) — needs PageManager + all pages
  → Task 13 (Publish + front page) — needs all pages

Task 12 (Patterns) — depends on Task 6 (CSS classes), can parallel with 7-11

Task 14 (Tests) — depends on Task 1, 7
Task 15 (Quality) — depends on all above
```

**Parallelizable groups:**
- Group A: Tasks 1, 2, 3 (Timer block)
- Group B: Tasks 4, 5, 6 (CSS + attributes)
- Group C: Tasks 7-11, 13 (Pages — sequential)
- Group D: Task 12 (Patterns — after Group B)
- Group E: Tasks 14-15 (Tests + quality — last)
