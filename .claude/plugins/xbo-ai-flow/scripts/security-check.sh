#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO AI Flow — WordPress Security Scanner
# =============================================================================
# Checks PHP files for common WordPress security issues.
# Usage: bash security-check.sh [file|directory]
# =============================================================================

TARGET="${1:-wp-content/plugins/xbo-market-kit/includes}"
ERRORS=0

echo "🔒 Security Scan: $TARGET"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Find all PHP files
if [ -d "$TARGET" ]; then
    FILES=$(find "$TARGET" -name "*.php" -type f)
elif [ -f "$TARGET" ]; then
    FILES="$TARGET"
else
    echo "Error: Target not found: $TARGET"
    exit 1
fi

for FILE in $FILES; do
    # Check for unescaped output (echo without esc_html/esc_attr/wp_kses)
    if grep -nP 'echo\s+\$(?!this)' "$FILE" 2>/dev/null | grep -vP 'esc_html|esc_attr|esc_url|esc_textarea|wp_kses|esc_js|absint|intval' > /dev/null 2>&1; then
        echo "❌ CRITICAL: Unescaped output in $FILE"
        grep -nP 'echo\s+\$(?!this)' "$FILE" | grep -vP 'esc_html|esc_attr|esc_url|esc_textarea|wp_kses|esc_js|absint|intval'
        ERRORS=$((ERRORS + 1))
    fi

    # Check for unsanitized superglobals
    if grep -nP '\$_(GET|POST|REQUEST|SERVER|COOKIE)\[' "$FILE" 2>/dev/null | grep -vP 'sanitize_|absint|intval|wp_unslash|isset' > /dev/null 2>&1; then
        echo "❌ CRITICAL: Unsanitized superglobal in $FILE"
        grep -nP '\$_(GET|POST|REQUEST|SERVER|COOKIE)\[' "$FILE" | grep -vP 'sanitize_|absint|intval|wp_unslash|isset'
        ERRORS=$((ERRORS + 1))
    fi

    # Check for direct SQL without prepare
    if grep -nP '\$wpdb->(query|get_results|get_var|get_row|get_col)\s*\(' "$FILE" 2>/dev/null | grep -vP 'prepare' > /dev/null 2>&1; then
        echo "⚠️ WARNING: Direct SQL without prepare in $FILE"
        grep -nP '\$wpdb->(query|get_results|get_var|get_row|get_col)\s*\(' "$FILE" | grep -vP 'prepare'
        ERRORS=$((ERRORS + 1))
    fi

    # Check for debug artifacts
    if grep -nP '(var_dump|print_r|console\.log|dd\(|error_log)' "$FILE" > /dev/null 2>&1; then
        echo "⚠️ WARNING: Debug artifact in $FILE"
        grep -nP '(var_dump|print_r|console\.log|dd\(|error_log)' "$FILE"
        ERRORS=$((ERRORS + 1))
    fi
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ $ERRORS -eq 0 ]; then
    echo "✅ Security scan PASSED — no issues found"
    exit 0
else
    echo "❌ Security scan FAILED — $ERRORS issue(s) found"
    exit 1
fi
