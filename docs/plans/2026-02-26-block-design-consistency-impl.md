# Block Design Consistency — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make all 5 blocks visually consistent with alignfull + inner container pattern, responsive across themes and page builders.

**Architecture:** Each block gets `alignfull` support in block.json. `AbstractShortcode::render_wrapper()` adds `.xbo-mk-inner` wrapper around content. CSS tokens control max-width (1200px default) and responsive padding. Ticker cards get responsive breakpoints.

**Tech Stack:** PHP (WordPress block API, shortcodes), PostCSS (CSS custom properties, media queries)

**Design doc:** `docs/plans/2026-02-26-block-design-consistency.md`

---

### Task 1: Add layout tokens to CSS variables

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_variables.css:46-52`

**Step 1: Add layout tokens after the Spacing section**

In `_variables.css`, after the spacing variables block (line 52), add:

```css
  /* ===== Layout ===== */
  --xbo-mk--content-max: 1200px;
  --xbo-mk--content-padding: var(--xbo-mk--space-lg);   /* 16px */
```

Also add dark mode override in both dark mode blocks — no layout changes needed for dark mode, so nothing to add there.

**Step 2: Verify file is valid CSS**

Run: `cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/widgets.css -o /dev/null 2>&1; echo "exit: $?"`
Expected: `exit: 0`

**Step 3: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/src/_variables.css
git commit -m "feat(css): add layout tokens --content-max and --content-padding"
```

---

### Task 2: Add .xbo-mk-inner wrapper and defensive alignfull CSS

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_base.css`

**Step 1: Add inner wrapper and alignfull styles at the end of `_base.css`**

Append after existing content (after line 67):

```css
/* ===== Layout: Inner Container ===== */
/* Used by all blocks to constrain content width inside alignfull wrappers */
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

/* ===== Defensive alignfull ===== */
/* Ensures full-width even in classic themes without align-wide support */
[class^="wp-block-xbo-market-kit"].alignfull {
  width: 100vw;
  max-width: 100vw;
  margin-left: calc(-50vw + 50%);
}
```

**Step 2: Verify PostCSS builds without errors**

Run: `cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/widgets.css -o /dev/null 2>&1; echo "exit: $?"`
Expected: `exit: 0`

**Step 3: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/src/_base.css
git commit -m "feat(css): add .xbo-mk-inner wrapper and defensive alignfull styles"
```

---

### Task 3: Add responsive breakpoints to Ticker CSS

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_ticker.css`

**Step 1: Add responsive media queries at the end of `_ticker.css`**

Append after existing content (after line 116):

```css
/* ===== Responsive: Ticker Cards ===== */

/* Mobile: always 1 column, full width */
@media (max-width: 768px) {
  .xbo-mk-ticker__card {
    flex-basis: 100%;
    max-width: 100%;
    min-width: 0;
  }
}

/* Tablet: max 2 columns for dense layouts */
@media (min-width: 769px) and (max-width: 1024px) {
  .xbo-mk-ticker[class*="--cols-3"] .xbo-mk-ticker__card,
  .xbo-mk-ticker[class*="--cols-4"] .xbo-mk-ticker__card {
    max-width: calc(50% - var(--xbo-mk--space-lg) / 2);
  }
}
```

**Step 2: Verify PostCSS builds without errors**

Run: `cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/widgets.css -o /dev/null 2>&1; echo "exit: $?"`
Expected: `exit: 0`

**Step 3: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/src/_ticker.css
git commit -m "feat(css): add responsive breakpoints for ticker cards (mobile/tablet)"
```

---

### Task 4: Update all 5 block.json files with align support

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/ticker/block.json`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/movers/block.json`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/orderbook/block.json`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/trades/block.json`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/slippage/block.json`

**Step 1: In each block.json, replace the `supports` object and add `align` attribute**

For **all 5 files**, change:

```json
"supports": {
    "html": false
}
```

to:

```json
"supports": {
    "html": false,
    "align": ["wide", "full"]
}
```

And add `"align"` to the `"attributes"` object:

```json
"align": {
    "type": "string",
    "default": "full"
}
```

**Ticker block.json** — add `"align"` attribute alongside existing `symbols`, `refresh`, `columns`.

**Movers block.json** — add `"align"` attribute alongside existing `mode`, `limit`.

**Orderbook block.json** — add `"align"` attribute alongside existing `symbol`, `depth`, `refresh`.

**Trades block.json** — add `"align"` attribute alongside existing `symbol`, `limit`, `refresh`.

**Slippage block.json** — add `"align"` attribute alongside existing `symbol`, `side`, `amount`.

**Step 2: Verify JSON is valid**

Run: `for f in wp-content/plugins/xbo-market-kit/includes/Blocks/*/block.json; do echo "$f:"; python3 -m json.tool "$f" > /dev/null && echo "  OK" || echo "  INVALID"; done`
Expected: All 5 files show "OK"

**Step 3: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Blocks/*/block.json
git commit -m "feat(blocks): add alignwide/alignfull support to all 5 blocks"
```

---

### Task 5: Add .xbo-mk-inner wrapper to AbstractShortcode::render_wrapper()

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/AbstractShortcode.php:137-141`
- Test: `wp-content/plugins/xbo-market-kit/tests/Unit/Shortcodes/AbstractShortcodeTest.php` (create)

**Step 1: Write failing test**

Create `tests/Unit/Shortcodes/AbstractShortcodeTest.php`:

```php
<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Shortcodes;

use PHPUnit\Framework\TestCase;

/**
 * Concrete test double for AbstractShortcode.
 */
class ConcreteShortcode extends \XboMarketKit\Shortcodes\AbstractShortcode {
    protected function get_tag(): string {
        return 'test_shortcode';
    }

    protected function get_defaults(): array {
        return array();
    }

    protected function render( array $atts ): string {
        return '<div class="test-content">hello</div>';
    }

    /**
     * Expose render_wrapper for testing.
     */
    public function test_render_wrapper( string $content, array $context = array() ): string {
        return $this->render_wrapper( $content, $context );
    }
}

class AbstractShortcodeTest extends TestCase {

    public function test_render_wrapper_includes_inner_container(): void {
        $shortcode = new ConcreteShortcode();
        $html      = $shortcode->test_render_wrapper( '<div>content</div>' );

        $this->assertStringContainsString( 'xbo-mk-inner', $html );
        $this->assertStringContainsString( 'data-wp-interactive="xbo-market-kit"', $html );
    }

    public function test_render_wrapper_inner_wraps_content(): void {
        $shortcode = new ConcreteShortcode();
        $html      = $shortcode->test_render_wrapper( '<div>content</div>' );

        // Inner wrapper should contain the content
        $this->assertMatchesRegularExpression(
            '/<div class="xbo-mk-inner">.*<div>content<\/div>.*<\/div>/s',
            $html
        );
    }

    public function test_render_wrapper_with_context(): void {
        $shortcode = new ConcreteShortcode();
        $html      = $shortcode->test_render_wrapper(
            '<div>content</div>',
            array( 'key' => 'value' )
        );

        $this->assertStringContainsString( 'xbo-mk-inner', $html );
        $this->assertStringContainsString( 'data-wp-context', $html );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=AbstractShortcodeTest 2>&1`
Expected: FAIL — `xbo-mk-inner` not found in output

**Step 3: Modify `render_wrapper()` in `AbstractShortcode.php`**

In `AbstractShortcode.php`, change `render_wrapper()` (lines 137-141) from:

```php
protected function render_wrapper( string $content, array $context = array() ): string {
    $json = ! empty( $context ) ? wp_json_encode( $context ) : '';
    $data = $json ? ' data-wp-context=\'' . esc_attr( $json ) . '\'' : '';
    return '<div data-wp-interactive="xbo-market-kit"' . $data . '>' . $content . '</div>';
}
```

to:

```php
protected function render_wrapper( string $content, array $context = array() ): string {
    $json = ! empty( $context ) ? wp_json_encode( $context ) : '';
    $data = $json ? ' data-wp-context=\'' . esc_attr( $json ) . '\'' : '';
    return '<div data-wp-interactive="xbo-market-kit"' . $data . '>'
        . '<div class="xbo-mk-inner">' . $content . '</div>'
        . '</div>';
}
```

**Step 4: Run test to verify it passes**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=AbstractShortcodeTest 2>&1`
Expected: All 3 tests PASS

**Step 5: Run full test suite to check for regressions**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test 2>&1`
Expected: All tests pass. If existing tests check for exact HTML output, they may need updating to account for the new `.xbo-mk-inner` wrapper.

**Step 6: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/AbstractShortcode.php \
       wp-content/plugins/xbo-market-kit/tests/Unit/Shortcodes/AbstractShortcodeTest.php
git commit -m "feat(shortcodes): wrap block content in .xbo-mk-inner container

Adds inner wrapper to AbstractShortcode::render_wrapper() for consistent
max-width and padding across all blocks and shortcodes."
```

---

### Task 6: Build CSS dist files and run quality checks

**Files:**
- Rebuild: `wp-content/plugins/xbo-market-kit/assets/css/dist/widgets.css`
- Rebuild: `wp-content/plugins/xbo-market-kit/assets/css/dist/widgets.min.css`

**Step 1: Build development CSS**

Run: `cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/widgets.css -o assets/css/dist/widgets.css`
Expected: Exit 0, no errors

**Step 2: Build production (minified) CSS**

Run: `cd wp-content/plugins/xbo-market-kit && NODE_ENV=production npx postcss assets/css/src/widgets.css -o assets/css/dist/widgets.min.css`
Expected: Exit 0, no errors

**Step 3: Run PHPCS**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs 2>&1`
Expected: No new errors (existing warnings may be present)

**Step 4: Run PHPStan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpstan 2>&1`
Expected: No new errors

**Step 5: Run full test suite**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test 2>&1`
Expected: All tests pass

**Step 6: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/dist/
git commit -m "chore(css): rebuild dist files with layout and responsive changes"
```

---

### Task 7: Visual verification in browser

**Files:** None (manual verification)

**Step 1: Open demo page**

Navigate to: `http://claude-code-hackathon-xbo-market-kit.local/xbo-market-kit-demo/`

**Step 2: Take desktop screenshot (1280px)**

Verify:
- All blocks are wider than before (no longer constrained to 645px content area)
- All blocks have the same visual width
- Content is centered with max-width 1200px

**Step 3: Take tablet screenshot (768px)**

Verify:
- All blocks are ~100% width with small padding
- Ticker shows max 2 columns for 3-4 col settings
- All blocks visually aligned

**Step 4: Take mobile screenshot (375px)**

Verify:
- All blocks are edge-to-edge with 12px padding
- Ticker shows 1 card per row, full width
- No horizontal scrollbar

**Step 5: Verify in Gutenberg editor**

Navigate to: `http://claude-code-hackathon-xbo-market-kit.local/wp-admin/`
Open the demo page in block editor.
Verify:
- Blocks show "Full Width" alignment option in toolbar
- Blocks can be switched to "Wide" alignment
- Default is "Full Width"
