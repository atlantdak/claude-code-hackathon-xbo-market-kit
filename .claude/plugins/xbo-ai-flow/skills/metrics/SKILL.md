---
name: metrics
description: This skill should be used when the user asks to "collect metrics", "show metrics", "update metrics", "how much time was spent", "token usage", "analytics", or when the orchestrate skill needs to update task metrics after completion.
version: 0.3.0
---

# Metrics — Development Analytics

## Overview

Collect, aggregate, and display development metrics from JSONL session transcripts. Tracks sessions, tokens, costs, active time, and tasks.

**Announce at start:** "Using the metrics skill to collect and display development analytics."

## Process

### Step 1: Regenerate Sessions Data

Run the metrics collection script to parse all JSONL session files and write `docs/metrics/sessions.json`:

```bash
bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --full
```

This parses `~/.claude/projects/[slug]/*.jsonl`, calculates per-session metrics (tokens, cost, active duration), and writes `docs/metrics/sessions.json`.

### Step 2: Update Tasks Totals

Run the totals updater to sync tasks.json from sessions.json:

```bash
bash ".claude/plugins/xbo-ai-flow/scripts/update-metrics-totals.sh"
```

This fills `total_tokens`, `total_cost_usd`, `total_sessions` in tasks.json totals, assigns sessions to tasks, and fills any null `cost_usd` values.

### Step 3: Read and Display

Read `docs/metrics/sessions.json` for display. Key sections:

- **totals** — aggregate numbers
- **by_day** — per-day cost, sessions, active time, messages
- **sessions** — per-session detail

### Step 4: Display Summary

Output formatted metrics:

```
XBO Market Kit — Development Metrics
====================================
Tasks completed: [N]/[total]
Total sessions:  [N]
Total dev time:  [active_min]m ([active_min/60]h active)
Total tokens:    [total_all_tokens formatted]
Total messages:  [total_messages]
Total cost:      $[total_cost_usd]
Total commits:   [total_commits]
====================================
By Day:
  2026-02-22: [sessions] sessions, $[cost], [active]m active
  2026-02-23: [sessions] sessions, $[cost], [active]m active
====================================
```

### Step 5: Per-Session Table (optional, if user asks)

Display a table from sessions.json:

| Session | Date | Active | Messages | Cost |
|---------|------|--------|----------|------|
| [session_id] | [day] | [active_min]m | [messages] | $[cost_usd] |
