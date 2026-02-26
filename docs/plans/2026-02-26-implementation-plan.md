# Gutenberg Block Editor UI + Demo Site + Documentation — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add full Gutenberg block editor UI (InspectorControls + ServerSideRender) to all 5 blocks, create demo pages with XBO branding, take screenshots, and produce documentation for hackathon.

**Architecture:** Each block gets a JSX `edit.js` using `@wordpress/block-editor` InspectorControls for settings and `@wordpress/server-side-render` for live preview. Build via `@wordpress/scripts`. Demo pages use Prime FSE patterns + GetWid blocks for layout, with our XBO Market Kit blocks as the main content.

**Tech Stack:** WordPress 6.9.1, @wordpress/scripts (webpack), React/JSX, PHP 8.2, Prime FSE theme, GetWid plugin, Chrome DevTools Protocol (screenshots)

**Design doc:** `docs/plans/2026-02-26-gutenberg-demo-docs-design.md`
**Task tracker:** `docs/plans/2026-02-26-task-tracker.md`

**Plugin directory:** `wp-content/plugins/xbo-market-kit/`

---

## Task T01: Setup @wordpress/scripts Build Pipeline

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/package.json`
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/ticker/index.js` (placeholder)
- Modify: `wp-content/plugins/xbo-market-kit/.gitignore` (add `build/` ignore if needed)

**Step 1: Install @wordpress/scripts and block dependencies**

```bash
cd wp-content/plugins/xbo-market-kit
npm install --save-dev @wordpress/scripts
npm install @wordpress/blocks @wordpress/block-editor @wordpress/components @wordpress/server-side-render @wordpress/i18n @wordpress/element
```

**Step 2: Update package.json scripts**

Add to the `scripts` section of `package.json`:

```json
{
  "scripts": {
    "build": "wp-scripts build --webpack-src-dir=src/blocks --output-path=build/blocks",
    "start": "wp-scripts start --webpack-src-dir=src/blocks --output-path=build/blocks",
    "css:dev": "postcss assets/css/src/widgets.css -o assets/css/dist/widgets.css --watch",
    "css:build": "NODE_ENV=production postcss assets/css/src/widgets.css -o assets/css/dist/widgets.min.css"
  }
}
```

**Step 3: Create placeholder entry point to test build**

Create `src/blocks/ticker/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import metadata from '../../../includes/Blocks/ticker/block.json';

registerBlockType( metadata.name, {
    edit: () => <p>Ticker block placeholder</p>,
} );
```

**Step 4: Run build to verify pipeline works**

```bash
cd wp-content/plugins/xbo-market-kit
npm run build
```

Expected: `build/blocks/ticker/index.js` and `build/blocks/ticker/index.asset.php` created.

**Step 5: Update ticker block.json to reference built editor script**

Add `"editorScript"` field to `includes/Blocks/ticker/block.json`:

```json
{
  "editorScript": "file:../../build/blocks/ticker/index.js"
}
```

**Step 6: Verify block loads in WP admin editor**

Open `http://claude-code-hackathon-xbo-market-kit.local/wp-admin/post-new.php?post_type=page` and confirm "XBO Ticker" block appears in inserter.

**Step 7: Commit**

```bash
git add package.json package-lock.json src/ build/ includes/Blocks/ticker/block.json
git commit -m "feat(blocks): setup @wordpress/scripts build pipeline for block editor UI"
```

---

## Task T02: Create Base Edit Component Template + Shared Helper

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/shared/XboBlockEdit.js`
- Modify: `wp-content/plugins/xbo-market-kit/src/blocks/ticker/index.js`

**Step 1: Create shared XboBlockEdit component**

Create `src/blocks/shared/XboBlockEdit.js`:

```jsx
/**
 * Shared block edit component wrapper for all XBO Market Kit blocks.
 *
 * Provides InspectorControls panel + ServerSideRender preview.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, Placeholder, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function XboBlockEdit( {
    blockName,
    attributes,
    setAttributes,
    inspectorControls,
    title,
    icon,
} ) {
    const blockProps = useBlockProps();

    return (
        <div { ...blockProps }>
            <InspectorControls>
                <PanelBody
                    title={ __( 'Settings', 'xbo-market-kit' ) }
                    initialOpen={ true }
                >
                    { inspectorControls }
                </PanelBody>
            </InspectorControls>
            <ServerSideRender
                block={ blockName }
                attributes={ attributes }
                LoadingResponsePlaceholder={ () => (
                    <Placeholder icon={ icon } label={ title }>
                        <Spinner />
                    </Placeholder>
                ) }
                ErrorResponsePlaceholder={ () => (
                    <Placeholder icon={ icon } label={ title }>
                        <p>{ __( 'Error loading preview.', 'xbo-market-kit' ) }</p>
                    </Placeholder>
                ) }
            />
        </div>
    );
}
```

**Step 2: Refactor ticker/index.js to use XboBlockEdit**

Update `src/blocks/ticker/index.js`:

```jsx
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../../../includes/Blocks/ticker/block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { symbols, refresh, columns } = attributes;

        return (
            <XboBlockEdit
                blockName={ metadata.name }
                attributes={ attributes }
                setAttributes={ setAttributes }
                title={ __( 'XBO Ticker', 'xbo-market-kit' ) }
                icon="chart-line"
                inspectorControls={
                    <>
                        <TextControl
                            label={ __( 'Trading Pairs', 'xbo-market-kit' ) }
                            help={ __( 'Comma-separated, e.g. BTC/USDT,ETH/USDT', 'xbo-market-kit' ) }
                            value={ symbols }
                            onChange={ ( val ) => setAttributes( { symbols: val } ) }
                        />
                        <RangeControl
                            label={ __( 'Refresh Interval (seconds)', 'xbo-market-kit' ) }
                            value={ parseInt( refresh, 10 ) }
                            onChange={ ( val ) => setAttributes( { refresh: String( val ) } ) }
                            min={ 5 }
                            max={ 60 }
                        />
                        <RangeControl
                            label={ __( 'Columns', 'xbo-market-kit' ) }
                            value={ parseInt( columns, 10 ) }
                            onChange={ ( val ) => setAttributes( { columns: String( val ) } ) }
                            min={ 1 }
                            max={ 4 }
                        />
                    </>
                }
            />
        );
    },
} );
```

**Step 3: Build and verify**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

**Step 4: Test in WP admin — insert Ticker block, check sidebar settings + live preview**

Open editor, add Ticker block, verify:
- InspectorControls sidebar shows 3 controls (Trading Pairs, Refresh, Columns)
- ServerSideRender shows live ticker preview
- Changing settings updates preview

**Step 5: Commit**

```bash
git add src/blocks/shared/ src/blocks/ticker/ build/
git commit -m "feat(blocks): add shared XboBlockEdit component and ticker editor UI"
```

---

## Task T03: Block Editor UI — Ticker

> **Note:** If T02 already completed the full ticker edit.js, this task only needs to verify and polish.

**Files:**
- Verify: `wp-content/plugins/xbo-market-kit/src/blocks/ticker/index.js`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/ticker/block.json` (ensure editorScript)

**Step 1: Verify ticker block.json has editorScript**

`includes/Blocks/ticker/block.json` must contain:
```json
"editorScript": "file:../../build/blocks/ticker/index.js"
```

**Step 2: Build and test in editor**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

Insert block in editor, verify all 3 controls work and preview updates.

**Step 3: Commit if any changes**

```bash
git add includes/Blocks/ticker/block.json src/blocks/ticker/ build/blocks/ticker/
git commit -m "feat(blocks): finalize ticker block editor UI with InspectorControls"
```

---

## Task T04: Block Editor UI — Movers

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/movers/index.js`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/movers/block.json`

**Step 1: Create movers/index.js**

```jsx
import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../../../includes/Blocks/movers/block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { mode, limit } = attributes;

        return (
            <XboBlockEdit
                blockName={ metadata.name }
                attributes={ attributes }
                setAttributes={ setAttributes }
                title={ __( 'XBO Top Movers', 'xbo-market-kit' ) }
                icon="sort"
                inspectorControls={
                    <>
                        <SelectControl
                            label={ __( 'Default Tab', 'xbo-market-kit' ) }
                            value={ mode }
                            options={ [
                                { label: __( 'Gainers', 'xbo-market-kit' ), value: 'gainers' },
                                { label: __( 'Losers', 'xbo-market-kit' ), value: 'losers' },
                            ] }
                            onChange={ ( val ) => setAttributes( { mode: val } ) }
                        />
                        <RangeControl
                            label={ __( 'Number of Items', 'xbo-market-kit' ) }
                            value={ parseInt( limit, 10 ) }
                            onChange={ ( val ) => setAttributes( { limit: String( val ) } ) }
                            min={ 1 }
                            max={ 50 }
                        />
                    </>
                }
            />
        );
    },
} );
```

**Step 2: Update movers/block.json**

Add `"editorScript": "file:../../build/blocks/movers/index.js"` to the JSON.

**Step 3: Build and test**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

Verify in editor: SelectControl (Gainers/Losers) + RangeControl (limit) + live preview.

**Step 4: Commit**

```bash
git add src/blocks/movers/ includes/Blocks/movers/block.json build/blocks/movers/
git commit -m "feat(blocks): add movers block editor UI with mode and limit controls"
```

---

## Task T05: Block Editor UI — Orderbook

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/orderbook/index.js`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/orderbook/block.json`

**Step 1: Create orderbook/index.js**

```jsx
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../../../includes/Blocks/orderbook/block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { symbol, depth, refresh } = attributes;

        return (
            <XboBlockEdit
                blockName={ metadata.name }
                attributes={ attributes }
                setAttributes={ setAttributes }
                title={ __( 'XBO Order Book', 'xbo-market-kit' ) }
                icon="book"
                inspectorControls={
                    <>
                        <TextControl
                            label={ __( 'Trading Pair', 'xbo-market-kit' ) }
                            help={ __( 'Use underscore format: BTC_USDT', 'xbo-market-kit' ) }
                            value={ symbol }
                            onChange={ ( val ) => setAttributes( { symbol: val } ) }
                        />
                        <RangeControl
                            label={ __( 'Depth', 'xbo-market-kit' ) }
                            value={ parseInt( depth, 10 ) }
                            onChange={ ( val ) => setAttributes( { depth: String( val ) } ) }
                            min={ 1 }
                            max={ 250 }
                        />
                        <RangeControl
                            label={ __( 'Refresh Interval (seconds)', 'xbo-market-kit' ) }
                            value={ parseInt( refresh, 10 ) }
                            onChange={ ( val ) => setAttributes( { refresh: String( val ) } ) }
                            min={ 1 }
                            max={ 60 }
                        />
                    </>
                }
            />
        );
    },
} );
```

**Step 2: Update orderbook/block.json**

Add `"editorScript": "file:../../build/blocks/orderbook/index.js"`.

**Step 3: Build and test**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

**Step 4: Commit**

```bash
git add src/blocks/orderbook/ includes/Blocks/orderbook/block.json build/blocks/orderbook/
git commit -m "feat(blocks): add orderbook block editor UI with symbol, depth, refresh controls"
```

---

## Task T06: Block Editor UI — Trades

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/trades/index.js`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/trades/block.json`

**Step 1: Create trades/index.js**

```jsx
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../../../includes/Blocks/trades/block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { symbol, limit, refresh } = attributes;

        return (
            <XboBlockEdit
                blockName={ metadata.name }
                attributes={ attributes }
                setAttributes={ setAttributes }
                title={ __( 'XBO Recent Trades', 'xbo-market-kit' ) }
                icon="list-view"
                inspectorControls={
                    <>
                        <TextControl
                            label={ __( 'Trading Pair', 'xbo-market-kit' ) }
                            help={ __( 'Use slash format: BTC/USDT', 'xbo-market-kit' ) }
                            value={ symbol }
                            onChange={ ( val ) => setAttributes( { symbol: val } ) }
                        />
                        <RangeControl
                            label={ __( 'Number of Trades', 'xbo-market-kit' ) }
                            value={ parseInt( limit, 10 ) }
                            onChange={ ( val ) => setAttributes( { limit: String( val ) } ) }
                            min={ 1 }
                            max={ 100 }
                        />
                        <RangeControl
                            label={ __( 'Refresh Interval (seconds)', 'xbo-market-kit' ) }
                            value={ parseInt( refresh, 10 ) }
                            onChange={ ( val ) => setAttributes( { refresh: String( val ) } ) }
                            min={ 1 }
                            max={ 60 }
                        />
                    </>
                }
            />
        );
    },
} );
```

**Step 2: Update trades/block.json**

Add `"editorScript": "file:../../build/blocks/trades/index.js"`.

**Step 3: Build and test**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

**Step 4: Commit**

```bash
git add src/blocks/trades/ includes/Blocks/trades/block.json build/blocks/trades/
git commit -m "feat(blocks): add trades block editor UI with symbol, limit, refresh controls"
```

---

## Task T07: Block Editor UI — Slippage

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/src/blocks/slippage/index.js`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/slippage/block.json`

**Step 1: Create slippage/index.js**

```jsx
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../../../includes/Blocks/slippage/block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { symbol, side, amount } = attributes;

        return (
            <XboBlockEdit
                blockName={ metadata.name }
                attributes={ attributes }
                setAttributes={ setAttributes }
                title={ __( 'XBO Slippage Calculator', 'xbo-market-kit' ) }
                icon="calculator"
                inspectorControls={
                    <>
                        <TextControl
                            label={ __( 'Trading Pair', 'xbo-market-kit' ) }
                            help={ __( 'Use underscore format: BTC_USDT', 'xbo-market-kit' ) }
                            value={ symbol }
                            onChange={ ( val ) => setAttributes( { symbol: val } ) }
                        />
                        <SelectControl
                            label={ __( 'Side', 'xbo-market-kit' ) }
                            value={ side }
                            options={ [
                                { label: __( 'Buy', 'xbo-market-kit' ), value: 'buy' },
                                { label: __( 'Sell', 'xbo-market-kit' ), value: 'sell' },
                            ] }
                            onChange={ ( val ) => setAttributes( { side: val } ) }
                        />
                        <TextControl
                            label={ __( 'Default Amount', 'xbo-market-kit' ) }
                            help={ __( 'Leave empty to let user input amount on frontend', 'xbo-market-kit' ) }
                            value={ amount }
                            onChange={ ( val ) => setAttributes( { amount: val } ) }
                        />
                    </>
                }
            />
        );
    },
} );
```

**Step 2: Update slippage/block.json**

Add `"editorScript": "file:../../build/blocks/slippage/index.js"`.

**Step 3: Build and test**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

**Step 4: Commit**

```bash
git add src/blocks/slippage/ includes/Blocks/slippage/block.json build/blocks/slippage/
git commit -m "feat(blocks): add slippage block editor UI with symbol, side, amount controls"
```

---

## Task T08: Full Verification

**Step 1: Build all blocks**

```bash
cd wp-content/plugins/xbo-market-kit && npm run build
```

Expected: `build/blocks/` contains 5 directories (ticker, movers, orderbook, trades, slippage), each with `index.js` + `index.asset.php`.

**Step 2: Run PHPCS**

```bash
cd wp-content/plugins/xbo-market-kit && composer run phpcs
```

Expected: 0 errors.

**Step 3: Run PHPStan**

```bash
cd wp-content/plugins/xbo-market-kit && composer run phpstan
```

Expected: 0 errors.

**Step 4: Run PHPUnit**

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
```

Expected: 59 tests, 822 assertions, 0 failures.

**Step 5: Rebuild CSS**

```bash
cd wp-content/plugins/xbo-market-kit && npm run css:build
```

**Step 6: If any issues found, fix and re-run. Commit fixes.**

---

## Task T09: Integration Test — Blocks in Editor and Frontend

**Step 1: Open WP admin post editor**

Navigate to `http://claude-code-hackathon-xbo-market-kit.local/wp-admin/post-new.php?post_type=page`

**Step 2: Insert each of the 5 XBO blocks**

For each block (Ticker, Movers, Orderbook, Trades, Slippage):
- Add block from inserter (category "XBO Market Kit")
- Verify InspectorControls appear in sidebar
- Change a setting and verify ServerSideRender updates
- Save page

**Step 3: View published page on frontend**

Verify all 5 blocks render correctly with live data.

**Step 4: Check PHP error log**

```bash
tail -50 /Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/debug.log
```

Expected: No PHP errors related to XBO blocks.

---

## Task T10: Download XBO Assets

**Step 1: Download logo and key images from xbo.com**

Use Chrome DevTools or wget to download:
- XBO logo (SVG or PNG) from xbo.com header
- Hero background image or gradient reference
- Favicon

Save to: `wp-content/plugins/xbo-market-kit/assets/images/branding/`

**Step 2: Upload to WordPress Media Library**

Use WP-CLI:
```bash
wp media import wp-content/plugins/xbo-market-kit/assets/images/branding/xbo-logo.svg --title="XBO Logo"
```

Or upload via admin dashboard.

**Step 3: Commit branding assets**

```bash
git add wp-content/plugins/xbo-market-kit/assets/images/branding/
git commit -m "feat(assets): add XBO branding assets (logo, hero background, favicon)"
```

---

## Task T11: Analyze Prime FSE Patterns + GetWid Blocks for Demo

**Step 1: List available Prime FSE patterns**

Check `wp-content/themes/prime-fse/patterns/` for hero sections, CTA, features.

Key patterns to evaluate:
- `hero-1.php`, `hero-2.php` — hero sections with gradient/image backgrounds
- `call-to-action-*.php` — CTA sections
- `features-list-*.php` — feature showcases
- `image-text-*.php` — text + image layouts

**Step 2: List GetWid blocks useful for demo**

Key GetWid blocks:
- `getwid/section` — Section with background
- `getwid/banner` — Banner with overlay
- `getwid/advanced-heading` — Styled headings
- `getwid/icon-box` — Feature boxes with icons
- `getwid/tabs` — Tabbed content

**Step 3: Document recommended combinations**

Write findings as notes in task tracker. Recommended layout:
- Home: Prime FSE `hero-1` pattern (modified) → Ticker block → Movers block → GetWid section with CTA
- Demo: Full-width page with GetWid sections separating each block

---

## Task T12: Home Page

**Step 1: Create or edit the front page**

Using WP REST API or block editor, create home page with:

1. **Hero section** — Prime FSE hero pattern adapted with XBO branding
   - XBO logo
   - Headline: "XBO Market Kit — Live Crypto Market Data for WordPress"
   - Subtitle describing the plugin
   - Gradient background matching XBO purple (#6319ff)

2. **Ticker block** — `xbo-market-kit/ticker` with `symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT"` columns=4, align=full

3. **Feature description section** — GetWid icon-boxes or columns explaining capabilities

4. **Movers block** — `xbo-market-kit/movers` with limit=10, align=wide

5. **CTA section** — Links to Demo page, API Docs, GitHub

**Step 2: Set as static front page**

Settings > Reading > Static page > Front page = created page.

**Step 3: Verify on frontend**

**Step 4: Commit page export (if applicable via patterns/templates)**

---

## Task T13: Demo Page

**Step 1: Create Demo page with all 5 blocks**

Full-width layout with sections for each block:

1. **Page title:** "Live Demo — XBO Market Kit Widgets"
2. **Ticker section** — heading + description + Ticker block (align=full)
3. **Movers section** — heading + description + Movers block (align=wide)
4. **Order Book section** — heading + description + Orderbook block (align=wide)
5. **Trades section** — heading + description + Trades block (align=wide)
6. **Slippage section** — heading + description + Slippage block (align=full)

Each section includes:
- H2 heading with anchor ID
- 1-2 sentences describing the widget
- The block with appropriate settings
- Spacer between sections

**Step 2: Verify page renders correctly**

**Step 3: Commit**

---

## Task T14: API Docs Page

**Step 1: Create API Documentation page**

Use `page-canvas` template (Prime FSE) for maximum width.

Content:
- H1: "XBO Market Kit — REST API Documentation"
- Brief intro paragraph
- MP API Docs shortcode: `[mp-api-docs-offline oas_filename="xbo-v1.json"]`
  OR `[mp-api-docs-online namespace="xbo/v1"]`

**Step 2: Copy OpenAPI spec to mp-api-docs directory if needed**

```bash
cp docs/api/xbo-v1.json wp-content/plugins/mp-api-docs/oas/xbo-v1.json
```

**Step 3: Verify Swagger UI renders on the page**

**Step 4: Commit**

---

## Task T15: Screenshot Pages (5 clean pages)

**Step 1: Create 5 minimal pages, one for each block**

Each page:
- Template: `page-canvas` (minimal chrome)
- Title: "Screenshot — {Block Name}"
- Content: Only the single block with default settings
- Status: Private (not visible to visitors)

Pages:
1. Screenshot — Ticker (`xbo-market-kit/ticker`, symbols="BTC/USDT,ETH/USDT,SOL/USDT,XRP/USDT", columns=4)
2. Screenshot — Movers (`xbo-market-kit/movers`, limit=10)
3. Screenshot — Order Book (`xbo-market-kit/orderbook`, symbol="BTC_USDT", depth=20)
4. Screenshot — Trades (`xbo-market-kit/trades`, symbol="BTC/USDT", limit=20)
5. Screenshot — Slippage (`xbo-market-kit/slippage`, symbol="BTC_USDT")

**Step 2: Verify each page renders cleanly**

---

## Task T16: Take Screenshots

**Prerequisites:** Chrome DevTools Protocol (MCP server `chrome-devtools`)

**Viewport:** 1728x1117 (MacBook 16")

**Step 1: Take frontend screenshots**

For each of the 5 screenshot pages + Home + Demo:
- Navigate to page URL
- Wait for data to load (widgets populate)
- Take screenshot
- Save to `docs/screenshots/`

Naming convention:
- `docs/screenshots/frontend-ticker.png`
- `docs/screenshots/frontend-movers.png`
- `docs/screenshots/frontend-orderbook.png`
- `docs/screenshots/frontend-trades.png`
- `docs/screenshots/frontend-slippage.png`
- `docs/screenshots/frontend-home.png`
- `docs/screenshots/frontend-demo.png`

**Step 2: Take editor screenshots**

For each block, open in editor and screenshot showing:
- Block selected in editor
- InspectorControls visible in sidebar
- ServerSideRender preview visible

Login: admin / 1

Naming:
- `docs/screenshots/editor-ticker.png`
- `docs/screenshots/editor-movers.png`
- `docs/screenshots/editor-orderbook.png`
- `docs/screenshots/editor-trades.png`
- `docs/screenshots/editor-slippage.png`

**Step 3: Commit screenshots**

```bash
git add docs/screenshots/
git commit -m "docs(screenshots): add frontend and editor screenshots for all 5 blocks"
```

---

## Task T17: Create docs/SHOWCASE.md

**Step 1: Write SHOWCASE.md**

```markdown
# XBO Market Kit — Visual Showcase

Screenshots taken at 1728x1117 (MacBook 16" viewport).

## Gutenberg Block Editor

Each block provides InspectorControls (sidebar settings panel) with live ServerSideRender preview.

### Ticker Block
![Ticker Editor](screenshots/editor-ticker.png)

### Top Movers Block
![Movers Editor](screenshots/editor-movers.png)

... (all 5 blocks)

## Frontend Rendering

### Live Ticker
![Ticker Frontend](screenshots/frontend-ticker.png)

... (all 5 blocks)

## Demo Pages

### Home Page
![Home Page](screenshots/frontend-home.png)

### Demo Page
![Demo Page](screenshots/frontend-demo.png)
```

**Step 2: Commit**

```bash
git add docs/SHOWCASE.md
git commit -m "docs: add SHOWCASE.md with screenshot gallery for all blocks"
```

---

## Task T18: Update README.md

**Step 1: Update README.md**

Add sections:
- Link to `docs/SHOWCASE.md` ("See full visual showcase")
- Updated metrics (tasks count, test count, commit count)
- Gutenberg blocks section (editor UI screenshots inline — pick 1-2 best)
- Updated feature matrix showing editor UI support

**Step 2: Commit**

```bash
git add README.md
git commit -m "docs(readme): add SHOWCASE link, update metrics and feature matrix"
```

---

## Task T19: Worklog + Metrics Update

**Step 1: Update worklog**

Add entry to `docs/worklog/2026-02-26.md` covering:
- All tasks completed in this session
- Commit hashes
- Time spent
- Key decisions

**Step 2: Update metrics**

Update `docs/metrics/tasks.json` with new tasks (T01-T20).

**Step 3: Commit**

```bash
git add docs/worklog/ docs/metrics/
git commit -m "docs(worklog): update worklog and metrics for block editor UI session"
```

---

## Task T20: Final Code Review

**Step 1: Run full verification**

```bash
cd wp-content/plugins/xbo-market-kit
npm run build
composer run phpcs
composer run phpstan
composer run test
```

**Step 2: Review all changes since session start**

```bash
git log --oneline ef4d0f6..HEAD
git diff ef4d0f6..HEAD --stat
```

**Step 3: Check for security issues, code quality, WordPress standards compliance**

**Step 4: Document review findings**

---

## Execution Notes

### Per-task workflow reminder
1. Read `docs/plans/2026-02-26-task-tracker.md`
2. Update task status to `in-progress`
3. Execute all steps
4. Run verification (build/phpcs/phpstan/test as applicable)
5. Commit with proper prefix
6. Update task-tracker to `done` with commit hash

### Parallel execution (Wave 3: T03-T07)
When running blocks T03-T07 in parallel using git worktrees:
- Each worktree creates its own branch: `feat/block-editor-{name}`
- After all complete, merge all branches into main
- Resolve any conflicts in block.json files (should be none — separate files)

### Recovery after crash/token exhaustion
1. Open new Claude Code session
2. Read `docs/plans/2026-02-26-task-tracker.md`
3. Find first task that is NOT `done`
4. If a task is `in-progress`, check git log to see if it was partially committed
5. Continue from where it left off
