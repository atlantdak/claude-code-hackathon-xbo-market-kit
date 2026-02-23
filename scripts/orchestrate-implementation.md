# Autonomous Implementation Orchestrator

You are executing the XBO Market Kit implementation plan autonomously.

## Context

- **Plan:** `docs/plans/2026-02-23-xbo-market-kit-implementation-plan.md`
- **Design:** `docs/plans/2026-02-23-xbo-market-kit-full-design.md`
- **Spec:** `docs/plans/2026-02-22-xbo-market-kit-spec.md`
- **GitHub Issues:** #5 through #15 (Tasks 1-11)
- **Working directory:** `/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public`
- **Plugin directory:** `wp-content/plugins/xbo-market-kit/`

## Instructions

Read the implementation plan file first. Then execute ALL 11 tasks in order, respecting the parallelism map:

### Phase 1: Foundation (sequential)
1. Execute Task 1 (Plugin Bootstrap) — GitHub Issue #5
2. Execute Task 2 (ApiClient) — GitHub Issue #6
3. Execute Task 3 (CacheManager) — GitHub Issue #7

### Phase 2: REST API (parallel)
4. Execute Tasks 4 and 5 in PARALLEL using subagents:
   - Task 4 (Ticker + Movers REST) — GitHub Issue #8
   - Task 5 (Orderbook + Trades + Slippage REST) — GitHub Issue #9

### Phase 3: Shortcodes + Frontend (parallel)
5. Execute Tasks 6, 7, and 8 in PARALLEL using subagents:
   - Task 6 (Ticker + Movers Shortcodes + JS) — GitHub Issue #10
   - Task 7 (Orderbook + Trades Shortcodes + JS) — GitHub Issue #11
   - Task 8 (Slippage Calculator Shortcode + JS) — GitHub Issue #12

### Phase 4: Blocks + Admin (parallel)
6. Execute Tasks 9 and 10 in PARALLEL using subagents:
   - Task 9 (Gutenberg Blocks) — GitHub Issue #13
   - Task 10 (Admin Settings + Demo Page) — GitHub Issue #14

### Phase 5: Polish (sequential)
7. Execute Task 11 (Code Quality + Final Verification) — GitHub Issue #15

## For EACH task:

1. Read the task details from the implementation plan
2. Use the `superpowers:subagent-driven-development` skill to dispatch subagents
3. Use the `superpowers:test-driven-development` skill — write tests FIRST
4. After implementation: run `composer run test`, `composer run phpcs`, `composer run phpstan` from the plugin directory
5. Commit with descriptive message ending with `Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>`
6. Close the corresponding GitHub Issue with a completion comment including duration and commits count

## After ALL tasks complete:

1. Run final verification:
   ```bash
   cd wp-content/plugins/xbo-market-kit
   composer run test
   composer run phpcs
   composer run phpstan
   ```

2. Test REST endpoints:
   ```bash
   curl -s http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/ticker?symbols=BTC/USDT | head -c 200
   curl -s http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/movers?mode=gainers&limit=3 | head -c 200
   curl -s http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/orderbook?symbol=BTC_USDT&depth=5 | head -c 200
   curl -s "http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/trades?symbol=BTC/USDT&limit=3" | head -c 200
   curl -s "http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/slippage?symbol=BTC_USDT&side=buy&amount=1" | head -c 200
   ```

3. Update docs:
   - Use `/metrics` skill
   - Use `/worklog-update` skill
   - Use `/readme-update` skill

4. Create git tag `v0.2.0-mvp` and push

5. Push all changes to GitHub

## Rules

- Do NOT ask interactive questions. Make reasonable decisions based on the plan.
- Use the WordPress skills from Context7 when you need WP API docs.
- Follow WordPress Coding Standards (PHPCS).
- All code in English. All PHP files must have `declare(strict_types=1);`.
- Use `XboMarketKit\` namespace for all classes.
- CSS prefix: `.xbo-mk-*`
- REST namespace: `xbo/v1`
- Use Tailwind CSS CDN for styling.
- Use WP Interactivity API for frontend reactivity.
