#!/usr/bin/env bash
set -euo pipefail

###############################################################################
# deploy.sh — WordPress deployment script for XBO Market Kit
#
# Usage:
#   ./scripts/deploy.sh <command> [command_arg] [flags]
#
# Commands:
#   full              Deploy plugins + theme (no database)
#   plugins [name]    Deploy all plugins or a specific one
#   theme             Deploy theme prime-fse
#   assets            Deploy xbo-market-kit/assets/ only
#   db                Database sync (requires double confirmation)
#   status            Show remote server status
#
# Flags:
#   --confirm         Execute for real (default is dry-run)
#   --no-cache-flush  Skip LiteSpeed cache flush after deploy
#   -h, --help        Show this help message
#
# Configuration:
#   Copy scripts/deploy.conf.example to scripts/deploy.conf and fill in your
#   values. deploy.conf is gitignored — never commit credentials.
#
# Examples:
#   ./scripts/deploy.sh full                  # dry-run full deploy
#   ./scripts/deploy.sh full --confirm        # real full deploy
#   ./scripts/deploy.sh plugins --confirm --no-cache-flush
#   ./scripts/deploy.sh db --confirm
#   ./scripts/deploy.sh status
###############################################################################

# ---------------------------------------------------------------------------
# Resolve paths
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
CONFIG_FILE="${SCRIPT_DIR}/deploy.conf"

# ---------------------------------------------------------------------------
# Defaults
# ---------------------------------------------------------------------------
DRY_RUN=true
CACHE_FLUSH=true
COMMAND=""
COMMAND_ARG=""

# ---------------------------------------------------------------------------
# Plugin lists
# ---------------------------------------------------------------------------
DEPLOY_PLUGINS=(
    "xbo-market-kit"
    "getwid"
    "getwid-megamenu"
    "breadcrumb-navxt"
    "svg-support"
    "one-click-demo-import"
)

XBO_EXTRA_EXCLUDES="tests/,phpstan.neon,phpcs.xml,phpunit.xml"

# Plugins that must be active on production after DB import
PRODUCTION_PLUGINS=(
    "xbo-market-kit"
    "getwid"
    "getwid-megamenu"
    "breadcrumb-navxt"
    "svg-support"
    "one-click-demo-import"
    "litespeed-cache"
)

# Dev-only plugins (exist locally but NOT deployed to server)
DEV_ONLY_PLUGINS=(
    "mcp-adapter"
    "mp-api-docs"
)

# Server-only plugins (exist on server but NOT locally — must stay active after DB import)
SERVER_ONLY_PLUGINS=(
    "litespeed-cache"
)

# ---------------------------------------------------------------------------
# Color helpers
# ---------------------------------------------------------------------------
_color() { printf "\033[%sm%s\033[0m\n" "$1" "$2"; }
info()    { _color "0;36" "[INFO]  $1"; }
success() { _color "0;32" "[OK]    $1"; }
warn()    { _color "0;33" "[WARN]  $1"; }
error()   { _color "0;31" "[ERROR] $1"; }
die()     { error "$1"; exit 1; }

# ---------------------------------------------------------------------------
# usage()
# ---------------------------------------------------------------------------
usage() {
    cat <<'HELP'
Usage: deploy.sh <command> [command_arg] [flags]

Commands:
  full              Deploy plugins + theme (no database)
  plugins [name]    Deploy all plugins or a specific one
  theme             Deploy theme prime-fse
  assets            Deploy xbo-market-kit/assets/ only
  db                Database sync (requires double confirmation)
  status            Show remote server status

Flags:
  --confirm         Execute for real (default is dry-run)
  --no-cache-flush  Skip LiteSpeed cache flush after deploy
  -h, --help        Show this help message

Examples:
  deploy.sh full                          # dry-run full deploy
  deploy.sh full --confirm                # real full deploy
  deploy.sh plugins --confirm --no-cache-flush
  deploy.sh db --confirm
  deploy.sh status
HELP
    exit 0
}

# ---------------------------------------------------------------------------
# parse_args()
# ---------------------------------------------------------------------------
parse_args() {
    if [[ $# -eq 0 ]]; then
        usage
    fi

    while [[ $# -gt 0 ]]; do
        case "$1" in
            -h|--help)
                usage
                ;;
            --confirm)
                DRY_RUN=false
                shift
                ;;
            --no-cache-flush)
                CACHE_FLUSH=false
                shift
                ;;
            -*)
                die "Unknown flag: $1 (see --help)"
                ;;
            *)
                if [[ -z "${COMMAND}" ]]; then
                    COMMAND="$1"
                elif [[ -z "${COMMAND_ARG}" ]]; then
                    COMMAND_ARG="$1"
                else
                    die "Unexpected argument: $1 (see --help)"
                fi
                shift
                ;;
        esac
    done

    if [[ -z "${COMMAND}" ]]; then
        die "No command specified (see --help)"
    fi
}

# ---------------------------------------------------------------------------
# load_config()
# ---------------------------------------------------------------------------
load_config() {
    if [[ ! -f "${CONFIG_FILE}" ]]; then
        die "Config not found: ${CONFIG_FILE}\nCopy deploy.conf.example to deploy.conf and fill in your values."
    fi

    # shellcheck source=/dev/null
    source "${CONFIG_FILE}"

    # Validate required variables
    local required_vars=("REMOTE_HOST" "REMOTE_PATH" "REMOTE_OWNER" "LOCAL_URL" "REMOTE_URL")
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            die "Missing required config variable: ${var}"
        fi
    done

    info "Config loaded from ${CONFIG_FILE}"
    info "Remote: ${REMOTE_HOST}:${REMOTE_PATH}"
}

# ---------------------------------------------------------------------------
# remote_exec() — SSH wrapper
# ---------------------------------------------------------------------------
remote_exec() {
    ssh -o ConnectTimeout=10 "${REMOTE_HOST}" "$@"
}

# ---------------------------------------------------------------------------
# do_rsync() — rsync wrapper
#   $1 = source path
#   $2 = destination path
#   $3 = optional extra excludes (comma-separated)
# ---------------------------------------------------------------------------
do_rsync() {
    local src="$1"
    local dest="$2"
    local extra_excludes="${3:-}"

    local rsync_args=(
        -avz
        --delete
        --exclude="node_modules/"
        --exclude=".phpunit.result.cache"
        --exclude=".DS_Store"
        --exclude="*.log"
    )

    # Add extra excludes if provided
    if [[ -n "${extra_excludes}" ]]; then
        IFS=',' read -ra extras <<< "${extra_excludes}"
        for ex in "${extras[@]}"; do
            rsync_args+=( --exclude="${ex}" )
        done
    fi

    if [[ "${DRY_RUN}" == "true" ]]; then
        warn "DRY-RUN mode — adding --dry-run flag to rsync"
        rsync_args+=( --dry-run )
    fi

    info "rsync ${src} -> ${dest}"
    rsync "${rsync_args[@]}" "${src}" "${dest}"

    # Set ownership on remote after real rsync
    if [[ "${DRY_RUN}" == "false" ]]; then
        info "Setting ownership to ${REMOTE_OWNER} on ${REMOTE_PATH}"
        remote_exec "chown -R ${REMOTE_OWNER} ${REMOTE_PATH}"
    fi
}

# ---------------------------------------------------------------------------
# flush_cache() — LiteSpeed cache flush via WP-CLI
# ---------------------------------------------------------------------------
flush_cache() {
    if [[ "${CACHE_FLUSH}" == "false" ]]; then
        info "Cache flush skipped (--no-cache-flush)"
        return 0
    fi

    if [[ "${DRY_RUN}" == "true" ]]; then
        info "Cache flush skipped (dry-run mode)"
        return 0
    fi

    info "Flushing LiteSpeed cache on remote..."
    remote_exec "cd ${REMOTE_PATH} && wp litespeed-purge all --allow-root" \
        && success "Cache flushed" \
        || warn "Cache flush failed (non-fatal)"
}

# ---------------------------------------------------------------------------
# smoke_check() — basic HTTP health checks
# ---------------------------------------------------------------------------
smoke_check() {
    info "Running smoke checks against ${REMOTE_URL}..."

    local homepage_status
    homepage_status=$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "${REMOTE_URL}/")
    if [[ "${homepage_status}" == "200" ]]; then
        success "Homepage: HTTP ${homepage_status}"
    else
        error "Homepage: HTTP ${homepage_status} (expected 200)"
    fi

    local admin_status
    admin_status=$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "${REMOTE_URL}/wp-admin/")
    if [[ "${admin_status}" == "200" || "${admin_status}" == "302" ]]; then
        success "WP Admin: HTTP ${admin_status}"
    else
        error "WP Admin: HTTP ${admin_status} (expected 200 or 302)"
    fi
}

# ---------------------------------------------------------------------------
# Commands
# ---------------------------------------------------------------------------
cmd_full() {
    info "=== FULL DEPLOY (plugins + theme) ==="
    info "Note: database is NOT included. Use 'deploy.sh db' separately."
    echo ""

    # Temporarily disable cache flush and smoke check for sub-commands
    local saved_cache_flush="${CACHE_FLUSH}"
    CACHE_FLUSH=false

    cmd_plugins
    cmd_theme

    # Restore and run once at the end
    CACHE_FLUSH="${saved_cache_flush}"
    flush_cache

    if [[ "${DRY_RUN}" == "false" ]]; then
        smoke_check
    fi

    success "=== FULL DEPLOY COMPLETE ==="
}
cmd_plugins() {
    local plugins_to_deploy=()

    if [[ -n "${COMMAND_ARG}" ]]; then
        local plugin_dir="${PROJECT_ROOT}/wp-content/plugins/${COMMAND_ARG}"
        if [[ ! -d "${plugin_dir}" ]]; then
            die "Plugin directory not found: ${plugin_dir}"
        fi
        plugins_to_deploy=("${COMMAND_ARG}")
    else
        plugins_to_deploy=("${DEPLOY_PLUGINS[@]}")
    fi

    # Run composer install --no-dev for xbo-market-kit before deploy
    for plugin in "${plugins_to_deploy[@]}"; do
        if [[ "${plugin}" == "xbo-market-kit" ]]; then
            info "Running composer install --no-dev for xbo-market-kit..."
            (cd "${PROJECT_ROOT}/wp-content/plugins/xbo-market-kit" && composer install --no-dev --no-interaction --prefer-dist --quiet)
            success "Composer dependencies ready (production only)"
        fi
    done

    for plugin in "${plugins_to_deploy[@]}"; do
        local src="${PROJECT_ROOT}/wp-content/plugins/${plugin}/"
        local dest="${REMOTE_HOST}:${REMOTE_PATH}/wp-content/plugins/${plugin}/"
        local excludes=""

        if [[ "${plugin}" == "xbo-market-kit" ]]; then
            excludes="${XBO_EXTRA_EXCLUDES}"
        fi

        info "Deploying plugin: ${plugin}"
        do_rsync "${src}" "${dest}" "${excludes}"
        echo ""
    done

    # Restore dev dependencies locally after deploy
    for plugin in "${plugins_to_deploy[@]}"; do
        if [[ "${plugin}" == "xbo-market-kit" ]]; then
            info "Restoring dev dependencies locally..."
            (cd "${PROJECT_ROOT}/wp-content/plugins/xbo-market-kit" && composer install --no-interaction --quiet)
        fi
    done

    flush_cache

    if [[ "${DRY_RUN}" == "false" ]]; then
        smoke_check
    fi

    success "Plugin deploy complete"
}
cmd_theme() {
    local src="${PROJECT_ROOT}/wp-content/themes/prime-fse/"
    local dest="${REMOTE_HOST}:${REMOTE_PATH}/wp-content/themes/prime-fse/"

    if [[ ! -d "${src}" ]]; then
        die "Theme not found: ${src}"
    fi

    info "Deploying theme: prime-fse"
    do_rsync "${src}" "${dest}"

    flush_cache

    if [[ "${DRY_RUN}" == "false" ]]; then
        smoke_check
    fi

    success "Theme deploy complete"
}
cmd_assets() {
    local src="${PROJECT_ROOT}/wp-content/plugins/xbo-market-kit/assets/"
    local dest="${REMOTE_HOST}:${REMOTE_PATH}/wp-content/plugins/xbo-market-kit/assets/"

    if [[ ! -d "${src}" ]]; then
        die "Assets directory not found: ${src}"
    fi

    info "Deploying assets: xbo-market-kit/assets/"
    do_rsync "${src}" "${dest}"

    flush_cache

    if [[ "${DRY_RUN}" == "false" ]]; then
        smoke_check
    fi

    success "Assets deploy complete"
}
cmd_db() {
    # In dry-run mode, just show what would happen
    if [[ "${DRY_RUN}" == "true" ]]; then
        warn "DB deploy requires --confirm flag. This is what would happen:"
        echo ""
        echo "  1.  Enable maintenance mode on server"
        echo "  2.  Backup production DB on server"
        echo "  3.  Export local DB (excluding litespeed tables)"
        echo "  4.  Upload dump to server"
        echo "  5.  Import dump on server"
        echo "  6.  Search-replace: ${LOCAL_URL} → ${REMOTE_URL}"
        echo "  7.  Ensure HTTPS URLs"
        echo "  8.  Activate production plugins"
        echo "  9.  Activate theme prime-fse"
        echo "  10. Disable maintenance mode"
        echo "  11. Flush cache + smoke check"
        echo ""
        warn "Run with --confirm to execute."
        return 0
    fi

    # Double confirmation for DB operations
    echo ""
    warn "╔══════════════════════════════════════════════════════════╗"
    warn "║  DATABASE DEPLOY — THIS WILL OVERWRITE PRODUCTION DB!  ║"
    warn "╠══════════════════════════════════════════════════════════╣"
    warn "║  Target: ${REMOTE_URL}"
    warn "║  Server: ${REMOTE_HOST}:${REMOTE_PATH}"
    warn "╚══════════════════════════════════════════════════════════╝"
    echo ""
    read -rp "Type 'deploy-db' to confirm: " confirmation
    if [[ "${confirmation}" != "deploy-db" ]]; then
        die "Aborted. You typed: '${confirmation}'"
    fi

    local timestamp
    timestamp=$(date +%Y%m%d_%H%M%S)
    local local_dump="/tmp/xbo_local_dump_${timestamp}.sql"
    local remote_backup="/tmp/xbo_prod_backup_${timestamp}.sql"
    local remote_import="/tmp/xbo_local_dump_${timestamp}.sql"

    # Step 1: Maintenance mode ON
    info "Step 1/11: Enabling maintenance mode..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root maintenance-mode activate" || true
    success "Maintenance mode enabled"

    # Step 2: Backup production DB
    info "Step 2/11: Backing up production DB..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root db export ${remote_backup}"
    success "Production DB backed up to: ${remote_backup}"

    # Step 3: Discover litespeed tables to exclude from local export
    info "Step 3/11: Exporting local DB..."
    local exclude_args=""
    local ls_tables
    ls_tables=$(wp --path="${PROJECT_ROOT}" db tables 'wp_litespeed*' --format=csv 2>/dev/null || echo "")
    if [[ -n "${ls_tables}" ]]; then
        IFS=',' read -ra tables <<< "${ls_tables}"
        for table in "${tables[@]}"; do
            exclude_args="${exclude_args} --exclude_tables=${table}"
        done
        info "Excluding litespeed tables: ${ls_tables}"
    fi

    # Export local DB
    # shellcheck disable=SC2086
    wp --path="${PROJECT_ROOT}" db export "${local_dump}" ${exclude_args}
    success "Local DB exported to: ${local_dump}"

    # Step 4: Upload dump to server
    info "Step 4/11: Uploading dump to server..."
    scp "${local_dump}" "${REMOTE_HOST}:${remote_import}"
    success "Dump uploaded"

    # Step 5: Import on server
    info "Step 5/11: Importing dump on server..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root db import ${remote_import}"
    success "DB imported"

    # Step 6: Search-replace URLs
    info "Step 6/11: Search-replace: ${LOCAL_URL} → ${REMOTE_URL}"
    remote_exec "wp --path=${REMOTE_PATH} --allow-root search-replace '${LOCAL_URL}' '${REMOTE_URL}' --skip-columns=guid --all-tables"
    success "Search-replace complete"

    # Step 7: Ensure HTTPS
    info "Step 7/11: Ensuring HTTPS URLs..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root search-replace 'http://kishkin.dev' 'https://kishkin.dev' --skip-columns=guid --all-tables" || true
    success "HTTPS enforced"

    # Step 8: Activate production plugins and theme
    info "Step 8/11: Activating production plugins..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root plugin activate ${PRODUCTION_PLUGINS[*]}" || true
    success "Plugins activated"

    info "Step 9/11: Activating theme prime-fse..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root theme activate prime-fse" || true
    success "Theme activated"

    # Step 10: Maintenance mode OFF
    info "Step 10/11: Disabling maintenance mode..."
    remote_exec "wp --path=${REMOTE_PATH} --allow-root maintenance-mode deactivate" || true
    success "Maintenance mode disabled"

    # Step 11: Flush cache + smoke check
    info "Step 11/11: Post-deploy checks..."
    flush_cache
    smoke_check

    # Cleanup
    rm -f "${local_dump}"
    info "Local dump cleaned up"

    # Cleanup remote import file
    remote_exec "rm -f ${remote_import}" || true

    echo ""
    success "=== DATABASE DEPLOY COMPLETE ==="
    info "Production backup saved on server: ${remote_backup}"
}
cmd_status() {
    info "Checking remote server: ${REMOTE_HOST}"
    echo ""

    info "WordPress version:"
    remote_exec "wp --path=${REMOTE_PATH} --allow-root core version"
    echo ""

    info "Site URL:"
    remote_exec "wp --path=${REMOTE_PATH} --allow-root option get siteurl"
    echo ""

    info "Active plugins:"
    remote_exec "wp --path=${REMOTE_PATH} --allow-root plugin list --status=active --format=table"
    echo ""

    info "Themes:"
    remote_exec "wp --path=${REMOTE_PATH} --allow-root theme list --format=table"
    echo ""

    success "Status check complete"
}

# ---------------------------------------------------------------------------
# main()
# ---------------------------------------------------------------------------
main() {
    parse_args "$@"
    load_config

    if [[ "${DRY_RUN}" == "true" ]]; then
        warn "DRY-RUN mode — no changes will be made. Use --confirm to execute."
    fi

    case "${COMMAND}" in
        full)    cmd_full    ;;
        plugins) cmd_plugins ;;
        theme)   cmd_theme   ;;
        assets)  cmd_assets  ;;
        db)      cmd_db      ;;
        status)  cmd_status  ;;
        *)       die "Unknown command: ${COMMAND} (see --help)" ;;
    esac
}

main "$@"
