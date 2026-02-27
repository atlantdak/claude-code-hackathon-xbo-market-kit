# Homepage Redesign Design

**Date:** 2026-02-27
**Status:** Approved
**Approach:** Showcase Landing (Approach A, Codex-reviewed)

## Overview

Rework the XBO Market Kit homepage from a flat widget dump into a structured showcase landing page. Each widget gets its own "stage" with description and link to detailed demo. Documentation pages get dedicated navigation section.

## Design Principles

- **Visual rhythm:** Alternating white ↔ color-6 backgrounds for depth and section separation
- **Each widget sells itself:** Title + description + live widget + specific CTA button
- **Credibility early:** Statistics section right after features, before deep widget demos
- **Quick onboarding:** 3-step "Quick Start" section to lower barrier to entry
- **Specific CTAs:** Each button has unique label ("View Movers Demo", "Try Order Book") instead of generic text

## Section Structure (10 sections)

### 1. Hero (UNCHANGED)
Cover block with background image, "XBO Market Kit" heading, description, author line, buttons (View Live Demo + GitHub Repository), crypto coins image. No changes.

### 2. Refresh Timer + Ticker
- `xbo-market-kit/refresh-timer` block (15s interval — project standard)
- `xbo-market-kit/ticker` with 6 pairs: BTC/USDT, ETH/USDT, SOL/USDT, XRP/USDT, BNB/USDT, ADA/USDT
- Button "View Ticker Demo →" linking to `/xbo-demo-ticker/`
- Background: white (default)

### 3. Features Grid
Based on theme's Features-List-1 pattern. 3 cards in columns:

| Card | Title | Description |
|------|-------|-------------|
| 1 | 5 Gutenberg Blocks | Ticker, Top Movers, Order Book, Recent Trades, and Slippage Calculator — all with live editor preview. |
| 2 | Real-Time API Data | Connected to XBO Exchange API with server-side caching and 15-second auto-refresh. |
| 3 | Multiple Integration Methods | Gutenberg blocks, shortcodes, or REST API — works with any theme or page builder. |

- Background: white
- Cards: white bg, 1px border (#dddddd), 8px radius, padding

### 4. Statistics
Based on theme's Performance pattern. 4 equal columns with large numbers:

| Stat | Label |
|------|-------|
| 5 | Gutenberg Blocks |
| 280+ | Trading Pairs |
| 15s | Auto-Refresh |
| 100% | Free & Open Source |

- Background: color-6

### 5. Quick Start (3 Steps)
3 columns showing the onboarding flow:

| Step | Title | Description |
|------|-------|-------------|
| 1 | Install the Plugin | Upload via WordPress admin or clone from GitHub. Activate and you're ready. |
| 2 | Add a Block | Open the block editor, search "XBO", and drop any widget into your page. |
| 3 | Publish & Go Live | Hit publish — real-time crypto data appears instantly. No API keys needed. |

- Background: white

### 6. Top Movers Showcase
- Heading: "Top Movers"
- Paragraph: "Track the biggest gainers and losers across 280+ trading pairs in real-time."
- `xbo-market-kit/movers` (count=10)
- Button: "View Movers Demo →" → `/xbo-demo-movers/`
- Background: color-6

### 7. Order Book + Recent Trades
- Heading: "Market Depth & Trade History"
- 2 columns:
  - **Left:** "Order Book" h3 + "Real-time bid/ask spread and depth visualization for BTC/USDT." + `xbo-market-kit/orderbook` (symbol=BTC/USDT, depth=15) + Button "Try Order Book →" → `/xbo-demo-orderbook/`
  - **Right:** "Recent Trades" h3 + "Live feed of executed trades with price, amount, and direction." + `xbo-market-kit/trades` (symbol=BTC/USDT, limit=15) + Button "View Trade History →" → `/xbo-demo-trades/`
- Background: white

### 8. Slippage Calculator
- Heading: "Slippage Calculator"
- Paragraph: "Calculate average execution price and slippage based on real-time order book depth."
- `xbo-market-kit/slippage` (symbol=BTC/USDT)
- Button: "Open Slippage Calculator →" → `/xbo-demo-slippage/`
- Background: color-6

### 9. Documentation & Resources
- Heading: "Documentation & Resources"
- 6 cards in 2 rows (3 columns each):

| Card | Title | Description | Link |
|------|-------|-------------|------|
| 1 | Getting Started | Install and configure in minutes | /getting-started/ |
| 2 | Widgets Overview | Explore all 5 widgets and their features | /widgets-overview/ |
| 3 | Integration Guide | Shortcodes, REST API, and theme integration | /integration-guide/ |
| 4 | API Documentation | REST endpoints and live API explorer | /xbo-api-docs/ |
| 5 | Real-world Layouts | News blogs, dashboards, portfolio examples | /real-world-layouts/ |
| 6 | FAQ | Installation, configuration, troubleshooting | /faq/ |

- Background: white
- Cards: white bg on color-6... Actually, white bg with border (same style as Features Grid cards)

### 10. CTA "Ready to Add" (UNCHANGED)
Dark background (color-1), heading, description, buttons (Download from GitHub + Read Documentation). No changes.

## Visual Rhythm

| Section | Background |
|---------|------------|
| 1. Hero | Image cover |
| 2. Timer + Ticker | White |
| 3. Features Grid | White |
| 4. Statistics | color-6 |
| 5. Quick Start | White |
| 6. Top Movers | color-6 |
| 7. OrderBook + Trades | White |
| 8. Slippage | color-6 |
| 9. Documentation | White |
| 10. CTA | color-1 (dark) |

## Technical Notes

- All widgets use project-standard 15s refresh interval
- No custom JS or CSS needed — all core WordPress blocks
- Ticker expanded from 4 to 6 pairs (matches Ticker Demo page)
- Using theme color presets (color-1, color-4, color-6, color-9) for consistency
- Buttons styled with 8px border-radius, color-1 bg or outline style

## Codex Review Summary

Codex approved the design with these incorporated improvements:
- Specific CTA labels per widget instead of generic "Explore Full Demo"
- Statistics moved earlier (after Features) for credibility
- Quick Start section added for onboarding
- Recommended NOT combining widgets into tabs (would need custom JS)
