# XBO Market Kit — Claude Code Instructions

## Project Overview

WordPress plugin developed for the **Claude Code Hackathon 2026**.
Repository: https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit

**Author:** Dmytro Kishkin (<atlantdak@gmail.com>)

Use this authorship info for all plugin headers, composer.json, license files, and any other metadata requiring an author.

## Project Structure

- **Git root / PHPStorm root:** this directory (`app/public/`)
- **Plugin code:** `wp-content/plugins/xbo-market-kit/`
- **Documentation:** `docs/`
- **Product spec:** `docs/plans/2026-02-22-xbo-market-kit-spec.md`
- **Setup scripts:** `scripts/`

## Tech Stack

- WordPress 6.9.1
- PHP 8.1+ (currently 8.2)
- Composer for dependency management (inside plugin dir)
- No npm/node required for MVP

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

# Code style check (WordPress Coding Standards)
composer run phpcs

# Code style auto-fix
composer run phpcbf

# Static analysis
composer run phpstan

# Unit tests
composer run test
```

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
│   ├── Api/              # XBO API client and response handling
│   ├── Cache/            # Caching layer (transients)
│   ├── Rest/             # WP REST API route controllers
│   ├── Shortcodes/       # Shortcode handlers
│   ├── Blocks/           # Gutenberg block registration
│   ├── Elementor/        # Elementor widget registration
│   └── Admin/            # Settings page
├── assets/               # Static assets (CSS, JS, images)
├── tests/                # PHPUnit tests
├── composer.json
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
- **Context7** — up-to-date documentation for WordPress, Gutenberg, Tailwind, Elementor
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
- Plugin must be self-contained (all deps via Composer)
- Read the product spec before implementing any feature
