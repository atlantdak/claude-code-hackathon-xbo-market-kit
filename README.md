<div align="center">

<!-- HERO HEADER -->
<img src="https://img.shields.io/badge/%F0%9F%93%88-XBO%20Market%20Kit-000000?style=for-the-badge&labelColor=1a1a2e" alt="XBO Market Kit" />

# XBO Market Kit

### Real-Time Crypto Market Data for WordPress

[![WordPress](https://img.shields.io/badge/WordPress-6.9+-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0-blue?style=for-the-badge)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%206-brightgreen?style=for-the-badge)](https://phpstan.org/)

[![Claude Code](https://img.shields.io/badge/Built%20with-Claude%20Code%20(Opus%204.6)-D97706?style=for-the-badge&logo=anthropic&logoColor=white)](https://claude.ai/)
[![Hackathon](https://img.shields.io/badge/%F0%9F%8F%86%20Claude%20Code-Hackathon%202026-10B981?style=for-the-badge)](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)

**Live tickers, orderbooks, trade feeds & market analytics — delivered through Shortcodes, Gutenberg Blocks, and Elementor Widgets.**

*100% AI-developed: Every line of code, every test, every doc — written by Claude Code agents.*

---

[Dashboard](#ai-development-dashboard) &bull;
[Features](#features) &bull;
[AI Agents](#ai-powered-development) &bull;
[Architecture](#architecture) &bull;
[API Docs](https://atlantdak.github.io/claude-code-hackathon-xbo-market-kit/api/) &bull;
[Quick Start](#quick-start) &bull;
[Documentation](#documentation)

</div>

---

## AI Development Dashboard

<!-- Dynamic metrics — updated by /readme-update skill -->

<div align="center">
<table>
<tr>
<td align="center" width="130"><h2>~$109</h2><sub>Total Cost</sub></td>
<td align="center" width="130"><h2>~11h 10m</h2><sub>Active Time</sub></td>
<td align="center" width="130"><h2>10 / 10</h2><sub>Tasks Done</sub></td>
<td align="center" width="130"><h2>89</h2><sub>Commits</sub></td>
<td align="center" width="130"><h2>~272M</h2><sub>Tokens</sub></td>
<td align="center" width="130"><h2>23</h2><sub>Sessions</sub></td>
</tr>
</table>

> *Cost from [ccusage](https://github.com/ryoppippi/ccusage) billing data (Opus 4.6 + Sonnet 4.6 + Haiku 4.5). Active time excludes idle gaps > 5 min. Tracked by [xbo-ai-flow](/.claude/plugins/xbo-ai-flow/) plugin.*

</div>

### Time Allocation

```mermaid
pie title Active Dev Time by Task (~790 min total)
    "Project Setup (62m)" : 62
    "AI Workflow v1 (66m)" : 66
    "Workflow Improvements v2 (90m)" : 90
    "Metrics Fix (18m)" : 18
    "Plugin MVP (178m)" : 178
    "Widget Styling (34m)" : 34
    "Local Icons (53m)" : 53
    "API Documentation (20m)" : 20
    "Sparkline Ticker (60m)" : 60
    "Slippage UX Redesign (60m)" : 60
```

### Commits per Task

```mermaid
xychart-beta
    title "Commits by Task"
    x-axis ["Setup", "AI Wf v1", "Wf v2", "Metrics", "MVP", "Styling", "Icons", "API Docs", "Sparkline", "Slippage UX"]
    y-axis "Commits" 0 --> 15
    bar [1, 6, 12, 2, 13, 9, 12, 5, 8, 8]
```

### Timeline

```mermaid
gantt
    title Development Timeline
    dateFormat YYYY-MM-DD HH:mm
    axisFormat %b %d %H:%M
    section Day 1 — Feb 22
    Project Setup and Scaffold   :done, setup, 2026-02-22 15:33, 62min
    AI Workflow v1               :done, wf1, 2026-02-22 16:35, 66min
    Workflow v2 Full Autonomy    :done, wf2, 2026-02-22 17:41, 90min
    Metrics Collector Fix        :done, fix, 2026-02-22 19:11, 18min
    section Day 2 — Feb 23 (active ~178m)
    Design Doc and Plan          :done, plan, 2026-02-23 08:26, 128min
    Bootstrap API Cache          :done, core, 2026-02-23 16:18, 5min
    REST Controllers x5          :done, rest, 2026-02-23 16:22, 4min
    Shortcodes Interactivity x5  :done, sc, 2026-02-23 16:26, 5min
    Gutenberg Blocks Admin       :done, blk, 2026-02-23 16:31, 23min
    Code Quality Fixes           :done, qa, 2026-02-23 16:54, 52min
    data-wp-each Bugfix          :done, bugfix, 2026-02-23 17:46, 1min
    section Day 4 — Feb 25 (AM)
    Widget Styling Redesign    :done, style, 2026-02-25 10:00, 34min
    section Day 5 — Feb 25 (PM)
    Local Icons Implementation :done, icons, 2026-02-25 14:00, 53min
    Icon Transparency Fix      :done, iconfix, 2026-02-25 15:00, 5min
    section Day 5 — Feb 25 (Evening)
    API Documentation + Swagger UI :done, apidocs, 2026-02-25 21:00, 70min
    section Day 5 — Feb 26
    Sparkline Ticker              :done, sparkline, 2026-02-26 00:00, 60min
    Slippage UX Redesign          :done, slippage, 2026-02-26 00:00, 60min
```

### Cost Breakdown

```mermaid
pie title Cost by Day (~$109+ total)
    "Day 1 Feb 22 ($28)" : 28
    "Day 2 Feb 23 ($40)" : 40
    "Day 4-5 Feb 25 ($41)" : 41
    "Day 5 Feb 26" : 10
```

> **Pricing source:** [ccusage](https://github.com/ryoppippi/ccusage) billing API — per-project, per-model breakdown. Opus 4.6: ~$92 (84%) + Sonnet 4.6: ~$4 (4%) + Haiku 4.5: ~$1 (1%). 23 sessions across 3 days.

### Task Details

| # | Task | Duration | Commits | Cost | Status |
|:-:|:-----|:--------:|:-------:|:----:|:------:|
| 1 | **Project Setup** — repo, scaffold, docs, CLAUDE.md, README | 62m | 1 | $7.28 | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 2 | **AI Workflow v1** — 5 agents, 4 skills, hooks, metrics, MCP | 66m | 6 | $7.74 | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 3 | **Workflow v2** — executable skills, TDD, security, commands, ADR | 90m | 12 | $10.62 | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 4 | **Metrics Fix** — ccusage integration + visual dashboard | 18m | 2 | $2.12 | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| **5** | **Plugin MVP** — full implementation (5 phases, 11 sub-tasks) | **178m** | **13** | **$38.64** | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.1 | &nbsp;&nbsp;↳ Plugin bootstrap, autoloader, activation hook | | | *Phase 1* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.2 | &nbsp;&nbsp;↳ ApiClient with error handling, timeouts, filterable base URL | | | *Phase 1* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.3 | &nbsp;&nbsp;↳ CacheManager with per-endpoint TTL via transients | | | *Phase 1* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.4 | &nbsp;&nbsp;↳ REST controllers: Ticker + Movers (stats-based) | | | *Phase 2* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.5 | &nbsp;&nbsp;↳ REST controllers: Orderbook + Trades + Slippage (symbol-based) | | | *Phase 2* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.6 | &nbsp;&nbsp;↳ Shortcodes: Ticker + Movers with Interactivity API | | | *Phase 3* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.7 | &nbsp;&nbsp;↳ Shortcodes: Orderbook + Trades with Interactivity API | | | *Phase 3* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.8 | &nbsp;&nbsp;↳ Shortcode: Slippage Calculator with debounced auto-calc | | | *Phase 3* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.9 | &nbsp;&nbsp;↳ 5 Gutenberg blocks (server-rendered via render.php) | | | *Phase 4* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.10 | &nbsp;&nbsp;↳ Admin settings page + demo page on activation | | | *Phase 4* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 5.11 | &nbsp;&nbsp;↳ Code quality (PHPCS/PHPStan/PHPUnit) + data-wp-each bugfix | | | *Phase 5* | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 6 | **Widget Styling** — Tailwind → BEM + CSS Custom Properties, PostCSS pipeline | 34m | 9 | ~$10 | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 7 | **Local Icons** — IconResolver, IconSync, WP-CLI, 205 SVGs, zero CDN dependency | 53m | 12 | ~$14 | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 8 | **API Documentation** — OAS3 generation, Swagger UI, GitHub Pages | — | — | — | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 8.1 | &nbsp;&nbsp;↳ Update production URL in OAS3 spec when deploying | | | | ![pending](https://img.shields.io/badge/-pending-F59E0B?style=flat-square) |
| 9 | **Sparkline Ticker** — constrained random walk SVG generation, ring buffer live updates | ~60m | 8 | — | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |
| 10 | **Slippage UX Redesign** — dropdown selectors, PairCatalog, context-based state | ~60m | 8 | — | ![done](https://img.shields.io/badge/-done-22C55E?style=flat-square) |

> **Duration = active session time** (idle gaps > 5 min excluded). Wall clock span was significantly longer — see details below.

<details>
<summary><strong>Wall Clock vs Active Time</strong></summary>

| Day | Tasks | Wall Clock | Active Time | Idle/Pauses | Sessions |
|:----|:------|:----------:|:-----------:|:-----------:|:--------:|
| Feb 22 | Tasks 1–4 | 24h 50m | 3h 56m | 20h 54m | 4 |
| Feb 23 | Task 5 (Plugin MVP) | 8h 43m | 3h 5m | 5h 38m | 9 |
| Feb 25 | Tasks 6–8 | 10h 38m | 4h 9m | 6h 29m | 10 |
| Feb 26 | Tasks 9–10 | ~2h | ~2h | — | — |
| **Total** | **All 10 tasks** | **~46h** | **~13h 10m** | **~33h** | **23+** |

Active time = continuous Claude Code processing with no gap > 5 min between API calls. Source: `docs/metrics/sessions.json`.

</details>

---

## Features

<table>
<tr>
<th>Widget</th>
<th>Description</th>
<th>Shortcode</th>
<th>Block</th>
<th>Elementor</th>
</tr>
<tr>
<td><strong>Live Ticker</strong></td>
<td>Real-time prices for selected trading pairs with 24h change + sparklines</td>
<td align="center">✅</td>
<td align="center">🔄</td>
<td align="center">⬜</td>
</tr>
<tr>
<td><strong>Top Movers</strong></td>
<td>Biggest gainers and losers by 24h % change with tab switching</td>
<td align="center">✅</td>
<td align="center">🔄</td>
<td align="center">⬜</td>
</tr>
<tr>
<td><strong>Mini Orderbook</strong></td>
<td>Live bid/ask depth table with depth bars and spread indicator</td>
<td align="center">✅</td>
<td align="center">🔄</td>
<td align="center">⬜</td>
</tr>
<tr>
<td><strong>Recent Trades</strong></td>
<td>Trade feed with color-coded side, price, volume, timestamp</td>
<td align="center">✅</td>
<td align="center">🔄</td>
<td align="center">⬜</td>
</tr>
<tr>
<td><strong>Slippage Calculator</strong></td>
<td>Avg execution price, slippage %, spread, total cost from orderbook depth</td>
<td align="center">✅</td>
<td align="center">🔄</td>
<td align="center">⬜</td>
</tr>
</table>

> **Legend:** ✅ Done &nbsp; 🔄 Server render only (no editor UI) &nbsp; ⬜ Planned
>
> All crypto icons served locally (205 SVGs). Daily sync via WP-Cron.

### Shortcode Examples

```
[xbo_ticker symbols="BTC/USDT,ETH/USDT" refresh="15"]
[xbo_movers mode="gainers" limit="8"]
[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]
[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]
[xbo_slippage symbol="BTC_USDT" side="buy" amount="10000"]
```

---

## AI-Powered Development

This project demonstrates a **fully autonomous AI development workflow**. Five specialized Claude Code agents collaborate through an orchestration pipeline:

```mermaid
graph LR
    A["User Request"] --> B["Orchestrator"]
    B --> C["Backend Dev"]
    B --> D["Frontend Dev"]
    C --> E["Verifier"]
    D --> E
    E -->|pass| F["Integration Tester"]
    E -->|fail| C
    E -->|fail| D
    F -->|pass| G["Reviewer"]
    F -->|fail| C
    G -->|approve| H["Commit & Ship"]
    G -->|critical| C

    style A fill:#1a1a2e,color:#fff
    style B fill:#D97706,color:#fff
    style C fill:#3B82F6,color:#fff
    style D fill:#06B6D4,color:#fff
    style E fill:#EAB308,color:#000
    style F fill:#10B981,color:#fff
    style G fill:#A855F7,color:#fff
    style H fill:#22C55E,color:#fff
```

### Agent Roster

| Agent | Role | Model | Specialty |
|:------|:-----|:------|:----------|
| **Backend Dev** | PHP/WordPress | Opus 4.6 | API client, REST endpoints, caching, shortcodes |
| **Frontend Dev** | CSS/JS | Opus 4.6 | UI components, BEM CSS design system, Gutenberg blocks, Elementor widgets |
| **Verifier** | Quality Gates | Haiku 4.5 | PHPCS, PHPStan (L6), PHPUnit |
| **Integration Tester** | Live Testing | Haiku 4.5 | WP-CLI page testing, browser verification |
| **Reviewer** | Code Review | Haiku 4.5 | Codex CLI review, security audit |

### Process Pipeline

```mermaid
graph TD
    A["Brainstorm"] --> B["Design Doc"]
    B --> C["Implementation Plan"]
    C --> D["Subagent Execution"]
    D --> E["Verification Loop"]
    E --> F["Code Review"]
    F --> G["Documentation Update"]
    G --> H["README + Worklog"]

    style A fill:#F59E0B,color:#000
    style B fill:#F59E0B,color:#000
    style C fill:#3B82F6,color:#fff
    style D fill:#3B82F6,color:#fff
    style E fill:#EF4444,color:#fff
    style F fill:#A855F7,color:#fff
    style G fill:#10B981,color:#fff
    style H fill:#10B981,color:#fff
```

### Skills & Automation

| Skill | Purpose |
|:------|:--------|
| `/orchestrate` | Full pipeline: brainstorm → plan → code → verify → review → ship |
| `/readme-update` | Regenerate this README with live metrics and status |
| `/worklog-update` | Add entries to the development journal |
| `/metrics` | Collect and display time/token/task analytics |

### Command Shortcuts

| Command | Purpose |
|:--------|:--------|
| `/feature "desc"` | Start full pipeline for a new feature |
| `/verify` | Run all quality checks (PHPCS + PHPStan + PHPUnit + Security) |
| `/test` | Quick PHPUnit test run |
| `/review` | Code review with Codex CLI |
| `/docs` | Update worklog + README + metrics in one command |
| `/status` | Show project status, features, and metrics |

---

## Architecture

```mermaid
graph LR
    Browser["Browser"] -->|"fetch /wp-json/xbo/v1/*"| REST["WP REST API"]
    REST -->|"check"| Cache{"Transient Cache"}
    Cache -->|"HIT"| REST
    Cache -->|"MISS"| API["XBO Public API"]
    API -->|"response"| Cache
    REST -->|"JSON"| Browser

    style Browser fill:#1a1a2e,color:#fff
    style REST fill:#21759B,color:#fff
    style Cache fill:#F59E0B,color:#000
    style API fill:#10B981,color:#fff
```

**Key decisions:**
- **Server-side only** — All XBO API calls go through WordPress backend (no CORS)
- **Transient caching** — Per-endpoint TTL prevents rate limiting
- **One data core** — Shortcodes, Blocks, and Elementor widgets share the same services
- **Graceful degradation** — API failures show cached data or friendly error states
- **BEM + CSS Custom Properties** — XBO-inspired design system with PostCSS build pipeline

### WP REST Endpoints

| Route | Source | Cache TTL |
|:------|:-------|:----------|
| `GET /xbo/v1/ticker` | `/trading-pairs/stats` | 15-60s |
| `GET /xbo/v1/movers` | `/trading-pairs/stats` | 15-60s |
| `GET /xbo/v1/orderbook?symbol=` | `/orderbook/{symbol}` | 1-30s |
| `GET /xbo/v1/trades?symbol=` | `/trades` | 5-15s |
| `GET /xbo/v1/slippage?symbol=&side=&amount=` | `/orderbook/{symbol}` (calculated) | 1-30s |

### Project Structure

```
app/public/                             # Git root
├── .claude/plugins/xbo-ai-flow/        # AI workflow plugin
│   ├── agents/                         # 5 specialized agents
│   ├── skills/                         # 4 automation skills
│   ├── hooks/                          # Session hooks
│   └── scripts/                        # Metrics collection
├── docs/                               # Full documentation
│   ├── plans/                          # Design docs & plans
│   ├── worklog/                        # Development journal
│   ├── metrics/                        # Task analytics
│   └── architecture/                   # ADRs
├── wp-content/plugins/xbo-market-kit/  # The WordPress plugin
│   ├── includes/                       # PHP source (PSR-4)
│   │   ├── Api/                        # XBO API client
│   │   ├── Cache/                      # Caching layer
│   │   ├── Rest/                       # REST controllers
│   │   ├── Shortcodes/                 # Shortcode handlers
│   │   ├── Blocks/                     # Gutenberg blocks
│   │   ├── Elementor/                  # Elementor widgets
│   │   ├── Icons/                      # Icon resolver + sync engine
│   │   ├── Cli/                        # WP-CLI commands
│   │   └── Admin/                      # Settings page
│   ├── assets/                         # CSS (BEM + PostCSS), JS (Interactivity API)
│   └── tests/                          # PHPUnit tests
└── CLAUDE.md                           # AI agent instructions
```

---

## Development Timeline

> **Hackathon:** 7 days, Feb 22–28, 2026

| Day | Focus | Progress |
|:----|:------|:---------|
| **Day 1** | Repo setup, plugin scaffold, AI workflow (full autonomy) | `████████████████████` 100% |
| **Day 2** | Full plugin MVP — all 5 widgets, blocks, admin, demo page | `████████████████████` 100% |
| ~~Day 3~~ | ~~Shortcodes: ticker + movers~~ — *completed in Day 2* | `████████████████████` 100% |
| ~~Day 4~~ | ~~Shortcodes: orderbook + trades~~ — *completed in Day 2* | `████████████████████` 100% |
| ~~Day 5~~ | ~~Slippage calculator + UX polish~~ — *completed in Day 2* | `████████████████████` 100% |
| ~~Day 6~~ | ~~Gutenberg blocks for all widgets~~ — *completed in Day 2* | `████████████████████` 100% |
| **Day 4-5** | Widget styling + Local crypto icons (205 SVGs, zero CDN) + API Docs | `████████████████████` 100% |
| **Day 5** | Sparkline Ticker (algorithmic SVG) + Slippage UX Redesign (parallel branches) | `████████████████████` 100% |
| **Day 7** | Elementor widgets, demo video, README polish | `░░░░░░░░░░░░░░░░░░░░` 0% |

> Days 3–6 were originally planned as separate days but all work was completed in Day 2 by Claude Code agents.

---

## Quick Start

### Prerequisites

- [Local by Flywheel](https://localwp.com/) or any WordPress environment
- PHP 8.1+
- Composer
- WP-CLI (optional)

### Setup

```bash
# Clone the repository
git clone git@github.com:atlantdak/claude-code-hackathon-xbo-market-kit.git
cd claude-code-hackathon-xbo-market-kit/app/public

# Run setup script (downloads WP core + installs dependencies)
bash scripts/setup.sh

# Or manually:
# 1. Set up WordPress (wp core download, create wp-config, etc.)
# 2. cd wp-content/plugins/xbo-market-kit && composer install
# 3. Activate the plugin in WP Admin
```

### Development Commands

```bash
cd wp-content/plugins/xbo-market-kit

composer install          # Install dependencies
composer run phpcs        # Code style checks (WordPress standards)
composer run phpcbf       # Auto-fix code style
composer run phpstan      # Static analysis (level 6)
composer run test         # PHPUnit tests

npm install                # Install CSS build dependencies
npm run css:build          # Build production CSS
npm run css:dev            # Watch mode for CSS development

wp xbo icons sync           # Download all crypto icons
wp xbo icons sync --force   # Force re-download all
wp xbo icons status         # Show icon sync status
```

---

## Documentation

| Document | Description |
|:---------|:------------|
| [Product Spec](docs/plans/2026-02-22-xbo-market-kit-spec.md) | Full product specification & API reference |
| [Plugin Design](docs/plans/2026-02-23-xbo-market-kit-full-design.md) | Complete plugin architecture & widget design |
| [Implementation Plan](docs/plans/2026-02-23-xbo-market-kit-implementation-plan.md) | 11-task plan with parallelism map |
| [AI Workflow Design](docs/plans/2026-02-22-ai-workflow-design.md) | Agent architecture & orchestration design |
| [Project Setup Design](docs/plans/2026-02-22-project-setup-design.md) | Environment & repository decisions |
| [Work Log](docs/worklog/) | Daily development journal |
| [Metrics](docs/metrics/) | Task & session analytics (tokens, cost, active time) |
| [Architecture](docs/architecture/) | Key technical decisions (ADRs) |
| [Widget Styling Design](docs/plans/2026-02-25-widget-styling-design.md) | CSS design system architecture & tokens |
| [Widget Styling Plan](docs/plans/2026-02-25-widget-styling-plan.md) | 10-task implementation plan |
| [Local Icons Design](docs/plans/2026-02-25-local-icons-design.md) | Icon sync architecture & cascade strategy |
| [Local Icons Plan](docs/plans/2026-02-25-local-icons-plan.md) | 9-task TDD implementation plan |
| [REST API Docs](https://atlantdak.github.io/claude-code-hackathon-xbo-market-kit/api/) | Interactive Swagger UI for all plugin endpoints |
| [Sparkline Ticker Design](docs/plans/2026-02-25-sparkline-ticker-design.md) | Algorithmic sparkline generation architecture |
| [Sparkline Ticker Plan](docs/plans/2026-02-25-sparkline-ticker-implementation.md) | Implementation plan for sparkline ticker |
| [Slippage UX Design](docs/plans/2026-02-25-slippage-ux-design.md) | Slippage calculator UX redesign architecture |
| [Slippage UX Plan](docs/plans/2026-02-25-slippage-ux-implementation.md) | Implementation plan for slippage UX redesign |
| [CLAUDE.md](CLAUDE.md) | Instructions for Claude Code agents |

---

## Demo Pages

| Page | Content | Status |
|:-----|:--------|:------:|
| **XBO Market Kit Demo** | All 5 widgets: ticker, movers, orderbook, trades, slippage | ✅ Auto-created on activation |
| **Landing Showcase** | Hero + live ticker + top movers + CTA | ⬜ Planned |
| **Trading Insights** | Orderbook + recent trades + slippage calculator | ⬜ Planned |

---

## License

GPL-2.0-or-later

---

<div align="center">

**Built with [Claude Code](https://claude.ai/) (Opus 4.6) for the Claude Code Hackathon 2026**

*Every line of code, every test, every document — created by AI agents*

[![GitHub](https://img.shields.io/badge/GitHub-Repository-181717?style=flat-square&logo=github)](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)
[![Documentation](https://img.shields.io/badge/Docs-Browse-blue?style=flat-square&logo=bookstack)](docs/)
[![Work Log](https://img.shields.io/badge/Work%20Log-Daily%20Journal-green?style=flat-square&logo=notion)](docs/worklog/)

**Author:** [Dmytro Kishkin](mailto:atlantdak@gmail.com)

</div>
