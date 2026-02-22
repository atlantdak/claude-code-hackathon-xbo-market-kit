---
name: readme-update
description: This skill should be used when the user asks to "update README", "refresh README", "regenerate README", "update project landing page", or after completing a significant feature via the orchestrate skill.
version: 0.2.0
---

# README Update — Landing Page Generator

## Overview

Generate a visually impactful README.md serving as the GitHub landing page with live metrics, feature status, and architecture diagrams.

**Announce at start:** "Using the readme-update skill to regenerate the project README."

## Process

### Step 1: Collect Data

**Metrics:** Read `docs/metrics/tasks.json` to get:
- `totals.total_tasks` → task count
- `totals.total_duration_minutes` → convert to "Xh Ym" format
- `totals.total_tokens` → format with commas
- `totals.total_commits` → commit count
- `totals.total_cost_usd` → format as "$X.XX"

**Live cost data:** Run `bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --json` to get fresh `cost_total` from ccusage. Use this value for the Cost KPI card and pie chart.

**Git stats:**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git log --oneline | wc -l | tr -d ' '
```

**Day number:** Calculate from project start (2026-02-22). Day 1 = Feb 22, Day 2 = Feb 23, etc.

**Feature status:** Check which PHP classes exist to determine implementation status:

| Feature | Check for file |
|---------|---------------|
| Live Ticker | `includes/Shortcodes/Ticker.php` |
| Top Movers | `includes/Shortcodes/Movers.php` |
| Mini Orderbook | `includes/Shortcodes/Orderbook.php` |
| Recent Trades | `includes/Shortcodes/Trades.php` |
| Slippage Calculator | `includes/Shortcodes/Slippage.php` |
| API Client | `includes/Api/Client.php` |
| Cache Layer | `includes/Cache/TransientCache.php` |
| REST Endpoints | `includes/Rest/` (any controller files) |

Use ✅ if file exists and has >50 lines, 🔄 if file exists but is skeleton (<50 lines), ⬜ if file does not exist.

**Test results (if available):**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
composer run test 2>&1 | tail -5
```

### Step 2: Generate README

Read the current `README.md` to understand existing structure. Then generate the full README with all 11 sections using the collected data.

**KPI cards format (HTML table):**
The dashboard uses an HTML `<table>` with `<h2>` headings for each KPI. Update these values:
- **Total Cost** — `$X.XX` from `cost_total`
- **Dev Time** — `Xh Ym` from `total_duration_minutes`
- **Tasks Done** — `N / N` from `total_tasks`
- **Commits** — from `git log --oneline | wc -l`
- **Tokens** — formatted from `total_all_tokens` (e.g. "34.4M")
- **API Calls** — from `assistant_messages`

**Cost Breakdown pie chart:** Use per-model data from ccusage `--breakdown` output.

**Task Details table:** Include Cost column (`cost_usd` from each task in tasks.json).

Replace `[VALUE]` with actual numbers. URL-encode spaces as `%20`, hyphens as `--`.

**Feature table:** Use status from Step 1 for Shortcode/Block/Elementor columns.

**Timeline progress bars:** Calculate per-day progress:
- 0% = `░░░░░░░░░░░░░░░░░░░░`
- 50% = `██████████░░░░░░░░░░`
- 100% = `████████████████████`

### Step 3: Write README.md

Write the generated content to `README.md` at project root.

### Step 4: Verify

Read back the file. Check no broken markdown, valid badge URLs, proper Mermaid fencing.
