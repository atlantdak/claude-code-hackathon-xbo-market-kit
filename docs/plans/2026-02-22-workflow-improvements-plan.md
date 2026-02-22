# Workflow Improvements Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Upgrade xbo-ai-flow plugin from design-only (~40%) to fully autonomous development pipeline with executable skills, hooks, commands, git automation, security scanning, and TDD enforcement.

**Architecture:** Rewrite 4 SKILL.md files with precise step-by-step instructions. Add 4 hooks to hooks.json for code quality interception. Create 6 command shortcuts in `commands/`. Add security scanning and pre-commit scripts. Update all 5 agents with TDD enforcement and enhanced capabilities.

**Tech Stack:** Claude Code Plugin API (agents, skills, hooks, commands), Bash scripts, PHP lint tools, WordPress Coding Standards

**Design doc:** `docs/plans/2026-02-22-workflow-improvements-design.md`

**Base path:** `.claude/plugins/xbo-ai-flow/` (all paths relative to this unless noted)

---

## Phase 1: Executable Skills

### Task 1: Rewrite `/orchestrate` SKILL.md

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/skills/orchestrate/SKILL.md`

**Step 1: Read current SKILL.md**

Read the current file to understand existing structure.

**Step 2: Rewrite SKILL.md with executable instructions**

Replace the entire content of `skills/orchestrate/SKILL.md` with:

```markdown
---
name: orchestrate
description: This skill should be used when the user asks to "implement a feature", "build", "develop", "create a new widget", "add functionality", "orchestrate", or any task requiring the full AI development pipeline. It orchestrates the entire workflow from brainstorming through implementation, verification, review, and documentation.
version: 0.2.0
---

# Orchestrate — Full AI Development Pipeline

## Overview

Entry point for autonomous feature development. Invoke Superpowers process skills with project-specific agent routing, verification loops, and automatic documentation updates.

**Announce at start:** "Using the orchestrate skill to run the full development pipeline."

## Pre-Flight Checks

Before starting, verify:
1. Read `CLAUDE.md` to confirm project conventions
2. Read `docs/plans/2026-02-22-xbo-market-kit-spec.md` for product context
3. Check `docs/metrics/tasks.json` for any in-progress tasks

## Pipeline Steps

Execute these steps in order. Do NOT skip any step.

### Step 1: Record Task Start

Read `docs/metrics/tasks.json`, then add a new task entry:

```json
{
  "id": "YYYY-MM-DD-task-slug",
  "description": "Brief task description",
  "plan": "",
  "started": "YYYY-MM-DDTHH:MM:SSZ",
  "completed": null,
  "duration_minutes": 0,
  "commits": 0,
  "coverage": null,
  "status": "in_progress"
}
```

Write the updated file back. The task slug should be lowercase-hyphenated from the feature name.

### Step 2: Brainstorm

Invoke `superpowers:brainstorming` skill via the Skill tool.

Follow the brainstorming process completely:
- Explore project context
- Ask clarifying questions (one at a time)
- Propose 2-3 approaches
- Present design sections for approval
- Save design doc to `docs/plans/YYYY-MM-DD-<topic>-design.md`

### Step 3: Write Implementation Plan

Invoke `superpowers:writing-plans` skill via the Skill tool.

The plan MUST structure each task as TDD cycle:
- Step 1: Write failing test
- Step 2: Verify it fails
- Step 3: Write minimal implementation
- Step 4: Verify it passes
- Step 5: Commit

Save plan to `docs/plans/YYYY-MM-DD-<topic>-plan.md`.

### Step 4: Execute Plan with Subagents

Invoke `superpowers:subagent-driven-development` skill via the Skill tool.

**Agent Routing Rules:**

Analyze each task in the plan and route to the correct agent:

| Task content keywords | Route to |
|----------------------|----------|
| PHP, class, namespace, hook, filter, action, REST, endpoint, shortcode, admin, cache, transient, API client | `backend-dev` agent |
| CSS, JavaScript, JS, Tailwind, style, responsive, Gutenberg block UI, Elementor widget template, animation, layout | `frontend-dev` agent |
| Mixed PHP + CSS/JS | `backend-dev` first, then `frontend-dev` |

**TDD Enforcement:**

Tell EVERY implementation subagent:
> "REQUIRED: Use superpowers:test-driven-development skill. Write failing test FIRST, then implement. No production code without a failing test."

### Step 5: Verification Loop

After each implementation subtask completes:

1. **Run verifier:** Dispatch the `verifier` agent via Task tool
2. **If FAIL:** Send exact error messages back to the implementing agent. Say: "Fix these errors: [paste errors]. Do NOT change the test — fix the implementation."
3. **If PASS and task involves UI:** Dispatch `integration-tester` agent
4. **If integration FAIL:** Send errors back to implementing agent
5. **If all PASS:** Dispatch `reviewer` agent
6. **If reviewer says REQUEST CHANGES with CRITICAL:** Send back to implementing agent
7. **If reviewer APPROVES:** Proceed to commit

**Maximum 3 retry loops per subtask.** If still failing after 3 retries, stop and ask the user for guidance.

### Step 6: Commit Changes

After reviewer approval:

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add [specific files from the task]
git commit -m "feat: [description from task]

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

### Step 7: Update Documentation

After ALL plan tasks are complete:

1. Invoke `/metrics` skill to update task metrics
2. Invoke `/worklog-update` skill to add worklog entry
3. Invoke `/readme-update` skill to regenerate README.md
4. Commit documentation changes:

```bash
git add docs/ README.md
git commit -m "docs: update metrics, worklog, and README after [feature]

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

### Step 8: Record Task End

Update the task entry in `docs/metrics/tasks.json`:
- Set `completed` to current ISO-8601 timestamp
- Calculate `duration_minutes` from start to now
- Count commits: `git log --oneline --after="[started]" | wc -l`
- Set `status` to "completed"
- Set `plan` to the plan file path

Recalculate totals section.

## Error Recovery

- If brainstorming is declined → stop, ask user what to do
- If plan is rejected → loop back to brainstorming
- If verification fails 3x → stop loop, report errors, ask user
- If reviewer blocks → show review comments, ask user to decide
```

**Step 3: Verify the file renders**

Read back the file and verify:
- YAML frontmatter is valid (name, description, version)
- All steps are numbered and clear
- No broken markdown

**Step 4: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/skills/orchestrate/SKILL.md
git commit -m "feat(ai-flow): rewrite orchestrate skill with executable pipeline

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 2: Rewrite `/metrics` SKILL.md

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/skills/metrics/SKILL.md`
- Modify: `.claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh`

**Step 1: Rewrite SKILL.md**

Replace content of `skills/metrics/SKILL.md` with:

```markdown
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

This returns JSON with `total_input`, `total_output`, `total_tokens`, `total_sessions`, `total_minutes`.

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
```

**Step 2: Update collect-metrics.sh to support --json flag**

Read the current `scripts/collect-metrics.sh`. Add a `--json` output mode. After the existing display code, add:

```bash
# JSON output mode
if [ "${1:-}" = "--json" ]; then
    echo "{\"total_input\":$total_input,\"total_output\":$total_output,\"total_tokens\":$total_tokens,\"sessions\":$total_sessions,\"minutes\":$total_minutes}"
    exit 0
fi
```

Move this check BEFORE the echo display block so `--json` skips the human-readable output.

**Step 3: Verify**

```bash
bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh"
bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --json
```

**Step 4: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/skills/metrics/SKILL.md .claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh
git commit -m "feat(ai-flow): rewrite metrics skill with executable collection logic

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 3: Rewrite `/worklog-update` SKILL.md

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/skills/worklog-update/SKILL.md`

**Step 1: Rewrite SKILL.md**

Replace content of `skills/worklog-update/SKILL.md` with:

```markdown
---
name: worklog-update
description: This skill should be used when the user asks to "update worklog", "add worklog entry", "log work", "document what was done today", "update development journal", or after completing a development task via the orchestrate skill.
version: 0.2.0
---

# Worklog Update — Development Journal

## Overview

Add structured entries to the daily worklog. Each entry documents completed work, decisions made, and metrics.

**Announce at start:** "Using the worklog-update skill to record today's progress."

## Process

### Step 1: Determine Date

Get today's date. Use `date +%Y-%m-%d` or the current date from context. Format: `YYYY-MM-DD`.

### Step 2: Check Existing Worklog

Read `docs/worklog/YYYY-MM-DD.md`. If file does not exist, create it with this template:

```markdown
# YYYY-MM-DD — [Day Summary]

## Summary

[Brief description of the day's focus — 1-2 sentences]

## Completed

## Decisions

## Metrics

| Metric | Value |
|--------|-------|
| Tasks completed | 0 |
| Total dev time | 0m |
| Commits | 0 |

## Tools Used

- Claude Code (brainstorming, planning, implementation)
- Local by Flywheel (WordPress 6.9.1 environment)
- PHPStorm (IDE)
```

### Step 3: Collect Data

**Completed tasks:** Read `docs/metrics/tasks.json`. Filter tasks completed today (where `completed` starts with today's date).

**Commits today:**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git log --oneline --since="YYYY-MM-DDT00:00:00" --until="YYYY-MM-DDT23:59:59"
```

**Decisions:** Review any design docs created today in `docs/plans/`.

### Step 4: Update Completed Section

For each completed task, add a checkbox entry:

```markdown
- [x] Task description (Xm, N commits)
  - Key detail or sub-item
```

If tasks are from a plan, reference the plan file.

### Step 5: Update Metrics Table

| Metric | Value |
|--------|-------|
| Tasks completed | [count from tasks.json] |
| Total dev time | [sum of durations]m |
| Commits | [from git log] |

### Step 6: Update Day Summary

Set the H1 title to reflect today's main focus area. Set the Summary section to 1-2 sentences describing what was accomplished.

### Step 7: Update Worklog Index

Read `docs/worklog/README.md`. If today's entry is not listed, add it:

```markdown
| YYYY-MM-DD | [Day Summary] |
```

If the index file doesn't exist, create it:

```markdown
# Work Log

Daily development journal for XBO Market Kit.

## Entries

| Date | Summary |
|------|---------|
| YYYY-MM-DD | [Summary] |
```

### Step 8: Update Plans Index

Read `docs/plans/README.md`. Check if any plan statuses changed today. Update the status column if needed (In Progress → Completed).
```

**Step 2: Verify file structure**

Read back the file. Verify YAML frontmatter and all 8 steps are present.

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/skills/worklog-update/SKILL.md
git commit -m "feat(ai-flow): rewrite worklog-update skill with executable steps

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 4: Rewrite `/readme-update` SKILL.md

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/skills/readme-update/SKILL.md`

**Step 1: Rewrite SKILL.md**

Replace content of `skills/readme-update/SKILL.md` with:

```markdown
---
name: readme-update
description: This skill should be used when the user asks to "update README", "refresh README", "regenerate README", "update project landing page", or after completing a significant feature via the orchestrate skill.
version: 0.2.0
---

# README Update — Landing Page Generator

## Overview

Generate a visually impactful README.md serving as the GitHub landing page with live metrics, feature status, and architecture diagrams.

**Announce at start:** "Using the readme-update skill to regenerate the project README."

## Process

### Step 1: Collect Data

**Metrics:** Read `docs/metrics/tasks.json` to get:
- `totals.total_tasks` → task count
- `totals.total_duration_minutes` → convert to "Xh Ym" format
- `totals.total_tokens` → format with commas
- `totals.total_commits` → commit count

**Git stats:**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git log --oneline | wc -l | tr -d ' '
```

**Day number:** Calculate from project start (2026-02-22). Day 1 = Feb 22, Day 2 = Feb 23, etc.

**Feature status:** Check which PHP classes exist to determine implementation status:

| Feature | Check for file |
|---------|---------------|
| Live Ticker | `includes/Shortcodes/Ticker.php` |
| Top Movers | `includes/Shortcodes/Movers.php` |
| Mini Orderbook | `includes/Shortcodes/Orderbook.php` |
| Recent Trades | `includes/Shortcodes/Trades.php` |
| Slippage Calculator | `includes/Shortcodes/Slippage.php` |
| API Client | `includes/Api/Client.php` |
| Cache Layer | `includes/Cache/TransientCache.php` |
| REST Endpoints | `includes/Rest/` (any controller files) |

Use ✅ if file exists and has >50 lines, 🔄 if file exists but is skeleton (<50 lines), ⬜ if file does not exist.

**Test results (if available):**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
composer run test 2>&1 | tail -5
```

### Step 2: Generate README

Read the current `README.md` to understand existing structure. Then generate the full README with all 11 sections using the collected data.

**Badge format for metrics:**
```
![Dev Time](https://img.shields.io/badge/Dev%20Time-[VALUE]-blue?style=flat-square&logo=clockify&logoColor=white)
![Tasks](https://img.shields.io/badge/Tasks-[VALUE]-orange?style=flat-square&logo=todoist&logoColor=white)
![Commits](https://img.shields.io/badge/Commits-[VALUE]-lightgrey?style=flat-square&logo=git&logoColor=white)
![Agents](https://img.shields.io/badge/AI%20Agents-5-purple?style=flat-square&logo=anthropic&logoColor=white)
![Skills](https://img.shields.io/badge/Skills-4-teal?style=flat-square&logo=zap&logoColor=white)
```

Replace `[VALUE]` with actual numbers from Step 1. URL-encode spaces as `%20`, hyphens as `--`.

**Feature table status:** Use the status determined in Step 1 for each Shortcode/Block/Elementor column.

**Timeline progress bars:** Calculate per-day progress based on completed tasks:
- 0% = `░░░░░░░░░░░░░░░░░░░░`
- 25% = `█████░░░░░░░░░░░░░░░`
- 50% = `██████████░░░░░░░░░░`
- 75% = `███████████████░░░░░`
- 100% = `████████████████████`

### Step 3: Write README.md

Write the generated content to `README.md` at the project root (`/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/README.md`).

### Step 4: Verify

Read back the file. Check:
- No broken markdown syntax
- All badges have valid URLs (no unencoded special characters)
- Mermaid diagrams have proper ``` fencing
- Feature status matches actual file existence
```

**Step 2: Verify file**

Read back and confirm YAML frontmatter, 4 steps, all data collection commands present.

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/skills/readme-update/SKILL.md
git commit -m "feat(ai-flow): rewrite readme-update skill with executable generation logic

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Phase 2: Hooks and Quality Gates

### Task 5: Create Security Check Script

**Files:**
- Create: `.claude/plugins/xbo-ai-flow/scripts/security-check.sh`

**Step 1: Create the script**

```bash
#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — WordPress Security Scanner
# =============================================================================
# Checks PHP files for common WordPress security issues.
# Usage: bash security-check.sh [file|directory]
# =============================================================================

TARGET="${1:-wp-content/plugins/xbo-market-kit/includes}"
ERRORS=0

echo "🔒 Security Scan: $TARGET"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Find all PHP files
if [ -d "$TARGET" ]; then
    FILES=$(find "$TARGET" -name "*.php" -type f)
elif [ -f "$TARGET" ]; then
    FILES="$TARGET"
else
    echo "Error: Target not found: $TARGET"
    exit 1
fi

for FILE in $FILES; do
    # Check for unescaped output (echo without esc_html/esc_attr/wp_kses)
    if grep -nP 'echo\s+\$(?!this)' "$FILE" 2>/dev/null | grep -vP 'esc_html|esc_attr|esc_url|esc_textarea|wp_kses|esc_js|absint|intval' > /dev/null 2>&1; then
        echo "❌ CRITICAL: Unescaped output in $FILE"
        grep -nP 'echo\s+\$(?!this)' "$FILE" | grep -vP 'esc_html|esc_attr|esc_url|esc_textarea|wp_kses|esc_js|absint|intval'
        ERRORS=$((ERRORS + 1))
    fi

    # Check for unsanitized superglobals
    if grep -nP '\$_(GET|POST|REQUEST|SERVER|COOKIE)\[' "$FILE" 2>/dev/null | grep -vP 'sanitize_|absint|intval|wp_unslash|isset' > /dev/null 2>&1; then
        echo "❌ CRITICAL: Unsanitized superglobal in $FILE"
        grep -nP '\$_(GET|POST|REQUEST|SERVER|COOKIE)\[' "$FILE" | grep -vP 'sanitize_|absint|intval|wp_unslash|isset'
        ERRORS=$((ERRORS + 1))
    fi

    # Check for direct SQL without prepare
    if grep -nP '\$wpdb->(query|get_results|get_var|get_row|get_col)\s*\(' "$FILE" 2>/dev/null | grep -vP 'prepare' > /dev/null 2>&1; then
        echo "⚠️ WARNING: Direct SQL without prepare in $FILE"
        grep -nP '\$wpdb->(query|get_results|get_var|get_row|get_col)\s*\(' "$FILE" | grep -vP 'prepare'
        ERRORS=$((ERRORS + 1))
    fi

    # Check for debug artifacts
    if grep -nP '(var_dump|print_r|console\.log|dd\(|error_log)' "$FILE" > /dev/null 2>&1; then
        echo "⚠️ WARNING: Debug artifact in $FILE"
        grep -nP '(var_dump|print_r|console\.log|dd\(|error_log)' "$FILE"
        ERRORS=$((ERRORS + 1))
    fi
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo "✅ Security scan PASSED — no issues found"
    exit 0
else
    echo "❌ Security scan FAILED — $ERRORS issue(s) found"
    exit 1
fi
```

**Step 2: Make executable**

```bash
chmod +x ".claude/plugins/xbo-ai-flow/scripts/security-check.sh"
```

**Step 3: Test on current plugin code**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
bash .claude/plugins/xbo-ai-flow/scripts/security-check.sh wp-content/plugins/xbo-market-kit/includes 2>&1 || true
```

**Step 4: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/scripts/security-check.sh
git commit -m "feat(ai-flow): add WordPress security scanning script

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 6: Create Pre-Commit Check Script

**Files:**
- Create: `.claude/plugins/xbo-ai-flow/scripts/pre-commit-check.sh`

**Step 1: Create the script**

```bash
#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Pre-Commit Validation
# =============================================================================
# Runs quick checks on staged PHP files before commit.
# Usage: bash pre-commit-check.sh
# =============================================================================

PLUGIN_DIR="wp-content/plugins/xbo-market-kit"
ERRORS=0

echo "🔍 Pre-commit check"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Get staged PHP files in plugin directory
STAGED=$(git diff --cached --name-only --diff-filter=ACMR -- "$PLUGIN_DIR/**/*.php" 2>/dev/null || echo "")

if [ -z "$STAGED" ]; then
    echo "No staged PHP files in plugin — skipping"
    exit 0
fi

for FILE in $STAGED; do
    # Syntax check
    if ! php -l "$FILE" > /dev/null 2>&1; then
        echo "❌ Syntax error: $FILE"
        php -l "$FILE" 2>&1
        ERRORS=$((ERRORS + 1))
    fi

    # Debug artifact check
    if grep -nP '(var_dump|print_r|dd\(|console\.log|error_log)' "$FILE" > /dev/null 2>&1; then
        echo "⚠️ Debug artifact: $FILE"
        grep -nP '(var_dump|print_r|dd\(|console\.log|error_log)' "$FILE"
        ERRORS=$((ERRORS + 1))
    fi

    # Hardcoded secret patterns
    if grep -nPi '(api_key|api_secret|password|token)\s*=\s*["\x27][^"\x27]{8,}' "$FILE" > /dev/null 2>&1; then
        echo "❌ Possible hardcoded secret: $FILE"
        grep -nPi '(api_key|api_secret|password|token)\s*=\s*["\x27]' "$FILE"
        ERRORS=$((ERRORS + 1))
    fi
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo "✅ Pre-commit check PASSED"
    exit 0
else
    echo "❌ Pre-commit check FAILED — $ERRORS issue(s)"
    exit 1
fi
```

**Step 2: Make executable**

```bash
chmod +x ".claude/plugins/xbo-ai-flow/scripts/pre-commit-check.sh"
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/scripts/pre-commit-check.sh
git commit -m "feat(ai-flow): add pre-commit validation script

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 7: Rewrite hooks.json with All Hooks

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/hooks/hooks.json`

**Step 1: Read current hooks.json**

Read the file to confirm current structure.

**Step 2: Rewrite hooks.json**

Replace the entire content with:

```json
{
  "hooks": [
    {
      "type": "PreToolUse",
      "matcher": "Write|Edit",
      "hooks": [
        {
          "type": "command",
          "command": "bash -c 'FILE=\"$TOOL_INPUT_FILE_PATH\"; if [[ \"$FILE\" == *xbo-market-kit*.php ]]; then php -l \"$FILE\" 2>&1 || echo \"BLOCK: PHP syntax error in $FILE\"; fi'",
          "timeout": 10
        }
      ]
    },
    {
      "type": "PostToolUse",
      "matcher": "Write|Edit",
      "hooks": [
        {
          "type": "command",
          "command": "bash -c 'FILE=\"$TOOL_INPUT_FILE_PATH\"; PLUGIN_DIR=\"wp-content/plugins/xbo-market-kit\"; if [[ \"$FILE\" == *xbo-market-kit*.php ]] && command -v \"$PLUGIN_DIR/vendor/bin/phpcbf\" &>/dev/null; then cd \"$(dirname \"$FILE\")\" && \"$PLUGIN_DIR/vendor/bin/phpcbf\" --standard=\"$PLUGIN_DIR/phpcs.xml\" \"$FILE\" 2>/dev/null; fi'",
          "timeout": 30
        }
      ]
    },
    {
      "type": "Stop",
      "matcher": "",
      "hooks": [
        {
          "type": "command",
          "command": "bash -c 'echo \"\\n📊 Session ending.\\n📝 Remember to run: /worklog-update, /readme-update, /metrics\\n💡 Or run /docs to update all documentation at once.\"'"
        }
      ]
    }
  ]
}
```

**Note:** The PreToolUse hook checks PHP syntax before writes. The PostToolUse hook runs phpcbf auto-formatter after writes. The Stop hook reminds about documentation. The UserPromptSubmit hook was removed as it would add overhead to every prompt without clear benefit — we rely on verification loops instead.

**Step 3: Verify JSON is valid**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
python3 -c "import json; json.load(open('.claude/plugins/xbo-ai-flow/hooks/hooks.json')); print('Valid JSON')"
```

**Step 4: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/hooks/hooks.json
git commit -m "feat(ai-flow): add PreToolUse/PostToolUse hooks for PHP quality gates

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Phase 3: Command Shortcuts

### Task 8: Create All 6 Commands

**Files:**
- Create: `.claude/plugins/xbo-ai-flow/commands/feature.md`
- Create: `.claude/plugins/xbo-ai-flow/commands/verify.md`
- Create: `.claude/plugins/xbo-ai-flow/commands/test.md`
- Create: `.claude/plugins/xbo-ai-flow/commands/review.md`
- Create: `.claude/plugins/xbo-ai-flow/commands/docs.md`
- Create: `.claude/plugins/xbo-ai-flow/commands/status.md`

**Step 1: Create commands directory**

```bash
mkdir -p "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/.claude/plugins/xbo-ai-flow/commands"
```

**Step 2: Create `/feature` command**

File: `commands/feature.md`

```markdown
---
name: feature
description: Start the full AI development pipeline for a new feature. Usage: /feature [description]
---

You are starting the full AI development pipeline for a new feature.

**User's feature request:** $ARGUMENTS

Execute the orchestrate skill to run the complete pipeline:

1. Invoke the `xbo-ai-flow:orchestrate` skill via the Skill tool
2. Pass the user's feature description as context
3. Follow ALL steps in the orchestrate skill (record → brainstorm → plan → execute → verify → review → document)

If no arguments were provided, ask the user: "What feature would you like to implement?"
```

**Step 3: Create `/verify` command**

File: `commands/verify.md`

```markdown
---
name: verify
description: Run all code quality checks (PHPCS, PHPStan, PHPUnit, security scan) on the XBO Market Kit plugin
---

Run all quality checks on the XBO Market Kit plugin. Execute these commands sequentially from the plugin directory:

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
```

**Step 1 — PHPCS (WordPress Coding Standards):**
```bash
composer run phpcs 2>&1
```

**Step 2 — PHPStan (Static Analysis Level 6):**
```bash
composer run phpstan 2>&1
```

**Step 3 — PHPUnit Tests:**
```bash
composer run test 2>&1
```

**Step 4 — Security Scan:**
```bash
bash "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/.claude/plugins/xbo-ai-flow/scripts/security-check.sh" includes/ 2>&1
```

Report results in this format:

```
## Verification Results

### PHPCS: [PASS/FAIL]
### PHPStan: [PASS/FAIL]
### PHPUnit: [PASS/FAIL] ([N] tests, [N] assertions)
### Security: [PASS/FAIL]

### Summary: [N]/4 checks passed
```

If any check fails, provide specific error details with file:line references and suggested fixes.
```

**Step 4: Create `/test` command**

File: `commands/test.md`

```markdown
---
name: test
description: Run PHPUnit tests for XBO Market Kit plugin
---

Run PHPUnit tests:

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
composer run test 2>&1
```

Report: test count, assertions, pass/fail status. If tests fail, show the failure details.
```

**Step 5: Create `/review` command**

File: `commands/review.md`

```markdown
---
name: review
description: Run code review on recent changes using Codex CLI and manual checks
---

Perform a code review on recent changes.

**Step 1:** Get the diff of uncommitted or recent changes:
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git diff HEAD 2>/dev/null || git diff HEAD~1 2>/dev/null
```

**Step 2:** If Codex CLI is available, run automated review:
```bash
git diff HEAD~1 2>/dev/null | codex --approval-mode full-auto --quiet "Review this WordPress plugin code. Check for: 1) WordPress Coding Standards 2) Security (XSS, SQL injection, CSRF) 3) Performance 4) PHP 8.1+ practices. Return structured review." 2>&1
```

**Step 3:** Manually review changed files for:
- Input sanitization and output escaping
- Nonce verification on forms
- Capability checks on admin actions
- Hardcoded strings (should use i18n)

**Output format:**
```
## Code Review

### Critical Issues: [N]
### Warnings: [N]
### Recommendation: APPROVE / REQUEST CHANGES
```
```

**Step 6: Create `/docs` command**

File: `commands/docs.md`

```markdown
---
name: docs
description: Update all project documentation (worklog, README, metrics) in one command
---

Update all project documentation. Execute these skills in order:

1. Invoke `xbo-ai-flow:metrics` skill — collect and display development metrics
2. Invoke `xbo-ai-flow:worklog-update` skill — update today's worklog entry
3. Invoke `xbo-ai-flow:readme-update` skill — regenerate README.md with live data

After all three complete, commit the documentation changes:

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add docs/ README.md
git commit -m "docs: update metrics, worklog, and README

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```
```

**Step 7: Create `/status` command**

File: `commands/status.md`

```markdown
---
name: status
description: Show current project status — pending tasks, test results, last commit, metrics summary
---

Show current project status. Collect and display:

**1. Git status:**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git log --oneline -5
git status --short
```

**2. Metrics summary:** Read `docs/metrics/tasks.json` and display totals.

**3. Quick test check:**
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
composer run test 2>&1 | tail -3
```

**4. Feature status:** Check which files exist in `wp-content/plugins/xbo-market-kit/includes/`:
- Api/Client.php
- Cache/TransientCache.php
- Rest/ (any controllers)
- Shortcodes/ (Ticker, Movers, Orderbook, Trades, Slippage)

**Display format:**
```
📋 XBO Market Kit — Project Status
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 Metrics: [N] tasks, [H]h [M]m dev time, [N] commits
🧪 Tests: [PASS/FAIL] ([N] tests)
📝 Last commit: [hash] [message]

📦 Features:
  [✅/⬜] API Client
  [✅/⬜] Cache Layer
  [✅/⬜] REST Endpoints
  [✅/⬜] Ticker Shortcode
  [✅/⬜] Movers Shortcode
  [✅/⬜] Orderbook Shortcode
  [✅/⬜] Trades Shortcode
  [✅/⬜] Slippage Calculator
```
```

**Step 8: Commit all commands**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/commands/
git commit -m "feat(ai-flow): add 6 command shortcuts (/feature, /verify, /test, /review, /docs, /status)

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Phase 4: Agent Updates (TDD + Security + Enhanced Capabilities)

### Task 9: Update backend-dev Agent with TDD Enforcement

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/agents/backend-dev.md`

**Step 1: Read current file**

Read `agents/backend-dev.md` to see existing content.

**Step 2: Add TDD enforcement to system prompt**

After the existing "**After Implementation:**" section, add before it a new section. Insert after the "**Testing:**" section and before "**After Implementation:**":

```markdown
**TDD Enforcement (MANDATORY):**
- REQUIRED SKILL: `superpowers:test-driven-development`
- For EVERY piece of production code, follow Red-Green-Refactor:
  1. Write failing test in `tests/` FIRST
  2. Run test, verify it FAILS (not errors — fails)
  3. Write MINIMAL code to make test pass
  4. Run test, verify it PASSES
  5. Refactor if needed, keep tests green
- NO EXCEPTIONS. Configuration files (phpcs.xml, composer.json) excluded.
- If you wrote production code before a test: DELETE IT and start over.

**Security Awareness:**
- All user input MUST be sanitized: `sanitize_text_field()`, `absint()`, `sanitize_email()`, etc.
- All output MUST be escaped: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`
- All form handlers MUST verify nonces: `wp_verify_nonce()`, `check_admin_referer()`
- All admin actions MUST check capabilities: `current_user_can()`
- All SQL MUST use `$wpdb->prepare()`
- Never trust data from `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/agents/backend-dev.md
git commit -m "feat(ai-flow): add TDD enforcement and security awareness to backend-dev

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 10: Update frontend-dev Agent with TDD Enforcement

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/agents/frontend-dev.md`

**Step 1: Read current file**

Read `agents/frontend-dev.md`.

**Step 2: Add TDD enforcement**

Insert before the "**After Implementation:**" section:

```markdown
**TDD Enforcement (MANDATORY):**
- REQUIRED SKILL: `superpowers:test-driven-development`
- For PHP rendering code: write PHPUnit test FIRST
- For JavaScript: write behavior tests or verify via integration-tester
- Follow Red-Green-Refactor for all testable code
- Configuration and pure CSS files are excluded from TDD requirement

**Output Escaping:**
- All dynamic content in HTML templates MUST be escaped
- Use `esc_html()`, `esc_attr()`, `esc_url()` for PHP output
- Use `wp_kses()` for rich content
- Never output raw user data
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/agents/frontend-dev.md
git commit -m "feat(ai-flow): add TDD enforcement to frontend-dev agent

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 11: Update verifier Agent with Security + TDD Compliance

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/agents/verifier.md`

**Step 1: Read current file**

Read `agents/verifier.md`.

**Step 2: Add Step 4 (security scan) and Step 5 (TDD compliance)**

After the existing Step 3 (PHPUnit), add:

```markdown
Step 4 — Run Security Scan:
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
bash .claude/plugins/xbo-ai-flow/scripts/security-check.sh wp-content/plugins/xbo-market-kit/includes 2>&1
```

Step 5 — TDD Compliance Check:
For each PHP file modified in the last commit (or staged), verify a corresponding test file exists:
- `includes/Api/Client.php` → `tests/Api/ClientTest.php`
- `includes/Cache/TransientCache.php` → `tests/Cache/TransientCacheTest.php`
- Pattern: `includes/Foo/Bar.php` → `tests/Foo/BarTest.php`

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
for FILE in $(git diff --name-only HEAD~1 -- includes/ 2>/dev/null); do
    TEST_FILE=$(echo "$FILE" | sed 's|includes/|tests/|' | sed 's|\.php$|Test.php|')
    if [ ! -f "$TEST_FILE" ]; then
        echo "❌ Missing test: $TEST_FILE for $FILE"
    fi
done
```
```

Update the output format to include 5 checks instead of 3:

```markdown
**Output Format:**

```
## Verification Results

### PHPCS: [PASS/FAIL]
### PHPStan: [PASS/FAIL]
### PHPUnit: [PASS/FAIL] ([N] tests, [N] assertions)
### Security: [PASS/FAIL]
### TDD Compliance: [PASS/FAIL]

### Summary
- Total checks: 5
- Passed: [N]
- Failed: [N]
```
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/agents/verifier.md
git commit -m "feat(ai-flow): add security scan and TDD compliance to verifier

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 12: Update reviewer Agent with TDD Verification

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/agents/reviewer.md`

**Step 1: Read current file**

Read `agents/reviewer.md`.

**Step 2: Add TDD verification to manual checks**

In the "Step 3 — Manual checks" section, add:

```markdown
- Verify TDD was followed: git log should show test files committed WITH or BEFORE implementation files
- Check that new PHP classes in `includes/` have corresponding test files in `tests/`
- If implementation exists without tests → mark as CRITICAL: "Missing tests — TDD violation"
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/agents/reviewer.md
git commit -m "feat(ai-flow): add TDD compliance check to reviewer agent

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 13: Update integration-tester Agent with Coverage Check

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/agents/integration-tester.md`

**Step 1: Read current file**

Read `agents/integration-tester.md`.

**Step 2: Add test coverage check**

After Step 5 (Clean up), add:

```markdown
Step 6 — Check test coverage (if phpunit supports it):
```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/wp-content/plugins/xbo-market-kit"
composer run test -- --coverage-text 2>&1 | grep -E "^\s*(Classes|Methods|Lines):" || echo "Coverage data not available"
```

Include coverage data in the output if available.
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/agents/integration-tester.md
git commit -m "feat(ai-flow): add test coverage check to integration-tester

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Phase 5: ADR System

### Task 14: Create ADR Template and Initialize Architecture Docs

**Files:**
- Create: `docs/architecture/ADR-template.md`
- Modify: `docs/architecture/README.md` (create if not exists)

**Step 1: Check if architecture directory exists**

```bash
ls "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public/docs/architecture/" 2>/dev/null || echo "Directory missing"
```

**Step 2: Create ADR template**

File: `docs/architecture/ADR-template.md`

```markdown
# ADR-NNN: [Title]

**Date:** YYYY-MM-DD
**Status:** Proposed | Accepted | Deprecated | Superseded

## Context

[What is the issue or decision that needs to be made?]

## Decision

[What was decided and why?]

## Consequences

### Positive
- [Benefit 1]

### Negative
- [Trade-off 1]

### Neutral
- [Observation 1]
```

**Step 3: Create or update README.md**

File: `docs/architecture/README.md`

```markdown
# Architecture Decisions

Architecture Decision Records (ADRs) for XBO Market Kit.

## Index

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [ADR-001](ADR-001-server-side-api-calls.md) | Server-side API calls only | Accepted | 2026-02-22 |
| [ADR-002](ADR-002-whitelist-gitignore.md) | Whitelist .gitignore strategy | Accepted | 2026-02-22 |
| [ADR-003](ADR-003-tailwind-cdn.md) | Tailwind CSS via CDN | Accepted | 2026-02-22 |

---

*ADRs follow the template in [ADR-template.md](ADR-template.md).*
```

**Step 4: Create the 3 initial ADRs from existing decisions**

File: `docs/architecture/ADR-001-server-side-api-calls.md`

```markdown
# ADR-001: Server-Side API Calls Only

**Date:** 2026-02-22
**Status:** Accepted

## Context

The XBO Public API (api.xbo.com) does not send CORS headers. Browser-based JavaScript cannot directly call the API.

## Decision

All XBO API calls go through the WordPress backend. The plugin provides WP REST API endpoints that proxy requests to the XBO API. Frontend JavaScript fetches data from the WP REST endpoints, never from api.xbo.com directly.

## Consequences

### Positive
- No CORS issues
- Server-side caching via WordPress transients
- API key management stays server-side (future-proof)

### Negative
- Extra hop adds latency
- WordPress server must handle all API traffic

### Neutral
- Standard pattern for WordPress plugins that consume external APIs
```

File: `docs/architecture/ADR-002-whitelist-gitignore.md`

```markdown
# ADR-002: Whitelist .gitignore Strategy

**Date:** 2026-02-22
**Status:** Accepted

## Context

WordPress core files, uploads, and local configs should not be in the repository. The git root is at `app/public/` which contains WordPress core.

## Decision

Use a whitelist `.gitignore` that ignores everything by default (`*`) and explicitly allows project files with `!` rules. This is safer than blacklisting individual WP core directories.

## Consequences

### Positive
- New WP core files are automatically ignored
- Cannot accidentally commit WP core or uploads
- Clean git status

### Negative
- New project files must be explicitly allowed
- Slightly unusual pattern for developers unfamiliar with whitelist gitignore

### Neutral
- Requires updating .gitignore when adding new project directories
```

File: `docs/architecture/ADR-003-tailwind-cdn.md`

```markdown
# ADR-003: Tailwind CSS via CDN

**Date:** 2026-02-22
**Status:** Accepted

## Context

The hackathon timeline is 7 days. Setting up a proper CSS build pipeline (PostCSS, npm, Tailwind CLI) would consume significant time.

## Decision

Use Tailwind CSS via CDN (`<script src="https://cdn.tailwindcss.com">`) for rapid prototyping. Custom plugin-specific styles use the `.xbo-mk-` prefix.

## Consequences

### Positive
- No npm/node required
- Instant access to all Tailwind utilities
- Faster development during hackathon

### Negative
- CDN adds ~300KB to page load
- Not suitable for production deployment
- Cannot tree-shake unused styles

### Neutral
- Can be migrated to Tailwind CLI build in post-hackathon optimization
```

**Step 5: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add docs/architecture/
git commit -m "docs: add ADR system with template and 3 initial architecture decisions

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Phase 6: Finalize and Update Infrastructure

### Task 15: Update plugin.json Version and Update .gitignore

**Files:**
- Modify: `.claude/plugins/xbo-ai-flow/.claude-plugin/plugin.json`
- Modify: `.gitignore`

**Step 1: Update plugin version**

Read `plugin.json` and change version from `"0.1.0"` to `"0.2.0"`.

**Step 2: Update .gitignore for new directories**

Read `.gitignore`. Verify that the `.claude/plugins/xbo-ai-flow/` section includes `commands/`:

```
# ---------- Claude Code Plugin: xbo-ai-flow ----------
!.claude/
!.claude/plugins/
!.claude/plugins/xbo-ai-flow/
!.claude/plugins/xbo-ai-flow/**
```

The `**` wildcard should already cover `commands/`, but verify.

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add .claude/plugins/xbo-ai-flow/.claude-plugin/plugin.json .gitignore
git commit -m "chore(ai-flow): bump plugin version to 0.2.0

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 16: Update Plans Index and Worklog

**Files:**
- Modify: `docs/plans/README.md`
- Modify: `docs/worklog/2026-02-22.md`

**Step 1: Update plans index**

Read `docs/plans/README.md`. Add new entries:

```markdown
| 2026-02-22 | [Workflow Improvements Design](2026-02-22-workflow-improvements-design.md) | Approved |
| 2026-02-22 | [Workflow Improvements Plan](2026-02-22-workflow-improvements-plan.md) | Completed |
```

**Step 2: Update worklog**

Read `docs/worklog/2026-02-22.md`. Add to the Completed section:

```markdown
- [x] Designed workflow improvements: full autonomy upgrade for xbo-ai-flow plugin
- [x] Implemented workflow improvements plan with 16 tasks across 6 phases
- [x] Rewrote 4 skills with executable step-by-step instructions
- [x] Added PreToolUse/PostToolUse hooks for PHP quality gates
- [x] Created 6 command shortcuts (/feature, /verify, /test, /review, /docs, /status)
- [x] Created security scanning and pre-commit validation scripts
- [x] Updated all 5 agents with TDD enforcement and security awareness
- [x] Created ADR system with 3 initial architecture decisions
```

Update the Decisions section:

```markdown
- **TDD mandatory** — All implementation agents must use superpowers:test-driven-development
- **5-step verification** — phpcs → phpstan → phpunit → security → TDD compliance
- **Command shortcuts** — 6 slash commands for common actions
- **ADR system** — Architecture decisions documented in docs/architecture/
```

**Step 3: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add docs/plans/README.md docs/worklog/2026-02-22.md
git commit -m "docs: update plans index and worklog with workflow improvements

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 17: Update README.md with New Workflow Info

**Files:**
- Modify: `README.md`

**Step 1: Update README**

Read current `README.md`. Update these sections:

1. **AI Development Dashboard** — Update task count badge
2. **Skills table** — Add new commands: `/feature`, `/verify`, `/test`, `/review`, `/docs`, `/status`
3. **Development Timeline** — Update Day 1 progress to 100%:
   ```
   | **Day 1** | Repo setup, plugin scaffold, AI workflow | `████████████████████` 100% |
   ```

**Step 2: Commit**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add README.md
git commit -m "docs: update README with workflow improvements and command shortcuts

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 18: Final Push

**Step 1: Push all changes**

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git push origin main
```

---

## Summary

| Phase | Tasks | Description |
|-------|-------|-------------|
| Phase 1 | 1-4 | Rewrite 4 skills with executable instructions |
| Phase 2 | 5-7 | Security/pre-commit scripts + hooks |
| Phase 3 | 8 | 6 command shortcuts |
| Phase 4 | 9-13 | Update 5 agents (TDD, security, coverage) |
| Phase 5 | 14 | ADR system |
| Phase 6 | 15-18 | Version bump, docs, README, push |

**Total: 18 tasks, ~45 files created/modified**
