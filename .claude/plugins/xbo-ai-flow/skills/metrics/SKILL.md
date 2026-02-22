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

This returns JSON with `total_input`, `total_output`, `total_tokens`, `sessions`, `minutes`.

If the script is not available or fails, manually parse session data:

```bash
python3 -c "
import json, glob, os
sessions = glob.glob(os.path.expanduser('~/.claude/usage-data/session-meta/*.json'))
total_in, total_out, count, mins = 0, 0, 0, 0
for s in sessions:
    try:
        d = json.load(open(s))
        if 'claude-code-hackathon' in d.get('project_path', ''):
            total_in += d.get('input_tokens', 0)
            total_out += d.get('output_tokens', 0)
            mins += d.get('duration_minutes', 0)
            count += 1
    except: pass
print(json.dumps({'total_input': total_in, 'total_output': total_out, 'total_tokens': total_in + total_out, 'sessions': count, 'minutes': mins}))
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
