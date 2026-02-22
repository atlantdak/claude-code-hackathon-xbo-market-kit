#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Metrics Collector
# =============================================================================
# Parses Claude Code JSONL session transcripts to aggregate token usage
# for sessions related to the xbo-market-kit project.
#
# Data source: ~/.claude/projects/<project-slug>/*.jsonl
# Each assistant message contains usage.input_tokens, usage.output_tokens,
# usage.cache_read_input_tokens, usage.cache_creation_input_tokens
#
# Usage: bash collect-metrics.sh [--json]
# =============================================================================

PROJECT_SLUG="-Users-atlantdak-Local-Sites-claude-code-hackathon-xbo-market-kit-app-public"
PROJECT_DIR="$HOME/.claude/projects/$PROJECT_SLUG"

if [ ! -d "$PROJECT_DIR" ]; then
    echo "Error: Project directory not found: $PROJECT_DIR"
    exit 1
fi

# Use python3 to parse JSONL files and aggregate token usage
METRICS=$(python3 -c "
import json, os, glob

project_dir = '$PROJECT_DIR'
jsonl_files = glob.glob(os.path.join(project_dir, '*.jsonl'))

total_input = 0
total_output = 0
total_cache_read = 0
total_cache_create = 0
session_count = len(jsonl_files)
msg_count = 0

for f in sorted(jsonl_files):
    with open(f) as fp:
        for line in fp:
            try:
                d = json.loads(line)
                if d.get('type') == 'assistant':
                    msg = d.get('message', {})
                    usage = msg.get('usage', {})
                    if usage:
                        total_input += usage.get('input_tokens', 0)
                        total_output += usage.get('output_tokens', 0)
                        total_cache_read += usage.get('cache_read_input_tokens', 0)
                        total_cache_create += usage.get('cache_creation_input_tokens', 0)
                        msg_count += 1
            except:
                pass

total_tokens = total_input + total_output
total_all = total_tokens + total_cache_read + total_cache_create

print(json.dumps({
    'total_input': total_input,
    'total_output': total_output,
    'total_cache_read': total_cache_read,
    'total_cache_create': total_cache_create,
    'total_tokens': total_tokens,
    'total_all_tokens': total_all,
    'sessions': session_count,
    'assistant_messages': msg_count
}))
" 2>/dev/null)

if [ -z "$METRICS" ]; then
    echo "Error: Failed to parse session data"
    exit 1
fi

# Extract values
total_input=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['total_input'])")
total_output=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['total_output'])")
total_cache_read=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['total_cache_read'])")
total_cache_create=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['total_cache_create'])")
total_tokens=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['total_tokens'])")
total_all=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['total_all_tokens'])")
total_sessions=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['sessions'])")
msg_count=$(echo "$METRICS" | python3 -c "import json,sys; print(json.load(sys.stdin)['assistant_messages'])")

# JSON output mode
if [ "${1:-}" = "--json" ]; then
    echo "$METRICS"
    exit 0
fi

# Format numbers with commas
fmt() { python3 -c "print(f'{$1:,}')"; }

echo "📊 XBO Market Kit — Token & Time Report"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Sessions:           $total_sessions"
echo "API calls:          $msg_count"
echo "Input tokens:       $(fmt $total_input)"
echo "Output tokens:      $(fmt $total_output)"
echo "Cache read tokens:  $(fmt $total_cache_read)"
echo "Cache create tokens:$(fmt $total_cache_create)"
echo "Total (in+out):     $(fmt $total_tokens)"
echo "Total (all):        $(fmt $total_all)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
