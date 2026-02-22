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
