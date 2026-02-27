#!/usr/bin/env bash
set -euo pipefail

###############################################################################
# deploy.sh — WordPress deployment script for XBO Market Kit
#
# Usage:
#   ./scripts/deploy.sh <command> [command_arg] [flags]
#
# Commands:
#   full              Full deployment (files + database)
#   plugins           Deploy plugins only
#   theme             Deploy active theme only
#   assets            Deploy static assets only
#   db                Database operations (export / import / push / pull)
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
#   ./scripts/deploy.sh db push --confirm
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
  full              Full deployment (files + database)
  plugins           Deploy plugins only
  theme             Deploy active theme only
  assets            Deploy static assets only
  db <arg>          Database operations (export | import | push | pull)
  status            Show remote server status

Flags:
  --confirm         Execute for real (default is dry-run)
  --no-cache-flush  Skip LiteSpeed cache flush after deploy
  -h, --help        Show this help message

Examples:
  deploy.sh full                          # dry-run full deploy
  deploy.sh full --confirm                # real full deploy
  deploy.sh plugins --confirm --no-cache-flush
  deploy.sh db push --confirm
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
        --exclude="build/"
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
# Command placeholders
# ---------------------------------------------------------------------------
cmd_full()    { die "Not implemented yet"; }
cmd_plugins() { die "Not implemented yet"; }
cmd_theme()   { die "Not implemented yet"; }
cmd_assets()  { die "Not implemented yet"; }
cmd_db()      { die "Not implemented yet"; }
cmd_status()  { die "Not implemented yet"; }

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
