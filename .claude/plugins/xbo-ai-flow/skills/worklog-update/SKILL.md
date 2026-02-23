---
name: worklog-update
description: This skill should be used when the user asks to "update worklog", "add worklog entry", "log work", "document what was done today", "update development journal", or after completing a development task via the orchestrate skill.
version: 0.3.0
---

# Worklog Update — Development Journal

## Overview

Add structured entries to the daily worklog. Each entry documents completed work, decisions made, and metrics.

**Announce at start:** "Using the worklog-update skill to record today's progress."

## Process

### Step 1: Determine Date

Get today's date. Use `date +%Y-%m-%d` or the current date from context. Format: `YYYY-MM-DD`.

### Step 2: Check Existing Worklog

Read `docs/worklog/YYYY-MM-DD.md`. If file does not exist, create it with this template:

```markdown
# YYYY-MM-DD — [Day Summary]

## Summary

[Brief description of the day's focus — 1-2 sentences]

## Completed

## Commits

| Hash | Message |
|------|---------|

## Decisions

## Metrics

| Metric | Value |
|--------|-------|
| Sessions | 0 |
| Active time | 0m |
| Tasks completed | 0 |
| Commits | 0 |
| Messages (API calls) | 0 |
| **Total cost** | **$0** |

## Tools Used

- Claude Code (brainstorming, planning, implementation)
- Local by Flywheel (WordPress 6.9.1 environment)
- PHPStorm (IDE)

## Next Steps
```

### Step 3: Collect Data

**Sessions data:** Read `docs/metrics/sessions.json`. Use `by_day` section for today's metrics:
- `by_day[YYYY-MM-DD].sessions` → session count
- `by_day[YYYY-MM-DD].cost_usd` → daily cost
- `by_day[YYYY-MM-DD].active_min` → active development time
- `by_day[YYYY-MM-DD].messages` → API call count

If sessions.json is missing or stale, regenerate:
```bash
bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --full
```

**Completed tasks:** Read `docs/metrics/tasks.json`. Filter tasks completed today (where `completed` starts with today's date).

**Commits today:**
```bash
git log --oneline --since="YYYY-MM-DDT00:00:00" --until="YYYY-MM-DDT23:59:59"
```

**Decisions:** Review any design docs created today in `docs/plans/`.

### Step 4: Update Completed Section

For each completed task, add a checkbox entry:

```markdown
- [x] Task description (Xm, N commits, $X.XX)
  - Key detail or sub-item
```

### Step 5: Update Metrics Table

| Metric | Value |
|--------|-------|
| Sessions | [from sessions.json by_day] |
| Active time | [active_min]m |
| Tasks completed | [count from tasks.json] |
| Commits | [from git log] |
| Messages (API calls) | [from sessions.json by_day] |
| **Total cost** | **$[cost from sessions.json by_day]** |

### Step 6: Update Day Summary

Set the H1 title to reflect today's main focus area. Set the Summary section to 1-2 sentences.

### Step 7: Update Worklog Index

Read `docs/worklog/README.md`. Add today's entry if not listed.
