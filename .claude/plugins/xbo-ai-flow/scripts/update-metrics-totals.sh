#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Metrics Totals Updater v2
# =============================================================================
# Runs collect-metrics.sh --full to regenerate sessions.json, then updates
# tasks.json with real token/cost data. Designed as a Stop hook command.
#
# Usage: bash update-metrics-totals.sh
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
TASKS_FILE="$PROJECT_ROOT/docs/metrics/tasks.json"
SESSIONS_FILE="$PROJECT_ROOT/docs/metrics/sessions.json"

if [ ! -f "$TASKS_FILE" ]; then
    echo "Warning: $TASKS_FILE not found, skipping metrics update"
    exit 0
fi

# Step 1: Regenerate sessions.json via collect-metrics.sh --full
export PROJECT_SLUG="-Users-atlantdak-Local-Sites-claude-code-hackathon-xbo-market-kit-app-public"
export PROJECT_DIR="$HOME/.claude/projects/$PROJECT_SLUG"
export SESSIONS_FILE
export MODE="--full"

# Fetch ccusage billing data (accurate per-project, per-model cost)
export CCUSAGE_DATA=""
if command -v npx &>/dev/null; then
    CCUSAGE_DATA=$(npx ccusage@17 daily --since 20260222 --json --instances --breakdown 2>/dev/null || echo "")
fi
export CCUSAGE_DATA

METRICS_JSON=$(bash "$SCRIPT_DIR/collect-metrics.sh" --full 2>/dev/null || echo "")

if [ -z "$METRICS_JSON" ]; then
    echo "Warning: Failed to collect metrics, skipping update"
    exit 0
fi

# Step 2: Get commit count
COMMITS=$(cd "$PROJECT_ROOT" && git log --oneline 2>/dev/null | wc -l | tr -d ' ')

# Step 3: Update tasks.json from sessions.json
python3 << PYEOF
import json

sessions_file = "$SESSIONS_FILE"
tasks_file = "$TASKS_FILE"
commits = int("$COMMITS")

# Read sessions.json
with open(sessions_file) as f:
    sessions_data = json.load(f)

# Read tasks.json
with open(tasks_file) as f:
    tasks_data = json.load(f)

totals = sessions_data.get("totals", {})
sessions = sessions_data.get("sessions", [])

# Parse session timestamps for overlap detection
from datetime import datetime

def parse_ts(ts_str):
    if not ts_str:
        return None
    try:
        return datetime.fromisoformat(ts_str.replace("Z", "+00:00"))
    except Exception:
        return None

# For each task, assign overlapping sessions and distribute cost by duration
total_duration = sum(t.get("duration_minutes", 0) for t in tasks_data.get("tasks", []))
total_cost = totals.get("total_cost_usd", 0)

# Group tasks by day for per-day cost distribution
by_day_cost = {}
for s in sessions:
    day = s.get("day", "")
    if day:
        by_day_cost[day] = by_day_cost.get(day, 0) + s.get("cost_usd", 0)

for task in tasks_data.get("tasks", []):
    task_start = parse_ts(task.get("started", ""))
    task_end = parse_ts(task.get("completed", ""))

    # Find overlapping sessions
    task_sessions = []
    for s in sessions:
        s_start = parse_ts(s.get("started", ""))
        s_end = parse_ts(s.get("ended", ""))
        if s_start and s_end and task_start and task_end:
            if s_start < task_end and s_end > task_start:
                task_sessions.append(s["session_id"])

    task["sessions"] = task_sessions

    # Distribute cost proportionally by duration within same day
    if task.get("cost_usd") is None and total_duration > 0:
        # Determine task's day from start date
        task_day = task.get("started", "")[:10]
        day_cost = by_day_cost.get(task_day, 0)
        # Sum duration of all tasks on this day
        day_duration = sum(
            t.get("duration_minutes", 0) for t in tasks_data.get("tasks", [])
            if t.get("started", "")[:10] == task_day
        )
        if day_duration > 0:
            proportion = task.get("duration_minutes", 0) / day_duration
            task["cost_usd"] = round(proportion * day_cost, 2)
        else:
            task["cost_usd"] = 0.0

# Update totals
completed_tasks = [t for t in tasks_data.get("tasks", []) if t.get("status") == "completed"]
duration = sum(t.get("duration_minutes", 0) for t in tasks_data.get("tasks", []))

tasks_data["totals"] = {
    "total_tasks": len(completed_tasks),
    "total_duration_minutes": duration,
    "total_tokens": totals.get("total_all_tokens", 0),
    "total_commits": commits,
    "total_cost_usd": totals.get("total_cost_usd", 0),
    "total_sessions": totals.get("total_sessions", 0),
    "total_active_minutes": totals.get("total_active_min", 0),
    "total_messages": totals.get("total_messages", 0),
}

with open(tasks_file, "w") as f:
    json.dump(tasks_data, f, indent=2)
    f.write("\n")

print(f"Updated tasks.json: {len(completed_tasks)} tasks, {commits} commits, \${totals.get('total_cost_usd', 0)}, {totals.get('total_sessions', 0)} sessions")
PYEOF

# Step 4: Git-add the updated files (don't commit — leave for prompt hook or manual)
cd "$PROJECT_ROOT"
git add docs/metrics/sessions.json docs/metrics/tasks.json 2>/dev/null || true
