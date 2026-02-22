#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Metrics Collector
# =============================================================================
# Collects token usage and cost data for the xbo-market-kit project.
#
# Primary source: npx ccusage (accurate per-model pricing, billing data)
# Fallback: manual JSONL transcript parsing (approximate, Opus pricing only)
#
# Usage: bash collect-metrics.sh [--json]
# =============================================================================

PROJECT_SLUG="-Users-atlantdak-Local-Sites-claude-code-hackathon-xbo-market-kit-app-public"
PROJECT_DIR="$HOME/.claude/projects/$PROJECT_SLUG"

# Try ccusage first (accurate per-model pricing)
CCUSAGE_DATA=""
if command -v npx &>/dev/null; then
    CCUSAGE_DATA=$(npx ccusage daily --since 20260222 --json --instances --breakdown 2>/dev/null || echo "")
fi

if [ -n "$CCUSAGE_DATA" ]; then
    # Extract project-specific data from ccusage
    METRICS=$(python3 -c "
import json, sys

data = json.loads('''$CCUSAGE_DATA''')
projects = data.get('projects', {})

# Find our project
project_key = '$PROJECT_SLUG'
project_data = projects.get(project_key, [])

total_input = 0
total_output = 0
total_cache_read = 0
total_cache_create = 0
total_cost = 0.0
models = set()

for entry in project_data:
    total_input += entry.get('inputTokens', 0)
    total_output += entry.get('outputTokens', 0)
    total_cache_read += entry.get('cacheReadTokens', 0)
    total_cache_create += entry.get('cacheCreationTokens', 0)
    total_cost += entry.get('totalCost', 0.0)
    for m in entry.get('modelsUsed', []):
        models.add(m)

total_tokens = total_input + total_output
total_all = total_tokens + total_cache_read + total_cache_create

# Count sessions from JSONL files
import os, glob
jsonl_files = glob.glob(os.path.join('$PROJECT_DIR', '*.jsonl'))
session_count = len(jsonl_files)
msg_count = 0
for f in jsonl_files:
    with open(f) as fp:
        for line in fp:
            try:
                d = json.loads(line)
                if d.get('type') == 'assistant':
                    msg_count += 1
            except:
                pass

print(json.dumps({
    'total_input': total_input,
    'total_output': total_output,
    'total_cache_read': total_cache_read,
    'total_cache_create': total_cache_create,
    'total_tokens': total_tokens,
    'total_all_tokens': total_all,
    'sessions': session_count,
    'assistant_messages': msg_count,
    'cost_total': round(total_cost, 2),
    'models': sorted(list(models)),
    'source': 'ccusage'
}))
" 2>/dev/null)
else
    # Fallback: parse JSONL transcripts directly (approximate)
    if [ ! -d "$PROJECT_DIR" ]; then
        echo "Error: Project directory not found and ccusage unavailable"
        exit 1
    fi

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

# Approximate cost using Opus pricing (fallback only)
cost = (total_input * 15.00 + total_output * 75.00 + total_cache_read * 1.50 + total_cache_create * 18.75) / 1_000_000

print(json.dumps({
    'total_input': total_input,
    'total_output': total_output,
    'total_cache_read': total_cache_read,
    'total_cache_create': total_cache_create,
    'total_tokens': total_tokens,
    'total_all_tokens': total_all,
    'sessions': session_count,
    'assistant_messages': msg_count,
    'cost_total': round(cost, 2),
    'models': ['approximate-opus-pricing'],
    'source': 'jsonl-fallback'
}))
" 2>/dev/null)
fi

if [ -z "$METRICS" ]; then
    echo "Error: Failed to collect metrics"
    exit 1
fi

# JSON output mode
if [ "${1:-}" = "--json" ]; then
    echo "$METRICS"
    exit 0
fi

# Extract values for display
eval "$(python3 -c "
import json
d = json.loads('''$METRICS''')
print(f\"total_input={d['total_input']}\")
print(f\"total_output={d['total_output']}\")
print(f\"total_cache_read={d['total_cache_read']}\")
print(f\"total_cache_create={d['total_cache_create']}\")
print(f\"total_tokens={d['total_tokens']}\")
print(f\"total_all={d['total_all_tokens']}\")
print(f\"total_sessions={d['sessions']}\")
print(f\"msg_count={d['assistant_messages']}\")
print(f\"cost_total={d['cost_total']}\")
print(f\"source={d['source']}\")
")"

# Format numbers with commas
fmt() { python3 -c "print(f'{$1:,}')"; }

echo "📊 XBO Market Kit — Token & Cost Report"
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
echo "💰 Cost:            \$$cost_total"
echo "📡 Source:          $source"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
