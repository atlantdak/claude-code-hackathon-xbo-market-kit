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

### Step 1: Record Task Start + Create GitHub Issue

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
  "cost_usd": 0,
  "coverage": null,
  "issue_number": null,
  "status": "in_progress"
}
```

Write the updated file back. The task slug should be lowercase-hyphenated from the feature name.

**Create GitHub Issue:** Use `mcp__plugin_github_github__issue_write` to create an Issue:
- **owner:** `atlantdak`
- **repo:** `claude-code-hackathon-xbo-market-kit`
- **method:** `create`
- **title:** Task description (short, imperative)
- **body:** Include task ID, plan link (if any), started timestamp
- **labels:** `["ai-task"]` + type label (`feat`, `fix`, `chore`, `infra`, `docs`)

Save the returned `issue_number` into the task entry in `tasks.json`.

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

### Step 8: Record Task End + Close GitHub Issue

Run `bash ".claude/plugins/xbo-ai-flow/scripts/collect-metrics.sh" --json` to get fresh `cost_total`.

Update the task entry in `docs/metrics/tasks.json`:
- Set `completed` to current ISO-8601 timestamp
- Calculate `duration_minutes` from start to now
- Count commits: `git log --oneline --after="[started]" | wc -l`
- Set `status` to "completed"
- Set `plan` to the plan file path
- Set `cost_usd` — estimate proportionally: `(duration_minutes / total_duration_all_tasks) * cost_total`

Recalculate totals section (including `total_cost_usd`).

**Close GitHub Issue:** Use `mcp__plugin_github_github__issue_write` to close the Issue:
- **method:** `update`
- **issue_number:** from task entry
- **state:** `closed`
- **state_reason:** `completed`

Then add a completion comment using `mcp__plugin_github_github__add_issue_comment`:

```markdown
## Completion Report

| Metric | Value |
|--------|-------|
| Duration | Xm |
| Commits | N |
| Cost | $X.XX |

🤖 Closed by AI orchestration pipeline
```

## Error Recovery

- If brainstorming is declined → stop, ask user what to do
- If plan is rejected → loop back to brainstorming
- If verification fails 3x → stop loop, report errors, ask user
- If reviewer blocks → show review comments, ask user to decide
