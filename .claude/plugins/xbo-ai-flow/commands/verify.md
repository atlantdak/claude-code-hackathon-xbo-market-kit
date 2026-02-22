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
