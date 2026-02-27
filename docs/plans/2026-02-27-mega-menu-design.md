# Mega Menu Design Document

**Date:** 2026-02-27
**Author:** Claude Opus 4.6 (with Dmytro Kishkin)
**Status:** Approved
**Approach:** Uniform Rich Panels with Promo Sidebar

## Overview

Redesign the site header mega menu using Getwid Mega Menu blocks to replace generic theme template items (Home, About, Blog, Pages, Contact) with a structured navigation reflecting all 17 site pages organized into logical categories.

## Header Structure

```
[Logo: XBO Market Kit (200px)]  ·····  [Home] [Widgets ▼] [Showcase ▼] [Developers ▼] [Resources ▼]  ·····  [Download ↗]
```

- **Font:** Sora, 600 weight, Deep Indigo (#140533)
- **Hover color:** Royal Purple (#6319ff)
- **Download button:** Outline style → GitHub releases
- **Bottom border:** 1px #14053333
- **Padding:** 20px top/bottom, 30px left/right

## Dropdown Panel Design Pattern

All 4 dropdowns follow a consistent layout:

- **Full-width:** `dropdownMaxWidth: 1160px` (matches theme wideSize)
- **Left section (75%):** Getwid icon-box blocks in columns
  - Icon: FontAwesome, 25px, Royal Purple (#6319ff)
  - Title: H4, Deep Indigo (#140533), clickable link to page
  - Description: 1 line, muted (#898299), 14px
- **Right sidebar (25%):** Accent promo/contact panel
  - Background: #f4f3f8 (Soft Section Background)
  - Border-left: 1px #14053333 separator
  - Content: Section-specific CTA or contact info
- **Padding:** 40px top/bottom, 30px left/right per column
- **Shadow:** `0px 4px 20px rgba(0, 0, 0, 0.1)`
- **Border radius:** `0 0 10px 10px`

## Dropdown 1: Widgets

**Left section:** 2 columns × 3 rows (6 items)

| Icon | Title | Description | Page Slug |
|------|-------|-------------|-----------|
| `fas fa-th-large` | Widgets Overview | Compare all 5 widgets and use cases | widgets-overview |
| `fas fa-chart-line` | Ticker | Real-time crypto price ticker | xbo-demo-ticker |
| `fas fa-sort-amount-up` | Top Movers | Biggest gainers and losers | xbo-demo-movers |
| `fas fa-book-open` | Order Book | Live order book depth | xbo-demo-orderbook |
| `fas fa-exchange-alt` | Recent Trades | Latest executed trades | xbo-demo-trades |
| `fas fa-calculator` | Slippage Calculator | Estimate trading slippage | xbo-demo-slippage |

**Right sidebar:**
- Heading: "Explore All Widgets" (H3, Royal Purple)
- Description: "5 interactive crypto widgets with live market data from XBO exchange"
- CTA button: "View All →" → /widgets-overview/
- Background: #f4f3f8

## Dropdown 2: Showcase

**Left section:** 1 column, 3 items with larger vertical spacing

| Icon | Title | Description | Page Slug |
|------|-------|-------------|-----------|
| `fas fa-star` | Showcase | Featured integration examples | xbo-showcase |
| `fas fa-cubes` | Block Demos | All Gutenberg blocks in action | xbo-demos |
| `fas fa-layer-group` | Real-world Layouts | Production-ready layout templates | real-world-layouts |

**Right sidebar:**
- Heading: "See It Live" (H3, Royal Purple)
- Description: "Explore real integration examples and layout templates for your WordPress site"
- CTA button: "View Showcase →" → /xbo-showcase/
- Background: #f4f3f8

## Dropdown 3: Developers

**Left section:** 1 column, 3 items with larger vertical spacing

| Icon | Title | Description | Page Slug |
|------|-------|-------------|-----------|
| `fas fa-rocket` | Getting Started | Quick start guide for developers | getting-started |
| `fas fa-code` | API Documentation | REST API reference and endpoints | xbo-api-docs |
| `fas fa-plug` | Integration Guide | Custom theme and plugin integration | integration-guide |

**Right sidebar:**
- Heading: "Quick Start" (H3, Royal Purple)
- Description: "Get up and running in 5 minutes. Install, configure, and display your first widget."
- CTA button: "Start Now →" → /getting-started/
- Background: #f4f3f8

## Dropdown 4: Resources

**Left section:** 1 column, 3 items with larger vertical spacing

| Icon | Title | Description | Page Slug |
|------|-------|-------------|-----------|
| `fas fa-question-circle` | FAQ | Frequently asked questions | faq |
| `fas fa-history` | Changelog | Version history and updates | changelog |
| `fas fa-shield-alt` | Privacy Policy | Data handling and privacy | privacy-policy |

**Right sidebar (Contact block):**
- Heading: "Get In Touch" (H3, Royal Purple)
- Email: atlantdak@gmail.com (with `fas fa-envelope` icon)
- GitHub: Repository link (with `fab fa-github` icon)
- CTA button: "View on GitHub →" → https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit
- Background: #f4f3f8

## Visual Effects

### CSS Enhancements
- **Dropdown animation:** Fade-in + slide-down on open (CSS transition)
- **Icon-box hover:** Background highlight (#f4f3f8) + icon shift right (2px translateX)
- **Smooth transitions:** `transition: all 0.2s ease`
- **Active menu item:** Royal Purple underline indicator

### Responsive Behavior
- Desktop (1000px+): Full mega menu panels
- Tablet/Mobile (<1000px): Collapse to hamburger with Getwid's built-in overlay menu

## Technical Implementation

- **Block:** `getwid-megamenu/menu` with `expandDropdown: true`
- **File:** `wp-content/themes/prime-fse/patterns/getwid/header.php`
- **Inner blocks:** `wp:columns`, `wp:column`, `wp:getwid/icon-box`, `wp:getwid/advanced-heading`, `wp:buttons`, `wp:paragraph`
- **Page URLs:** Use relative paths based on page slugs
- **Menu items:** `getwid-megamenu/menu-item` with `textColor: color-4`

## Approval

✅ **Design approved by:** Dmytro Kishkin
✅ **Date:** 2026-02-27
✅ **Style chosen:** Uniform Rich Panels with Promo Sidebar
✅ **Contact info:** GitHub + Email
