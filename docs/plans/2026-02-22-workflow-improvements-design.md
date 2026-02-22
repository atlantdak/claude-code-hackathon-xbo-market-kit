# Workflow Improvements Design — Full Autonomy

> **Status:** Approved
> **Date:** 2026-02-22
> **Context:** Upgrade xbo-ai-flow plugin from design-only (~40% complete) to fully autonomous pipeline

## Goal

Transform the xbo-ai-flow Claude Code plugin from scaffolded design documents into a fully executable, autonomous development pipeline with quality gates, TDD enforcement, git automation, security scanning, and comprehensive metrics tracking.

## Current State

The plugin has excellent architecture with 5 agents, 4 skills, 1 hook, and metrics infrastructure. However, the 4 skills exist only as SKILL.md descriptions — they are not executable. No hooks intercept code quality. No commands provide shortcuts. Git workflow is manual.

**Completion:** ~40% (structure + agents done, skills + automation missing)

---

## Section 1: Executable Skills

### Problem

All 4 skills (orchestrate, metrics, worklog-update, readme-update) contain design documentation but no execution logic. Claude Code loads SKILL.md but the instructions are too vague to produce consistent, reliable results.

### Solution

Rewrite each SKILL.md with precise, deterministic instructions that Claude can follow step-by-step. Add supporting scripts where shell automation is more reliable than LLM inference.

#### 1.1 `/orchestrate` — Main Pipeline Entry Point

**Responsibilities:**
- Accept task description → record in `docs/metrics/tasks.json` (start timestamp)
- Invoke `superpowers:brainstorming` → `superpowers:writing-plans`
- Analyze plan: route PHP/API/REST tasks to `backend-dev`, CSS/JS/Tailwind to `frontend-dev`
- Execute via `superpowers:subagent-driven-development` with agent routing
- Verification loop: `verifier` → `integration-tester` → `reviewer` (max 3 retries)
- On approval: auto-commit with conventional message
- Finalize: update metrics, worklog, readme

**Key implementation details:**
- Agent routing: keyword analysis of task (PHP, API, REST, hook → backend-dev; CSS, JS, Tailwind, block, UI → frontend-dev; mixed → both sequentially)
- Verification loop: if verifier fails → return to implementer with specific errors; if fails 3x → escalate to user
- TDD enforcement: all implementation subagents MUST use `superpowers:test-driven-development`

#### 1.2 `/metrics` — Analytics Collector

**Responsibilities:**
- Parse `~/.claude/usage-data/session-meta/*.json` for token counts
- Update `docs/metrics/tasks.json` with task-level data (start, end, duration, tokens, commits, coverage)
- Calculate totals (total_tasks, total_duration_minutes, total_tokens, total_commits)
- Display formatted ASCII table in terminal

**Supporting script:** `scripts/collect-metrics.sh` (already exists, needs enhancement to output JSON)

#### 1.3 `/worklog-update` — Development Journal

**Responsibilities:**
- Create `docs/worklog/YYYY-MM-DD.md` if missing (with header template)
- Read completed tasks from `docs/metrics/tasks.json` (today's entries)
- Count commits from `git log --since="today" --oneline`
- Append/update sections: Summary, Completed, Decisions, Tools Used
- Update `docs/worklog/README.md` index

#### 1.4 `/readme-update` — Landing Page Generator

**Responsibilities:**
- Collect data: tasks.json metrics, git log stats, test results, feature status
- Feature status detection: scan `wp-content/plugins/xbo-market-kit/includes/` for implemented components
- Generate 11 sections: hero header, badges, AI stats dashboard, features table, shortcode examples, architecture Mermaid diagram, AI workflow Mermaid diagram, development timeline with progress bars, quick start, documentation links, footer
- Populate shields.io badges with real numbers
- Overwrite `README.md`

---

## Section 2: Hooks and Quality Gates

### Problem

Single Stop hook (echo reminder). No code quality interception. No automatic formatting.

### Solution

Add 4 hooks covering the full tool lifecycle.

#### 2.1 PreToolUse: PHP Syntax Check

- **Matcher:** `Write|Edit`
- **Trigger:** Only for files matching `wp-content/plugins/xbo-market-kit/**/*.php`
- **Action:** Run `php -l` on file content before write
- **On failure:** Block write, return syntax error message
- **Rationale:** Catches syntax errors before they're written. Fast (~50ms per file).

#### 2.2 PostToolUse: Auto-Format

- **Matcher:** `Write|Edit`
- **Trigger:** Only for PHP files in plugin directory
- **Action:** Run `phpcbf` (WordPress Coding Standards auto-fixer) on the file
- **On changes:** Show brief diff of what was fixed
- **On clean:** Silent pass
- **Rationale:** Reduces PHPCS failures in verification loop.

#### 2.3 UserPromptSubmit: Context Loader

- **Trigger:** Every user prompt
- **Action:** Check if working in WP plugin context; if so, output brief status (last phpstan result, failing tests count)
- **Rationale:** Keeps agents aware of current code quality state.

#### 2.4 Enhanced Stop Hook

- **Action:** Run metrics collection script (`collect-metrics.sh`)
- **Output:** Session summary (tokens used, duration, tasks completed)
- **Reminder:** Prompt to run `/worklog-update` and `/readme-update`
- **Rationale:** Ensures metrics are captured before session ends.

---

## Section 3: Command Shortcuts and DX

### Problem

No quick entry points. Users must remember skill names.

### Solution

Add 6 commands as markdown files in `commands/` directory.

| Command | Maps To | Description |
|---------|---------|-------------|
| `/feature "desc"` | `/orchestrate` | Full pipeline for a new feature |
| `/verify` | Verifier agent | Run phpcs + phpstan + phpunit + security |
| `/test` | PHPUnit only | Quick test run |
| `/review` | Reviewer agent | Code review with Codex CLI |
| `/docs` | worklog + readme + metrics | Update all documentation |
| `/status` | Status check | Show pending tasks, failing tests, last commit |

### DX Improvements

- **Progress indicators:** Orchestrate outputs "Phase N/7: description..." at each pipeline stage
- **Structured error output:** Verifier returns JSON `{tool: "phpcs", status: "FAIL", errors: [...]}` for programmatic retry
- **Smart agent selection:** Orchestrate analyzes task keywords to auto-select backend-dev or frontend-dev

---

## Section 4: Git Workflow Automation

### Problem

Manual git operations: branch creation, staging, committing, merging.

### Solution

Integrate git operations into the orchestrate pipeline.

#### 4.1 Feature Branch Management

- `/orchestrate` creates branch `feature/{task-slug}` from `main`
- Integration with `superpowers:using-git-worktrees` for workspace isolation
- After reviewer approval: merge to `main` (squash merge for clean history)
- Cleanup: delete feature branch after merge

#### 4.2 Auto-Commit Flow

- After each passed verification loop: auto-stage changed files
- Commit message generated from task: `feat: add ticker shortcode`
- `Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>` header
- After reviewer approval: final commit + optional push

#### 4.3 Pre-Commit Validation Script

- Quick `php -l` on all staged PHP files
- Check for debug artifacts: `var_dump`, `console.log`, `dd()`, `print_r`
- Check for hardcoded secrets: API keys, passwords, tokens in source
- Block commit if any check fails

#### 4.4 Rollback on Failure

- If verification fails 3x: offer `git stash` and rollback
- Save failed attempt details in metrics for analysis
- User decides: retry with different approach or abandon

---

## Section 5: Quality, Security, and TDD

### 5.1 Security Scanning

**New script:** `scripts/security-check.sh` in xbo-ai-flow plugin

**Checks:**
- Unescaped output: `echo $var` without `esc_html()`, `esc_attr()`, `wp_kses()`
- Unsanitized input: `$_GET`, `$_POST`, `$_REQUEST` without `sanitize_*()`, `absint()`, `intval()`
- Missing nonces: form handlers without `wp_verify_nonce()` / `check_admin_referer()`
- Missing capability checks: admin actions without `current_user_can()`
- Hardcoded secrets: patterns matching API keys, passwords, tokens in source
- SQL injection: direct `$wpdb->query()` without `$wpdb->prepare()`

**Integration:** Added as 4th step in verifier agent (phpcs → phpstan → phpunit → security)

### 5.2 Test Coverage Tracking

- PHPUnit with `--coverage-text --coverage-clover=coverage.xml`
- Parse coverage percentage from output
- Record in `tasks.json` per-task: `"coverage": "78%"`
- Alert if coverage drops below 60% threshold
- Display in README.md badge

### 5.3 Architecture Decision Records (ADR)

- Template: `docs/architecture/ADR-NNN-title.md`
- Structure: Context → Decision → Consequences → Status
- Auto-record when orchestrate encounters architectural decisions
- Index maintained in `docs/architecture/README.md`

### 5.4 TDD Integration

**Core principle:** `superpowers:test-driven-development` is MANDATORY for all implementation agents.

**Agent integration:**
- Both `backend-dev` and `frontend-dev` system prompts include: `REQUIRED SKILL: superpowers:test-driven-development`
- Agents MUST follow Red-Green-Refactor cycle for every piece of production code
- Exception: configuration files (phpcs.xml, phpstan.neon, composer.json)

**Orchestrate integration:**
- Plans generated via `superpowers:writing-plans` structure each task as TDD cycle:
  - Step 1: Write failing test
  - Step 2: Run test, verify it fails
  - Step 3: Write minimal implementation
  - Step 4: Run test, verify it passes
  - Step 5: Refactor if needed
  - Step 6: Commit

**Verifier TDD compliance check:**
- For each new/modified PHP file in `includes/`: verify corresponding test exists in `tests/`
- Mapping: `includes/Api/Client.php` → `tests/Api/ClientTest.php`
- If test not found → FAIL with specific message

**Reviewer TDD compliance check:**
- Git diff analysis: tests must be committed WITH or BEFORE implementation
- If implementation without tests → REQUEST CHANGES

**Bug fixes:**
- Orchestrate requires: failing test reproducing bug → fix → verify
- Integration with `superpowers:systematic-debugging` → TDD → fix

### 5.5 Parallel Execution

- If plan contains independent tasks (backend + frontend): use `superpowers:dispatching-parallel-agents`
- Orchestrate analyzes task dependencies
- Independent tasks → parallel, dependent tasks → sequential
- Result aggregation before verification loop

---

## Implementation Priority

| Phase | Components | Estimated Effort |
|-------|-----------|-----------------|
| **Phase 1** | 4 executable skills (orchestrate, metrics, worklog-update, readme-update) | 4-6 hours |
| **Phase 2** | 4 hooks (PreToolUse, PostToolUse, UserPromptSubmit, Stop) | 2-3 hours |
| **Phase 3** | 6 command shortcuts + DX improvements | 2-3 hours |
| **Phase 4** | Git automation (branches, auto-commit, rollback) | 2-3 hours |
| **Phase 5** | Security scanning + TDD enforcement + test coverage | 2-3 hours |
| **Phase 6** | ADR system + parallel execution | 1-2 hours |
| **Total** | | **13-20 hours** |

---

## Files to Create/Modify

### New Files
- `commands/feature.md` — /feature command
- `commands/verify.md` — /verify command
- `commands/test.md` — /test command
- `commands/review.md` — /review command
- `commands/docs.md` — /docs command
- `commands/status.md` — /status command
- `scripts/security-check.sh` — Security scanning script
- `scripts/pre-commit-check.sh` — Pre-commit validation
- `docs/architecture/ADR-template.md` — ADR template

### Modified Files
- `skills/orchestrate/SKILL.md` — Full rewrite with execution logic
- `skills/metrics/SKILL.md` — Full rewrite with data collection
- `skills/worklog-update/SKILL.md` — Full rewrite with file I/O
- `skills/readme-update/SKILL.md` — Full rewrite with generation logic
- `hooks/hooks.json` — Add 3 new hooks, enhance Stop hook
- `agents/backend-dev.md` — Add TDD requirement + security awareness
- `agents/frontend-dev.md` — Add TDD requirement
- `agents/verifier.md` — Add security scanning + TDD compliance check
- `agents/reviewer.md` — Add TDD compliance verification
- `agents/integration-tester.md` — Add test coverage check
- `scripts/collect-metrics.sh` — Enhance to output JSON format
- `.claude-plugin/plugin.json` — Update version to 0.2.0

---

## Success Criteria

1. `/feature "add ticker shortcode"` triggers full autonomous pipeline
2. TDD enforced: no production code without failing test
3. Verification loop catches and auto-fixes code quality issues
4. Git workflow automated: feature branches, auto-commit, merge
5. Security scanning catches common WordPress vulnerabilities
6. Metrics accurately track time, tokens, coverage per task
7. README.md auto-updates with live metrics after each feature
8. Worklog auto-records daily progress
