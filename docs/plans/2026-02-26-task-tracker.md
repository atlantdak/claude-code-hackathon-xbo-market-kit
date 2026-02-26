# Task Tracker — 2026-02-26 Session

> **For Claude:** On session start, read this file to determine which tasks need work.
> Pick the first task with status `pending` whose dependencies are all `done`.
> Update status to `in-progress` before starting. Update to `done` with commit hash when complete.

## Active Tasks

| ID | Task | Status | Agent | Deps | Commit | Notes |
|----|------|--------|-------|------|--------|-------|
| T01 | Setup @wordpress/scripts build pipeline | done | backend-dev | — | 07ddd48 | |
| T02 | Create base edit.js template + shared helper | done | frontend-dev | T01 | 1a3ff0d | |
| T03 | Block editor UI: Ticker | done | frontend-dev | T02 | 1a3ff0d | verified in T02 |
| T04 | Block editor UI: Movers | done | frontend-dev | T02 | 55874fd | |
| T05 | Block editor UI: Orderbook | done | frontend-dev | T02 | 305cb0d | |
| T06 | Block editor UI: Trades | done | frontend-dev | T02 | 08998ca | |
| T07 | Block editor UI: Slippage | done | frontend-dev | T02 | 88dadce | |
| T08 | Full verification (build + PHPCS + PHPStan + PHPUnit) | in-progress | verifier | T03-T07 | | |
| T09 | Integration test: blocks on test page | pending | integration-tester | T08 | | |
| T10 | Download XBO assets (logo, hero-bg, favicon) | done | frontend-dev | — | 04bb964 | |
| T11 | Analyze Prime FSE patterns + GetWid for demo | done | frontend-dev | — | — | 60+ patterns, 41 GetWid blocks, page-canvas template |
| T12 | Home Page: hero + Ticker + Movers + CTA | pending | frontend-dev | T09,T10,T11 | | |
| T13 | Demo Page: all 5 blocks with descriptions | pending | frontend-dev | T09,T10 | | |
| T14 | API Docs Page: MP API Docs shortcode | done | frontend-dev | — | — | page ID 10 |
| T15 | Screenshot pages (5 clean pages, one block each) | pending | frontend-dev | T09 | | |
| T16 | Take screenshots (frontend + editor, 1728x1117) | pending | integration-tester | T12,T13,T15 | | |
| T17 | Create docs/SHOWCASE.md with screenshot gallery | pending | backend-dev | T16 | | |
| T18 | Update README.md (SHOWCASE link, metrics, badges) | pending | backend-dev | T17 | | |
| T19 | Worklog + metrics update | pending | backend-dev | T18 | | |
| T20 | Final code review | pending | reviewer | T19 | | |

## Backlog

| ID | Task | Status | Note |
|----|------|--------|------|
| B01 | Elementor widgets (5) | backlog | Elementor not installed |
| B02 | Elementor widget screenshots | backlog | After B01 |
| B03 | MotoPress Demo Builder setup | backlog | Requires multisite |
| B04 | Demo video recording | backlog | After all demo pages |

## Execution Waves

| Wave | Tasks | Mode |
|------|-------|------|
| 1 | T01 | sequential |
| 2 | T02 + T10 + T11 + T14 | 4 parallel |
| 3 | T03, T04, T05, T06, T07 | 5 parallel (worktrees) |
| 4 | T08 | sequential |
| 5 | T09 + T13 + T15 | 3 parallel |
| 6 | T12 | sequential |
| 7 | T16 | sequential |
| 8 | T17 > T18 > T19 | sequential chain |
| 9 | T20 | sequential |

## Status Legend

- `pending` — not started
- `in-progress` — agent working on it
- `done` — completed and committed
- `blocked` — dependency not met or issue found
- `backlog` — deferred, not blocking release
