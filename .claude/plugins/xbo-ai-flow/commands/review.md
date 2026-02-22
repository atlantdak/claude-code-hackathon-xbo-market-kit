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
