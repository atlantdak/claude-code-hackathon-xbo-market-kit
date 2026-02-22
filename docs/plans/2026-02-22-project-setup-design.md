# Project Setup & Environment Design

**Date:** 2026-02-22
**Status:** Approved

## Context

Setting up a WordPress plugin development environment for the Claude Code Hackathon.
The project lives in a Local by Flywheel site. The GitHub repository
(`atlantdak/claude-code-hackathon-xbo-market-kit`) is empty and needs initial configuration.

## Decisions

### 1. Git Root

**Decision:** `app/public/` is the git root.

**Rationale:** Claude Code is launched from this directory. PHPStorm project root is here.
All project files (docs, scripts, CLAUDE.md) live alongside `wp-content/`. WordPress core
files are excluded via `.gitignore`.

### 2. .gitignore Strategy

**Decision:** Whitelist approach — ignore everything (`*`), then explicitly un-ignore
project files with `!` rules.

**Rationale:** Safer than blacklisting individual WP core files. New WP files added by
updates are automatically ignored. Only explicitly tracked files enter the repo.

### 3. Themes

**Decision:** Standard themes (twentytwentythree/four/five) are gitignored.
`setup.sh` installs them during project bootstrap.

### 4. PHP Version

**Decision:** PHP 8.1+ minimum.

**Rationale:** Minimum for WP 6.7+. Enables enums, fibers, readonly properties,
intersection types.

### 5. Code Quality Tools

**Decision:** Composer, PHPCS (WordPress standards), PHPStan, and PHPUnit are configured
inside the plugin directory (`wp-content/plugins/xbo-market-kit/`).

**Rationale:** Claude Code subagents will use these tools for automated code quality
checks. Having them in the plugin root keeps the plugin self-contained.

### 6. Language Policy

**Decision:** All documentation, code comments, commit messages, logs, and MD files are
written in **English**. User interaction with Claude Code is in **Russian**.

**Implementation:** Language preferences are stored in `CLAUDE.local.md` (gitignored)
so they don't pollute the public repo but are always active locally.

### 7. Documentation as Mini-Site

**Decision:** README.md and docs/ are designed for visual impact when viewed on GitHub.
Rich formatting, badges, navigation, table of contents, and cross-linked pages.

## File Structure

```
app/public/                            # git root
├── .gitignore                         # whitelist approach
├── .editorconfig                      # consistent formatting
├── CLAUDE.md                          # shared Claude Code instructions (English)
├── CLAUDE.local.md                    # local language prefs (gitignored)
├── README.md                          # project showcase (badges, TOC, visuals)
│
├── docs/
│   ├── README.md                      # docs index / navigation hub
│   ├── plans/                         # design docs and implementation plans
│   ├── worklog/                       # development journal
│   └── architecture/                  # ADR (Architecture Decision Records)
│
├── scripts/
│   └── setup.sh                       # WP core download + plugin deps install
│
├── .idea/                             # PHPStorm (selected files in git)
│   ├── php.xml                        # PHP 8.1 language level
│   ├── wordpress.xml                  # WP integration enabled
│   ├── vcs.xml                        # git root config
│   └── xbo-market-kit.iml            # project module
│
├── wp-content/
│   ├── plugins/
│   │   └── xbo-market-kit/            # THE PLUGIN
│   │       ├── xbo-market-kit.php     # main plugin file
│   │       ├── composer.json          # autoload + dev tools
│   │       ├── phpstan.neon
│   │       ├── phpcs.xml
│   │       ├── phpunit.xml
│   │       ├── includes/
│   │       ├── assets/
│   │       └── tests/
│   ├── themes/                        # gitignored
│   └── uploads/                       # gitignored
│
├── wp-admin/                          # gitignored
├── wp-includes/                       # gitignored
└── wp-config.php                      # gitignored
```

## Setup Script Responsibilities

1. Check/install WP-CLI
2. Download WordPress core (specific version)
3. Install default themes
4. Run `composer install` in plugin directory
5. Validate configuration
