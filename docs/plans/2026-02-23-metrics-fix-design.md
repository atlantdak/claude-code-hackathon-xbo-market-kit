# Metrics Tracking System — Fix Design

**Date:** 2026-02-23
**Status:** Draft
**Problem:** Metrics are not auto-tracked. tasks.json has null values for tokens/cost. No per-session tracking. Stop hook is unreliable for README updates.

## Current State

### What Works
- JSONL session transcripts exist at `~/.claude/projects/[slug]/*.jsonl` (13 sessions)
- `collect-metrics.sh` fallback parses JSONL and produces accurate per-project token/cost data
- `update-metrics-totals.sh` runs on Stop hook and updates tasks.json totals
- Stop hook command part executes reliably

### What's Broken
1. **Null totals** — `total_tokens` and `total_cost_usd` in tasks.json are null
2. **Null task cost** — `cost_usd` is null for Day 2 task
3. **No sessions tracking** — no way to see per-session data
4. **ccusage unavailable** — script falls back to JSONL but that's fine for our project
5. **Stop hook prompt unreliable** — `type: "prompt"` means LLM may not update README
6. **Duration = wall-clock** — includes idle time, not active time

### Data Available from JSONL
Per session: session_id, timestamps, per-message token usage (input, output, cache_read, cache_create), model used. Can derive: active duration (gaps < 5min), cost (model-specific pricing), message count.

## Design

### 1. New Schema: sessions.json

Create `docs/metrics/sessions.json` — auto-generated on every session end:

```json
{
  "sessions": [
    {
      "session_id": "5fb3233b",
      "started": "2026-02-22T15:40:00+02:00",
      "ended": "2026-02-22T15:56:00+02:00",
      "duration_wall_min": 16,
      "duration_active_min": 14,
      "input_tokens": 24,
      "output_tokens": 229,
      "cache_read_tokens": 313880,
      "cache_create_tokens": 89857,
      "total_tokens": 253,
      "all_tokens": 403990,
      "messages": 12,
      "cost_usd": 2.17,
      "model": "claude-opus-4-6",
      "day": "2026-02-22"
    }
  ],
  "by_day": {
    "2026-02-22": { "sessions": 4, "cost_usd": 184.83, "active_min": 120, "messages": 815 },
    "2026-02-23": { "sessions": 9, "cost_usd": 156.15, "active_min": 300, "messages": 812 }
  },
  "totals": {
    "total_sessions": 13,
    "total_cost_usd": 340.98,
    "total_active_min": 420,
    "total_wall_min": 1868,
    "total_input_tokens": 27534,
    "total_output_tokens": 84662,
    "total_cache_read_tokens": 152684573,
    "total_cache_create_tokens": 5610370,
    "total_all_tokens": 158406139,
    "total_messages": 1627,
    "model": "claude-opus-4-6"
  }
}
```

### 2. Rewritten collect-metrics.sh

Remove ccusage dependency entirely. JSONL is more reliable and project-specific.

Changes:
- Always parse JSONL (no ccusage branch)
- Output per-session data in addition to totals
- Calculate active duration (exclude gaps > 5min between messages)
- Write results to `docs/metrics/sessions.json`
- Still support `--json` flag for totals-only output

### 3. Rewritten update-metrics-totals.sh

Called by Stop hook. Now does:
1. Run `collect-metrics.sh` to regenerate `sessions.json`
2. Update `tasks.json` totals from `sessions.json` data
3. Fill in null `cost_usd` in tasks (proportional to duration)
4. Git-add the changed metrics files (but don't commit — leave for prompt hook)

### 4. Fixed hooks.json

Stop hook order:
1. **command:** `update-metrics-totals.sh` (reliable, always runs, updates tasks.json + sessions.json)
2. **prompt:** Simplified — just tells LLM to commit the auto-updated files and optionally refresh README

The prompt hook is kept for "nice to have" README updates but the data files are always correct because the command hook handles them.

### 5. Updated tasks.json Schema

Add `sessions` field to each task (list of session IDs active during that task's timeframe):

```json
{
  "id": "2026-02-23-plugin-implementation-mvp",
  "cost_usd": 156.15,
  "sessions": ["ec08d051", "d8bf3b45", "75eec9bf", "d789a628"]
}
```

Totals section gets real values from sessions.json:

```json
{
  "total_tasks": 5,
  "total_duration_minutes": 744,
  "total_tokens": 158406139,
  "total_commits": 44,
  "total_cost_usd": 340.98,
  "total_sessions": 13
}
```

### 6. Updated Skills

**metrics skill** — references sessions.json for display, no longer runs collect-metrics.sh inline.

**readme-update skill** — reads sessions.json for KPI cards (tokens, cost, sessions count).

**worklog-update skill** — reads sessions.json `by_day` for daily metrics.

## Active Duration Calculation

A session's "active duration" = sum of inter-message intervals where gap < 5 minutes.

Example: messages at 14:00, 14:02, 14:04, 14:30, 14:32 → active = (2 + 2 + 2) = 6 min (14:30 gap excluded).

## Cost Calculation

Using Opus 4.6 pricing (all sessions use this model):
- Input: $15/1M tokens
- Output: $75/1M tokens
- Cache read: $1.50/1M tokens
- Cache create: $18.75/1M tokens

Per-task cost: sum of session costs where session timeframe overlaps task timeframe.

## Files Changed

| File | Action |
|------|--------|
| `scripts/collect-metrics.sh` | Rewrite — JSONL-only, per-session output, writes sessions.json |
| `scripts/update-metrics-totals.sh` | Rewrite — reads sessions.json, updates tasks.json |
| `hooks/hooks.json` | Fix Stop hook prompt, keep command |
| `skills/metrics/SKILL.md` | Update to reference sessions.json |
| `skills/readme-update/SKILL.md` | Update data source to sessions.json |
| `skills/worklog-update/SKILL.md` | Update data source to sessions.json |
| `docs/metrics/sessions.json` | New — auto-generated |
| `docs/metrics/tasks.json` | Update — fill nulls, add sessions field |
