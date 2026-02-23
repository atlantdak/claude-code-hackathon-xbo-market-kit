# Metrics Fix — Implementation Plan

**Design:** [metrics-fix-design.md](2026-02-23-metrics-fix-design.md)
**Tasks:** 7

## Task 1: Rewrite collect-metrics.sh

Rewrite the metrics collection script to:
- Remove ccusage dependency entirely
- Parse all JSONL session files from `~/.claude/projects/[slug]/`
- For each session: extract session_id, timestamps, per-message token usage, model, calculate active duration (gaps < 5min excluded)
- Generate `docs/metrics/sessions.json` with sessions array, by_day summary, and totals
- Keep `--json` flag for backward compatibility (outputs totals only)
- Add `--full` flag to output everything including per-session data

**Verify:** `bash .claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh --json` returns valid JSON with non-null cost_total and total_all_tokens.

## Task 2: Rewrite update-metrics-totals.sh

Rewrite to:
1. Call `collect-metrics.sh --full` (generates sessions.json)
2. Read `sessions.json` totals
3. Update `tasks.json`:
   - Set `totals.total_tokens` from sessions.json
   - Set `totals.total_cost_usd` from sessions.json
   - Set `totals.total_sessions` from sessions.json
   - For each task with `cost_usd: null`, estimate cost proportionally: `(task_duration / total_duration) * total_cost`
   - For each task, add `sessions` array listing session IDs whose timeframe overlaps the task timeframe
4. Git-add `docs/metrics/sessions.json` and `docs/metrics/tasks.json`

**Verify:** Run script, check tasks.json has no null values in totals or cost_usd fields.

## Task 3: Fix hooks.json

Update Stop hook:
- Keep command hook calling `update-metrics-totals.sh` (already correct)
- Simplify the prompt hook — just remind LLM to commit the auto-updated metrics files and optionally update README dashboard

```json
{
  "type": "prompt",
  "prompt": "Metrics files were auto-updated. Commit them: git add docs/metrics/ && git commit -m 'docs: auto-update session metrics'. Then optionally update README.md dashboard KPIs from docs/metrics/sessions.json."
}
```

**Verify:** Read hooks.json, confirm structure is valid JSON.

## Task 4: Update tasks.json with real data

Run the rewritten scripts to populate real data:
- Fill `total_tokens`, `total_cost_usd`, `total_sessions` in totals
- Fill `cost_usd` for task 5 (Day 2 MVP)
- Add session IDs to each task

**Verify:** Read tasks.json, confirm no null values in totals.

## Task 5: Update metrics skill

Update `skills/metrics/SKILL.md`:
- Reference `sessions.json` as primary data source
- Remove inline JSONL parsing code (now handled by script)
- Add session-level display (table of sessions with date, duration, cost)
- Show by-day breakdown

**Verify:** Read updated skill, confirm it references sessions.json.

## Task 6: Update readme-update and worklog-update skills

**readme-update:** Update KPI data sources to read from `sessions.json`:
- Tokens KPI from `sessions.json totals.total_all_tokens`
- Cost KPI from `sessions.json totals.total_cost_usd`
- Sessions count from `sessions.json totals.total_sessions`
- API Calls from `sessions.json totals.total_messages`

**worklog-update:** Update daily metrics to read from `sessions.json by_day`:
- Daily cost, sessions, messages from by_day section

**Verify:** Read both skills, confirm they reference sessions.json.

## Task 7: Update README.md with real metrics

Run the full pipeline:
1. Execute collect-metrics.sh to generate sessions.json
2. Execute update-metrics-totals.sh to update tasks.json
3. Update README.md dashboard with real values from sessions.json:
   - Cost: real total from sessions.json
   - Tokens: formatted from sessions.json
   - Sessions: count from sessions.json
   - API Calls: messages count from sessions.json
4. Commit all changes

**Verify:** Read README.md dashboard section, confirm KPI values match sessions.json.

## Execution Order

Tasks 1-2 are sequential (Task 2 depends on Task 1).
Task 3 is independent.
Task 4 depends on Tasks 1-2.
Tasks 5-6 are independent of each other, can parallel.
Task 7 depends on Task 4.

```
[Task 1] → [Task 2] → [Task 4] → [Task 7]
[Task 3]              [Task 5]
                      [Task 6]
```
