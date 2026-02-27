# 2026-02-27 — Production Deployment Pipeline

## Summary

Built a complete deployment pipeline (`scripts/deploy.sh`) for deploying WordPress content from Local by Flywheel to production server (178.62.244.240, LiteSpeed, https://kishkin.dev). Bash script with 6 subcommands (full, plugins, theme, assets, db, status), dry-run by default, rsync-based file sync, WP-CLI database operations with LiteSpeed table preservation, and automatic production plugin activation.

## Time Tracking

| Phase | Duration |
|-------|----------|
| Brainstorming (SSH, server, approach) | ~30 min |
| Design doc + Codex review | ~15 min |
| Implementation (13 commits via subagent) | ~30 min |
| First full deploy + fixes (3 iterations) | ~40 min |
| Plugin list fixes + redeploy | ~15 min |
| REST API 401 debugging + password fix | ~30 min |
| LiteSpeed table preservation improvement | ~10 min |
| **Total** | **~170 min** |

## Problem Solved

### Initial Deployment
- SSH key mismatch (id_rsa vs id_ed25519) — identified and resolved
- Theme and plugins not activated after deploy — added activation steps
- LiteSpeed deactivated after DB import — added to PRODUCTION_PLUGINS
- Dev-only plugins (mcp-adapter) causing errors on production — added DEV_ONLY_PLUGINS list

### Content & Style Issues
- `build/` directory excluded from rsync — Gutenberg blocks broken on production (no compiled JS/CSS)
- `wp-content/uploads/` not synced — images missing from content
- `wp db import` without reset — stale tables, wrong `page_on_front`

### Plugin & Auth Issues
- getwid-megamenu and mp-api-docs not deployed — added to DEPLOY_PLUGINS
- REST API 401 errors (`rest_cannot_view`) on `context=edit` — root cause: password hash mismatch after DB import without table reset
- LiteSpeed tables lost during `wp db reset` — added backup/restore cycle

## Completed

- [x] Design document: `docs/plans/2026-02-27-deployment-design.md`
- [x] Implementation plan: `docs/plans/2026-02-27-deployment-implementation.md`
- [x] `scripts/deploy.sh` — 6 commands (full, plugins, theme, assets, db, status)
- [x] `scripts/deploy.conf.example` — config template (gitignored `deploy.conf`)
- [x] Dry-run by default, `--confirm` for real execution
- [x] Double confirmation for DB operations (type `deploy-db`)
- [x] LiteSpeed cache flush after deploys
- [x] Smoke checks (HTTP 200 homepage, 302 admin)
- [x] Composer `--no-dev` for production, restore after deploy
- [x] DB command: export local → upload → reset → import → restore LiteSpeed tables → search-replace URLs → activate plugins/theme
- [x] Production plugins activation after every plugin deploy
- [x] All 7 plugins deployed and active (xbo-market-kit, getwid, getwid-megamenu, breadcrumb-navxt, svg-support, one-click-demo-import, mp-api-docs)
- [x] LiteSpeed cache remains active
- [x] Production site verified: https://kishkin.dev

## Commits

| Hash | Message |
|------|---------|
| 17cbfad | docs: add deployment pipeline design document |
| 27b95ad | chore: add deployment config template |
| 9088ba5 | feat: add deploy.sh skeleton with arg parsing and helpers |
| 73d4fe2 | feat: implement deploy.sh status command |
| e79f3c5 | feat: implement deploy.sh plugins command with composer support |
| be79923 | feat: implement deploy.sh theme and assets commands |
| ba60b4f | feat: implement deploy.sh full command |
| 4189d42 | feat: implement deploy.sh db command with safety guards |
| 843c550 | fix: update deploy.sh usage to match actual commands |
| 78b93ec | docs: add deployment implementation plan |
| bebc9b7 | fix: activate production plugins and theme after DB import |
| 76da03c | fix: remove build/ from rsync excludes |
| 1e81654 | fix: reset DB before import to prevent stale data |

## Metrics

| Metric | Value |
|--------|-------|
| Commits | 13 |
| Files created | 3 (deploy.sh, deploy.conf.example, deploy.conf) |
| Deploy commands | 6 (full, plugins, theme, assets, db, status) |
| Production plugins | 8 (7 deployed + litespeed-cache server-only) |
| Deploy iterations | 4 (initial + 3 fix cycles) |
| Issues debugged | 6 (SSH, build/, uploads, DB reset, plugin list, REST 401) |

## Decisions

- **Bash script over CI/CD**: Manual deployment fits hackathon workflow — no GitHub Actions overhead
- **Dry-run by default**: Safety-first approach — `--confirm` required for real execution
- **LiteSpeed table preservation**: Before `wp db reset`, back up `wp_litespeed*` tables, restore after import — prevents losing server cache config
- **PRODUCTION_PLUGINS array**: Separate from DEPLOY_PLUGINS to include server-only plugins (litespeed-cache) that must be activated but aren't deployed from local
- **Plugin activation after every deploy**: Not just DB import — ensures litespeed-cache stays active even after file-only deploys
- **DB export excludes litespeed tables**: Local has no LiteSpeed, so export skips `wp_litespeed*` tables to avoid overwriting production cache config

## Files Changed

| Status | File |
|--------|------|
| A | scripts/deploy.sh |
| A | scripts/deploy.conf.example |
| A | docs/plans/2026-02-27-deployment-design.md |
| A | docs/plans/2026-02-27-deployment-implementation.md |
| M | .gitignore (added scripts/deploy.conf to ignore) |

## Architecture

```
Local (dev)                          Production (178.62.244.240)
  ├── plugins/ ──rsync──────────────→ /var/www/html/wp-content/plugins/
  ├── themes/  ──rsync──────────────→ /var/www/html/wp-content/themes/
  └── wp db export ──scp──────────→ wp db reset + import
                                      ├── search-replace URLs
                                      ├── restore LiteSpeed tables
                                      ├── activate PRODUCTION_PLUGINS
                                      └── flush LiteSpeed cache
```
