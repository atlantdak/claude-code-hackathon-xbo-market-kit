# Deployment Pipeline Design

**Date:** 2026-02-27
**Status:** Approved

## Overview

Manual deployment pipeline from local dev (Local by Flywheel) to production
server via SSH + rsync. Single bash script with subcommands for granular control.

## Infrastructure

| | Local | Production |
|---|---|---|
| WordPress | 6.9.1 | 6.9 |
| URL | http://claude-code-hackathon-xbo-market-kit.local | https://kishkin.dev |
| Path | (project root) | /var/www/html |
| Server | Local by Flywheel | LiteSpeed |
| SSH | n/a | root@178.62.244.240 |
| File owner | n/a | www-data:www-data |
| WP-CLI | yes | yes (--allow-root) |

## Commands

```
scripts/deploy.sh <command> [options]
```

| Command | Description |
|---------|-------------|
| `full` | Deploy plugins + theme + assets (NO database) |
| `plugins [name]` | All plugins, or a specific one |
| `theme` | Theme `prime-fse` |
| `assets` | Only `xbo-market-kit/assets/` (quick CSS/JS push) |
| `db` | Database sync (separate, requires double confirmation) |
| `status` | Show remote state (WP version, plugins, theme) |

### Global flags

| Flag | Effect |
|------|--------|
| `--confirm` | Execute for real (default is dry-run) |
| `--no-cache-flush` | Skip LiteSpeed cache purge after deploy |

## What Gets Deployed

### Plugins (rsync)

Deployed plugins:
- `xbo-market-kit` (with `vendor/`, excluding `node_modules/`, `build/`, `.phpunit.result.cache`)
- `getwid`
- `getwid-megamenu`
- `breadcrumb-navxt`
- `svg-support`
- `one-click-demo-import`

Excluded (dev-only):
- `mp-api-docs`
- `mcp-adapter`

**Note:** `vendor/` is deployed with the code (not installed on server) to ensure
deterministic dependencies. `composer install --no-dev` runs locally before deploy.

### Theme (rsync)

- `wp-content/themes/prime-fse/`

### Assets (rsync subset)

- `wp-content/plugins/xbo-market-kit/assets/` only

### Database (separate command)

Process:
1. Enable maintenance mode on server (`wp maintenance-mode activate`)
2. Backup production DB on server (`wp db export` with timestamp)
3. Export local DB (`wp db export`, excluding tables matching `*litespeed*`)
4. SCP dump to server
5. Import on server (`wp db import`)
6. Search-replace URLs (`wp search-replace --skip-columns=guid`)
7. Disable maintenance mode
8. Flush LiteSpeed cache
9. Run smoke check

Excluded tables from local export:
- `wp_litespeed_img_optm`
- `wp_litespeed_crawler`
- `wp_litespeed_crawler_blacklist`
- `wp_litespeed_url`
- `wp_litespeed_url_file`
- Any other `wp_litespeed_*` tables

## Safety Measures

1. **Dry-run by default** — all rsync commands show what would change without `--confirm`
2. **DB separated from full** — `deploy.sh full` never touches the database
3. **DB double confirmation** — `deploy.sh db --confirm` still asks for typed confirmation
4. **Pre-import backup** — production DB backed up before every import
5. **File ownership** — `chown -R www-data:www-data` after every rsync
6. **Post-deploy smoke check** — verify homepage returns 200, wp-admin accessible
7. **LiteSpeed cache flush** — automatic after deploy (skippable with flag)
8. **Maintenance mode** — enabled during DB operations only

## Configuration

File: `scripts/deploy.conf` (gitignored)

```bash
REMOTE_HOST="root@178.62.244.240"
REMOTE_PATH="/var/www/html"
REMOTE_OWNER="www-data:www-data"
LOCAL_URL="http://claude-code-hackathon-xbo-market-kit.local"
REMOTE_URL="https://kishkin.dev"
```

Template: `scripts/deploy.conf.example` (committed to git)

## Post-deploy Checks

1. `curl -sL -o /dev/null -w "%{http_code}" https://kishkin.dev` → expect 200
2. `curl -sL -o /dev/null -w "%{http_code}" https://kishkin.dev/wp-admin/` → expect 200/302
3. `wp --path=... --allow-root plugin list` → verify active plugins
4. `wp --path=... --allow-root option get siteurl` → verify correct URL

## Rsync Exclude Patterns

Common excludes for all plugin syncs:
```
--exclude='node_modules/'
--exclude='build/'
--exclude='.phpunit.result.cache'
--exclude='.DS_Store'
--exclude='*.log'
```

Additional excludes for `xbo-market-kit`:
```
--exclude='tests/'
--exclude='phpstan.neon'
--exclude='phpcs.xml'
--exclude='phpunit.xml'
```
