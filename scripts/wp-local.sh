#!/usr/bin/env bash
# =============================================================================
# WP-CLI wrapper for Local by Flywheel
# =============================================================================
# Resolves PHP binary, php.ini (with MySQL socket), and WP path automatically.
# Usage: scripts/wp-local.sh <wp-cli-arguments...>
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_PATH="$(dirname "$SCRIPT_DIR")"

# Local by Flywheel site ID (from sites.json)
LOCAL_SITE_ID="1z-3Wmq6u"

# Paths to Local's bundled binaries
PHP_BIN="/Users/atlantdak/Library/Application Support/Local/lightning-services/php-8.2.27+1/bin/darwin-arm64/bin/php"
PHP_INI="/Users/atlantdak/Library/Application Support/Local/run/${LOCAL_SITE_ID}/conf/php/php.ini"
WP_CLI="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp"

# Validate required files exist
if [ ! -f "$PHP_BIN" ]; then
    echo "Error: PHP binary not found at $PHP_BIN" >&2
    echo "Is Local by Flywheel installed?" >&2
    exit 1
fi

if [ ! -f "$PHP_INI" ]; then
    echo "Error: php.ini not found at $PHP_INI" >&2
    echo "Is the Local site running? (Site ID: $LOCAL_SITE_ID)" >&2
    exit 1
fi

if [ ! -f "$WP_CLI" ]; then
    echo "Error: WP-CLI not found at $WP_CLI" >&2
    exit 1
fi

# Execute WP-CLI with Local's PHP and config
exec "$PHP_BIN" -c "$PHP_INI" "$WP_CLI" --path="$WP_PATH" "$@"
