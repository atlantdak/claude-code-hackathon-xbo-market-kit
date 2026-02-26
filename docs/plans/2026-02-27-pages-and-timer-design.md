# Pages Redesign & Refresh Timer Block — Design Document

**Date:** 2026-02-27
**Status:** Approved
**Author:** Claude Opus 4.6

## Overview

Complete redesign of all site pages with a modern gradient + glassmorphism visual style, creation of individual block demo pages, a full showcase landing, and a new Refresh Timer Gutenberg block that synchronizes with other XBO blocks on the page.

## 1. Refresh Timer Block (`xbo-market-kit/refresh-timer`)

### Purpose

Visual circular countdown timer showing time until next trading data refresh. Loops continuously to indicate it represents a recurring update interval, not a one-time countdown.

### Visual Design

- **Shape:** SVG circle with `stroke-dasharray`/`stroke-dashoffset` animation
- **Interior:** Large seconds display (e.g., `05s`) centered inside the circle
- **Label:** Configurable text below circle (default: "Next data refresh")
- **Stroke gradient:** `#6319ff` → `#49b47a` (primary → positive)
- **On reset (0s):** Pulse/glow animation, color flash, restart from max
- **Background:** Transparent (adapts to page background)

### Sizes

| Size | Circle Diameter | Font Size |
|------|:-:|:-:|
| small | 80px | 16px |
| medium | 120px | 24px |
| large | 160px | 32px |

### Block Attributes (`block.json`)

```json
{
  "name": "xbo-market-kit/refresh-timer",
  "title": "XBO Refresh Timer",
  "icon": "clock",
  "category": "xbo-market-kit",
  "description": "Circular countdown timer synced with XBO block refresh intervals",
  "attributes": {
    "interval": { "type": "number", "default": 0 },
    "label": { "type": "string", "default": "Next data refresh" },
    "size": { "type": "string", "default": "medium", "enum": ["small", "medium", "large"] },
    "showSeconds": { "type": "boolean", "default": true }
  },
  "supports": {
    "align": ["wide", "full"],
    "html": false
  }
}
```

- `interval: 0` means auto-detect from other XBO blocks on the page
- Manual override: any positive integer (seconds)

### Synchronization Logic (Interactivity API)

1. On `initRefreshTimer`: scan DOM for `[data-xbo-refresh]` elements
2. Collect all refresh intervals from sibling XBO blocks
3. Use the **minimum** interval (fastest update = most visually impressive)
4. If no blocks found, fall back to 15s default
5. Run `setInterval(1000)` — decrement counter every second
6. At 0: trigger pulse animation, reset to max, repeat

### Data Attribute Contract

Each XBO block with polling adds `data-xbo-refresh="{seconds}"` to its wrapper:
- Ticker: `data-xbo-refresh="15"`
- Trades: `data-xbo-refresh="10"`
- Orderbook: `data-xbo-refresh="5"`
- Movers: no attribute (no polling)
- Slippage: no attribute (no polling)

### Files

```
includes/Blocks/refresh-timer/
├── block.json
└── render.php

src/blocks/refresh-timer/
└── index.js

assets/css/src/_timer.css
assets/js/interactivity/refresh-timer.js
```

## 2. Page Structure

### Navigation Menu

```
Home | Showcase | Block Demos ▼           | API Docs
                   ├─ Ticker Demo
                   ├─ Top Movers Demo
                   ├─ Order Book Demo
                   ├─ Recent Trades Demo
                   └─ Slippage Calculator Demo
```

### Pages (8 total + front page)

| Page | Slug | Parent | Template | Purpose |
|------|------|--------|----------|---------|
| Home | `/` (front page) | — | — | Landing page with hero, timer, live blocks, features, stats, CTA |
| Showcase | `showcase` | — | — | All 5 blocks together in curated layout |
| Ticker Demo | `ticker` | `demo` | — | Live ticker + parameters + shortcode usage |
| Top Movers Demo | `movers` | `demo` | — | Movers block + parameters + usage |
| Order Book Demo | `orderbook` | `demo` | — | Orderbook + parameters + usage |
| Recent Trades Demo | `trades` | `demo` | — | Trades feed + parameters + usage |
| Slippage Demo | `slippage` | `demo` | — | Calculator + parameters + usage |
| API Docs | `api-docs` | — | — | Swagger/OAS3 interactive documentation |

### Individual Demo Page Template

Each demo page follows this structure:

1. **Mini Hero** — Block name + icon + one-line description (gradient bg)
2. **Live Block** — Full-width working block + Refresh Timer (small) beside it
3. **Parameters Table** — Block attributes with types, defaults, descriptions
4. **Shortcode Example** — Code block with copyable shortcode
5. **Gutenberg Usage** — Screenshot placeholder + prompt for AI generation
6. **Navigation** — Previous/Next demo links at bottom

## 3. Visual Design System

### Extended Color Tokens

```css
/* Gradient backgrounds */
--xbo-mk--gradient-hero: linear-gradient(135deg, #6319ff 0%, #00d2ff 100%);
--xbo-mk--gradient-section: linear-gradient(180deg, #f4f3f8 0%, #ffffff 100%);
--xbo-mk--gradient-dark: linear-gradient(135deg, #0D1117 0%, #1a1040 50%, #0D1117 100%);
--xbo-mk--gradient-accent: linear-gradient(135deg, #6319ff 0%, #fd3b5e 100%);

/* Glassmorphism */
--xbo-mk--glass-bg: rgba(255, 255, 255, 0.08);
--xbo-mk--glass-border: rgba(255, 255, 255, 0.15);
--xbo-mk--glass-blur: 20px;

/* Timer-specific */
--xbo-mk--timer-stroke-start: #6319ff;
--xbo-mk--timer-stroke-end: #49b47a;
--xbo-mk--timer-glow: rgba(99, 25, 255, 0.4);
```

### Home Page Sections

#### 1. Hero Section (full-width, gradient bg)

- **Heading:** "Real-Time Crypto Market Data for WordPress"
- **Subheading:** "5 live trading widgets powered by XBO API. Built entirely by AI."
- **Elements:** Refresh Timer (large) + Live Ticker (4 pairs)
- **Background:** `--xbo-mk--gradient-hero`
- **Text:** White on gradient
- **Placeholder image:** Trading dashboard mockup

#### 2. Features Grid (3 columns)

Cards with glassmorphism effect showing each block:
- Icon (from block.json) + Name + Short description
- Hover: translate-y(-4px) + shadow increase
- Link to individual demo page

#### 3. Live Showcase Section

- **Top Movers** — full width
- **Orderbook + Trades** — 2-column grid side by side
- **Refresh Timer** (small) positioned at section top-right
- Section background: subtle gradient (`--xbo-mk--gradient-section`)

#### 4. Stats Counter Section

Animated count-up numbers (CSS counter or simple JS):
- **5** Widgets
- **280+** Trading Pairs
- **205** Crypto Icons
- **6** API Endpoints
- **100%** AI-Built

#### 5. Slippage Calculator (Featured)

- Full-width glassmorphism card
- Live slippage calculator embedded
- Brief description of the algorithm

#### 6. CTA Section

- Gradient background (`--xbo-mk--gradient-accent`)
- "View on GitHub" button → repository link
- "API Documentation" button → `/api-docs/`
- "View Showcase" button → `/showcase/`

### Showcase Page

All 5 blocks displayed with:
- Brief description card above each
- Full-width rendering
- Separator between blocks
- Refresh Timer (medium) at page top

### CSS Architecture

New file: `assets/css/src/_pages.css`

CSS class naming:
- `.xbo-mk-page-hero` — Hero sections
- `.xbo-mk-page-features` — Features grid
- `.xbo-mk-page-showcase` — Showcase sections
- `.xbo-mk-page-stats` — Stats counter
- `.xbo-mk-page-cta` — Call to action
- `.xbo-mk-glass` — Glassmorphism modifier
- `.xbo-mk-gradient--hero` — Hero gradient modifier
- `.xbo-mk-gradient--dark` — Dark gradient modifier

## 4. Technical Implementation

### PageManager.php (replaces DemoPage.php)

```php
class PageManager {
    // Option key for storing created page IDs
    const OPTION_KEY = 'xbo_market_kit_pages';

    public static function create(): void;    // Creates all 8 pages + nav menu
    public static function delete(): void;    // Trashes all pages + removes menu

    // Page content generators
    private static function get_home_content(): string;
    private static function get_showcase_content(): string;
    private static function get_demo_content(string $block): string;
    private static function get_api_docs_content(): string;

    // Menu creation
    private static function create_navigation_menu(): void;
}
```

### PatternRegistrar.php

Registers reusable block patterns:
- `xbo-market-kit/hero-section`
- `xbo-market-kit/features-grid`
- `xbo-market-kit/live-showcase`
- `xbo-market-kit/stats-counter`
- `xbo-market-kit/glass-card`
- `xbo-market-kit/demo-page-header`

### Enqueuing Strategy

- `_pages.css` is imported into main `widgets.css` (always loaded where XBO blocks appear)
- `_timer.css` is imported into `widgets.css`
- `refresh-timer.js` registered as Interactivity API module (loaded only when timer block is on page)
- Timer editor JS (`build/blocks/refresh-timer/index.js`) loaded in block editor

### Image Placeholders & Prompts

For each placeholder image, include CSS-based colored rectangles with text overlay. User will replace with AI-generated images. Prompts to provide:

1. **Hero image:** "Futuristic trading dashboard with purple gradient, showing live price charts and crypto icons, clean modern design, dark background"
2. **Editor screenshots (5):** Actual screenshots to be taken from the block editor
3. **Feature icons:** Use block.json icons (already available as WordPress dashicons)

## 5. Testing Strategy

### Timer Block Tests

- Unit: render.php outputs correct SVG markup with attributes
- Integration: Timer detects sibling blocks' refresh intervals
- Integration: Timer cycles correctly (0→reset→countdown)
- Visual: Timer renders at all 3 sizes

### Page Tests

- Unit: PageManager creates correct number of pages
- Unit: PageManager creates nav menu with correct structure
- Unit: Page content contains expected blocks
- Integration: Pages render without PHP errors
- Integration: All shortcodes/blocks on pages produce output

## 6. Image Prompts for AI Generation

### Hero Background (1400x600)
> "Wide panoramic cryptocurrency trading dashboard visualization, purple to cyan gradient background, abstract geometric shapes representing market data flow, floating holographic price charts, modern SaaS product style, no text, dark edges fading to transparency"

### Feature Section Background (1400x400)
> "Subtle abstract background with soft purple geometric shapes, glassmorphism floating cards effect, light gradient from white to lavender, modern fintech aesthetic, no text"

### Stats Section Background (1400x300)
> "Dark background with subtle purple grid lines, futuristic data visualization aesthetic, glowing accent points, cryptocurrency network nodes connected by light lines, no text"

### Slippage Calculator Section (800x400)
> "Abstract visualization of order book depth, stacked green and red transparent layers representing bids and asks, modern financial data aesthetic, dark purple background, no text"

### Timer Icon / Illustration (400x400)
> "Circular countdown timer with gradient stroke from purple to green, digital seconds display in center, subtle glow effect, dark transparent background, fintech style"
