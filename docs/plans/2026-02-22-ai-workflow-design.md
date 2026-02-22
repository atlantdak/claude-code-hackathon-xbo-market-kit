# AI Development Workflow Design

**Date:** 2026-02-22
**Status:** Approved

---

## 1. Overview

Hybrid AI-driven development workflow for XBO Market Kit. Uses **Superpowers** for process orchestration (brainstorming → plans → execution → review) and a **custom Claude Code plugin** (`xbo-ai-flow`) for project-specific automation: specialized agents, metrics tracking, README/worklog generation, and hooks.

### Design Principles

- **Fully autonomous:** One task input triggers the entire pipeline
- **Superpowers-first:** Leverage existing process skills, don't reinvent
- **Metrics-driven:** Track time, tokens, and progress per task
- **Visual documentation:** README.md as a landing page with live metrics

## 2. Architecture

```
┌─────────────────────────────────────────────────────┐
│                    USER REQUEST                      │
│              "Implement Live Ticker"                 │
└──────────────────────┬──────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────┐
│            SUPERPOWERS PROCESS LAYER                 │
│  brainstorming → writing-plans → executing-plans     │
│  → subagent-driven-development → verification        │
└──────────────────────┬──────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────┐
│         XBO-AI-FLOW PLUGIN (project-level)           │
│                                                      │
│  SKILLS:                                             │
│  ├── /orchestrate — entry point, wraps Superpowers   │
│  ├── /readme-update — regenerate README.md           │
│  ├── /worklog-update — add worklog entry             │
│  └── /metrics — parse & display session metrics      │
│                                                      │
│  AGENTS:                                             │
│  ├── backend-dev — PHP/WP (wp-* skills, Context7)    │
│  ├── frontend-dev — CSS/JS/Tailwind (Context7)       │
│  ├── verifier — phpcs + phpstan + phpunit            │
│  ├── integration-tester — WP-CLI + browser testing   │
│  └── reviewer — Codex CLI review                     │
│                                                      │
│  HOOKS:                                              │
│  └── Stop hook — reminder to update worklog/metrics  │
└─────────────────────────────────────────────────────┘
```

## 3. Plugin Structure

Location: `.claude/plugins/xbo-ai-flow/` (project-level, committed to git)

```
.claude/plugins/xbo-ai-flow/
├── .claude-plugin/
│   └── plugin.json              # Plugin manifest
├── hooks/
│   └── hooks.json               # Stop hook
├── skills/
│   ├── orchestrate/
│   │   └── SKILL.md             # Main entry point skill
│   ├── readme-update/
│   │   └── SKILL.md             # README.md generator
│   ├── worklog-update/
│   │   └── SKILL.md             # Worklog entry writer
│   └── metrics/
│       └── SKILL.md             # Metrics collector and display
├── agents/
│   ├── backend-dev.md           # PHP/WordPress backend agent
│   ├── frontend-dev.md          # CSS/JS/Tailwind agent
│   ├── verifier.md              # Code quality testing agent
│   ├── integration-tester.md    # WP page testing agent
│   └── reviewer.md              # Code review agent (Codex CLI)
└── scripts/
    └── collect-metrics.sh       # Shell script for parsing session-meta
```

## 4. Skills

### 4.1 `/orchestrate`

**Purpose:** Main entry point. Takes a task description, runs the full pipeline.

**Flow:**
1. Record task start timestamp in `docs/metrics/tasks.json`
2. Invoke Superpowers `brainstorming` → get design approval
3. Invoke Superpowers `writing-plans` → create implementation plan
4. Invoke Superpowers `subagent-driven-development` → execute plan using project agents
5. After completion, invoke `/worklog-update`, `/metrics`, `/readme-update`
6. Record task end timestamp

**Agent routing:** The orchestrate skill includes routing logic:
- PHP/WP backend tasks → `backend-dev` agent
- CSS/JS/UI tasks → `frontend-dev` agent
- After each task → `verifier` agent
- After verifier passes → `integration-tester` agent (if UI-related)
- Before commit → `reviewer` agent

### 4.2 `/readme-update`

**Purpose:** Regenerate README.md with current project state.

**Data sources:**
- `docs/metrics/tasks.json` — task stats (time, tokens, count)
- `git log` — commit count
- Feature status from codebase inspection
- Test results from latest run

**Output sections:**
- Header with badges (shields.io)
- AI Development Stats (time, tasks, tokens, tests, commits)
- Features table with status indicators (✅ 🔄 ⬜)
- Architecture diagram (Mermaid)
- AI Workflow diagram (Mermaid)
- Development Timeline (progress bars)
- Quick Start
- Documentation links
- Footer

### 4.3 `/worklog-update`

**Purpose:** Add entry to `docs/worklog/YYYY-MM-DD.md`.

**Content:**
- Task name and description
- Duration
- Commits made
- Decisions taken
- Issues encountered

### 4.4 `/metrics`

**Purpose:** Collect and aggregate metrics.

**Sources:**
- `~/.claude/usage-data/session-meta/*.json` — tokens, duration per session
- `docs/metrics/tasks.json` — task-level timestamps
- `git log` — commit history

**Output:**
- Update `docs/metrics/tasks.json` with aggregated totals
- Display summary in terminal

## 5. Agents

### 5.1 `backend-dev`

**When:** PHP backend tasks (API client, cache, REST endpoints, shortcodes, admin)

**Skills access:** wp-plugin-development, wp-rest-api, wp-phpstan, wp-performance, Context7

**Context:**
- Plugin lives at `wp-content/plugins/xbo-market-kit/`
- PSR-4 autoload under `XboMarketKit\` namespace
- WordPress Coding Standards enforced
- Server-side only API calls (no CORS)
- Naming conventions from CLAUDE.md

### 5.2 `frontend-dev`

**When:** CSS, JavaScript, Tailwind, Gutenberg block UI, Elementor widget UI

**Skills access:** wp-block-development, wpds, Context7

**Context:**
- Tailwind CSS via CDN (`<script src="https://cdn.tailwindcss.com">`)
- CSS class prefix: `.xbo-mk-`
- Asset handles: `xbo-market-kit-*`
- No npm/node build step

### 5.3 `verifier`

**When:** After every implementation task completes

**Commands:**
```bash
cd wp-content/plugins/xbo-market-kit
composer run phpcs
composer run phpstan
composer run test
```

**Behavior:**
- Returns structured pass/fail for each tool
- If any fail → returns specific errors for retry
- Orc hestrator sends task back to the original agent with error details

### 5.4 `integration-tester`

**When:** After verifier passes, for UI-related tasks

**Process:**
1. Create test page via WP-CLI: `wp post create --post_type=page --post_title="Test" --post_content='[xbo_ticker]' --post_status=publish`
2. Get page URL
3. Open via Chrome DevTools MCP or Playwright MCP
4. Take screenshot
5. Check for PHP errors in page output
6. Clean up test page

**Fallback:** If no browser MCP available, check via `wp eval` or REST API call.

### 5.5 `reviewer`

**When:** After verifier (and integration-tester if applicable), before commit

**Process:**
```bash
git diff HEAD --staged | codex --approval-mode full-auto \
  "Review this diff for: code quality, security issues, \
   WordPress coding standards, performance concerns. \
   Return findings as structured list."
```

**Output:** Structured report with severity levels (critical/warning/info).
- Critical findings → task returned to developer agent
- Warnings → logged but allowed to proceed
- Info → added to commit message

## 6. Metrics System

### 6.1 Data Storage

File: `docs/metrics/tasks.json`

```json
{
  "tasks": [
    {
      "id": "task-slug",
      "plan": "2026-02-23-feature-plan.md",
      "started": "2026-02-23T10:00:00Z",
      "completed": "2026-02-23T11:30:00Z",
      "duration_minutes": 90,
      "session_ids": ["uuid-here"],
      "estimated_tokens": 150000,
      "commits": 3,
      "status": "completed"
    }
  ],
  "totals": {
    "total_tasks": 1,
    "total_duration_minutes": 90,
    "total_tokens": 150000,
    "total_commits": 3
  }
}
```

### 6.2 Token Tracking

Claude Code stores session data in `~/.claude/usage-data/session-meta/{session_id}.json`:
- `input_tokens` and `output_tokens` fields
- `duration_minutes` field
- `start_time` field

The `collect-metrics.sh` script parses these files by session ID to aggregate per-task.

### 6.3 Time Tracking

- Task start/end timestamps recorded by orchestrate skill
- Wall-clock time between start and completion

## 7. Hooks

### Stop Hook (`hooks/hooks.json`)

```json
{
  "hooks": [
    {
      "type": "Stop",
      "matcher": "",
      "hooks": [
        {
          "type": "command",
          "command": "echo '\\n📊 Remember to run /worklog-update and /readme-update before closing.'"
        }
      ]
    }
  ]
}
```

**Note:** Hooks execute shell commands, not skills. The reminder approach keeps things simple. The orchestrate skill handles automatic updates in its flow.

## 8. README.md as Landing Page

### Visual Techniques (GitHub Markdown native)

| Technique | Usage |
|-----------|-------|
| Shields.io badges | Tech stack, status, metrics |
| Mermaid diagrams | Architecture, AI workflow |
| HTML centering | `<div align="center">` |
| HTML tables | Custom layouts |
| Unicode progress bars | `█░` for timeline |
| Emoji section icons | Visual structure |
| `<details>/<summary>` | Collapsible sections |
| Custom shields.io | Dynamic metrics badges |

### README Sections

1. **Hero Header** — centered title, badges, tagline, nav links
2. **AI Development Stats** — metrics dashboard (time, tasks, tokens, tests, commits)
3. **Features** — table with status indicators per delivery method
4. **Shortcode Examples** — code blocks
5. **Architecture** — Mermaid diagram (Browser → REST → Cache → API)
6. **AI Workflow** — Mermaid flowchart of the development process
7. **Development Timeline** — 7-day plan with progress bars
8. **Quick Start** — setup instructions
9. **Documentation** — links to plans, worklog, architecture
10. **Development Commands** — composer scripts
11. **Footer** — credits, links

## 9. Frontend Approach

- **Tailwind CSS via CDN** — `<script src="https://cdn.tailwindcss.com">`
- No npm/node build step required
- Suitable for hackathon demo; not production
- CSS class prefix `.xbo-mk-` for plugin-specific overrides

## 10. Integration with Existing Skills

| Superpowers Skill | Role in Workflow |
|-------------------|-----------------|
| brainstorming | First step — explore requirements |
| writing-plans | Create implementation plan from design |
| subagent-driven-development | Execute plan tasks via agents |
| test-driven-development | Write tests before implementation |
| verification-before-completion | Final check before claiming done |
| requesting-code-review | Trigger reviewer agent |
| finishing-a-development-branch | Merge/commit workflow |

| WordPress Skill | Used By |
|-----------------|---------|
| wp-plugin-development | backend-dev agent |
| wp-rest-api | backend-dev agent |
| wp-block-development | frontend-dev agent |
| wp-phpstan | verifier agent |
| wp-wpcli-and-ops | integration-tester agent |
| wpds | frontend-dev agent |

## 11. Implementation Priority

1. **Plugin manifest and structure** — `.claude-plugin/plugin.json`
2. **Agents** — backend-dev, frontend-dev, verifier, reviewer, integration-tester
3. **Core skills** — orchestrate, readme-update, worklog-update, metrics
4. **Hooks** — Stop hook
5. **Metrics infrastructure** — tasks.json, collect-metrics.sh
6. **README template** — initial generation with /readme-update
