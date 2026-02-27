# Homepage Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rework the XBO Market Kit homepage into a structured showcase landing page with 10 sections, each widget showcased with description and link to detailed demo.

**Architecture:** Replace post_content of page ID 44 via `wp post update`. The page uses WordPress block markup (Gutenberg). Hero and CTA sections are preserved unchanged. Middle sections are completely rewritten with new block markup based on prime-fse theme patterns.

**Tech Stack:** WordPress Block Editor markup, WP-CLI for content updates, prime-fse theme color presets (color-1=#6319ff, color-4=#270a66, color-6=#f4f3f8, color-9=#ffffff)

---

### Task 1: Backup Current Homepage

**Files:**
- Create: `docs/backup/homepage-backup-2026-02-27.html`

**Step 1: Export current page content**

Run:
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
wp post get 44 --field=post_content > docs/backup/homepage-backup-2026-02-27.html
```

**Step 2: Verify backup file exists**

Run: `ls -la docs/backup/homepage-backup-2026-02-27.html`
Expected: File exists, non-empty

**Step 3: Commit backup**

```bash
git add docs/backup/homepage-backup-2026-02-27.html
git commit -m "chore: backup homepage content before redesign"
```

---

### Task 2: Build New Homepage Block Markup

**Files:**
- Create: `/tmp/homepage-new-content.html` (temporary, used by wp-cli)

This is the core task. Build the complete block markup for all 10 sections as a single HTML file. The file structure:

**Sections to include (in order):**

1. **Hero** — copy exactly from backup (the `wp:cover` block through the end of the hero columns)
2. **Spacer (40px) + Refresh Timer + Spacer (20px)**
3. **Ticker** — `xbo-market-kit/ticker` with 6 pairs + button "View Ticker Demo →"
4. **Features Grid** — 3 columns with bordered cards
5. **Statistics** — 4 columns with big numbers on color-6 bg
6. **Quick Start** — 3 columns, step numbers, on white bg
7. **Top Movers** — heading + description + widget + button on color-6
8. **Order Book + Trades** — 2 columns with headings, descriptions, widgets, buttons
9. **Slippage** — heading + description + widget + button on color-6
10. **Documentation** — heading + 6 cards in 2 rows (3 columns each)
11. **CTA** — copy exactly from backup (the dark `wp:group` at the end)

**Block markup reference:**

Color presets used throughout:
- `color-1` = #6319ff (XBO Purple) — primary buttons, dark backgrounds
- `color-4` = #270a66 (XBO Dark) — headings
- `color-6` = #f4f3f8 (XBO Light) — alternating section backgrounds
- `color-9` = #ffffff (White) — text on dark, card backgrounds

Spacing presets:
- `spacing|40` = small (10px)
- `spacing|50` = medium (20px)
- `spacing|60` = large (40px)
- `spacing|80` = 2x-large

Button style: 8px border-radius, `color-1` background or outline variant.

Heading style: `color-4` text color, link color matching.

**Step 1: Create the complete block markup file**

Write the file `/tmp/homepage-new-content.html` with ALL sections. The exact block markup for each section is specified below.

**Section 1: Hero** — Copy the exact hero markup from backup (everything from the opening `<!-- wp:cover` to its closing `<!-- /wp:cover -->`).

**Section 2: Timer + Ticker + Button**
```html
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:xbo-market-kit/refresh-timer /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:xbo-market-kit/ticker {"symbols":"BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT,BNB/USDT,ADA/USDT","columns":3} /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/xbo-demo-ticker/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">View Ticker Demo →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
```

**Section 3: Features Grid**
```html
<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h2 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Everything You Need for Crypto Market Data</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">5 Gutenberg Blocks</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Ticker, Top Movers, Order Book, Recent Trades, and Slippage Calculator — all with live editor preview.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Real-Time API Data</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Connected to XBO Exchange API with server-side caching and 15-second auto-refresh.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Multiple Integration Methods</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Gutenberg blocks, shortcodes, or REST API — works with any theme or page builder.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
```

**Section 4: Statistics (color-6 bg)**
```html
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"color-6","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-color-6-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"border":{"right":{"color":"#dddddd","width":"1px"}}}} -->
<div class="wp-block-column" style="border-right-color:#dddddd;border-right-width:1px"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h2 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:48px;font-weight:700">5</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<p class="has-text-align-center has-color-4-color has-text-color has-link-color">Gutenberg Blocks</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"right":{"color":"#dddddd","width":"1px"}}}} -->
<div class="wp-block-column" style="border-right-color:#dddddd;border-right-width:1px"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h2 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:48px;font-weight:700">280+</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<p class="has-text-align-center has-color-4-color has-text-color has-link-color">Trading Pairs</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"right":{"color":"#dddddd","width":"1px"}}}} -->
<div class="wp-block-column" style="border-right-color:#dddddd;border-right-width:1px"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h2 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:48px;font-weight:700">15s</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<p class="has-text-align-center has-color-4-color has-text-color has-link-color">Auto-Refresh</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h2 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:48px;font-weight:700">100%</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<p class="has-text-align-center has-color-4-color has-text-color has-link-color">Free &amp; Open Source</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
```

**Section 5: Quick Start (3 Steps)**
```html
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h2 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Get Started in 3 Steps</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"36px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h3 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:36px">1</h3>
<!-- /wp:heading -->

<!-- wp:heading {"textAlign":"center","level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h4 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Install the Plugin</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Upload via WordPress admin or clone from GitHub. Activate and you're ready.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"36px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h3 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:36px">2</h3>
<!-- /wp:heading -->

<!-- wp:heading {"textAlign":"center","level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h4 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Add a Block</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Open the block editor, search "XBO", and drop any widget into your page.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"36px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<h3 class="wp-block-heading has-text-align-center has-color-1-color has-text-color has-link-color" style="font-size:36px">3</h3>
<!-- /wp:heading -->

<!-- wp:heading {"textAlign":"center","level":4,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h4 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Publish &amp; Go Live</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hit publish — real-time crypto data appears instantly. No API keys needed.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"color-1","style":{"border":{"radius":"8px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-color-1-background-color has-background wp-element-button" href="/getting-started/" style="border-radius:8px">Read Getting Started Guide →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
```

**Section 6: Top Movers (color-6 bg)**
```html
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"color-6","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-color-6-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h2 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Top Movers</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Track the biggest gainers and losers across 280+ trading pairs in real-time.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:xbo-market-kit/movers {"count":10} /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/xbo-demo-movers/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">View Movers Demo →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
```

**Section 7: Order Book + Trades (white bg)**
```html
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h2 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Market Depth &amp; Trade History</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-color-4-color has-text-color has-link-color">Order Book</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Real-time bid/ask spread and depth visualization for BTC/USDT.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:xbo-market-kit/orderbook {"symbol":"BTC/USDT","depth":15} /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/xbo-demo-orderbook/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">Try Order Book →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-color-4-color has-text-color has-link-color">Recent Trades</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Live feed of executed trades with price, amount, and direction.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:xbo-market-kit/trades {"symbol":"BTC/USDT","limit":15} /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/xbo-demo-trades/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">View Trade History →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
```

**Section 8: Slippage Calculator (color-6 bg)**
```html
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"color-6","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-color-6-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h2 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Slippage Calculator</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Calculate average execution price and slippage based on real-time order book depth.</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:xbo-market-kit/slippage {"symbol":"BTC/USDT"} /-->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/xbo-demo-slippage/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">Open Slippage Calculator →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
```

**Section 9: Documentation & Resources (white bg)**
```html
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h2 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Documentation &amp; Resources</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Getting Started</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Install and configure in minutes.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/getting-started/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">Read Guide →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Widgets Overview</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Explore all 5 widgets and their features.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/widgets-overview/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">View Widgets →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Integration Guide</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Shortcodes, REST API, and theme integration.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/integration-guide/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">Read Guide →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">API Documentation</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">REST endpoints and live API explorer.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/xbo-api-docs/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">Explore API →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">Real-world Layouts</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">News blogs, dashboards, portfolio examples.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/real-world-layouts/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">View Layouts →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"width":"1px","color":"#dddddd","radius":"8px"}},"backgroundColor":"color-9"} -->
<div class="wp-block-column has-border-color has-color-9-background-color has-background" style="border-color:#dddddd;border-width:1px;border-radius:8px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"textAlign":"center","level":3,"style":{"elements":{"link":{"color":{"text":"var:preset|color|color-4"}}}},"textColor":"color-4"} -->
<h3 class="wp-block-heading has-text-align-center has-color-4-color has-text-color has-link-color">FAQ</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Installation, configuration, troubleshooting.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"8px","color":"var:preset|color|color-1","width":"2px"},"elements":{"link":{"color":{"text":"var:preset|color|color-1"}}}},"textColor":"color-1","fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size has-small-font-size is-style-outline"><a class="wp-block-button__link has-color-1-color has-text-color has-link-color has-border-color wp-element-button" href="/faq/" style="border-color:var(--wp--preset--color--color-1);border-width:2px;border-radius:8px">Read FAQ →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
```

**Section 10: CTA** — Copy exactly from backup (the `wp:group` with `color-1` background).

**Step 2: Verify the file is complete**

Run: `wc -l /tmp/homepage-new-content.html`
Expected: File exists with content

---

### Task 3: Update WordPress Page

**Step 1: Update the page content**

Run:
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
wp post update 44 /tmp/homepage-new-content.html
```
Expected: `Success: Updated post 44.`

**Step 2: Flush caches**

Run:
```bash
wp cache flush
wp transient delete --all
```

**Step 3: Verify the page loads**

Run:
```bash
curl -s -o /dev/null -w "%{http_code}" http://claude-code-hackathon-xbo-market-kit.local/
```
Expected: `200`

---

### Task 4: Visual Verification via Browser

**Step 1: Take screenshot of homepage**

Use Chrome DevTools or Playwright to navigate to `http://claude-code-hackathon-xbo-market-kit.local/` and take a full-page screenshot.

**Step 2: Verify all sections render correctly**

Check:
- Hero section unchanged
- Ticker shows 6 pairs
- Features Grid has 3 cards with borders
- Statistics shows 4 numbers in columns
- Quick Start shows 3 numbered steps
- Top Movers widget loads
- Order Book + Trades in 2 columns
- Slippage Calculator widget loads
- Documentation cards (6 total, 2 rows)
- CTA section unchanged
- All buttons link to correct pages

**Step 3: Fix any visual issues if found**

---

### Task 5: Commit Homepage Update

**Step 1: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add -A
git commit -m "feat: redesign homepage as showcase landing page

Rework homepage with 10 structured sections: Hero, Ticker,
Features Grid, Statistics, Quick Start, Top Movers, Order Book
+ Trades, Slippage Calculator, Documentation & Resources, CTA.
Each widget gets its own showcase with description and demo link."
```

---

### Task 6: Create GitHub Issue, Update Worklog & Metrics

**Step 1: Create GitHub issue**

Run:
```bash
gh issue create --title "feat: homepage redesign as showcase landing page" --body "## Summary
Redesigned the homepage from a flat widget dump into a structured showcase landing page.

## Changes
- 10 structured sections with alternating backgrounds
- Each widget showcased with description and link to detailed demo
- Added Statistics section (5 Blocks, 280+ Pairs, 15s Refresh, 100% Free)
- Added Quick Start section (3-step onboarding)
- Added Documentation & Resources section (6 cards linking to docs)
- Ticker expanded from 4 to 6 pairs
- All buttons have specific, unique labels
- Visual rhythm: white ↔ color-6 alternating backgrounds

## Design
See: docs/plans/2026-02-27-homepage-redesign-design.md

## Verified
- All widgets load correctly
- All links point to correct pages
- Responsive layout works"
```

**Step 2: Close issue with commit reference**

Run:
```bash
gh issue close <ISSUE_NUMBER> --comment "Completed in commit <COMMIT_SHA>"
```

**Step 3: Update worklog**

Use `/worklog-update` skill to add entry.

**Step 4: Update metrics**

Use `/metrics` skill to record task metrics.

**Step 5: Update README**

Use `/readme-update` skill to reflect homepage changes.
