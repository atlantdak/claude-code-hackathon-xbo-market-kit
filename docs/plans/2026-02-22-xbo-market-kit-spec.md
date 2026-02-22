# XBO Market Kit — Product Specification

> **For Claude:** This is the master product specification. All implementation plans derive from this document.

**Date:** 2026-02-22
**Status:** Approved
**Repository:** https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit

---

## 1. Hackathon Context

- **Format:** 1 week, standalone app, no internal company systems
- **Goal:** Complete application with strong visual impact and practical utility
- **Tech base:** WordPress plugin first, then Gutenberg blocks and Elementor widgets

## 2. Public API (Verified 2026-02-21)

Documentation: https://public-docs.xbo.com/ | https://api.xbo.com

### Working Endpoints (GET only)

| Endpoint | Response | Notes |
|----------|----------|-------|
| `/currencies` | 200 (205 records) | No query filtering |
| `/trading-pairs` | 200 (280 records) | No query filtering |
| `/trading-pairs/stats` | 200 (280 records) | No query filtering |
| `/trades?symbol=BTC/USDT` | 200 (1000 records) | Invalid symbol → empty `[]` |
| `/orderbook/{symbol}?depth={depth}` | 200 | Use `BTC_USDT` or URL-encoded. Max depth=250 |

### API Quirks

- Orderbook: use `BTC_USDT` format (not `BTC/USDT` in path — returns 404)
- Depth > 250 → HTTP 400 (`invalid-depth`)
- No CORS headers → **must use server-side proxy** (WordPress backend fetch)
- Orderbook is dynamic (prices/timestamps change every few seconds)
- Bulk endpoints don't support query parameter filtering

## 3. Available Data Fields

**From `/currencies`:** `code`, `name`, `type`, `isDepositEnabled`, `isWithdrawalEnabled`, `minWithdrawalAmount`

**From `/trading-pairs`:** `symbol`, `description`, `baseCurrency`, `quoteCurrency`, `lastPrice`, `isEnabled`, `last24HTradeVolume`, `last24HTradeVolumeUsd`

**From `/trading-pairs/stats`:** `lastPrice`, `lowestAsk`, `highestBid`, `priceChangePercent24H`, `highestPrice24H`, `lowestPrice24H`, `quoteVolume`

**From `/trades`:** `id`, `symbol`, `price`, `volume`, `quoteVolume`, `type`, `timeStamp`

**From `/orderbook`:** `bids`, `asks`, `timestamp`

## 4. Product Vision

**Name:** XBO Market Kit

Unified product deployed in stages:
1. **Shortcodes MVP** (quick launch and demo)
2. **Gutenberg Blocks**
3. **Elementor Widgets**
4. *(Post-hackathon)* Hosted mode / SaaS wrapper / embed script

One data-core, one source of truth backend.

## 5. MVP Features (Public API Only)

### Core Widgets

| Widget | Description | Data Source |
|--------|-------------|-------------|
| Live Ticker | Selected pairs, last price, 24h change | `/trading-pairs/stats` |
| Top Movers | Gainers/losers by 24h % change | `/trading-pairs/stats` |
| Mini Orderbook | Bids/asks table + spread | `/orderbook/{symbol}` |
| Recent Trades | Trade list with side, price, volume, time | `/trades` |
| Slippage Calculator | Avg execution price + slippage % from depth | `/orderbook/{symbol}` |

### Admin Settings

- API base URL
- Cache TTL per widget type
- Default symbols
- Theme presets
- Refresh intervals

### Shortcodes

```
[xbo_ticker symbols="BTC/USDT,ETH/USDT" refresh="15"]
[xbo_movers mode="gainers" limit="8"]
[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]
[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]
[xbo_slippage symbol="BTC_USDT" side="buy" amount="10000"]
```

## 6. Architecture

```
Browser → WP REST → XBO Public API → Cache → Browser
```

### Backend (WordPress Plugin)

- **XBO API Client** — server-side HTTP only
- **Caching layer** — transients / object cache
- **Normalizers / formatters**
- **WP REST routes** — `/xbo/v1/*`
- **Shortcode + Gutenberg + Elementor adapters**

### WP REST Routes

| Route | Source |
|-------|--------|
| `/xbo/v1/ticker` | `/trading-pairs/stats` |
| `/xbo/v1/movers` | `/trading-pairs/stats` |
| `/xbo/v1/orderbook` | `/orderbook/{symbol}` |
| `/xbo/v1/trades` | `/trades` |
| `/xbo/v1/slippage` | `/orderbook/{symbol}` (calculated) |

### Frontend

- JS widgets inside WP pages
- Data fetched from WP REST endpoints (not directly from XBO API)

## 7. Refresh Intervals

| Endpoint | Default TTL |
|----------|-------------|
| `/currencies` | 6-24 hours |
| `/trading-pairs`, `/trading-pairs/stats` | 15-60 seconds |
| `/trades` | 5-15 seconds |
| `/orderbook` | 1-5s (demo) / 10-30s (normal) |

## 8. Out of Scope

- Real trading, orders, balances, withdrawals
- Full billing / multi-tenant SaaS
- Complex role system

## 9. Seven-Day Plan

| Day | Focus |
|-----|-------|
| 1 | Repo setup, plugin scaffold, API client, basic cache |
| 2 | WP REST endpoints + input validation + error handling |
| 3 | Shortcodes: ticker + movers |
| 4 | Shortcodes: orderbook + trades |
| 5 | Slippage calculator + UX polish |
| 6 | Gutenberg blocks for all widgets |
| 7 | Elementor widgets, demo pages, demo video, README polish |

## 10. Showcase Pages

1. **Landing Showcase:** hero + live ticker + movers + CTA
2. **Trading Insights:** orderbook + trades + slippage
3. **Ops Status:** currencies availability + alert mock panel

## 11. Plugin Naming Conventions

| Element | Value |
|---------|-------|
| Plugin Name | XBO Market Kit |
| Slug | `xbo-market-kit` |
| Text Domain | `xbo-market-kit` |
| Namespace (PSR-4) | `XboMarketKit\` |
| Function prefix | `xbo_market_kit_` |
| Constants prefix | `XBO_MARKET_KIT_` |
| REST namespace | `xbo/v1` |
| Option prefix | `xbo_market_kit_` |
| Hook prefix | `xbo_market_kit/` |
| Asset handles | `xbo-market-kit-` |
| CSS class prefix | `.xbo-mk-` |

## 12. Production-Readiness Checklist (MVP Level)

- [ ] Error budget and fallback UI states defined
- [ ] API outages do not break frontend rendering
- [ ] Cache prevents request storms
- [ ] All user-configurable values validated
- [ ] No secrets hardcoded
- [ ] Demo environment reproducible from README
