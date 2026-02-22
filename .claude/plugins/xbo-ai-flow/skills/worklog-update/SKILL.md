---
name: worklog-update
description: This skill should be used when the user asks to "update worklog", "add worklog entry", "log work", "document what was done", or after completing a development task. Adds structured entries to the daily worklog file.
version: 0.1.0
---

# Worklog Update — Development Journal

## Overview

Add structured entries to the daily worklog file at `docs/worklog/YYYY-MM-DD.md`. Each entry documents completed work, decisions, metrics, and issues encountered.

## Process

### 1. Determine Today's Date

Use the current date in `YYYY-MM-DD` format.

### 2. Check if Worklog Exists

Read `docs/worklog/YYYY-MM-DD.md`. If it does not exist, create it with the header:

```markdown
# YYYY-MM-DD — [Day Summary]

## Summary

[Brief description of the day's focus]

## Completed

## Decisions

## Metrics

| Metric | Value |
|--------|-------|
| Tasks completed | 0 |
| Total dev time | 0m |
| Commits | 0 |

## Tools Used

- Claude Code (brainstorming, planning, implementation)
```

### 3. Add Entry

Append to the "Completed" section:

```markdown
- [x] [Task description] ([duration]m, [commit count] commits)
  - [Key details or sub-items]
```

### 4. Update Metrics Table

Recalculate from `docs/metrics/tasks.json` and git log for today.

### 5. Update docs/worklog/README.md

Add or update the entry in the index table:

```markdown
| YYYY-MM-DD | [Summary] |
```

### 6. Update docs/plans/README.md

If any plans changed status, update the plans index.

## Entry Format

Each completed task entry should include:
- Task name (from the plan)
- Duration in minutes
- Number of commits
- Key decisions or notable items as sub-bullets
- Any issues encountered
