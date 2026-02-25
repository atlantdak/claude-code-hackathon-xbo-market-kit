# Local Crypto Icons — Design Document

**Date:** 2026-02-25
**Status:** Approved

## Problem

Many crypto icons fail to load in the Top Movers widget because:

1. XBO CDN (`assets.xbo.com`) returns 403 for ~50% of tokens (lesser-known ones like UP, VIRTUAL, KAITO, WET, WLD, SKR, ESP, IMU, LINEA, LLYX)
2. The Movers widget has no `onerror` fallback (removed due to Preact/Interactivity API conflict)
3. The jsDelivr `cryptocurrency-icons` package is outdated and doesn't cover newer tokens
4. Both CDN sources are unreliable at runtime — network issues, CORS, availability

## Solution

Store all crypto icons locally in the plugin directory with:

- **Initial sync:** WP-CLI command `wp xbo icons sync` downloads all icons
- **Background updates:** WP-Cron job (daily) downloads missing icons for new currencies
- **Local serving:** PHP helper resolves local icon URL, eliminating CDN dependency at runtime

## Architecture

### Cascade Download Strategy

For each currency symbol, try sources in order until one succeeds:

1. `https://assets.xbo.com/token-icons/svg/{SYMBOL}.svg` — XBO native CDN (uppercase)
2. `https://cdn.jsdelivr.net/npm/cryptocurrency-icons@0.18.1/svg/color/{symbol}.svg` — open-source package (lowercase)
3. Generate SVG placeholder with first letter — always succeeds

### Storage

- **Location:** `wp-content/plugins/xbo-market-kit/assets/images/icons/{symbol_lower}.svg`
- **Committed to git** — survives plugin updates, no uploads dependency
- **~205 files** (~1-2 MB total for SVG icons)

### New Files

| File | Purpose |
|------|---------|
| `includes/Icons/IconSync.php` | Cascade download logic, SVG generation, sync orchestration |
| `includes/Icons/IconResolver.php` | Resolve local icon URL for a given currency symbol |
| `includes/Cli/IconsCommand.php` | WP-CLI `wp xbo icons sync` command |

### Modified Files

| File | Change |
|------|--------|
| `TickerShortcode.php` | Replace CDN URLs with `IconResolver::url($base)` |
| `movers.js` | Replace CDN URL construction with localized base path |
| `xbo-market-kit.php` | Register cron hook, schedule daily event |

### IconSync Class

```php
class IconSync {
    private const SOURCES = [
        'https://assets.xbo.com/token-icons/svg/%s.svg',         // uppercase
        'https://cdn.jsdelivr.net/npm/cryptocurrency-icons@0.18.1/svg/color/%s.svg', // lowercase
    ];

    public function sync_all(): array;       // Download all 205 icons
    public function sync_missing(): array;   // Download only missing icons (for cron)
    private function download_icon(string $symbol): bool;
    private function generate_placeholder(string $symbol): string;
}
```

### IconResolver Class

```php
class IconResolver {
    public static function url(string $symbol): string;  // Returns local URL or placeholder URL
    public static function path(string $symbol): string; // Returns filesystem path
    public static function exists(string $symbol): bool; // Check if local icon exists
}
```

### WP-CLI Command

```bash
wp xbo icons sync          # Download all missing icons
wp xbo icons sync --force   # Re-download all icons (overwrite existing)
wp xbo icons status         # Show stats: total, downloaded, missing
```

### WP-Cron

- **Hook:** `xbo_market_kit_sync_icons`
- **Interval:** Every 24 hours
- **Action:** `IconSync::sync_missing()` — fetches currency list from API, downloads icons for any new symbols
- **Registered on:** Plugin activation
- **Cleared on:** Plugin deactivation

### Generated Placeholder SVG

For symbols with no icon available from any CDN:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
  <circle cx="20" cy="20" r="20" fill="#6366f1"/>
  <text x="20" y="20" text-anchor="middle" dominant-baseline="central"
        fill="#fff" font-size="18" font-family="system-ui, sans-serif">B</text>
</svg>
```

### Movers Widget Icon URL Strategy

Since the Movers widget renders icons client-side via Interactivity API:

- Pass icon base URL via `wp_localize_script` or context data
- JS constructs: `{baseUrl}/{symbol_lower}.svg`
- No onerror needed — all icons exist locally (real or placeholder)

## Trade-offs

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| Storage location | Plugin dir (git) | Reliability > git size; ~1-2 MB is acceptable |
| Icon format | SVG | Small file size, scales to any dimension |
| Cron frequency | 24 hours | New currencies appear rarely |
| Placeholder style | Letter in circle | Consistent with existing CSS fallback |
| Source cascade | XBO → jsDelivr → generate | Maximize real icons, guarantee coverage |
