# Slippage Block Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix slippage form layout (narrow fields) and icon update bug with minimal CSS + PHP changes.

**Architecture:** CSS grid adjustment (3fr 2fr, toggle full-width row) + add `data-wp-bind--src` reactive binding to selector trigger icons. No JS changes needed.

**Tech Stack:** WordPress Interactivity API, PHP render, CSS grid

**Design doc:** `docs/plans/2026-02-26-slippage-redesign-design.md`

---

### Task 1: Fix CSS grid layout

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_slippage.css:28-32` (desktop grid)
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_slippage.css:34-38` (mobile grid)

**Step 1: Change desktop grid from 3 equal columns to 2 columns (pair + amount)**

In `_slippage.css`, change `.xbo-mk-slippage__fields`:

```css
/* BEFORE */
.xbo-mk-slippage__fields {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--xbo-mk--space-md);
}

/* AFTER */
.xbo-mk-slippage__fields {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: var(--xbo-mk--space-md);
}
```

**Step 2: Add toggle full-width rule**

Add after `.xbo-mk-slippage__field` block (after line ~43):

```css
.xbo-mk-slippage__field:last-child {
  grid-column: 1 / -1;
}
```

This targets the toggle field (3rd field) and spans it across both columns.

**Step 3: Verify mobile grid unchanged**

The existing `@media (max-width: 768px)` rule already sets `grid-template-columns: 1fr` — no change needed. Confirm order is Pair → Amount → Toggle in single column (matches HTML order).

**Step 4: Run PHPCS to verify no issues**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs`
Expected: No new errors (CSS file not checked by PHPCS)

**Step 5: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/assets/css/src/_slippage.css
git commit -m "feat(slippage): change form layout to 2-column grid with full-width toggle"
```

---

### Task 2: Fix icon reactive binding

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php:300-302`

**Step 1: Add data-wp-bind--src to trigger icon img**

In `SlippageShortcode.php`, method `render_selector()`, find the trigger button `<img>` (around line 300):

```php
// BEFORE
$html .= '<img class="xbo-mk-slippage__selector-icon"'
    . ' src="' . esc_url( $selected_icon ) . '"'
    . ' alt="" width="20" height="20" />';

// AFTER
$icon_state = 'base' === $type ? 'state.slippageBaseIcon' : 'state.slippageQuoteIcon';
$html .= '<img class="xbo-mk-slippage__selector-icon"'
    . ' src="' . esc_url( $selected_icon ) . '"'
    . ' data-wp-bind--src="' . $icon_state . '"'
    . ' alt="" width="20" height="20" />';
```

The `src` attribute stays for initial server render. `data-wp-bind--src` takes over reactively on the client.

**Step 2: Run PHPCS**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs`
Expected: PASS (no new violations)

**Step 3: Run PHPStan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpstan`
Expected: PASS

**Step 4: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php
git commit -m "fix(slippage): bind selector icon src reactively via Interactivity API"
```

---

### Task 3: Remove "Side" label from toggle

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php:190-193`

**Step 1: Remove the Side label from toggle field**

In `SlippageShortcode.php`, in `render()` method, find the side toggle section (around line 190):

```php
// BEFORE
$html .= '<div class="xbo-mk-slippage__field">';
$html .= '<label class="xbo-mk-slippage__label">'
    . esc_html__( 'Side', 'xbo-market-kit' ) . '</label>';
$html .= '<div class="xbo-mk-slippage__toggle">';

// AFTER
$html .= '<div class="xbo-mk-slippage__field">';
$html .= '<div class="xbo-mk-slippage__toggle">';
```

Simply remove the `<label>` line — the Buy/Sell toggle is self-explanatory.

**Step 2: Run PHPCS + PHPStan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs && composer run phpstan`
Expected: PASS

**Step 3: Run unit tests**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test`
Expected: All tests pass

**Step 4: Commit**

```bash
git add wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php
git commit -m "refactor(slippage): remove redundant Side label from toggle"
```

---

### Task 4: Manual browser verification

**Step 1: Open slippage calculator in browser**

URL: `http://claude-code-hackathon-xbo-market-kit.local/`
Navigate to a page with the slippage block.

**Step 2: Verify checklist**

- [ ] Desktop: Pair + Amount on row 1, Toggle full-width on row 2
- [ ] Select different base currency → icon updates immediately
- [ ] Select different quote currency → icon updates immediately
- [ ] Long ticker names visible without truncation
- [ ] Buy/Sell toggle works, spans full width
- [ ] Mobile (resize to < 768px): Pair → Amount → Toggle in single column
- [ ] Dark mode: no visual regressions
