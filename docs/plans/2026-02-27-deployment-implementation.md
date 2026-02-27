# Deployment Pipeline Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create `scripts/deploy.sh` — a single bash script with subcommands for deploying WordPress plugins, theme, assets, and database to production.

**Architecture:** One self-contained bash script with functions per subcommand. Config loaded from `scripts/deploy.conf`. All rsync operations are dry-run by default; `--confirm` flag triggers real execution. DB operations require double confirmation.

**Tech Stack:** Bash, rsync, SSH, WP-CLI

**Design doc:** `docs/plans/2026-02-27-deployment-design.md`

---

### Task 1: Create config template and config file

**Files:**
- Create: `scripts/deploy.conf.example`
- Create: `scripts/deploy.conf` (local only, gitignored)

**Step 1: Create config template**

```bash
# scripts/deploy.conf.example
# Copy to deploy.conf and fill in your values.
# deploy.conf is gitignored — never commit credentials.

REMOTE_HOST="root@YOUR_SERVER_IP"
REMOTE_PATH="/var/www/html"
REMOTE_OWNER="www-data:www-data"
LOCAL_URL="http://your-local-site.local"
REMOTE_URL="https://your-domain.com"
```

**Step 2: Create actual config**

```bash
# scripts/deploy.conf
REMOTE_HOST="root@178.62.244.240"
REMOTE_PATH="/var/www/html"
REMOTE_OWNER="www-data:www-data"
LOCAL_URL="http://claude-code-hackathon-xbo-market-kit.local"
REMOTE_URL="https://kishkin.dev"
```

**Step 3: Verify deploy.conf is gitignored**

Run: `cd /Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public && git status scripts/`
Expected: `deploy.conf.example` shows as untracked, `deploy.conf` does NOT appear.

**Step 4: Commit**

```bash
git add scripts/deploy.conf.example
git commit -m "chore: add deployment config template"
```

---

### Task 2: Script skeleton with argument parsing and config loading

**Files:**
- Create: `scripts/deploy.sh`

**Step 1: Write the script skeleton**

```bash
#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO Market Kit — Deployment Script
# =============================================================================
# Usage: scripts/deploy.sh <command> [options]
#
# Commands:
#   full              Deploy plugins + theme + assets (no DB)
#   plugins [name]    Deploy all plugins or a specific one
#   theme             Deploy theme prime-fse
#   assets            Deploy xbo-market-kit/assets/ only
#   db                Database sync (requires double confirmation)
#   status            Show remote server state
#
# Flags:
#   --confirm         Execute for real (default is dry-run)
#   --no-cache-flush  Skip LiteSpeed cache purge
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONF_FILE="$SCRIPT_DIR/deploy.conf"

# --- Defaults ---
DRY_RUN=true
CACHE_FLUSH=true

# --- Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# --- Helpers ---
info()    { echo -e "${CYAN}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; }
die()     { error "$@"; exit 1; }

# --- Load config ---
load_config() {
    if [[ ! -f "$CONF_FILE" ]]; then
        die "Config not found: $CONF_FILE"
        echo "Copy deploy.conf.example to deploy.conf and fill in values."
    fi
    # shellcheck source=deploy.conf
    source "$CONF_FILE"

    # Validate required vars
    for var in REMOTE_HOST REMOTE_PATH REMOTE_OWNER LOCAL_URL REMOTE_URL; do
        if [[ -z "${!var:-}" ]]; then
            die "Missing required config: $var"
        fi
    done
}

# --- Parse arguments ---
COMMAND=""
COMMAND_ARG=""

parse_args() {
    if [[ $# -lt 1 ]]; then
        usage
        exit 1
    fi

    COMMAND="$1"
    shift

    while [[ $# -gt 0 ]]; do
        case "$1" in
            --confirm)
                DRY_RUN=false
                ;;
            --no-cache-flush)
                CACHE_FLUSH=false
                ;;
            *)
                COMMAND_ARG="$1"
                ;;
        esac
        shift
    done
}

usage() {
    echo "Usage: scripts/deploy.sh <command> [options]"
    echo ""
    echo "Commands:"
    echo "  full              Deploy plugins + theme + assets (no DB)"
    echo "  plugins [name]    Deploy all plugins or a specific one"
    echo "  theme             Deploy theme prime-fse"
    echo "  assets            Deploy xbo-market-kit/assets/ only"
    echo "  db                Database sync (requires double confirmation)"
    echo "  status            Show remote server state"
    echo ""
    echo "Flags:"
    echo "  --confirm         Execute for real (default is dry-run)"
    echo "  --no-cache-flush  Skip LiteSpeed cache purge"
}

# --- SSH helper ---
remote_exec() {
    ssh -o ConnectTimeout=10 "$REMOTE_HOST" "$@"
}

# --- Rsync helper ---
do_rsync() {
    local src="$1"
    local dest="$2"
    shift 2
    local extra_excludes=("$@")

    local rsync_args=(
        -avz
        --delete
        --exclude='node_modules/'
        --exclude='build/'
        --exclude='.phpunit.result.cache'
        --exclude='.DS_Store'
        --exclude='*.log'
    )

    for ex in "${extra_excludes[@]}"; do
        rsync_args+=(--exclude="$ex")
    done

    if $DRY_RUN; then
        rsync_args+=(--dry-run)
        warn "DRY RUN — add --confirm to execute for real"
    fi

    rsync "${rsync_args[@]}" "$src" "${REMOTE_HOST}:${dest}"

    if ! $DRY_RUN; then
        remote_exec "chown -R $REMOTE_OWNER ${dest}"
    fi
}

# --- Placeholder command functions ---
cmd_full()    { die "Not implemented yet"; }
cmd_plugins() { die "Not implemented yet"; }
cmd_theme()   { die "Not implemented yet"; }
cmd_assets()  { die "Not implemented yet"; }
cmd_db()      { die "Not implemented yet"; }
cmd_status()  { die "Not implemented yet"; }

# --- Cache flush ---
flush_cache() {
    if $CACHE_FLUSH && ! $DRY_RUN; then
        info "Flushing LiteSpeed cache..."
        remote_exec "wp --path=$REMOTE_PATH --allow-root litespeed-purge all 2>/dev/null || wp --path=$REMOTE_PATH --allow-root cache flush"
        success "Cache flushed"
    fi
}

# --- Smoke check ---
smoke_check() {
    info "Running smoke checks..."
    local status
    status=$(curl -sL -o /dev/null -w "%{http_code}" "$REMOTE_URL")
    if [[ "$status" == "200" ]]; then
        success "Homepage: $status"
    else
        warn "Homepage returned: $status (expected 200)"
    fi

    status=$(curl -sL -o /dev/null -w "%{http_code}" "${REMOTE_URL}/wp-admin/")
    if [[ "$status" == "200" || "$status" == "302" ]]; then
        success "WP Admin: $status"
    else
        warn "WP Admin returned: $status (expected 200 or 302)"
    fi
}

# --- Main ---
main() {
    parse_args "$@"
    load_config

    if $DRY_RUN; then
        echo ""
        warn "=== DRY RUN MODE === (add --confirm to execute)"
        echo ""
    fi

    case "$COMMAND" in
        full)    cmd_full ;;
        plugins) cmd_plugins ;;
        theme)   cmd_theme ;;
        assets)  cmd_assets ;;
        db)      cmd_db ;;
        status)  cmd_status ;;
        *)       die "Unknown command: $COMMAND"; usage ;;
    esac
}

main "$@"
```

**Step 2: Make executable and test**

Run: `chmod +x scripts/deploy.sh && bash scripts/deploy.sh`
Expected: usage message printed.

Run: `bash scripts/deploy.sh status`
Expected: `[ERROR] Not implemented yet` (placeholder).

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: add deploy.sh skeleton with arg parsing and helpers"
```

---

### Task 3: Implement `status` command

**Files:**
- Modify: `scripts/deploy.sh` — replace `cmd_status` placeholder

**Step 1: Implement cmd_status**

```bash
cmd_status() {
    info "Checking remote server: $REMOTE_HOST"
    echo ""

    info "WordPress version:"
    remote_exec "wp --path=$REMOTE_PATH --allow-root core version"
    echo ""

    info "Site URL:"
    remote_exec "wp --path=$REMOTE_PATH --allow-root option get siteurl"
    echo ""

    info "Active plugins:"
    remote_exec "wp --path=$REMOTE_PATH --allow-root plugin list --status=active --format=table"
    echo ""

    info "Themes:"
    remote_exec "wp --path=$REMOTE_PATH --allow-root theme list --format=table"
    echo ""

    success "Status check complete"
}
```

**Step 2: Test**

Run: `bash scripts/deploy.sh status`
Expected: see WP version 6.9, site URL https://kishkin.dev, plugin list, theme list.

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: implement deploy.sh status command"
```

---

### Task 4: Implement `plugins` command

**Files:**
- Modify: `scripts/deploy.sh` — replace `cmd_plugins` placeholder

**Step 1: Define plugin list and implement cmd_plugins**

Add near the top (after config loading section):

```bash
# --- Plugin lists ---
DEPLOY_PLUGINS=(
    "xbo-market-kit"
    "getwid"
    "getwid-megamenu"
    "breadcrumb-navxt"
    "svg-support"
    "one-click-demo-import"
)

XBO_EXTRA_EXCLUDES=(
    "tests/"
    "phpstan.neon"
    "phpcs.xml"
    "phpunit.xml"
)
```

Replace `cmd_plugins`:

```bash
cmd_plugins() {
    local plugins_to_deploy=()

    if [[ -n "${COMMAND_ARG:-}" ]]; then
        # Deploy a specific plugin
        local plugin_dir="$PROJECT_ROOT/wp-content/plugins/$COMMAND_ARG"
        if [[ ! -d "$plugin_dir" ]]; then
            die "Plugin not found: $plugin_dir"
        fi
        plugins_to_deploy=("$COMMAND_ARG")
    else
        plugins_to_deploy=("${DEPLOY_PLUGINS[@]}")
    fi

    # Run composer install --no-dev for xbo-market-kit before deploy
    for plugin in "${plugins_to_deploy[@]}"; do
        if [[ "$plugin" == "xbo-market-kit" ]]; then
            info "Running composer install --no-dev for xbo-market-kit..."
            (cd "$PROJECT_ROOT/wp-content/plugins/xbo-market-kit" && composer install --no-dev --no-interaction --prefer-dist --quiet)
            success "Composer dependencies ready (production)"
        fi
    done

    for plugin in "${plugins_to_deploy[@]}"; do
        local src="$PROJECT_ROOT/wp-content/plugins/$plugin/"
        local dest="$REMOTE_PATH/wp-content/plugins/$plugin/"
        local excludes=()

        if [[ "$plugin" == "xbo-market-kit" ]]; then
            excludes=("${XBO_EXTRA_EXCLUDES[@]}")
        fi

        info "Deploying plugin: $plugin"
        do_rsync "$src" "$dest" "${excludes[@]}"
        echo ""
    done

    # Restore dev dependencies locally
    for plugin in "${plugins_to_deploy[@]}"; do
        if [[ "$plugin" == "xbo-market-kit" ]]; then
            info "Restoring dev dependencies locally..."
            (cd "$PROJECT_ROOT/wp-content/plugins/xbo-market-kit" && composer install --no-interaction --quiet)
        fi
    done

    flush_cache

    if ! $DRY_RUN; then
        smoke_check
    fi

    success "Plugin deploy complete"
}
```

**Step 2: Test dry-run**

Run: `bash scripts/deploy.sh plugins xbo-market-kit`
Expected: dry-run rsync output showing files that would sync.

Run: `bash scripts/deploy.sh plugins`
Expected: dry-run for all 6 plugins.

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: implement deploy.sh plugins command"
```

---

### Task 5: Implement `theme` command

**Files:**
- Modify: `scripts/deploy.sh` — replace `cmd_theme` placeholder

**Step 1: Implement cmd_theme**

```bash
cmd_theme() {
    local src="$PROJECT_ROOT/wp-content/themes/prime-fse/"
    local dest="$REMOTE_PATH/wp-content/themes/prime-fse/"

    if [[ ! -d "$src" ]]; then
        die "Theme not found: $src"
    fi

    info "Deploying theme: prime-fse"
    do_rsync "$src" "$dest"

    flush_cache

    if ! $DRY_RUN; then
        smoke_check
    fi

    success "Theme deploy complete"
}
```

**Step 2: Test dry-run**

Run: `bash scripts/deploy.sh theme`
Expected: dry-run rsync output for prime-fse theme files.

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: implement deploy.sh theme command"
```

---

### Task 6: Implement `assets` command

**Files:**
- Modify: `scripts/deploy.sh` — replace `cmd_assets` placeholder

**Step 1: Implement cmd_assets**

```bash
cmd_assets() {
    local src="$PROJECT_ROOT/wp-content/plugins/xbo-market-kit/assets/"
    local dest="$REMOTE_PATH/wp-content/plugins/xbo-market-kit/assets/"

    if [[ ! -d "$src" ]]; then
        die "Assets dir not found: $src"
    fi

    info "Deploying assets: xbo-market-kit/assets/"
    do_rsync "$src" "$dest"

    flush_cache

    if ! $DRY_RUN; then
        smoke_check
    fi

    success "Assets deploy complete"
}
```

**Step 2: Test dry-run**

Run: `bash scripts/deploy.sh assets`
Expected: dry-run rsync output for CSS/JS assets.

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: implement deploy.sh assets command"
```

---

### Task 7: Implement `full` command

**Files:**
- Modify: `scripts/deploy.sh` — replace `cmd_full` placeholder

**Step 1: Implement cmd_full**

```bash
cmd_full() {
    info "=== FULL DEPLOY (plugins + theme + assets) ==="
    info "Note: database is NOT included. Use 'deploy.sh db' separately."
    echo ""

    cmd_plugins
    cmd_theme

    success "=== FULL DEPLOY COMPLETE ==="
}
```

Note: `cmd_plugins` already covers assets since `xbo-market-kit` includes its `assets/` dir.
The `full` command calls `cmd_plugins` + `cmd_theme`. No need to call `cmd_assets` separately.

**Step 2: Test dry-run**

Run: `bash scripts/deploy.sh full`
Expected: dry-run output for all plugins + theme.

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: implement deploy.sh full command"
```

---

### Task 8: Implement `db` command

**Files:**
- Modify: `scripts/deploy.sh` — replace `cmd_db` placeholder

**Step 1: Implement cmd_db**

This is the most complex command with safety guards.

```bash
cmd_db() {
    if $DRY_RUN; then
        warn "DB deploy requires --confirm flag. This is what would happen:"
        echo ""
        echo "  1. Enable maintenance mode on server"
        echo "  2. Backup production DB on server"
        echo "  3. Export local DB (excluding litespeed tables)"
        echo "  4. SCP dump to server"
        echo "  5. Import dump on server"
        echo "  6. Search-replace: $LOCAL_URL → $REMOTE_URL"
        echo "  7. Disable maintenance mode"
        echo "  8. Flush cache"
        echo "  9. Smoke check"
        echo ""
        warn "Run with --confirm to execute."
        return 0
    fi

    # Double confirmation
    echo ""
    warn "=== DATABASE DEPLOY ==="
    warn "This will OVERWRITE the production database at $REMOTE_URL"
    warn "Target: $REMOTE_HOST:$REMOTE_PATH"
    echo ""
    read -rp "Type 'deploy-db' to confirm: " confirmation
    if [[ "$confirmation" != "deploy-db" ]]; then
        die "Aborted. You typed: '$confirmation'"
    fi

    local timestamp
    timestamp=$(date +%Y%m%d_%H%M%S)
    local local_dump="/tmp/xbo_local_dump_${timestamp}.sql"
    local remote_dump="/tmp/xbo_prod_backup_${timestamp}.sql"
    local remote_import="/tmp/xbo_local_dump_${timestamp}.sql"

    # Step 1: Maintenance mode ON
    info "Enabling maintenance mode..."
    remote_exec "wp --path=$REMOTE_PATH --allow-root maintenance-mode activate" || true
    success "Maintenance mode enabled"

    # Step 2: Backup production DB
    info "Backing up production DB..."
    remote_exec "wp --path=$REMOTE_PATH --allow-root db export $remote_dump"
    success "Production DB backed up to: $remote_dump"

    # Step 3: Get litespeed table names to exclude
    info "Discovering litespeed tables to exclude..."
    local exclude_tables
    exclude_tables=$(wp --path="$PROJECT_ROOT" db tables 'wp_litespeed*' --format=csv 2>/dev/null || echo "")

    local export_args=()
    if [[ -n "$exclude_tables" ]]; then
        IFS=',' read -ra tables <<< "$exclude_tables"
        for table in "${tables[@]}"; do
            export_args+=(--exclude_tables="$table")
        done
        info "Excluding tables: $exclude_tables"
    fi

    # Step 4: Export local DB
    info "Exporting local DB..."
    wp --path="$PROJECT_ROOT" db export "$local_dump" "${export_args[@]}"
    success "Local DB exported to: $local_dump"

    # Step 5: SCP to server
    info "Uploading dump to server..."
    scp "$local_dump" "${REMOTE_HOST}:${remote_import}"
    success "Dump uploaded"

    # Step 6: Import on server
    info "Importing dump on server..."
    remote_exec "wp --path=$REMOTE_PATH --allow-root db import $remote_import"
    success "DB imported"

    # Step 7: Search-replace
    info "Running search-replace: $LOCAL_URL → $REMOTE_URL"
    remote_exec "wp --path=$REMOTE_PATH --allow-root search-replace '$LOCAL_URL' '$REMOTE_URL' --skip-columns=guid --all-tables"
    success "Search-replace complete"

    # Also replace http with https if needed
    info "Ensuring HTTPS URLs..."
    remote_exec "wp --path=$REMOTE_PATH --allow-root search-replace 'http://kishkin.dev' 'https://kishkin.dev' --skip-columns=guid --all-tables" || true

    # Step 8: Maintenance mode OFF
    info "Disabling maintenance mode..."
    remote_exec "wp --path=$REMOTE_PATH --allow-root maintenance-mode deactivate" || true
    success "Maintenance mode disabled"

    # Step 9: Flush cache
    flush_cache

    # Step 10: Smoke check
    smoke_check

    # Cleanup local dump
    rm -f "$local_dump"
    info "Local dump cleaned up"

    success "=== DATABASE DEPLOY COMPLETE ==="
    info "Production backup saved on server: $remote_dump"
}
```

**Step 2: Test dry-run**

Run: `bash scripts/deploy.sh db`
Expected: shows the 9-step plan and warns to use --confirm.

**Step 3: Commit**

```bash
git add scripts/deploy.sh
git commit -m "feat: implement deploy.sh db command with safety guards"
```

---

### Task 9: End-to-end test — deploy status + dry-run full

**Step 1: Run status**

Run: `bash scripts/deploy.sh status`
Expected: WP version, site URL, plugins, themes from remote server.

**Step 2: Run full dry-run**

Run: `bash scripts/deploy.sh full`
Expected: dry-run rsync output for all plugins and theme, no actual changes.

**Step 3: Run db dry-run**

Run: `bash scripts/deploy.sh db`
Expected: prints the 9-step plan, no execution.

---

### Task 10: First real deploy

**Step 1: Deploy plugins for real**

Run: `bash scripts/deploy.sh plugins --confirm`
Expected: rsync transfers files, chown runs, cache flushed, smoke check passes.

**Step 2: Deploy theme for real**

Run: `bash scripts/deploy.sh theme --confirm`
Expected: prime-fse synced, smoke check passes.

**Step 3: Verify on site**

Open: `https://kishkin.dev` — should show the site with deployed plugins/theme.

**Step 4: Commit plan doc**

```bash
git add docs/plans/2026-02-27-deployment-implementation.md
git commit -m "docs: add deployment implementation plan"
```
