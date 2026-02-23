#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Metrics Collector v3
# =============================================================================
# Primary: ccusage v17 (accurate billing API data, per-project, per-model)
# Fallback: JSONL transcript parsing (overestimates cost ~5x due to cache
#           counting differences between usage fields and billing API)
#
# Session data (timestamps, active duration, message counts) always comes
# from JSONL since ccusage doesn't provide per-session granularity.
#
# Usage:
#   bash collect-metrics.sh           # Human-readable summary
#   bash collect-metrics.sh --json    # JSON totals only
#   bash collect-metrics.sh --full    # Full JSON + write sessions.json
# =============================================================================

PROJECT_SLUG="-Users-atlantdak-Local-Sites-claude-code-hackathon-xbo-market-kit-app-public"
PROJECT_DIR="$HOME/.claude/projects/$PROJECT_SLUG"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
SESSIONS_FILE="$PROJECT_ROOT/docs/metrics/sessions.json"

if [ ! -d "$PROJECT_DIR" ]; then
    echo "Error: Project directory not found: $PROJECT_DIR"
    exit 1
fi

MODE="${1:-display}"

# Try ccusage first for accurate billing cost
CCUSAGE_DATA=""
if command -v npx &>/dev/null; then
    CCUSAGE_DATA=$(npx ccusage@17 daily --since 20260222 --json --instances --breakdown 2>/dev/null || echo "")
fi

RESULT=$(python3 << 'PYEOF'
import json, os, glob, subprocess
from datetime import datetime, timezone

project_dir = os.environ.get("PROJECT_DIR", "")
sessions_file = os.environ.get("SESSIONS_FILE", "")
mode = os.environ.get("MODE", "display")
project_slug = os.environ.get("PROJECT_SLUG", "")
ccusage_raw = os.environ.get("CCUSAGE_DATA", "")

ACTIVE_GAP_SECONDS = 300  # 5 min

# ─── Parse ccusage billing data (if available) ───
ccusage_by_day = {}
ccusage_total_cost = None
ccusage_models = {}
has_ccusage = False

if ccusage_raw:
    try:
        cc_data = json.loads(ccusage_raw)
        projects = cc_data.get("projects", {})
        our_data = projects.get(project_slug, [])
        if our_data:
            has_ccusage = True
            for entry in our_data:
                day = entry.get("date", "")
                ccusage_by_day[day] = {
                    "cost_usd": round(entry.get("totalCost", 0), 2),
                    "input": entry.get("inputTokens", 0),
                    "output": entry.get("outputTokens", 0),
                    "cache_read": entry.get("cacheReadTokens", 0),
                    "cache_create": entry.get("cacheCreationTokens", 0),
                    "models": entry.get("modelsUsed", []),
                    "breakdowns": entry.get("modelBreakdowns", []),
                }
                for bd in entry.get("modelBreakdowns", []):
                    m = bd.get("modelName", "unknown")
                    if m not in ccusage_models:
                        ccusage_models[m] = {"cost": 0, "input": 0, "output": 0, "cache_read": 0, "cache_create": 0}
                    ccusage_models[m]["cost"] += bd.get("cost", 0)
                    ccusage_models[m]["input"] += bd.get("inputTokens", 0)
                    ccusage_models[m]["output"] += bd.get("outputTokens", 0)
                    ccusage_models[m]["cache_read"] += bd.get("cacheReadTokens", 0)
                    ccusage_models[m]["cache_create"] += bd.get("cacheCreationTokens", 0)
            ccusage_total_cost = round(sum(d["cost_usd"] for d in ccusage_by_day.values()), 2)
    except Exception:
        pass

# ─── Parse JSONL sessions (always — for timestamps, active duration, messages) ───
jsonl_files = sorted(glob.glob(os.path.join(project_dir, "*.jsonl")))

sessions = []
for f in jsonl_files:
    sid = os.path.basename(f).replace(".jsonl", "")[:8]
    full_sid = os.path.basename(f).replace(".jsonl", "")
    inp = out = cr = cc = msgs = 0
    timestamps = []
    models = set()

    with open(f) as fp:
        for line in fp:
            try:
                d = json.loads(line)
                ts = d.get("timestamp")
                if ts:
                    timestamps.append(ts)
                if d.get("type") == "assistant":
                    msg = d.get("message", {})
                    model = msg.get("model", "")
                    if model and model != "<synthetic>":
                        models.add(model)
                    usage = msg.get("usage", {})
                    if usage:
                        inp += usage.get("input_tokens", 0)
                        out += usage.get("output_tokens", 0)
                        cr += usage.get("cache_read_input_tokens", 0)
                        cc += usage.get("cache_creation_input_tokens", 0)
                        msgs += 1
            except Exception:
                pass

    wall_min = 0
    active_min = 0
    started = ""
    ended = ""
    day = ""

    if timestamps:
        parsed = []
        for t in timestamps:
            try:
                parsed.append(datetime.fromisoformat(t.replace("Z", "+00:00")))
            except Exception:
                pass
        if parsed:
            parsed.sort()
            started = parsed[0].isoformat()
            ended = parsed[-1].isoformat()
            day = parsed[0].strftime("%Y-%m-%d")
            wall_min = round((parsed[-1] - parsed[0]).total_seconds() / 60, 1)
            active_sec = 0
            for i in range(1, len(parsed)):
                gap = (parsed[i] - parsed[i - 1]).total_seconds()
                if gap < ACTIVE_GAP_SECONDS:
                    active_sec += gap
            active_min = round(active_sec / 60, 1)

    sessions.append({
        "session_id": sid,
        "full_id": full_sid,
        "started": started,
        "ended": ended,
        "duration_wall_min": wall_min,
        "duration_active_min": active_min,
        "input_tokens": inp,
        "output_tokens": out,
        "cache_read_tokens": cr,
        "cache_create_tokens": cc,
        "total_tokens": inp + out,
        "all_tokens": inp + out + cr + cc,
        "messages": msgs,
        "model": sorted(list(models))[0] if models else "unknown",
        "day": day,
    })

# ─── Distribute ccusage cost across sessions proportionally by message count ───
# (ccusage gives per-day cost, we distribute to sessions by their message share)
for s in sessions:
    d = s["day"]
    if has_ccusage and d in ccusage_by_day:
        day_cost = ccusage_by_day[d]["cost_usd"]
        day_msgs = sum(x["messages"] for x in sessions if x["day"] == d)
        if day_msgs > 0:
            s["cost_usd"] = round(day_cost * s["messages"] / day_msgs, 2)
        else:
            s["cost_usd"] = 0.0
    else:
        # Fallback: Opus pricing from JSONL usage (overestimates ~5x)
        s["cost_usd"] = round(
            (s["input_tokens"] * 15.0 + s["output_tokens"] * 75.0
             + s["cache_read_tokens"] * 1.5 + s["cache_create_tokens"] * 18.75) / 1_000_000, 2)

# ─── By-day summary ───
by_day = {}
for s in sessions:
    d = s["day"]
    if not d:
        continue
    if d not in by_day:
        by_day[d] = {"sessions": 0, "cost_usd": 0, "active_min": 0, "wall_min": 0, "messages": 0, "tokens": 0}
    by_day[d]["sessions"] += 1
    by_day[d]["cost_usd"] = round(by_day[d]["cost_usd"] + s["cost_usd"], 2)
    by_day[d]["active_min"] = round(by_day[d]["active_min"] + s["duration_active_min"], 1)
    by_day[d]["wall_min"] = round(by_day[d]["wall_min"] + s["duration_wall_min"], 1)
    by_day[d]["messages"] += s["messages"]
    by_day[d]["tokens"] += s["all_tokens"]

# If ccusage available, use its per-day cost (more accurate than sum of distributed)
if has_ccusage:
    for d in by_day:
        if d in ccusage_by_day:
            by_day[d]["cost_usd"] = ccusage_by_day[d]["cost_usd"]

# ─── Totals ───
total_cost = ccusage_total_cost if has_ccusage else round(sum(s["cost_usd"] for s in sessions), 2)
source = "ccusage" if has_ccusage else "jsonl-fallback"
models_used = sorted(ccusage_models.keys()) if has_ccusage else ["claude-opus-4-6"]

totals = {
    "total_sessions": len(sessions),
    "total_cost_usd": total_cost,
    "total_active_min": round(sum(s["duration_active_min"] for s in sessions), 1),
    "total_wall_min": round(sum(s["duration_wall_min"] for s in sessions), 1),
    "total_input_tokens": sum(s["input_tokens"] for s in sessions),
    "total_output_tokens": sum(s["output_tokens"] for s in sessions),
    "total_cache_read_tokens": sum(s["cache_read_tokens"] for s in sessions),
    "total_cache_create_tokens": sum(s["cache_create_tokens"] for s in sessions),
    "total_all_tokens": sum(s["all_tokens"] for s in sessions),
    "total_messages": sum(s["messages"] for s in sessions),
    "models": models_used,
    "source": source,
}

# ccusage model breakdown
if has_ccusage:
    totals["model_breakdown"] = {
        m: {"cost_usd": round(v["cost"], 2), "input": v["input"], "output": v["output"],
            "cache_read": v["cache_read"], "cache_create": v["cache_create"]}
        for m, v in ccusage_models.items()
    }

full_data = {
    "sessions": sessions,
    "by_day": dict(sorted(by_day.items())),
    "totals": totals,
}

# Write sessions.json if --full mode
if mode == "--full":
    os.makedirs(os.path.dirname(sessions_file), exist_ok=True)
    with open(sessions_file, "w") as fp:
        json.dump(full_data, fp, indent=2)
        fp.write("\n")

# ─── Output ───
if mode in ("--json", "--full"):
    print(json.dumps({
        "total_input": totals["total_input_tokens"],
        "total_output": totals["total_output_tokens"],
        "total_cache_read": totals["total_cache_read_tokens"],
        "total_cache_create": totals["total_cache_create_tokens"],
        "total_tokens": totals["total_input_tokens"] + totals["total_output_tokens"],
        "total_all_tokens": totals["total_all_tokens"],
        "sessions": totals["total_sessions"],
        "assistant_messages": totals["total_messages"],
        "cost_total": totals["total_cost_usd"],
        "active_minutes": totals["total_active_min"],
        "models": totals["models"],
        "source": totals["source"],
    }))
else:
    def fmt(n):
        return f"{n:,.0f}" if isinstance(n, (int, float)) and n > 999 else str(n)

    print("XBO Market Kit — Metrics Report")
    print("=" * 50)
    print(f"Sessions:            {totals['total_sessions']}")
    print(f"API calls:           {totals['total_messages']}")
    print(f"Active time:         {totals['total_active_min']:.0f}m ({totals['total_active_min']/60:.1f}h)")
    print(f"All tokens (JSONL):  {fmt(totals['total_all_tokens'])}")
    print("=" * 50)
    print(f"Cost:                ${totals['total_cost_usd']:.2f}")
    print(f"Source:              {totals['source']}")
    if has_ccusage:
        print(f"Models:              {', '.join(totals['models'])}")
        for m, v in totals.get("model_breakdown", {}).items():
            short = m.replace("claude-", "").replace("-20251001", "")
            print(f"  {short}: ${v['cost_usd']:.2f}")
    print("=" * 50)
    print()
    print("By Day:")
    for d, v in sorted(by_day.items()):
        print(f"  {d}: {v['sessions']} sessions, ${v['cost_usd']:.2f}, {v['active_min']:.0f}m active, {v['messages']} msgs")

PYEOF
)

echo "$RESULT"
