---
name: readme-update
description: This skill should be used when the user asks to "update README", "refresh README", "regenerate README", "update project landing page", or after completing a significant feature via the orchestrate skill.
version: 0.3.0
---

# README Update — Landing Page Generator

## Overview

Generate a visually impactful README.md serving as the GitHub landing page with live metrics, feature status, and architecture diagrams.

**Announce at start:** "Using the readme-update skill to regenerate the project README."

## Process

### Step 1: Collect Data

**Sessions data:** Read `docs/metrics/sessions.json` for:
- `totals.total_cost_usd` → Cost KPI
- `totals.total_all_tokens` → Tokens KPI (format as "160M")
- `totals.total_sessions` → Sessions KPI
- `totals.total_messages` → API Calls KPI
- `totals.total_active_min` → Dev Time KPI (format as "Xh Ym")
- `by_day` → per-day cost breakdown for pie chart

If sessions.json is missing or stale, regenerate it:
```bash
bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --full
```

**Tasks data:** Read `docs/metrics/tasks.json` for:
- `totals.total_tasks` → Tasks Done KPI
- `totals.total_commits` → Commits KPI
- `tasks[]` → Task Details table (duration, cost, sessions, coverage)

**Git stats:**
```bash
git log --oneline | wc -l | tr -d ' '
```

**Day number:** Calculate from project start (2026-02-22). Day 1 = Feb 22, Day 2 = Feb 23, etc.

**Feature status:** Check which PHP classes exist under `wp-content/plugins/xbo-market-kit/includes/`:
- Use ✅ if file exists and has >50 lines
- Use 🔄 if file exists but is skeleton (<50 lines)
- Use ⬜ if file does not exist

### Step 2: Update KPI Cards

The dashboard uses an HTML `<table>` with `<h2>` headings. Update these values:
- **Total Cost** — `$X.XX` from `sessions.json totals.total_cost_usd`
- **Dev Time** — from `sessions.json totals.total_active_min` (convert to "Xh Ym")
- **Tasks Done** — `N / N` from `tasks.json totals.total_tasks`
- **Commits** — from `tasks.json totals.total_commits`
- **Tokens** — from `sessions.json totals.total_all_tokens` (format as "160M")
- **Sessions** — from `sessions.json totals.total_sessions`

### Step 3: Update Charts and Tables

- **Cost Breakdown:** Use `sessions.json by_day` for per-day cost
- **Task Details:** Include Cost column from each task's `cost_usd`
- **Feature table:** Check actual file existence for status icons

### Step 4: Write and Verify

Write `README.md`, then read back to check for broken markdown, valid Mermaid, proper formatting.
