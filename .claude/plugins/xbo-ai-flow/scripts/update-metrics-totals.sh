#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Metrics Totals Updater
# =============================================================================
# Collects fresh metrics via collect-metrics.sh and updates tasks.json totals.
# Designed to run as a Stop hook command for reliable auto-updating.
#
# Usage: bash update-metrics-totals.sh
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
TASKS_FILE="$PROJECT_ROOT/docs/metrics/tasks.json"

if [ ! -f "$TASKS_FILE" ]; then
    echo "Warning: $TASKS_FILE not found, skipping metrics update"
    exit 0
fi

# Collect fresh metrics
METRICS=$(bash "$SCRIPT_DIR/collect-metrics.sh" --json 2>/dev/null || echo "")

if [ -z "$METRICS" ]; then
    echo "Warning: Failed to collect metrics, skipping update"
    exit 0
fi

# Get commit count
COMMITS=$(cd "$PROJECT_ROOT" && git log --oneline 2>/dev/null | wc -l | tr -d ' ')

# Update tasks.json totals with fresh data
python3 -c "
import json, sys

metrics = json.loads('''$METRICS''')
commits = int('$COMMITS')

with open('$TASKS_FILE', 'r') as f:
    tasks_data = json.load(f)

# Update totals
completed_tasks = [t for t in tasks_data.get('tasks', []) if t.get('status') == 'completed']
duration = sum(t.get('duration_minutes', 0) for t in tasks_data.get('tasks', []))

tasks_data['totals'] = {
    'total_tasks': len(completed_tasks),
    'total_duration_minutes': duration,
    'total_tokens': metrics.get('total_all_tokens', 0),
    'total_commits': commits,
    'total_cost_usd': metrics.get('cost_total', 0)
}

with open('$TASKS_FILE', 'w') as f:
    json.dump(tasks_data, f, indent=2)
    f.write('\n')

print(f'Updated tasks.json: {len(completed_tasks)} tasks, {commits} commits, \${metrics.get(\"cost_total\", 0)}')
"
