#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — Pre-Commit Validation
# =============================================================================
# Runs quick checks on staged PHP files before commit.
# Usage: bash pre-commit-check.sh
# =============================================================================

PLUGIN_DIR="wp-content/plugins/xbo-market-kit"
ERRORS=0

echo "🔍 Pre-commit check"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Get staged PHP files in plugin directory
STAGED=$(git diff --cached --name-only --diff-filter=ACMR -- "$PLUGIN_DIR/**/*.php" 2>/dev/null || echo "")

if [ -z "$STAGED" ]; then
    echo "No staged PHP files in plugin — skipping"
    exit 0
fi

for FILE in $STAGED; do
    # Syntax check
    if ! php -l "$FILE" > /dev/null 2>&1; then
        echo "❌ Syntax error: $FILE"
        php -l "$FILE" 2>&1
        ERRORS=$((ERRORS + 1))
    fi

    # Debug artifact check
    if grep -nP '(var_dump|print_r|dd\(|console\.log|error_log)' "$FILE" > /dev/null 2>&1; then
        echo "⚠️ Debug artifact: $FILE"
        grep -nP '(var_dump|print_r|dd\(|console\.log|error_log)' "$FILE"
        ERRORS=$((ERRORS + 1))
    fi

    # Hardcoded secret patterns
    if grep -nPi '(api_key|api_secret|password|token)\s*=\s*["\x27][^"\x27]{8,}' "$FILE" > /dev/null 2>&1; then
        echo "❌ Possible hardcoded secret: $FILE"
        grep -nPi '(api_key|api_secret|password|token)\s*=\s*["\x27]' "$FILE"
        ERRORS=$((ERRORS + 1))
    fi
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo "✅ Pre-commit check PASSED"
    exit 0
else
    echo "❌ Pre-commit check FAILED — $ERRORS issue(s)"
    exit 1
fi
