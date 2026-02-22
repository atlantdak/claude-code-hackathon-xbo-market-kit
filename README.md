<div align="center">

# XBO Market Kit

**WordPress Plugin for Live Crypto Market Data**

[![WordPress](https://img.shields.io/badge/WordPress-6.9+-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Claude Code](https://img.shields.io/badge/Built%20with-Claude%20Code-D97706?style=for-the-badge&logo=anthropic&logoColor=white)](https://claude.ai/)
[![Hackathon](https://img.shields.io/badge/Claude%20Code-Hackathon%202026-10B981?style=for-the-badge)](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)

*Built entirely with AI-assisted development using Claude Code*

---

[Getting Started](#-getting-started) &bull;
[Features](#-features) &bull;
[Architecture](#-architecture) &bull;
[Documentation](#-documentation) &bull;
[AI Workflow](#-ai-workflow)

</div>

---

## About

**XBO Market Kit** is a WordPress plugin that brings live cryptocurrency market data to any WordPress site. It connects to the [XBO Public API](https://public-docs.xbo.com/) and delivers real-time tickers, orderbooks, trade feeds, and market analytics through shortcodes, Gutenberg blocks, and Elementor widgets.

Developed for the **Claude Code Hackathon 2026** — the entire development process is driven by AI agents using Claude Code, from brainstorming through implementation.

> **Hackathon Focus:** Demonstrating a complete AI-assisted development workflow with full transparency. Every decision, plan, and implementation step is documented.

## Features

| Widget | Description | Delivery |
|--------|-------------|----------|
| **Live Ticker** | Real-time prices for selected trading pairs | Shortcode, Block, Elementor |
| **Top Movers** | Biggest gainers and losers (24h) | Shortcode, Block, Elementor |
| **Mini Orderbook** | Live bid/ask depth with spread | Shortcode, Block, Elementor |
| **Recent Trades** | Trade feed with price, volume, direction | Shortcode, Block, Elementor |
| **Slippage Calculator** | Estimate execution price from orderbook depth | Shortcode, Block, Elementor |

### Shortcode Examples

```
[xbo_ticker symbols="BTC/USDT,ETH/USDT" refresh="15"]
[xbo_movers mode="gainers" limit="8"]
[xbo_orderbook symbol="BTC_USDT" depth="20" refresh="5"]
[xbo_trades symbol="BTC/USDT" limit="20" refresh="10"]
[xbo_slippage symbol="BTC_USDT" side="buy" amount="10000"]
```

## Getting Started

### Prerequisites

- [Local by Flywheel](https://localwp.com/) or any WordPress local environment
- PHP 8.1+
- Composer
- WP-CLI (optional)

### Quick Setup

```bash
# Clone the repository
git clone git@github.com:atlantdak/claude-code-hackathon-xbo-market-kit.git
cd claude-code-hackathon-xbo-market-kit/app/public

# Run the setup script (downloads WP core + installs dependencies)
bash scripts/setup.sh

# Or manually:
# 1. Set up WordPress (wp core download, create wp-config, etc.)
# 2. cd wp-content/plugins/xbo-market-kit && composer install
# 3. Activate plugin in WP Admin
```

## Architecture

```
Browser → WP REST API → WordPress Backend → XBO Public API
                ↑              ↓
            JSON Response ← Cache Layer (Transients)
```

### Project Structure

```
app/public/                        # Project root
├── docs/                          # Full project documentation
│   ├── plans/                     # Design docs & implementation plans
│   ├── worklog/                   # Development journal
│   └── architecture/              # Architecture Decision Records
├── scripts/                       # Automation scripts
├── wp-content/plugins/
│   └── xbo-market-kit/            # The plugin
│       ├── includes/
│       │   ├── Api/               # XBO API client
│       │   ├── Cache/             # Caching layer
│       │   ├── Rest/              # WP REST controllers
│       │   ├── Shortcodes/        # Shortcode handlers
│       │   ├── Blocks/            # Gutenberg blocks
│       │   ├── Elementor/         # Elementor widgets
│       │   └── Admin/             # Settings page
│       ├── assets/                # CSS, JS
│       └── tests/                 # PHPUnit tests
└── CLAUDE.md                      # AI agent instructions
```

### Key Design Decisions

- **Server-side only:** All XBO API calls go through WordPress backend (no CORS issues)
- **Transient caching:** Per-endpoint TTL prevents API rate limiting
- **One data core:** Shortcodes, Blocks, and Elementor widgets share the same backend services
- **Graceful degradation:** API failures show cached data or friendly error states

## Documentation

| Document | Description |
|----------|-------------|
| [Product Spec](docs/plans/2026-02-22-xbo-market-kit-spec.md) | Full product specification |
| [Project Plans](docs/plans/) | Design documents and implementation plans |
| [Work Log](docs/worklog/) | Daily development journal |
| [Architecture](docs/architecture/) | Key technical decisions (ADRs) |
| [CLAUDE.md](CLAUDE.md) | Instructions for Claude Code agents |

## Development

```bash
cd wp-content/plugins/xbo-market-kit

composer install          # Install dependencies
composer run phpcs        # Code style checks (WordPress standards)
composer run phpcbf       # Auto-fix code style
composer run phpstan      # Static analysis (level 6)
composer run test         # PHPUnit tests
```

## AI Workflow

This project showcases a complete AI-driven development workflow:

```
Brainstorm → Design Doc → Implementation Plan → TDD Execution → Code Review → Ship
     ↑                                                                    ↓
     └────────────────────── Iterate ──────────────────────────────────────┘
```

1. **Brainstorming** — Collaborative design sessions with Claude Code
2. **Planning** — Structured implementation plans with bite-sized TDD tasks
3. **Implementation** — Code written by Claude Code agents (subagent-driven)
4. **Review** — Automated code review via subagents
5. **Documentation** — Auto-generated and maintained docs

Every step is logged in [docs/worklog/](docs/worklog/) for full transparency.

## Demo Pages

| Page | Content |
|------|---------|
| **Landing Showcase** | Hero + live ticker + top movers + CTA |
| **Trading Insights** | Orderbook + recent trades + slippage calculator |
| **Ops Status** | Currency availability + deposit/withdraw status |

## License

GPL-2.0-or-later

---

<div align="center">

**Built with [Claude Code](https://claude.ai/) for the Claude Code Hackathon 2026**

[Documentation](docs/) &bull; [Plans](docs/plans/) &bull; [Work Log](docs/worklog/) &bull; [Spec](docs/plans/2026-02-22-xbo-market-kit-spec.md)

</div>
