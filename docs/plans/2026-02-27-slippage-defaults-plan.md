# Slippage Calculator Defaults Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Set default amount=1, always show results table (with dashes when no data), auto-calculate on load, and fix field height alignment.

**Architecture:** Changes span block registration (JSON), server render (PHP), Interactivity API store (JS), and CSS. The render.php fallback ensures backward compatibility for existing blocks with empty amount.

**Tech Stack:** WordPress Blocks API, Interactivity API, CSS Custom Properties

---

### Task 1: Update block.json defaults

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/src/blocks/slippage/block.json:14`
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/slippage/block.json:22`

**Step 1: Change source block.json**

In `src/blocks/slippage/block.json`, change line 14:

```json
"amount": { "type": "string", "default": "1" },
```

**Step 2: Change built block.json**

In `includes/Blocks/slippage/block.json`, change line 22:

```json
"default": "1"
```

**Step 3: Commit**

```bash
git add src/blocks/slippage/block.json includes/Blocks/slippage/block.json
git commit -m "feat(slippage): set default amount to 1 in block.json"
```

---

### Task 2: Add render.php fallback for empty amount

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Blocks/slippage/render.php:14`

**Step 1: Add fallback**

Change line 14 from:

```php
'amount' => $attributes['amount'] ?? '',
```

to:

```php
'amount' => ! empty( $attributes['amount'] ) ? $attributes['amount'] : '1',
```

This ensures existing blocks with `amount=""` saved in post content still get `'1'`.

**Step 2: Commit**

```bash
git add includes/Blocks/slippage/render.php
git commit -m "feat(slippage): fallback empty amount to 1 in block render"
```

---

### Task 3: Update editor help text

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/src/blocks/slippage/index.js:57-59`

**Step 1: Update help text**

Change the TextControl help text from:

```jsx
help={ __(
    'Leave empty to let user input amount on frontend',
    'xbo-market-kit'
) }
```

to:

```jsx
help={ __(
    'Default amount for slippage calculation (default: 1)',
    'xbo-market-kit'
) }
```

**Step 2: Rebuild JS**

```bash
cd wp-content/plugins/xbo-market-kit && npx wp-scripts build
```

**Step 3: Commit**

```bash
git add src/blocks/slippage/index.js build/blocks/slippage/index.js
git commit -m "feat(slippage): update editor help text for amount field"
```

---

### Task 4: Remove results table hiding, show dashes as default

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/includes/Shortcodes/SlippageShortcode.php:226-227`

**Step 1: Remove data-wp-class--xbo-mk-hidden from results div**

Change lines 226-227 from:

```php
$html .= '<div class="xbo-mk-slippage__results"'
    . ' data-wp-class--xbo-mk-hidden="!state.slippageHasResult">';
```

to:

```php
$html .= '<div class="xbo-mk-slippage__results">';
```

**Step 2: Change default metric text from `--` to `—`**

On line 244, change:

```php
. ' data-wp-text="state.slippageResult_' . esc_attr( $key ) . '">--</div>';
```

to:

```php
. ' data-wp-text="state.slippageResult_' . esc_attr( $key ) . '">' . "\xE2\x80\x94" . '</div>';
```

(UTF-8 em dash `—` instead of `--`)

**Step 3: Commit**

```bash
git add includes/Shortcodes/SlippageShortcode.php
git commit -m "feat(slippage): always show results table with em dash defaults"
```

---

### Task 5: Update JS getters to return em dash when no result

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/js/interactivity/slippage.js:77-106`

**Step 1: Update all 6 result getters**

Replace each `'--'` fallback with `'\u2014'` (em dash). Lines to change:

Line 82: `} ) || '--';` → `} ) ?? '\u2014';`
Line 86: `return r ? ... : '--';` → `return r ? ... : '\u2014';`
Line 90: `return r?.spread?.toFixed( 8 ) || '--';` → `return r?.spread?.toFixed( 8 ) ?? '\u2014';`
Line 94: `return r ? ... : '--';` → `return r ? ... : '\u2014';`
Line 98: `return r?.depth_used?.toFixed( 8 ) || '--';` → `return r?.depth_used?.toFixed( 8 ) ?? '\u2014';`
Line 105: `} ) || '--';` → `} ) ?? '\u2014';`

**Important:** Use `??` (nullish coalescing) instead of `||` because `toLocaleString()` and `toFixed()` can return `"0"` which is falsy with `||`. For getters that check `r ?` ternary, just change the fallback string.

Full replacement for all 6 getters:

```javascript
get slippageResult_avg_price() {
    const r = getContext().result;
    return r?.avg_price?.toLocaleString( 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    } ) ?? '\u2014';
},
get slippageResult_slippage_pct() {
    const r = getContext().result;
    return r ? `${ r.slippage_pct }%` : '\u2014';
},
get slippageResult_spread() {
    const r = getContext().result;
    return r?.spread?.toFixed( 8 ) ?? '\u2014';
},
get slippageResult_spread_pct() {
    const r = getContext().result;
    return r ? `${ r.spread_pct }%` : '\u2014';
},
get slippageResult_depth_used() {
    const r = getContext().result;
    return r?.depth_used?.toFixed( 8 ) ?? '\u2014';
},
get slippageResult_total_cost() {
    const r = getContext().result;
    return r?.total_cost?.toLocaleString( 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    } ) ?? '\u2014';
},
```

**Step 2: Commit**

```bash
git add assets/js/interactivity/slippage.js
git commit -m "feat(slippage): return em dash in getters when no result"
```

---

### Task 6: Fix Amount field height to match selector triggers

**Files:**
- Modify: `wp-content/plugins/xbo-market-kit/assets/css/src/_slippage.css:56-67,169-184`

**Step 1: Add explicit height to both elements**

Add `height: 40px; box-sizing: border-box;` to `.xbo-mk-slippage__input` (after line 66):

```css
.xbo-mk-slippage__input {
  width: 100%;
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-md);
  border: 1px solid var(--xbo-mk--color-border);
  border-radius: var(--xbo-mk--radius-md);
  font-size: var(--xbo-mk--font-size-sm);
  font-family: var(--xbo-mk--font-primary);
  color: var(--xbo-mk--color-text-primary);
  background: var(--xbo-mk--color-bg);
  transition: var(--xbo-mk--transition-fast);
  outline: none;
  height: 40px;
  box-sizing: border-box;
}
```

Add `height: 40px; box-sizing: border-box;` to `.xbo-mk-slippage__selector-trigger` (after line 183):

```css
.xbo-mk-slippage__selector-trigger {
  display: flex;
  align-items: center;
  gap: var(--xbo-mk--space-xs);
  width: 100%;
  padding: var(--xbo-mk--space-sm) var(--xbo-mk--space-md);
  border: 1px solid var(--xbo-mk--color-border);
  border-radius: var(--xbo-mk--radius-md);
  background: var(--xbo-mk--color-bg);
  font-size: var(--xbo-mk--font-size-sm);
  font-family: var(--xbo-mk--font-primary);
  color: var(--xbo-mk--color-text-primary);
  cursor: pointer;
  transition: var(--xbo-mk--transition-fast);
  outline: none;
  height: 40px;
  box-sizing: border-box;
}
```

**Step 2: Rebuild CSS**

```bash
cd wp-content/plugins/xbo-market-kit && npx postcss assets/css/src/main.css -o assets/css/xbo-market-kit.css
```

**Step 3: Commit**

```bash
git add assets/css/src/_slippage.css assets/css/xbo-market-kit.css
git commit -m "fix(slippage): align amount input height with selector triggers"
```

---

### Task 7: Update metrics, worklog, and README

**Files:**
- Modify: `docs/metrics/tasks.json`
- Modify: `docs/worklog/2026-02-27.md`
- Modify: `README.md` (project root)

**Step 1: Add task entry to tasks.json**

Add new task object to the `tasks` array (before the closing `]`):

```json
{
    "id": "2026-02-27-slippage-defaults",
    "description": "Slippage calculator: default amount=1, always-visible results table with em dash placeholders, auto-calculate on load, field height alignment",
    "plan": "docs/plans/2026-02-27-slippage-defaults-plan.md",
    "started": "2026-02-27T22:00:00+02:00",
    "completed": null,
    "duration_minutes": null,
    "commits": null,
    "cost_usd": null,
    "coverage": null,
    "issue_number": null,
    "status": "in_progress",
    "sessions": []
}
```

Update `totals.total_tasks` to 19.

**Step 2: Add worklog entry**

Append to `docs/worklog/2026-02-27.md` or create new section for this task.

**Step 3: Update README if there is a slippage section**

Check if README mentions slippage defaults or amount behavior and update accordingly.

**Step 4: Commit**

```bash
git add docs/metrics/tasks.json docs/worklog/2026-02-27.md README.md
git commit -m "docs: update metrics, worklog, and README for slippage defaults"
```

---

### Task 8: Manual verification

**Step 1: Load frontend page with slippage block**

Visit `http://claude-code-hackathon-xbo-market-kit.local/` and check:
- [ ] Amount field shows `1` by default
- [ ] Results table is visible immediately (with `—` before API responds)
- [ ] After ~300ms, real data populates the table
- [ ] Amount field height matches selector trigger height visually

**Step 2: Test in block editor**

Open a post with slippage block in Gutenberg:
- [ ] Default amount shows `1` in inspector
- [ ] Help text updated
- [ ] Preview renders correctly

**Step 3: Test empty amount edge case**

Manually clear the amount field on frontend:
- [ ] Table stays visible with `—` dashes
- [ ] No API request fires (amount <=0 guard in JS)

**Step 4: Run tests**

```bash
cd wp-content/plugins/xbo-market-kit && composer run test
```

Expected: All tests pass.

**Step 5: Run code quality checks**

```bash
cd wp-content/plugins/xbo-market-kit && composer run phpcs && composer run phpstan
```

Expected: No new errors.
