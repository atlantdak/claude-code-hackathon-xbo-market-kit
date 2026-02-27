# Documentation

Welcome to the XBO Market Kit project documentation.

**Live Demo:** [https://kishkin.dev](https://kishkin.dev) | **GitHub:** [atlantdak/claude-code-hackathon-xbo-market-kit](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)

**Version:** 1.0.0 | **Built with:** Claude Code during the Claude Code Hackathon 2026

## Navigation

| Section | Purpose |
|---------|---------|
| [Visual Showcase](SHOWCASE.md) | Screenshots of all pages, blocks, and admin UI |
| [Plans](plans/) | Design documents and implementation plans |
| [Work Log](worklog/) | Development journal — what was done and why |
| [Architecture](architecture/) | Architecture Decision Records (ADRs) |

## Key Documents

- [Product Specification](plans/2026-02-22-xbo-market-kit-spec.md) — Full product spec with API details
- [Visual Showcase](SHOWCASE.md) — Screenshots of production site (24 images)
- [AI Workflow Design](plans/2026-02-22-ai-workflow-design.md) — Claude Code plugin architecture
- [Deployment Pipeline](plans/2026-02-27-deployment-design.md) — Production deployment design

## Project Timeline

| Date | Milestone |
|------|-----------|
| 2026-02-22 | Project setup, environment, AI workflow plugin |
| 2026-02-23 | Full plugin implementation — MVP complete (5 blocks, REST API, shortcodes) |
| 2026-02-25 | Widget styling redesign (BEM + CSS Custom Properties), local crypto icons (205/205) |
| 2026-02-26 | Sparkline Ticker, Slippage UX redesign, block design consistency |
| 2026-02-27 | Block settings, mega menu, homepage redesign, deployment to production |

## Site Structure (Production)

| Page | URL | Description |
|------|-----|-------------|
| Home | `/` | Showcase landing with live widgets |
| Widgets Overview | `/widgets-overview/` | Compare all 5 widgets |
| Ticker Demo | `/xbo-demos/xbo-demo-ticker/` | Live ticker widget |
| Top Movers Demo | `/xbo-demos/xbo-demo-movers/` | Gainers/losers table |
| Order Book Demo | `/xbo-demos/xbo-demo-orderbook/` | Bid/ask depth |
| Recent Trades Demo | `/xbo-demos/xbo-demo-trades/` | Trade feed |
| Slippage Demo | `/xbo-demos/xbo-demo-slippage/` | Slippage calculator |
| Showcase | `/xbo-showcase/` | Integration examples |
| Block Demos | `/xbo-demos/` | All blocks index |
| Real-world Layouts | `/real-world-layouts/` | Layout templates |
| Getting Started | `/getting-started/` | Quick start guide |
| API Documentation | `/xbo-api-docs/` | REST API reference |
| Integration Guide | `/integration-guide/` | Theme/plugin integration |
| FAQ | `/faq/` | 15 questions, 4 categories |
| Changelog | `/changelog/` | Version history |
| Privacy Policy | `/privacy-policy/` | Data handling |

## Conventions

- All documentation is written in **English**
- Plans follow the naming pattern: `YYYY-MM-DD-<topic>.md`
- ADRs follow: `ADR-NNN-<title>.md`
- Work log entries: `YYYY-MM-DD.md`
