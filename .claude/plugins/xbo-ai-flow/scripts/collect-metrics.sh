#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Metrics Collector
# =============================================================================
# Parses Claude Code session metadata to aggregate token usage
# for sessions related to the xbo-market-kit project.
#
# Usage: bash collect-metrics.sh
# =============================================================================

SESSION_META_DIR="$HOME/.claude/usage-data/session-meta"
PROJECT_PATTERN="claude-code-hackathon"

if [ ! -d "$SESSION_META_DIR" ]; then
    echo "Error: Session metadata directory not found: $SESSION_META_DIR"
    exit 1
fi

total_input=0
total_output=0
total_sessions=0
total_minutes=0

for f in "$SESSION_META_DIR"/*.json; do
    [ -f "$f" ] || continue

    # Check if session belongs to our project
    project=$(python3 -c "import json; print(json.load(open('$f')).get('project_path',''))" 2>/dev/null || echo "")
    if ! echo "$project" | grep -q "$PROJECT_PATTERN"; then
        continue
    fi

    # Parse session data
    data=$(python3 -c "
import json, sys
d = json.load(open('$f'))
print(json.dumps({
    'input_tokens': d.get('input_tokens', 0),
    'output_tokens': d.get('output_tokens', 0),
    'duration_minutes': d.get('duration_minutes', 0),
    'start_time': d.get('start_time', ''),
    'session_id': d.get('session_id', '')
}))
" 2>/dev/null || echo '{}')

    input_t=$(echo "$data" | python3 -c "import json,sys; print(json.load(sys.stdin).get('input_tokens',0))")
    output_t=$(echo "$data" | python3 -c "import json,sys; print(json.load(sys.stdin).get('output_tokens',0))")
    duration=$(echo "$data" | python3 -c "import json,sys; print(json.load(sys.stdin).get('duration_minutes',0))")

    total_input=$((total_input + input_t))
    total_output=$((total_output + output_t))
    total_minutes=$((total_minutes + duration))
    total_sessions=$((total_sessions + 1))
done

total_tokens=$((total_input + total_output))
hours=$((total_minutes / 60))
mins=$((total_minutes % 60))

echo "📊 XBO Market Kit — Token & Time Report"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Sessions:      $total_sessions"
echo "Input tokens:  $total_input"
echo "Output tokens: $total_output"
echo "Total tokens:  $total_tokens"
echo "Total time:    ${hours}h ${mins}m"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
