# XBO Market Kit — Widget Styling Implementation Plan

**Date:** 2026-02-25
**Design doc:** `docs/plans/2026-02-25-widget-styling-design.md`
**Branch:** `feature/shortcode-styling`
**Time tracking:** Research started at session start, ~12min brainstorming/research

## Task Overview

| # | Task | Subagent Role | Dependencies | Est. Files |
|---|------|---------------|-------------|------------|
| 1 | PostCSS build pipeline setup | backend-dev | none | 3 new |
| 2 | Design tokens + base CSS | frontend-css | Task 1 | 3 new |
| 3 | Ticker widget CSS + HTML refactor | frontend-css | Task 2 | 2 modified |
| 4 | Movers widget CSS + HTML refactor | frontend-css | Task 2 | 2 modified |
| 5 | Orderbook widget CSS + HTML refactor | frontend-css | Task 2 | 2 modified |
| 6 | Trades widget CSS + HTML refactor | frontend-css | Task 2 | 2 modified |
| 7 | Slippage widget CSS + HTML refactor | frontend-css | Task 2 | 2 modified |
| 8 | Asset enqueuing + Tailwind removal | backend-dev | Tasks 3-7 | 3 modified |
| 9 | Dark mode support | frontend-css | Task 8 | 1 modified |
| 10 | Integration verification | verifier | Task 9 | 0 |

## Task Details

### Task 1: PostCSS Build Pipeline Setup

**What:** Create `package.json` and PostCSS configuration in plugin directory.

**Files to create:**
- `wp-content/plugins/xbo-market-kit/package.json`
- `wp-content/plugins/xbo-market-kit/postcss.config.js`
- `wp-content/plugins/xbo-market-kit/assets/css/src/widgets.css` (entry point with @imports)

**package.json:**
```json
{
  "name": "xbo-market-kit",
  "private": true,
  "scripts": {
    "css:dev": "postcss assets/css/src/widgets.css -o assets/css/dist/widgets.css --watch",
    "css:build": "NODE_ENV=production postcss assets/css/src/widgets.css -o assets/css/dist/widgets.min.css"
  },
  "devDependencies": {
    "postcss": "^8.5",
    "postcss-cli": "^11.0",
    "postcss-import": "^16.1",
    "postcss-nesting": "^13.0",
    "cssnano": "^7.0"
  }
}
```

**postcss.config.js:**
```js
module.exports = (ctx) => ({
  plugins: {
    'postcss-import': {},
    'postcss-nesting': {},
    ...(ctx.env === 'production' ? { cssnano: { preset: 'default' } } : {}),
  },
});
```

**Entry point `assets/css/src/widgets.css`:**
```css
@import '_variables.css';
@import '_base.css';
@import '_animations.css';
@import '_ticker.css';
@import '_movers.css';
@import '_orderbook.css';
@import '_trades.css';
@import '_slippage.css';
```

**Acceptance criteria:**
- `npm install` succeeds
- `npm run css:build` produces `assets/css/dist/widgets.min.css`
- `npm run css:dev` starts watch mode
- `.gitignore` updated: `node_modules/` added, `assets/css/dist/` NOT ignored (committed for no-build deploy)
- Add `assets/css/src/` and `assets/css/dist/` directories

**Commit:** `feat(styles): add PostCSS build pipeline for widget CSS`

---

### Task 2: Design Tokens + Base CSS

**What:** Create CSS custom properties file and base/reset styles.

**Files to create:**
- `assets/css/src/_variables.css` — all design tokens from design doc
- `assets/css/src/_base.css` — scoped reset, font import, shared utilities
- `assets/css/src/_animations.css` — price flash animations (replace current widgets.css)

**_variables.css** must include ALL tokens from design doc "Design Tokens" section. Use `:root` scope with `.xbo-mk--dark` override for dark mode.

**_base.css:**
- Google Fonts `@import` for IBM Plex Sans (400, 500, 600, 700)
- Scoped box-sizing reset: `.xbo-mk-*` elements use `box-sizing: border-box`
- Shared layout utilities: `.xbo-mk-grid`, `.xbo-mk-hidden`
- Typography base for all `.xbo-mk-*` elements

**_animations.css:**
- Price flash animations (2s duration, matching xbo.com)
- Depth bar transitions
- Card hover transitions

**Acceptance criteria:**
- Variables file declares ALL tokens listed in design doc
- Dark mode variables under `.xbo-mk--dark` selector
- Base file loads IBM Plex Sans font
- Animations match xbo.com behavior (2s fade, green→neutral, red→neutral)
- `npm run css:build` produces valid CSS

**Commit:** `feat(styles): add design tokens, base styles, and animations`

---

### Task 3: Ticker Widget CSS + HTML Refactor

**What:** Create ticker CSS module and refactor `TickerShortcode.php` to use BEM classes.

**Files:**
- Create: `assets/css/src/_ticker.css`
- Modify: `includes/Shortcodes/TickerShortcode.php`

**CSS requirements (from xbo.com visual style):**
- Card with `--xbo-mk--radius-lg`, `--xbo-mk--shadow-card`, border
- Grid layout: `columns` attribute controls grid-template-columns (1-4)
- Responsive: 2 cols at 768px, 1 col at 480px
- Icon circle: 40px, primary color background, white text
- Price: `--xbo-mk--font-size-lg`, bold, font-primary
- Change %: colored by positive/negative modifier
- Sparkline SVG: primary color stroke
- Hover: `--xbo-mk--shadow-hover`

**HTML refactor:**
Replace ALL Tailwind classes with BEM classes. Keep `data-wp-*` attributes unchanged.
Example: `class="bg-white dark:bg-gray-800 rounded-xl shadow-sm"` → `class="xbo-mk-ticker__card"`

**CRITICAL:** Do NOT change data-wp-interactive attributes, data-wp-context, data-wp-text, data-wp-class, data-wp-init, data-wp-each, or any Interactivity API binding. Only replace CSS class attributes.

**Acceptance criteria:**
- Zero Tailwind classes remain in TickerShortcode.php
- All visual appearance preserved via BEM CSS
- All `data-wp-*` bindings intact
- `data-wp-class--text-green-500` and similar Tailwind-based dynamic classes need to be changed to BEM equivalents: `data-wp-class--xbo-mk-ticker__change--positive`
- `npm run css:build` succeeds
- Widget renders correctly (check CSS manually)

**Commit:** `feat(styles): refactor ticker widget to BEM CSS`

---

### Task 4: Movers Widget CSS + HTML Refactor

**What:** Create movers CSS module and refactor `MoversShortcode.php`.

**Files:**
- Create: `assets/css/src/_movers.css`
- Modify: `includes/Shortcodes/MoversShortcode.php`

**CSS requirements:**
- Container: card styling (radius, shadow, border)
- Tabs: horizontal bar, active tab uses primary color (gainers=positive, losers=negative)
- Table: header with muted background, alternating row backgrounds (per xbo.com)
- Rows: 62px min-height, hover effect
- Trend arrow: CSS border triangle (from xbo.com)
- Price/change cells: monospace font, right-aligned

**HTML refactor:** Same rules as Task 3.
**CRITICAL:** Same data-wp-* preservation rules.

**Acceptance criteria:** Same as Task 3 but for movers widget.

**Commit:** `feat(styles): refactor movers widget to BEM CSS`

---

### Task 5: Orderbook Widget CSS + HTML Refactor

**What:** Create orderbook CSS module and refactor `OrderbookShortcode.php`.

**Files:**
- Create: `assets/css/src/_orderbook.css`
- Modify: `includes/Shortcodes/OrderbookShortcode.php`

**CSS requirements:**
- Container: card styling
- Header: title bar with border-bottom
- Grid: 2-column layout (bids left, asks right)
- Depth bars: colored background fill (green for bids, red for asks) with CSS `width` controlled by JS
- Price levels: monospace font, compact rows (py 2px)
- Spread indicator: centered, muted text
- Column headers: small muted text

**HTML refactor:** Same rules as Task 3.
**CRITICAL:** Depth bar styling uses `data-wp-style--width` — this must remain.

**Acceptance criteria:** Same pattern as Tasks 3-4.

**Commit:** `feat(styles): refactor orderbook widget to BEM CSS`

---

### Task 6: Trades Widget CSS + HTML Refactor

**What:** Create trades CSS module and refactor `TradesShortcode.php`.

**Files:**
- Create: `assets/css/src/_trades.css`
- Modify: `includes/Shortcodes/TradesShortcode.php`

**CSS requirements:**
- Container: card styling
- Table: compact rows, monospace font for all data
- Side badges: pill-shaped (radius-xl), colored background (buy=positive-bg, sell=negative-bg)
- Time column: muted color
- Price/amount: right-aligned

**HTML refactor:** Same rules as Task 3.

**Acceptance criteria:** Same pattern.

**Commit:** `feat(styles): refactor trades widget to BEM CSS`

---

### Task 7: Slippage Widget CSS + HTML Refactor

**What:** Create slippage CSS module and refactor `SlippageShortcode.php`.

**Files:**
- Create: `assets/css/src/_slippage.css`
- Modify: `includes/Shortcodes/SlippageShortcode.php`

**CSS requirements:**
- Container: card styling
- Form: 3-column grid, responsive to 1-column at 480px
- Input fields: border, radius-md, focus ring with primary color
- Buy/Sell toggle: pill buttons, colored by state
- Calculate button: primary color background, white text
- Results: 2-column grid of metric cards (bg-secondary, radius-md)
- Metric label: small, muted; metric value: bold, font-primary
- Loading state: pulse animation

**HTML refactor:** Same rules as Task 3.

**Acceptance criteria:** Same pattern.

**Commit:** `feat(styles): refactor slippage widget to BEM CSS`

---

### Task 8: Asset Enqueuing + Tailwind Removal

**What:** Update PHP to load new CSS, remove Tailwind CDN, add Gutenberg editor styles.

**Files to modify:**
- `includes/Shortcodes/AbstractShortcode.php` — change enqueue_assets()
- `includes/Admin/SettingsPage.php` — remove Tailwind toggle
- `xbo-market-kit.php` — add enqueue_block_editor_assets hook

**Changes:**
1. In `enqueue_assets()`: Remove Tailwind CDN script enqueue. Replace with:
   ```php
   wp_enqueue_style('xbo-market-kit-fonts', 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap', [], null);
   $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
   wp_enqueue_style('xbo-market-kit-widgets', XBO_MARKET_KIT_URL . "assets/css/dist/widgets{$suffix}.css", ['xbo-market-kit-fonts'], XBO_MARKET_KIT_VERSION);
   ```
2. Remove `enable_tailwind` option and settings UI toggle.
3. Add `enqueue_block_editor_assets` hook to load same styles in Gutenberg editor.

**Acceptance criteria:**
- Tailwind CDN no longer loaded on any page
- `enable_tailwind` setting removed from DB and admin UI
- New CSS loaded on frontend when any shortcode is used
- Same CSS loaded in Gutenberg block editor
- All 5 widgets render correctly without Tailwind

**Commit:** `feat(styles): replace Tailwind CDN with custom CSS, add editor styles`

---

### Task 9: Dark Mode Support

**What:** Add dark mode CSS variables and toggle mechanism.

**Files to modify:**
- `assets/css/src/_variables.css` — already has dark mode tokens from Task 2

**Changes:**
1. Add `prefers-color-scheme: dark` media query support
2. Add `.xbo-mk--dark` class override for manual toggle
3. Ensure ALL widgets properly inherit dark mode variables

**Acceptance criteria:**
- Widgets respond to OS dark mode preference
- `.xbo-mk--dark` class on wrapper triggers dark mode
- All text, backgrounds, borders change appropriately
- Financial colors (green/red) remain vibrant in dark mode

**Commit:** `feat(styles): add dark mode support`

---

### Task 10: Integration Verification

**What:** Run all tests, PHPCS, PHPStan. Verify visual rendering. Build production CSS.

**Steps:**
1. `cd wp-content/plugins/xbo-market-kit && composer run test`
2. `composer run phpcs`
3. `composer run phpstan`
4. `npm run css:build`
5. Verify `assets/css/dist/widgets.min.css` exists and is < 25kB
6. Check no Tailwind references remain in any PHP file

**Acceptance criteria:**
- All unit tests pass
- PHPCS passes (or only pre-existing warnings)
- PHPStan level 6 passes
- Production CSS built successfully
- No Tailwind CDN references in codebase

**Commit:** `chore(styles): verify integration and build production CSS` (only if fixes needed)
