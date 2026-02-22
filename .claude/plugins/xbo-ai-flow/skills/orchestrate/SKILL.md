---
name: orchestrate
description: This skill should be used when the user asks to "implement a feature", "build", "develop", "create a new widget", "add functionality", or any task requiring the full AI development pipeline. It orchestrates the entire workflow from brainstorming through implementation, verification, review, and documentation.
version: 0.1.0
---

# Orchestrate — Full AI Development Pipeline

## Overview

Entry point for autonomous feature development. Wraps Superpowers process skills with project-specific agent routing, verification loops, and automatic documentation updates.

## Pipeline

```
1. Record task start → docs/metrics/tasks.json
2. Brainstorm → superpowers:brainstorming
3. Plan → superpowers:writing-plans
4. Execute → superpowers:subagent-driven-development
   ├── Route to backend-dev or frontend-dev agent
   ├── After each task → verifier agent
   ├── If UI task → integration-tester agent
   └── Before commit → reviewer agent
5. Document → /worklog-update, /metrics, /readme-update
6. Record task end → docs/metrics/tasks.json
```

## Task Recording

Before starting work, record the task in `docs/metrics/tasks.json`:

```json
{
  "id": "task-slug-from-plan",
  "plan": "docs/plans/YYYY-MM-DD-feature-plan.md",
  "started": "ISO-8601 timestamp",
  "status": "in_progress"
}
```

After completion, update with:
```json
{
  "completed": "ISO-8601 timestamp",
  "duration_minutes": calculated,
  "commits": count,
  "status": "completed"
}
```

## Agent Routing

Route subtasks to the appropriate agent based on content:

| Task involves | Agent |
|---------------|-------|
| PHP classes, WP hooks, REST endpoints, caching, shortcode PHP | `backend-dev` |
| CSS, JavaScript, Tailwind, Gutenberg block UI, Elementor UI | `frontend-dev` |
| Code quality checks (phpcs, phpstan, phpunit) | `verifier` |
| Live WP page testing, shortcode rendering, block rendering | `integration-tester` |
| Code review, security audit, pre-commit check | `reviewer` |

## Verification Loop

After each implementation subtask:

1. Run `verifier` agent
2. If FAIL → send errors back to the implementing agent with fix instructions
3. If PASS and UI-related → run `integration-tester` agent
4. If integration FAIL → send errors back to implementing agent
5. If all PASS → run `reviewer` agent
6. If reviewer says REQUEST CHANGES with CRITICAL → send back to implementing agent
7. If reviewer APPROVES → commit the changes

Maximum retry loops: 3 per subtask. If still failing after 3 retries, escalate to user.

## Documentation Updates

After all subtasks complete:

1. Invoke `/worklog-update` skill to add worklog entry
2. Invoke `/metrics` skill to update metrics
3. Invoke `/readme-update` skill to regenerate README.md
4. Final commit with documentation changes

## Process Skills Integration

This skill builds on top of Superpowers. Invoke them in order:

1. `superpowers:brainstorming` — for design exploration
2. `superpowers:writing-plans` — for implementation plan creation
3. `superpowers:subagent-driven-development` — for parallel task execution
4. `superpowers:verification-before-completion` — for final verification
5. `superpowers:finishing-a-development-branch` — for merge/commit workflow
