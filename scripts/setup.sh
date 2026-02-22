#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO Market Kit — Project Setup Script
# =============================================================================
# Downloads WordPress core and installs plugin dependencies.
# Run from the project root (app/public/): bash scripts/setup.sh
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
PLUGIN_DIR="$PROJECT_ROOT/wp-content/plugins/xbo-market-kit"
WP_VERSION="6.9.1"

echo "============================================="
echo "  XBO Market Kit — Project Setup"
echo "============================================="
echo ""
echo "Project root: $PROJECT_ROOT"
echo "Plugin dir:   $PLUGIN_DIR"
echo ""

# --- Check prerequisites ---
check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo "ERROR: '$1' is not installed or not in PATH."
        echo ""
        echo "If using Local by Flywheel, open the site shell first:"
        echo "  Local > Right-click site > Open Site Shell"
        echo ""
        exit 1
    fi
}

echo "[1/4] Checking prerequisites..."
check_command php
check_command composer
echo "  PHP:      $(php -r 'echo PHP_VERSION;')"
echo "  Composer: $(composer --version 2>/dev/null | head -1)"

if command -v wp &> /dev/null; then
    echo "  WP-CLI:   $(wp --version 2>/dev/null || echo 'available')"
else
    echo "  WP-CLI:   not found (optional — WP core must be installed manually)"
fi
echo ""

# --- Download WordPress core ---
echo "[2/4] Checking WordPress core..."
if [ -f "$PROJECT_ROOT/wp-includes/version.php" ]; then
    CURRENT_VERSION=$(php -r "include '$PROJECT_ROOT/wp-includes/version.php'; echo \$wp_version;")
    echo "  WordPress $CURRENT_VERSION already installed. Skipping download."
else
    if command -v wp &> /dev/null; then
        echo "  Downloading WordPress $WP_VERSION..."
        wp core download --version="$WP_VERSION" --path="$PROJECT_ROOT" --skip-content
        echo "  WordPress $WP_VERSION downloaded."
    else
        echo "  WARNING: WP-CLI not found. Please install WordPress $WP_VERSION manually."
        echo "  Download from: https://wordpress.org/wordpress-$WP_VERSION.tar.gz"
    fi
fi
echo ""

# --- Install default themes ---
echo "[3/4] Checking themes..."
THEMES_DIR="$PROJECT_ROOT/wp-content/themes"
if [ -d "$THEMES_DIR/twentytwentyfive" ]; then
    echo "  Default themes present. Skipping."
else
    if command -v wp &> /dev/null; then
        echo "  Installing default theme (twentytwentyfive)..."
        wp theme install twentytwentyfive --path="$PROJECT_ROOT" 2>/dev/null || \
            echo "  Could not install theme via WP-CLI (may need active WP install). Skipping."
    else
        echo "  WARNING: No default themes found. Install manually if needed."
    fi
fi
echo ""

# --- Install plugin composer dependencies ---
echo "[4/4] Installing plugin dependencies..."
if [ -f "$PLUGIN_DIR/composer.json" ]; then
    cd "$PLUGIN_DIR"
    composer install --no-interaction --prefer-dist
    echo ""
    echo "  Plugin dependencies installed."
else
    echo "  WARNING: No composer.json found in plugin directory."
    echo "  Expected: $PLUGIN_DIR/composer.json"
fi
echo ""

echo "============================================="
echo "  Setup Complete!"
echo "============================================="
echo ""
echo "Next steps:"
echo "  1. Ensure wp-config.php exists (Local by Flywheel creates it automatically)"
echo "  2. Open the project in PHPStorm (root: $PROJECT_ROOT)"
echo "  3. Activate the plugin in WP Admin"
echo "  4. Start developing in: $PLUGIN_DIR"
echo ""
echo "Development commands (from plugin dir):"
echo "  composer run phpcs    — Check code style"
echo "  composer run phpstan  — Static analysis"
echo "  composer run test     — Run tests"
echo ""
