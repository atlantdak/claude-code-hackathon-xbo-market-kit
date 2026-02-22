---
name: metrics
description: This skill should be used when the user asks to "collect metrics", "show metrics", "update metrics", "how much time was spent", "token usage", "analytics", or when the orchestrate skill needs to update task metrics after completion.
version: 0.2.0
---

# Metrics — Development Analytics

## Overview

Collect, aggregate, and display development metrics. Track time spent, tokens consumed, tasks completed, and commits.

**Announce at start:** "Using the metrics skill to collect and display development analytics."

## Process

Execute these steps in order.

### Step 1: Ensure Data File Exists

Check if `docs/metrics/tasks.json` exists. If not, create it:

```json
{
  "tasks": [],
  "totals": {
    "total_tasks": 0,
    "total_duration_minutes": 0,
    "total_tokens": 0,
    "total_commits": 0
  }
}
```

### Step 2: Collect Token Data

Run the metrics collection script:

```bash
bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --json
```

This returns JSON with `total_input`, `total_output`, `total_tokens`, `total_cache_read`, `total_cache_create`, `total_all_tokens`, `sessions`, `assistant_messages`, `cost_total`, `models`, `source`.

The script uses `npx ccusage` as the primary data source (accurate per-model pricing for Opus + Haiku). Falls back to manual JSONL transcript parsing if ccusage is unavailable.

If the script is not available or fails, manually parse session data:

```bash
python3 -c "
import json, os, glob
project_dir = os.path.expanduser('~/.claude/projects/-Users-atlantdak-Local-Sites-claude-code-hackathon-xbo-market-kit-app-public')
jsonl_files = glob.glob(os.path.join(project_dir, '*.jsonl'))
total_in, total_out, count, msgs = 0, 0, len(jsonl_files), 0
for f in jsonl_files:
    with open(f) as fp:
        for line in fp:
            try:
                d = json.loads(line)
                if d.get('type') == 'assistant':
                    usage = d.get('message', {}).get('usage', {})
                    if usage:
                        total_in += usage.get('input_tokens', 0)
                        total_out += usage.get('output_tokens', 0)
                        msgs += 1
            except: pass
print(json.dumps({'total_input': total_in, 'total_output': total_out, 'total_tokens': total_in + total_out, 'sessions': count, 'assistant_messages': msgs}))
"
```

### Step 3: Count Commits

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git log --oneline | wc -l | tr -d ' '
```

### Step 4: Update Totals

Read `docs/metrics/tasks.json`. Update the totals section:

```json
{
  "total_tasks": <count of tasks with status "completed">,
  "total_duration_minutes": <sum of all task duration_minutes>,
  "total_tokens": <from Step 2>,
  "total_commits": <from Step 3>
}
```

Write the updated file.

### Step 5: Display Summary

Output formatted metrics to the terminal:

```
📊 XBO Market Kit — Development Metrics
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Tasks completed: [N]/[total]
Total dev time:  [H]h [M]m
Total tokens:    [N formatted with commas]
Total commits:   [N]
Sessions:        [N]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```
