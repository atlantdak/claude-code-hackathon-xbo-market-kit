# XBO Market Kit — Claude Code Instructions

## Project Overview

WordPress plugin developed for the **Claude Code Hackathon 2026**.
**Version:** 1.0.0 | **Live Demo:** [kishkin.dev](https://kishkin.dev) | **GitHub:** [atlantdak/claude-code-hackathon-xbo-market-kit](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)

**Author:** Dmytro Kishkin (<atlantdak@gmail.com>)

Use this authorship info for all plugin headers, composer.json, license files, and any other metadata requiring an author.

## Project Structure

- **Git root / PHPStorm root:** this directory (`app/public/`)
- **Plugin code:** `wp-content/plugins/xbo-market-kit/`
- **Documentation:** `docs/`
- **Product spec:** `docs/plans/2026-02-22-xbo-market-kit-spec.md`
- **Setup scripts:** `scripts/` (setup.sh, deploy.sh, deploy.conf)
- **Deployment:** `scripts/deploy.sh` — rsync + WP-CLI to production

## Tech Stack

- WordPress 6.9.1
- PHP 8.1+ (currently 8.2)
- Composer for dependency management (inside plugin dir)
- Node.js + npm for block builds (`@wordpress/scripts`) and CSS processing (PostCSS)

## Plugin Naming Conventions

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

## Development Commands

All commands run from `wp-content/plugins/xbo-market-kit/`:

```bash
# Install dependencies
composer install
npm install

# Build Gutenberg blocks (JSX → build/)
npm run build

# Watch mode for block development
npm run start

# Build minified CSS
npm run css:build

# Watch CSS changes
npm run css:dev

# Code style check (WordPress Coding Standards)
composer run phpcs

# Code style auto-fix
composer run phpcbf

# Static analysis
composer run phpstan

# Unit tests
composer run test
```

### Deployment

```bash
# Deploy to production (kishkin.dev)
scripts/deploy.sh
```

Requires `scripts/deploy.conf` (not in git). See `scripts/deploy.conf.example`.

## XBO Public API Reference

Base URL: `https://api.xbo.com`

| Endpoint | Method | Notes |
|----------|--------|-------|
| `/currencies` | GET | 205 records, no filtering |
| `/trading-pairs` | GET | 280 records, no filtering |
| `/trading-pairs/stats` | GET | 280 records, no filtering |
| `/trades?symbol={symbol}` | GET | Use `BTC/USDT` format |
| `/orderbook/{symbol}?depth={depth}` | GET | Use `BTC_USDT` format, max depth=250 |

**Critical:** No CORS headers — all API calls MUST go through WordPress backend (server-side only).

## Code Quality Rules

- Follow WordPress Coding Standards (enforced by PHPCS)
- PHPStan level 6 minimum
- All new features must have unit tests
- Use WordPress hooks and filters — never modify core
- Server-side API fetching only — no direct browser calls to api.xbo.com
- Validate and sanitize all user input, escape all output

## File Organization (Plugin)

```
xbo-market-kit/
├── xbo-market-kit.php    # Main plugin bootstrap
├── includes/             # PHP classes (PSR-4 autoloaded under XboMarketKit\)
│   ├── Admin/            # Settings page, PageManager, DemoPage
│   ├── Api/              # XBO API client and response handling
│   ├── Blocks/           # Gutenberg block registration + block.json/render.php per block
│   │   ├── BlockRegistrar.php
│   │   ├── ticker/       # Each block: block.json + render.php
│   │   ├── movers/
│   │   ├── orderbook/
│   │   ├── trades/
│   │   ├── slippage/
│   │   └── refresh-timer/
│   ├── Cache/            # Caching layer (transients)
│   ├── Cli/              # WP-CLI commands (icon sync)
│   ├── Icons/            # Icon resolver and sync (205 crypto SVG icons)
│   ├── Patterns/         # Block pattern registration
│   ├── Rest/             # WP REST API route controllers
│   ├── Shortcodes/       # Shortcode handlers
│   ├── Sparkline/        # SVG sparkline chart generator
│   └── Plugin.php        # Plugin orchestrator
├── src/blocks/           # Block editor JS source (JSX)
├── build/blocks/         # Compiled block editor JS (wp-scripts output)
├── assets/               # Static assets
│   ├── css/              # Stylesheets (src/ and dist/)
│   ├── js/               # Frontend JS
│   └── images/           # Icons (205 SVG crypto icons)
├── tests/                # PHPUnit tests
├── composer.json
├── package.json          # npm: @wordpress/scripts, PostCSS
├── phpstan.neon
├── phpcs.xml
└── phpunit.xml
```

## Git Workflow

- Commit messages in English, imperative mood
- One logical change per commit
- Prefix: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`

## Documentation

- All documentation, code comments, commit messages: **English**
- Plans: `docs/plans/`
- Work log: `docs/worklog/`
- Architecture decisions: `docs/architecture/`

## MCP Servers

Project-level MCP servers configured in `.mcp.json`:

| Server | Purpose |
|--------|---------|
| `codex-subagent` | Codex CLI as MCP — used by reviewer agent for code review |
| `chrome-devtools` | Chrome DevTools Protocol — used by integration-tester for browser testing |

Global plugins (always available):
- **Context7** — up-to-date documentation for WordPress, Gutenberg, and libraries
- **GitHub** — PR/issue management
- **Playwright** — browser automation alternative

## AI Development Workflow

The project uses the `xbo-ai-flow` Claude Code plugin (`.claude/plugins/xbo-ai-flow/`):

- **Agents:** backend-dev, frontend-dev, verifier, integration-tester, reviewer
- **Skills:** `/orchestrate`, `/readme-update`, `/worklog-update`, `/metrics`
- **Design doc:** `docs/plans/2026-02-22-ai-workflow-design.md`
- **Metrics:** `docs/metrics/tasks.json`

## Important Notes

- WordPress core files are NOT in git — use `scripts/setup.sh` to install
- Never edit WP core files
- Plugin must be self-contained (all deps via Composer + npm)
- Read the product spec before implementing any feature
- BEM + CSS Custom Properties for all widget styling (no Tailwind)
- 205 local SVG crypto icons — zero CDN dependency for icons
- All API calls server-side only (no CORS on api.xbo.com)
