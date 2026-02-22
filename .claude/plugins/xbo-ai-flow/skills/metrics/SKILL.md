---
name: metrics
description: This skill should be used when the user asks to "collect metrics", "show metrics", "update metrics", "how much time was spent", "token usage", or when the orchestrate skill needs to update task metrics after completion.
version: 0.1.0
---

# Metrics — Development Analytics

## Overview

Collect, aggregate, and display development metrics for the XBO Market Kit project. Tracks time spent, tokens consumed, tasks completed, and test results.

## Data Storage

Metrics are stored in `docs/metrics/tasks.json`.

### File Format

```json
{
  "tasks": [
    {
      "id": "task-slug",
      "plan": "docs/plans/YYYY-MM-DD-feature-plan.md",
      "started": "2026-02-22T10:00:00Z",
      "completed": "2026-02-22T11:30:00Z",
      "duration_minutes": 90,
      "session_ids": [],
      "estimated_tokens": 0,
      "commits": 0,
      "status": "completed"
    }
  ],
  "totals": {
    "total_tasks": 0,
    "total_duration_minutes": 0,
    "total_tokens": 0,
    "total_commits": 0
  }
}
```

## Collection Process

### 1. Initialize File

If `docs/metrics/tasks.json` does not exist, create it with empty arrays and zero totals.

### 2. Update Task Entry

When a task completes:
- Set `completed` timestamp
- Calculate `duration_minutes` from start/end
- Count commits via `git log --oneline --after="[started]" --before="[completed]" | wc -l`
- Set `status` to "completed"

### 3. Token Estimation

Claude Code session data lives in `~/.claude/usage-data/session-meta/`.

To estimate tokens for the current session:
- Parse JSON files matching the project path and time range:

```bash
for f in ~/.claude/usage-data/session-meta/*.json; do
  project=$(python3 -c "import json; print(json.load(open('$f')).get('project_path',''))" 2>/dev/null)
  if echo "$project" | grep -q "claude-code-hackathon"; then
    python3 -c "
import json
d = json.load(open('$f'))
inp = d.get('input_tokens', 0)
out = d.get('output_tokens', 0)
start = d.get('start_time', 'unknown')
print(f'{start}: input={inp}, output={out}, total={inp+out}')
"
  fi
done
```

### 4. Recalculate Totals

After updating any task:
```json
{
  "total_tasks": "count of completed tasks",
  "total_duration_minutes": "sum of all duration_minutes",
  "total_tokens": "sum of all estimated_tokens",
  "total_commits": "total from git log"
}
```

### 5. Display Summary

Print metrics summary to terminal:

```
📊 XBO Market Kit — Development Metrics
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Tasks completed: 5/12
Total dev time:  3h 45m
Total tokens:    450,000
Total commits:   15
Test pass rate:  100% (42 tests)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

## Creating the Metrics Directory

Ensure `docs/metrics/` directory exists before writing:

```bash
mkdir -p docs/metrics
```
