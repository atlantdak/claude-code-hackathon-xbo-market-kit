---
name: docs
description: Update all project documentation (worklog, README, metrics) in one command
---

Update all project documentation. Execute these skills in order:

1. Invoke `xbo-ai-flow:metrics` skill — collect and display development metrics
2. Invoke `xbo-ai-flow:worklog-update` skill — update today's worklog entry
3. Invoke `xbo-ai-flow:readme-update` skill — regenerate README.md with live data

After all three complete, commit the documentation changes:

```bash
cd "/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
git add docs/ README.md
git commit -m "docs: update metrics, worklog, and README

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```
