# Project Environment Setup — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Initialize git repo, configure `.gitignore` (whitelist), PHPStorm, CLAUDE.md, documentation structure, setup script, and push initial commit to `atlantdak/claude-code-hackathon-xbo-market-kit`.

**Architecture:** Flat project root at `app/public/`. WordPress core is excluded via whitelist `.gitignore`. Plugin code lives in `wp-content/plugins/xbo-market-kit/`. All project meta (docs, scripts, configs) at root level.

**Tech Stack:** WordPress 6.9.1, PHP 8.1+, Composer, PHPCS, PHPStan, PHPUnit, WP-CLI

---

### Task 1: Initialize Git Repository

**Files:**
- Create: `.git/` (via `git init`)

**Step 1: Initialize git**

```bash
cd /Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
git init
```

Expected: `Initialized empty Git repository`

**Step 2: Set remote**

```bash
git remote add origin git@github.com:atlantdak/claude-code-hackathon-xbo-market-kit.git
```

Expected: no output (success)

---

### Task 2: Create .gitignore (Whitelist Approach)

**Files:**
- Create: `.gitignore`

**Step 1: Write .gitignore**

The whitelist approach: ignore everything, then un-ignore only what we need.

```gitignore
# =============================================================================
# WordPress Project — Whitelist .gitignore
# =============================================================================
# Strategy: Ignore EVERYTHING, then explicitly allow project files.
# This keeps WP core, uploads, and local configs out of the repo automatically.
# =============================================================================

# Ignore everything by default
*

# ---------- Git & editor configs ----------
!.gitignore
!.editorconfig
!.gitattributes

# ---------- Project root files ----------
!CLAUDE.md
!README.md
!LICENSE

# ---------- PHPStorm / IDE ----------
!.idea/
!.idea/php.xml
!.idea/wordpress.xml
!.idea/vcs.xml
!.idea/xbo-market-kit.iml
!.idea/inspectionProfiles/
!.idea/inspectionProfiles/**
# Ignore local IDE files that vary per machine
.idea/workspace.xml
.idea/tasks.xml
.idea/dictionaries/
.idea/shelf/
.idea/.name
.idea/misc.xml
.idea/modules.xml
.idea/compiler.xml
.idea/jarRepositories.xml
.idea/libraries/
.idea/caches/
.idea/artifacts/

# ---------- Documentation ----------
!docs/
!docs/**

# ---------- Scripts ----------
!scripts/
!scripts/**

# ---------- Plugin: xbo-market-kit ----------
!wp-content/
!wp-content/plugins/
!wp-content/plugins/xbo-market-kit/
!wp-content/plugins/xbo-market-kit/**

# Ignore vendor inside plugin (installed via composer)
wp-content/plugins/xbo-market-kit/vendor/
wp-content/plugins/xbo-market-kit/node_modules/

# ---------- Always ignore ----------
.DS_Store
Thumbs.db
*.log
*.swp
*.swo
*~
local-xdebuginfo.php
```

**Step 2: Verify gitignore works**

```bash
git status
```

Expected: only `.gitignore` shows as untracked. No WP core files, no wp-admin, no wp-includes.

---

### Task 3: Create .editorconfig

**Files:**
- Create: `.editorconfig`

**Step 1: Write .editorconfig**

```ini
# https://editorconfig.org
root = true

[*]
charset = utf-8
end_of_line = lf
insert_final_newline = true
trim_trailing_whitespace = true
indent_style = tab
indent_size = 4

[*.md]
trim_trailing_whitespace = false

[*.yml]
indent_style = space
indent_size = 2

[*.json]
indent_style = space
indent_size = 2

[*.xml]
indent_style = space
indent_size = 2
```

---

### Task 4: Create CLAUDE.md

**Files:**
- Create: `CLAUDE.md`

**Step 1: Write CLAUDE.md**

```markdown
# XBO Market Kit — Claude Code Instructions

## Project Overview

WordPress plugin developed for the Claude Code Hackathon.
Repository: https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit

## Project Structure

- **Git root:** `app/public/` (this directory)
- **Plugin code:** `wp-content/plugins/xbo-market-kit/`
- **Documentation:** `docs/`
- **Setup scripts:** `scripts/`

## Tech Stack

- WordPress 6.9.1
- PHP 8.1+ (currently running 8.2)
- Composer for dependency management (inside plugin dir)

## Development Commands

All commands run from `wp-content/plugins/xbo-market-kit/`:

```bash
# Install dependencies
composer install

# Code style check (WordPress Coding Standards)
composer run phpcs

# Code style auto-fix
composer run phpcbf

# Static analysis
composer run phpstan

# Unit tests
composer run test
```

## Code Quality Rules

- Follow WordPress Coding Standards (enforced by PHPCS)
- PHPStan level 6 minimum
- All new features must have unit tests
- Use WordPress hooks and filters — never modify core

## File Organization (Plugin)

```
xbo-market-kit/
├── xbo-market-kit.php    # Main plugin bootstrap
├── includes/             # PHP classes (PSR-4 autoloaded)
├── assets/               # Static assets (CSS, JS, images)
├── tests/                # PHPUnit tests
├── composer.json
├── phpstan.neon
├── phpcs.xml
└── phpunit.xml
```

## Git Workflow

- Commit messages in English, imperative mood
- One logical change per commit
- Prefix: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`

## Documentation

- All documentation written in English
- Plans: `docs/plans/`
- Work log: `docs/worklog/`
- Architecture decisions: `docs/architecture/`

## Important Notes

- WordPress core files are NOT in git — use `scripts/setup.sh` to install
- Never edit WP core files
- Plugin must be self-contained (all deps via Composer)
```

---

### Task 5: Create CLAUDE.local.md (Language Preferences — Gitignored)

**Files:**
- Create: `CLAUDE.local.md`

This file is NOT committed to git (covered by the whitelist `.gitignore` since it's not
in the `!` allow list). It provides local-only instructions.

**Step 1: Write CLAUDE.local.md**

```markdown
# Local Claude Code Preferences

## Language

- User communicates in **Russian** — respond in **Russian**
- All code, documentation, comments, commit messages, logs, and MD files: **English only**
- This rule applies to ALL output that gets persisted (files, commits, docs)
- Conversation with user: always Russian
```

---

### Task 6: Create PHPStorm Configuration

**Files:**
- Create: `.idea/php.xml`
- Create: `.idea/wordpress.xml`
- Create: `.idea/vcs.xml`
- Create: `.idea/xbo-market-kit.iml`

**Step 1: Write `.idea/php.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="PhpProjectSharedConfiguration" php_language_level="8.1" />
</project>
```

**Step 2: Write `.idea/vcs.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="VcsDirectoryMappings">
    <mapping directory="$PROJECT_DIR$" vcs="Git" />
  </component>
</project>
```

**Step 3: Write `.idea/wordpress.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="WordPressConfiguration">
    <option name="enabled" value="true" />
    <option name="wordpressPath" value="$PROJECT_DIR$" />
  </component>
</project>
```

**Step 4: Write `.idea/xbo-market-kit.iml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<module type="WEB_MODULE" version="4">
  <component name="NewModuleRootManager">
    <content url="file://$MODULE_DIR$">
      <sourceFolder url="file://$MODULE_DIR$/wp-content/plugins/xbo-market-kit/includes" isTestSource="false" packagePrefix="XboMarketKit\" />
      <sourceFolder url="file://$MODULE_DIR$/wp-content/plugins/xbo-market-kit/tests" isTestSource="true" />
      <excludeFolder url="file://$MODULE_DIR$/wp-admin" />
      <excludeFolder url="file://$MODULE_DIR$/wp-includes" />
      <excludeFolder url="file://$MODULE_DIR$/wp-content/uploads" />
      <excludeFolder url="file://$MODULE_DIR$/wp-content/themes" />
    </content>
    <orderEntry type="inheritedJdk" />
    <orderEntry type="sourceFolder" forTests="false" />
  </component>
</module>
```

---

### Task 7: Create README.md (Hackathon Showcase)

**Files:**
- Create: `README.md`

**Step 1: Write README.md**

A visually impactful README for the GitHub repo landing page.

```markdown
<div align="center">

# XBO Market Kit

**WordPress Plugin for Market Management**

[![WordPress](https://img.shields.io/badge/WordPress-6.9+-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Claude Code](https://img.shields.io/badge/Built%20with-Claude%20Code-D97706?style=for-the-badge&logo=anthropic&logoColor=white)](https://claude.ai/)
[![Hackathon](https://img.shields.io/badge/Claude%20Code-Hackathon%202026-10B981?style=for-the-badge)](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)

*Built entirely with AI-assisted development using Claude Code*

---

[Getting Started](#-getting-started) &bull;
[Architecture](#-architecture) &bull;
[Documentation](#-documentation) &bull;
[Development](#-development) &bull;
[AI Workflow](#-ai-workflow)

</div>

---

## About

XBO Market Kit is a WordPress plugin developed as part of the **Claude Code Hackathon 2026**. The entire development process — from brainstorming to implementation — is driven by AI agents using Claude Code.

> **Hackathon Focus:** Demonstrating a complete AI-assisted development workflow with Claude Code, including planning, coding, testing, and documentation — all managed through conversational AI.

## Getting Started

### Prerequisites

- [Local by Flywheel](https://localwp.com/) or any WordPress local environment
- PHP 8.1+
- Composer
- WP-CLI

### Quick Setup

```bash
# Clone the repository
git clone git@github.com:atlantdak/claude-code-hackathon-xbo-market-kit.git
cd claude-code-hackathon-xbo-market-kit/app/public

# Run the setup script (downloads WP core + installs dependencies)
bash scripts/setup.sh

# Open in PHPStorm
# The .idea/ directory is pre-configured
```

## Architecture

```
app/public/                    # Project root
├── docs/                      # Full project documentation
│   ├── plans/                 # Design docs & implementation plans
│   ├── worklog/               # Development journal
│   └── architecture/          # Architecture Decision Records
├── scripts/                   # Automation scripts
├── wp-content/
│   └── plugins/
│       └── xbo-market-kit/    # The plugin
│           ├── includes/      # PHP source (PSR-4)
│           ├── assets/        # CSS, JS, images
│           └── tests/         # PHPUnit tests
└── CLAUDE.md                  # AI agent instructions
```

## Documentation

| Document | Description |
|----------|-------------|
| [Project Plans](docs/plans/) | Design documents and implementation plans |
| [Work Log](docs/worklog/) | Daily development journal |
| [Architecture](docs/architecture/) | ADR — key technical decisions |
| [CLAUDE.md](CLAUDE.md) | Instructions for Claude Code agents |

## Development

```bash
cd wp-content/plugins/xbo-market-kit

# Install dependencies
composer install

# Run code style checks
composer run phpcs

# Run static analysis
composer run phpstan

# Run tests
composer run test
```

## AI Workflow

This project showcases a complete AI-driven development workflow:

1. **Brainstorming** — Collaborative design sessions with Claude
2. **Planning** — Structured implementation plans with TDD
3. **Implementation** — Code written by Claude Code agents
4. **Review** — Automated code review via subagents
5. **Documentation** — Auto-generated and maintained docs

Every step is logged in [docs/worklog/](docs/worklog/) for full transparency.

---

<div align="center">

**Built with Claude Code for the Claude Code Hackathon 2026**

[Documentation](docs/) &bull; [Plans](docs/plans/) &bull; [Work Log](docs/worklog/)

</div>
```

---

### Task 8: Create docs/ Index Files

**Files:**
- Create: `docs/README.md`
- Create: `docs/worklog/README.md`
- Create: `docs/architecture/README.md`
- Create: `docs/plans/README.md`

**Step 1: Write `docs/README.md`**

```markdown
# Documentation

Welcome to the XBO Market Kit project documentation.

## Navigation

| Section | Purpose |
|---------|---------|
| [Plans](plans/) | Design documents and implementation plans |
| [Work Log](worklog/) | Development journal — what was done and why |
| [Architecture](architecture/) | Architecture Decision Records (ADRs) |

## Conventions

- All documentation is written in **English**
- Plans follow the naming pattern: `YYYY-MM-DD-<topic>.md`
- ADRs follow: `ADR-NNN-<title>.md`
- Work log entries: `YYYY-MM-DD.md`
```

**Step 2: Write `docs/plans/README.md`**

```markdown
# Plans

Design documents and implementation plans created during development.

## Index

| Date | Plan | Status |
|------|------|--------|
| 2026-02-22 | [Project Setup Design](2026-02-22-project-setup-design.md) | Approved |
| 2026-02-22 | [Project Setup Implementation](2026-02-22-project-setup-plan.md) | In Progress |

---

*Plans are created through the brainstorming skill and executed via the executing-plans skill.*
```

**Step 3: Write `docs/worklog/README.md`**

```markdown
# Work Log

Daily development journal tracking progress, decisions, and learnings.

## Entries

| Date | Summary |
|------|---------|
| 2026-02-22 | Project initialization and environment setup |

---

*New entries are added daily during active development.*
```

**Step 4: Write `docs/architecture/README.md`**

```markdown
# Architecture Decision Records

Key technical decisions documented for future reference.

## Index

| ADR | Title | Status |
|-----|-------|--------|
| — | *No ADRs yet* | — |

## Format

Each ADR follows this structure:
- **Context:** Why was a decision needed?
- **Decision:** What was decided?
- **Consequences:** What are the trade-offs?

---

*ADRs are created when significant technical decisions are made.*
```

---

### Task 9: Create First Worklog Entry

**Files:**
- Create: `docs/worklog/2026-02-22.md`

**Step 1: Write initial worklog entry**

```markdown
# 2026-02-22 — Project Initialization

## Summary

Set up the development environment for XBO Market Kit WordPress plugin.
Hackathon project using Claude Code for AI-assisted development.

## Completed

- [x] Chose project structure (git root at `app/public/`)
- [x] Designed whitelist `.gitignore` strategy
- [x] Created design document: [Project Setup Design](../plans/2026-02-22-project-setup-design.md)
- [x] Created implementation plan: [Project Setup Plan](../plans/2026-02-22-project-setup-plan.md)
- [x] Initialized git repository
- [x] Configured PHPStorm project
- [x] Created CLAUDE.md and documentation structure
- [x] Created setup script for WP core bootstrapping
- [x] Initial commit and push to GitHub

## Decisions

- **Git root = `app/public/`** — Claude Code runs from here, keeps everything accessible
- **Whitelist `.gitignore`** — safer than blacklisting WP core files individually
- **Language split** — Russian for conversation, English for all persisted artifacts
- **Code quality in plugin dir** — Composer, PHPCS, PHPStan, PHPUnit inside plugin

## Tools Used

- Claude Code (brainstorming, planning, implementation)
- Local by Flywheel (WordPress environment)
- PHPStorm (IDE)
```

---

### Task 10: Create scripts/setup.sh

**Files:**
- Create: `scripts/setup.sh`

**Step 1: Write setup script**

```bash
#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# XBO Market Kit — Project Setup Script
# =============================================================================
# Downloads WordPress core and installs plugin dependencies.
# Run from the project root: bash scripts/setup.sh
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
PLUGIN_DIR="$PROJECT_ROOT/wp-content/plugins/xbo-market-kit"
WP_VERSION="6.9.1"

echo "=== XBO Market Kit — Setup ==="
echo "Project root: $PROJECT_ROOT"
echo ""

# --- Check prerequisites ---
check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo "ERROR: $1 is not installed or not in PATH."
        echo "If using Local by Flywheel, open the site shell first."
        exit 1
    fi
}

echo "[1/4] Checking prerequisites..."
check_command php
check_command wp
check_command composer
echo "  PHP: $(php -r 'echo PHP_VERSION;')"
echo "  WP-CLI: $(wp --version 2>/dev/null || echo 'available')"
echo "  Composer: $(composer --version 2>/dev/null | head -1)"
echo ""

# --- Download WordPress core ---
echo "[2/4] Checking WordPress core..."
if [ -f "$PROJECT_ROOT/wp-includes/version.php" ]; then
    CURRENT_VERSION=$(php -r "include '$PROJECT_ROOT/wp-includes/version.php'; echo \$wp_version;")
    echo "  WordPress $CURRENT_VERSION already installed."
else
    echo "  Downloading WordPress $WP_VERSION..."
    wp core download --version="$WP_VERSION" --path="$PROJECT_ROOT" --skip-content
    echo "  WordPress $WP_VERSION downloaded."
fi
echo ""

# --- Install default themes ---
echo "[3/4] Checking themes..."
THEMES_DIR="$PROJECT_ROOT/wp-content/themes"
if [ ! -d "$THEMES_DIR/twentytwentyfive" ]; then
    echo "  Installing default theme..."
    wp theme install twentytwentyfive --path="$PROJECT_ROOT" 2>/dev/null || echo "  Theme install requires active WP. Skipping."
else
    echo "  Default themes present."
fi
echo ""

# --- Install plugin composer dependencies ---
echo "[4/4] Installing plugin dependencies..."
if [ -f "$PLUGIN_DIR/composer.json" ]; then
    cd "$PLUGIN_DIR"
    composer install --no-interaction
    echo "  Plugin dependencies installed."
else
    echo "  No composer.json in plugin yet. Skipping."
fi
echo ""

echo "=== Setup complete ==="
echo ""
echo "Next steps:"
echo "  1. Open the project in PHPStorm (root: $PROJECT_ROOT)"
echo "  2. Start developing in: $PLUGIN_DIR"
echo "  3. Run 'composer run phpcs' to check code style"
```

---

### Task 11: Create Plugin Skeleton

**Files:**
- Create: `wp-content/plugins/xbo-market-kit/xbo-market-kit.php`
- Create: `wp-content/plugins/xbo-market-kit/composer.json`
- Create: `wp-content/plugins/xbo-market-kit/phpcs.xml`
- Create: `wp-content/plugins/xbo-market-kit/phpstan.neon`
- Create: `wp-content/plugins/xbo-market-kit/phpunit.xml`
- Create: `wp-content/plugins/xbo-market-kit/includes/.gitkeep`
- Create: `wp-content/plugins/xbo-market-kit/assets/.gitkeep`
- Create: `wp-content/plugins/xbo-market-kit/tests/bootstrap.php`

**Step 1: Write main plugin file**

```php
<?php
/**
 * Plugin Name: XBO Market Kit
 * Plugin URI:  https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit
 * Description: Market management toolkit for WordPress.
 * Version:     0.1.0
 * Author:      atlantdak
 * Author URI:  https://github.com/atlantdak
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xbo-market-kit
 * Requires PHP: 8.1
 * Requires at least: 6.7
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'XBO_MARKET_KIT_VERSION', '0.1.0' );
define( 'XBO_MARKET_KIT_FILE', __FILE__ );
define( 'XBO_MARKET_KIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'XBO_MARKET_KIT_URL', plugin_dir_url( __FILE__ ) );

// Autoload via Composer.
if ( file_exists( XBO_MARKET_KIT_DIR . 'vendor/autoload.php' ) ) {
	require_once XBO_MARKET_KIT_DIR . 'vendor/autoload.php';
}
```

**Step 2: Write `composer.json`**

```json
{
	"name": "atlantdak/xbo-market-kit",
	"description": "Market management toolkit for WordPress",
	"type": "wordpress-plugin",
	"license": "GPL-2.0-or-later",
	"require": {
		"php": ">=8.1"
	},
	"require-dev": {
		"phpunit/phpunit": "^10.0",
		"wp-coding-standards/wpcs": "^3.0",
		"phpstan/phpstan": "^2.0",
		"szepeviktor/phpstan-wordpress": "^2.0",
		"dealerdirect/phpcodesniffer-composer-installer": "^1.0",
		"phpcompatibility/phpcompatibility-wp": "^2.1",
		"yoast/phpunit-polyfills": "^3.0"
	},
	"autoload": {
		"psr-4": {
			"XboMarketKit\\": "includes/"
		}
	},
	"autoload-dev": {
		"psr-4": {
			"XboMarketKit\\Tests\\": "tests/"
		}
	},
	"scripts": {
		"phpcs": "phpcs",
		"phpcbf": "phpcbf",
		"phpstan": "phpstan analyse",
		"test": "phpunit"
	},
	"config": {
		"allow-plugins": {
			"dealerdirect/phpcodesniffer-composer-installer": true
		},
		"sort-packages": true
	}
}
```

**Step 3: Write `phpcs.xml`**

```xml
<?xml version="1.0"?>
<ruleset name="XBO Market Kit">
	<description>PHPCS configuration for XBO Market Kit plugin.</description>

	<file>.</file>

	<exclude-pattern>/vendor/*</exclude-pattern>
	<exclude-pattern>/node_modules/*</exclude-pattern>
	<exclude-pattern>/tests/*</exclude-pattern>

	<arg name="extensions" value="php" />
	<arg name="colors" />
	<arg value="sp" />

	<config name="minimum_supported_wp_version" value="6.7" />
	<config name="testVersion" value="8.1-" />

	<rule ref="WordPress">
		<exclude name="WordPress.Files.FileName.InvalidClassFileName" />
	</rule>

	<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
		<properties>
			<property name="prefixes" type="array">
				<element value="xbo_market_kit" />
				<element value="XboMarketKit" />
			</property>
		</properties>
	</rule>

	<rule ref="WordPress.WP.I18n">
		<properties>
			<property name="text_domain" type="array">
				<element value="xbo-market-kit" />
			</property>
		</properties>
	</rule>
</ruleset>
```

**Step 4: Write `phpstan.neon`**

```neon
includes:
	- vendor/szepeviktor/phpstan-wordpress/extension.neon

parameters:
	level: 6
	paths:
		- xbo-market-kit.php
		- includes/
	scanDirectories:
		- ../../..
	excludePaths:
		- vendor/
```

**Step 5: Write `phpunit.xml`**

```xml
<?xml version="1.0"?>
<phpunit
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.0/phpunit.xsd"
	bootstrap="tests/bootstrap.php"
	colors="true"
	beStrictAboutTestsThatDoNotTestAnything="true"
>
	<testsuites>
		<testsuite name="unit">
			<directory suffix="Test.php">tests/</directory>
		</testsuite>
	</testsuites>
	<source>
		<include>
			<directory suffix=".php">includes/</directory>
		</include>
	</source>
</phpunit>
```

**Step 6: Write `tests/bootstrap.php`**

```php
<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
```

**Step 7: Create placeholder directories**

```bash
touch wp-content/plugins/xbo-market-kit/includes/.gitkeep
touch wp-content/plugins/xbo-market-kit/assets/.gitkeep
```

---

### Task 12: Create .gitattributes

**Files:**
- Create: `.gitattributes`

**Step 1: Write .gitattributes**

```gitattributes
# Auto detect text files and normalize line endings
* text=auto

# Force LF
*.php text eol=lf
*.js text eol=lf
*.css text eol=lf
*.md text eol=lf
*.json text eol=lf
*.xml text eol=lf
*.yml text eol=lf
*.sh text eol=lf

# Binary files
*.png binary
*.jpg binary
*.gif binary
*.ico binary
*.woff binary
*.woff2 binary
*.ttf binary
*.eot binary

# Export ignore (not included in GitHub archive downloads)
.idea/ export-ignore
docs/ export-ignore
tests/ export-ignore
scripts/ export-ignore
.editorconfig export-ignore
.gitattributes export-ignore
CLAUDE.md export-ignore
phpcs.xml export-ignore
phpstan.neon export-ignore
phpunit.xml export-ignore
```

---

### Task 13: Initial Commit and Push

**Step 1: Stage all files**

```bash
git add -A
git status
```

Expected: all project files staged — `.gitignore`, `CLAUDE.md`, `README.md`, `docs/`, `scripts/`, `.idea/` configs, plugin skeleton. NO WP core files.

**Step 2: Verify no WP core files**

```bash
git diff --cached --name-only | grep -E '^(wp-admin|wp-includes|wp-login|wp-settings|wp-cron|wp-config)' || echo "OK: No WP core files staged"
```

Expected: `OK: No WP core files staged`

**Step 3: Commit**

```bash
git commit -m "chore: initialize project structure

- Whitelist .gitignore excluding WordPress core
- CLAUDE.md with project instructions
- README.md with hackathon showcase
- PHPStorm configuration (.idea/)
- Documentation structure (docs/plans, worklog, architecture)
- Setup script (scripts/setup.sh)
- Plugin skeleton (xbo-market-kit) with composer, phpcs, phpstan, phpunit
- .editorconfig and .gitattributes"
```

**Step 4: Push to GitHub**

```bash
git branch -M main
git push -u origin main
```

Expected: pushed to `atlantdak/claude-code-hackathon-xbo-market-kit` main branch.

---

## Execution Summary

| Task | Description | ~Time |
|------|-------------|-------|
| 1 | Init git + remote | 1 min |
| 2 | .gitignore (whitelist) | 2 min |
| 3 | .editorconfig | 1 min |
| 4 | CLAUDE.md | 2 min |
| 5 | CLAUDE.local.md | 1 min |
| 6 | PHPStorm config | 2 min |
| 7 | README.md | 3 min |
| 8 | docs/ index files | 2 min |
| 9 | First worklog entry | 1 min |
| 10 | scripts/setup.sh | 2 min |
| 11 | Plugin skeleton | 5 min |
| 12 | .gitattributes | 1 min |
| 13 | Initial commit + push | 2 min |
