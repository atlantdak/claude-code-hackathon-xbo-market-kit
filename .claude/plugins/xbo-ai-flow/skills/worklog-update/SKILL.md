---
name: worklog-update
description: This skill should be used when the user asks to "update worklog", "add worklog entry", "log work", "document what was done today", "update development journal", or after completing a development task via the orchestrate skill.
version: 0.2.0
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

## Decisions

## Metrics

| Metric | Value |
|--------|-------|
| Tasks completed | 0 |
| Total dev time | 0m |
| Commits | 0 |
| **Total cost** | **$0** |

## Tools Used

- Claude Code (brainstorming, planning, implementation)
- Local by Flywheel (WordPress 6.9.1 environment)
- PHPStorm (IDE)
```

### Step 3: Collect Data

**Completed tasks:** Read `docs/metrics/tasks.json`. Filter tasks completed today (where `completed` starts with today's date).

**Commits today:**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git log --oneline --since="YYYY-MM-DDT00:00:00" --until="YYYY-MM-DDT23:59:59"
```

**Decisions:** Review any design docs created today in `docs/plans/`.

### Step 4: Update Completed Section

For each completed task, add a checkbox entry:

```markdown
- [x] Task description (Xm, N commits)
  - Key detail or sub-item
```

If tasks are from a plan, reference the plan file.

### Step 5: Update Metrics Table

Run `bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --json` to get fresh token and cost data.

| Metric | Value |
|--------|-------|
| Tasks completed | [count from tasks.json] |
| Total dev time | [sum of durations]m |
| Commits | [from git log] |
| Tokens (in+out) | [total_tokens from script] |
| Tokens (all incl. cache) | [total_all_tokens from script] |
| **Total cost** | **$[cost_total from script]** |
| Cost source | [source from script] |

### Step 6: Update Day Summary

Set the H1 title to reflect today's main focus area. Set the Summary section to 1-2 sentences describing what was accomplished.

### Step 7: Update Worklog Index

Read `docs/worklog/README.md`. If today's entry is not listed, add it. If the index file doesn't exist, create it:

```markdown
# Work Log

Daily development journal for XBO Market Kit.

## Entries

| Date | Summary |
|------|---------|
| YYYY-MM-DD | [Summary] |
```

### Step 8: Update Plans Index

Read `docs/plans/README.md`. Check if any plan statuses changed today. Update the status column if needed (In Progress → Completed).
