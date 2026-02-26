# Design: Gutenberg Block Editor UI + Demo Site + Documentation

**Date:** 2026-02-26
**Author:** Claude Opus 4.6 + Dmytro Kishkin
**Status:** Approved

## 1. Context

XBO Market Kit v0.1.0 has 5 working shortcodes, 5 basic Gutenberg blocks (server-side
render only, no editor UI), and 5 REST API endpoints. The plugin needs:

- Full Gutenberg block editor UI (InspectorControls + ServerSideRender)
- Demo site pages using Prime FSE theme patterns and GetWid blocks
- Screenshots and documentation for hackathon presentation
- Elementor widgets (backlog — not installed)

### Current State

| Component | Status |
|-----------|--------|
| Shortcodes (5) | Complete |
| REST API (5 endpoints) | Complete |
| Gutenberg blocks (block.json + render.php) | Basic — no editor UI |
| CSS design system | Complete (PostCSS, dark mode, responsive) |
| Interactivity API JS | Complete (5 modules) |
| Tests (59) | 100% passing |
| Elementor | Not started (plugin not installed) |

### Environment

- WordPress 6.9.1, PHP 8.2, Prime FSE v1.1.2
- GetWid v2.1.3 (42 blocks), MP API Docs v1.0.0
- Elementor: NOT installed
- MotoPress Demo Builder: NOT installed (requires multisite)

## 2. Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Block editor approach | `edit.js` + InspectorControls + ServerSideRender | Best impression for hackathon; SSR already supported |
| JS build tool | `@wordpress/scripts` + JSX | WordPress standard, readable, hot reload |
| Demo structure | Hero + blocks on Home, all blocks on Demo page, separate screenshot pages | Impressive Home, convenient Demo, clean screenshots |
| XBO assets | Download logo, hero-bg, favicon from xbo.com | Professional branding for hackathon |
| Documentation | Compact README + `docs/SHOWCASE.md` gallery | Clean first impression + detailed gallery |
| Orchestration | File-based task tracker (approach B) | Crash-resilient, restartable, no custom orchestrator overhead |
| Screenshots | MacBook 16" viewport (1728x1117) | Standard, good scaling |
| Demo Builder | Backlog | Requires multisite conversion |

## 3. Architecture

### Block Editor UI Structure

```
src/blocks/{name}/
├── edit.js          # React/JSX: InspectorControls + ServerSideRender
├── index.js         # registerBlockType() entry point
└── editor.css       # Editor-only styles (optional)

includes/Blocks/{name}/
├── block.json       # Existing — add editorScript field
└── render.php       # Existing — no changes needed
```

Each `edit.js` provides:
- **InspectorControls** (sidebar panel) with all shortcode parameters
- **ServerSideRender** for live preview in editor
- **Placeholder** state for unconfigured blocks
- **Block icon** from existing SVG assets

### Block Parameters (mirroring shortcodes)

| Block | Parameters | Control Types |
|-------|-----------|---------------|
| **Ticker** | `symbols` (string), `refresh` (number 5-60), `columns` (number 1-4) | TextControl, RangeControl, RangeControl |
| **Movers** | `mode` (gainers/losers), `limit` (number 1-50) | SelectControl, RangeControl |
| **Orderbook** | `symbol` (string), `depth` (number 1-250), `refresh` (number 1-60) | TextControl, RangeControl, RangeControl |
| **Trades** | `symbol` (string), `limit` (number 1-100), `refresh` (number 1-60) | TextControl, RangeControl, RangeControl |
| **Slippage** | `symbol` (string), `side` (buy/sell), `amount` (string) | TextControl, SelectControl, TextControl |

### Build Pipeline

```
package.json (extended):
  @wordpress/scripts → wp-scripts build
  Source: src/blocks/*/index.js
  Output: build/blocks/*/index.js + index.asset.php
  block.json: "editorScript": "file:../../build/blocks/{name}/index.js"
```

### Demo Site Pages

| Page | Template | Content |
|------|----------|---------|
| **Home** | front-page (Prime FSE) | Hero section (XBO branding, gradient), Ticker block, Movers block, CTA section |
| **Demo** | page (full-width) | All 5 blocks with descriptions, anchors, explanatory text |
| **API Docs** | page-canvas | MP API Docs shortcode `[mp-api-docs-offline]` or `[mp-api-docs-online]` |
| **Screenshot pages** (5) | page-canvas | One block each, minimal chrome, for documentation |

## 4. Task Breakdown

### Group 1: Infrastructure (sequential, blocks groups 2-3)

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T01 | Setup `@wordpress/scripts` — add to package.json, configure build, create `src/blocks/` structure | backend-dev | — | wp-block-development |
| T02 | Create base `edit.js` template and shared helper for InspectorControls pattern | frontend-dev | T01 | wp-block-development, context7 |

### Group 2: Gutenberg Block Editor UI (parallel, 5 tasks)

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T03 | Block editor UI: Ticker (symbols, refresh, columns) | frontend-dev | T02 | wp-block-development |
| T04 | Block editor UI: Movers (mode, limit) | frontend-dev | T02 | wp-block-development |
| T05 | Block editor UI: Orderbook (symbol, depth, refresh) | frontend-dev | T02 | wp-block-development |
| T06 | Block editor UI: Trades (symbol, limit, refresh) | frontend-dev | T02 | wp-block-development |
| T07 | Block editor UI: Slippage (symbol, side, amount) | frontend-dev | T02 | wp-block-development |

### Group 3: Verification (after group 2)

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T08 | Build, PHPCS, PHPStan, PHPUnit — full verification | verifier | T03-T07 | verification-before-completion |
| T09 | Integration test: insert blocks on test page, verify rendering | integration-tester | T08 | browsing |

### Group 4: Assets and Resources (parallel with group 2)

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T10 | Download assets from xbo.com: logo SVG, hero background, favicon. Upload to WP Media Library | frontend-dev | — | browsing (chrome) |
| T11 | Analyze Prime FSE patterns + GetWid blocks, select best for demo pages | frontend-dev | — | wp-block-themes |

### Group 5: Demo Pages (after groups 2-4)

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T12 | Home Page — hero section with XBO branding, Ticker + Movers blocks, CTA | frontend-dev | T09, T10, T11 | wp-block-themes, frontend-design |
| T13 | Demo Page — all 5 blocks with descriptions, anchors, full-width layout | frontend-dev | T09, T10 | wp-block-themes |
| T14 | API Docs Page — MP API Docs shortcode, wide template (page-canvas) | frontend-dev | — | wp-plugin-development |
| T15 | Screenshot pages for each block (5 pages, clean, no noise) | frontend-dev | T09 | — |

### Group 6: Screenshots and Documentation (after group 5)

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T16 | Screenshots: frontend + admin editor for each block (1728x1117 viewport) | integration-tester | T12, T13, T15 | browsing (chrome) |
| T17 | Create `docs/SHOWCASE.md` — gallery with all screenshots, descriptions | backend-dev | T16 | — |
| T18 | Update `README.md` — add SHOWCASE link, update metrics, badges | backend-dev | T17 | readme-update |

### Group 7: Finalization

| ID | Task | Agent | Deps | Skills |
|----|------|-------|------|--------|
| T19 | Worklog + metrics update for all session tasks | backend-dev | T18 | worklog-update, metrics |
| T20 | Final code review | reviewer | T19 | requesting-code-review |

### Backlog (non-blocking)

| ID | Task | Status | Note |
|----|------|--------|------|
| B01 | Elementor widgets (5) | backlog | Elementor not installed |
| B02 | Elementor widget screenshots | backlog | After B01 |
| B03 | MotoPress Demo Builder setup | backlog | Requires multisite |
| B04 | Demo video recording | backlog | After all demo pages |

## 5. Dependency Graph

```
T01 → T02 → [T03, T04, T05, T06, T07] → T08 → T09 ─┐
                                                       ├→ T12 → T16 → T17 → T18 → T19 → T20
T10 (parallel) ────────────────────────────────────────┤
T11 (parallel) ────────────────────────────────────────┘
T14 (independent)
T09 → T13 → T16
T09 → T15 → T16
```

## 6. Execution Waves

| Wave | Tasks | Parallelism | Est. Duration |
|------|-------|-------------|---------------|
| 1 | T01 | 1 agent | 15 min |
| 2 | T02 + T10 + T11 + T14 | 4 parallel agents | 20 min |
| 3 | T03, T04, T05, T06, T07 | 5 parallel agents (worktrees) | 30 min |
| 4 | T08 | 1 agent | 10 min |
| 5 | T09 + T13 + T15 | 3 parallel agents | 20 min |
| 6 | T12 | 1 agent | 25 min |
| 7 | T16 | 1 agent | 15 min |
| 8 | T17 → T18 → T19 | sequential | 20 min |
| 9 | T20 | 1 agent | 10 min |

## 7. Per-Task Workflow

Every subagent MUST:

1. Read task-tracker, update status to `in-progress`
2. Execute the task
3. Run `composer run phpcs && composer run phpstan` (if PHP changes)
4. Run `npm run build` (if JS changes)
5. Commit with proper prefix (`feat:`, `fix:`, `docs:`, etc.)
6. Update task-tracker to `done` with commit hash
7. Update worklog entry

## 8. Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| PHPStorm/Claude Code freeze | File-based task tracker survives crashes |
| Token exhaustion mid-task | Each task self-contained, restartable from tracker |
| Git conflicts from parallel worktrees | Each block in separate file, minimal overlap |
| ServerSideRender fails in editor | Fallback to Placeholder component with settings display |
| `@wordpress/scripts` build issues | Use Context7 for latest docs, test build early (T01) |
| XBO API downtime | Cached data in transients, graceful error states already implemented |
