# Mega Menu Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace generic theme mega menu with XBO-branded navigation covering all 17 site pages, using Getwid icon-box blocks with promo sidebars.

**Architecture:** Single file replacement (`patterns/getwid/header.php`) + CSS additions for hover effects. All navigation items use Getwid icon-box blocks with FontAwesome icons, organized in columns with promo sidebar panels.

**Tech Stack:** WordPress FSE, Getwid Mega Menu blocks, Gutenberg block markup, CSS

**Design Doc:** `docs/plans/2026-02-27-mega-menu-design.md`

---

### Task 1: Replace Getwid Header Pattern

**Files:**
- Modify: `wp-content/themes/prime-fse/patterns/getwid/header.php` (full replacement)

**Step 1: Replace header pattern file**

Replace the entire content of `wp-content/themes/prime-fse/patterns/getwid/header.php` with the mega menu markup below.

The file contains:
- **Header wrapper:** Full-width constrained group with bottom border
- **Logo:** Site logo (200px) on the left
- **Getwid Mega Menu** with 5 top-level items:
  - **Home** — direct link to `/`, no dropdown
  - **Widgets** — dropdown with 2×3 icon-box grid + promo sidebar (6 items)
  - **Showcase** — dropdown with 3 icon-boxes + promo sidebar
  - **Developers** — dropdown with 3 icon-boxes + promo sidebar
  - **Resources** — dropdown with 3 icon-boxes + contact sidebar
- **Download button:** Outline style, links to GitHub releases

Each dropdown follows the pattern:
```
columns (no gap)
├── column (70% width, padding 40px/30px)
│   └── [icon-box items in 1 or 2 columns]
└── column (30% width, #f4f3f8 background, border-left separator)
    └── [promo heading + description + CTA button]
```

Each icon-box item:
```
getwid/icon-box (icon: FontAwesome, color-1, 25px, left layout, middle position)
├── heading (H4, 16px, 600 weight, color-4, contains <a> link)
└── paragraph (13px, #898299 muted color, description text)
```

**Page URL mapping:**

| Menu Item | Page Slug | URL |
|-----------|-----------|-----|
| Home | xbo-home | `/` |
| Widgets Overview | widgets-overview | `/widgets-overview/` |
| Ticker | xbo-demo-ticker | `/xbo-demo-ticker/` |
| Top Movers | xbo-demo-movers | `/xbo-demo-movers/` |
| Order Book | xbo-demo-orderbook | `/xbo-demo-orderbook/` |
| Recent Trades | xbo-demo-trades | `/xbo-demo-trades/` |
| Slippage Calculator | xbo-demo-slippage | `/xbo-demo-slippage/` |
| Showcase | xbo-showcase | `/xbo-showcase/` |
| Block Demos | xbo-demos | `/xbo-demos/` |
| Real-world Layouts | real-world-layouts | `/real-world-layouts/` |
| Getting Started | getting-started | `/getting-started/` |
| API Documentation | xbo-api-docs | `/xbo-api-docs/` |
| Integration Guide | integration-guide | `/integration-guide/` |
| FAQ | faq | `/faq/` |
| Changelog | changelog | `/changelog/` |
| Privacy Policy | privacy-policy | `/privacy-policy/` |

**Icon mapping:**

| Item | FontAwesome Icon |
|------|-----------------|
| Widgets Overview | `fas fa-th-large` |
| Ticker | `fas fa-chart-line` |
| Top Movers | `fas fa-sort-amount-up` |
| Order Book | `fas fa-book-open` |
| Recent Trades | `fas fa-exchange-alt` |
| Slippage Calculator | `fas fa-calculator` |
| Showcase | `fas fa-star` |
| Block Demos | `fas fa-cubes` |
| Real-world Layouts | `fas fa-layer-group` |
| Getting Started | `fas fa-rocket` |
| API Documentation | `fas fa-code` |
| Integration Guide | `fas fa-plug` |
| FAQ | `fas fa-question-circle` |
| Changelog | `fas fa-history` |
| Privacy Policy | `fas fa-shield-alt` |

**Step 2: Verify in browser**

Open: `http://claude-code-hackathon-xbo-market-kit.local/`
- Header renders with logo + 5 menu items + Download button
- Hovering "Widgets" shows dropdown with 6 icon-boxes in 2 columns + sidebar
- Hovering "Showcase" shows 3 icon-boxes + sidebar
- Hovering "Developers" shows 3 icon-boxes + sidebar
- Hovering "Resources" shows 3 icon-boxes + contact sidebar
- All links point to correct pages
- Download button links to GitHub releases

**Step 3: Commit**

```bash
git add wp-content/themes/prime-fse/patterns/getwid/header.php
git commit -m "feat(theme): replace mega menu with XBO-branded navigation"
```

---

### Task 2: Add CSS Hover Effects

**Files:**
- Modify: `wp-content/themes/prime-fse/style.css` (append at end)

**Step 1: Add mega menu icon-box hover CSS**

Append the following CSS to the end of `style.css`:

```css
/* XBO Mega Menu Icon-Box Hover Effects */
.gw-mm-item__dropdown .wp-block-getwid-icon-box {
	padding: 12px 15px;
	border-radius: 8px;
	transition: background-color 0.2s ease;
}

.gw-mm-item__dropdown .wp-block-getwid-icon-box:hover {
	background-color: var(--wp--preset--color--color-6);
}

.gw-mm-item__dropdown .wp-block-getwid-icon-box:hover .wp-block-getwid-icon-box__icon-wrapper {
	transform: translateX(2px);
	transition: transform 0.2s ease;
}

.gw-mm-item__dropdown .wp-block-getwid-icon-box a {
	text-decoration: none;
	color: inherit;
}

.gw-mm-item__dropdown .wp-block-getwid-icon-box a:hover {
	text-decoration: none;
	color: var(--wp--preset--color--color-1);
}
```

**Step 2: Verify hover effects in browser**

- Hover over icon-box items in any dropdown
- Background should highlight with #f4f3f8
- Icon should shift 2px right
- Link text should turn Royal Purple on hover

**Step 3: Commit**

```bash
git add wp-content/themes/prime-fse/style.css
git commit -m "feat(theme): add mega menu icon-box hover effects"
```

---

### Task 3: Final Verification & Documentation

**Step 1: Full navigation test**

Click through every link in every dropdown panel:
- All 16 page links work (no 404s)
- Download button opens GitHub releases in new tab
- Home link navigates to homepage
- Responsive: menu collapses to hamburger on mobile (<1000px)

**Step 2: Update worklog**

Add entry to worklog if `docs/worklog/` exists.

**Step 3: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix(theme): mega menu navigation fixes"
```
