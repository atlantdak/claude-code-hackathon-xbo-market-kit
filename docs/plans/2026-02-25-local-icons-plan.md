# Local Crypto Icons — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Download and store all crypto icons locally, eliminating CDN dependency at runtime, with daily cron sync for new tokens.

**Architecture:** `IconSync` handles cascade download (XBO CDN → jsDelivr → generated SVG placeholder). `IconResolver` resolves local icon URLs for rendering. WP-CLI command for initial sync, WP-Cron for daily background updates. Both shortcodes and JS use local paths.

**Tech Stack:** PHP 8.1+, WordPress Cron API, WP-CLI, Brain\Monkey for tests

**Design doc:** `docs/plans/2026-02-25-local-icons-design.md`

---

### Task 1: IconResolver — static URL/path helper

**Files:**
- Create: `includes/Icons/IconResolver.php`
- Test: `tests/Unit/Icons/IconResolverTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Icons/IconResolverTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Icons;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Icons\IconResolver;

class IconResolverTest extends TestCase {
    use MockeryPHPUnitIntegration;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_url_returns_local_path_when_icon_exists(): void {
        $icons_dir = sys_get_temp_dir() . '/xbo-icons-test-' . uniqid();
        mkdir( $icons_dir, 0755, true );
        file_put_contents( $icons_dir . '/btc.svg', '<svg></svg>' );

        $resolver = new IconResolver( $icons_dir, 'http://example.com/icons' );
        $this->assertSame( 'http://example.com/icons/btc.svg', $resolver->url( 'BTC' ) );

        unlink( $icons_dir . '/btc.svg' );
        rmdir( $icons_dir );
    }

    public function test_url_returns_placeholder_when_icon_missing(): void {
        $icons_dir = sys_get_temp_dir() . '/xbo-icons-test-' . uniqid();
        mkdir( $icons_dir, 0755, true );

        $resolver = new IconResolver( $icons_dir, 'http://example.com/icons' );
        $url = $resolver->url( 'XYZ' );

        // Should contain data URI SVG with letter X
        $this->assertStringStartsWith( 'data:image/svg+xml', $url );
        $this->assertStringContainsString( '>X<', urldecode( $url ) );

        rmdir( $icons_dir );
    }

    public function test_path_returns_lowercased_filename(): void {
        $resolver = new IconResolver( '/tmp/icons', 'http://example.com/icons' );
        $this->assertSame( '/tmp/icons/eth.svg', $resolver->path( 'ETH' ) );
    }

    public function test_exists_returns_false_for_missing_icon(): void {
        $resolver = new IconResolver( '/tmp/nonexistent-dir', 'http://example.com/icons' );
        $this->assertFalse( $resolver->exists( 'BTC' ) );
    }

    public function test_placeholder_svg_returns_valid_svg(): void {
        $resolver = new IconResolver( '/tmp/icons', 'http://example.com/icons' );
        $svg = $resolver->placeholder_svg( 'BTC' );
        $this->assertStringContainsString( '<svg', $svg );
        $this->assertStringContainsString( '>B<', $svg );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=IconResolverTest`
Expected: FAIL — class `IconResolver` not found

**Step 3: Write minimal implementation**

Create `includes/Icons/IconResolver.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Icons;

class IconResolver {
    private string $icons_dir;
    private string $icons_url;

    public function __construct( string $icons_dir, string $icons_url ) {
        $this->icons_dir = rtrim( $icons_dir, '/' );
        $this->icons_url = rtrim( $icons_url, '/' );
    }

    public function url( string $symbol ): string {
        $file = strtolower( $symbol ) . '.svg';
        if ( file_exists( $this->icons_dir . '/' . $file ) ) {
            return $this->icons_url . '/' . $file;
        }
        return 'data:image/svg+xml,' . rawurlencode( $this->placeholder_svg( $symbol ) );
    }

    public function path( string $symbol ): string {
        return $this->icons_dir . '/' . strtolower( $symbol ) . '.svg';
    }

    public function exists( string $symbol ): bool {
        return file_exists( $this->path( $symbol ) );
    }

    public function placeholder_svg( string $symbol ): string {
        $letter = mb_strtoupper( mb_substr( $symbol, 0, 1 ) );
        $letter = htmlspecialchars( $letter, ENT_XML1, 'UTF-8' );
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">'
            . '<circle cx="20" cy="20" r="20" fill="#6366f1"/>'
            . '<text x="20" y="20" text-anchor="middle" dominant-baseline="central"'
            . ' fill="#fff" font-size="18" font-family="system-ui, sans-serif">'
            . $letter . '</text></svg>';
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=IconResolverTest`
Expected: PASS — all 5 tests green

**Step 5: Run phpcs + phpstan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs -- includes/Icons/IconResolver.php && composer run phpstan -- --no-progress`

**Step 6: Commit**

```bash
git add includes/Icons/IconResolver.php tests/Unit/Icons/IconResolverTest.php
git commit -m "feat(icons): add IconResolver for local icon URL resolution"
```

---

### Task 2: IconSync — cascade download engine

**Files:**
- Create: `includes/Icons/IconSync.php`
- Test: `tests/Unit/Icons/IconSyncTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Icons/IconSyncTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Icons;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Icons\IconSync;
use XboMarketKit\Icons\IconResolver;

class IconSyncTest extends TestCase {
    use MockeryPHPUnitIntegration;

    private string $icons_dir;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        $this->icons_dir = sys_get_temp_dir() . '/xbo-icons-sync-' . uniqid();
        mkdir( $this->icons_dir, 0755, true );
    }

    protected function tearDown(): void {
        // Clean up temp dir.
        $files = glob( $this->icons_dir . '/*' );
        if ( $files ) {
            array_map( 'unlink', $files );
        }
        if ( is_dir( $this->icons_dir ) ) {
            rmdir( $this->icons_dir );
        }
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_download_icon_saves_from_first_source(): void {
        $svg_content = '<svg><circle/></svg>';

        Functions\expect( 'wp_remote_get' )->once()->andReturn(
            array( 'response' => array( 'code' => 200 ), 'body' => $svg_content )
        );
        Functions\expect( 'is_wp_error' )->once()->andReturn( false );
        Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );
        Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( $svg_content );

        $resolver = new IconResolver( $this->icons_dir, 'http://example.com/icons' );
        $sync = new IconSync( $resolver );
        $result = $sync->download_icon( 'BTC' );

        $this->assertTrue( $result );
        $this->assertFileExists( $this->icons_dir . '/btc.svg' );
        $this->assertSame( $svg_content, file_get_contents( $this->icons_dir . '/btc.svg' ) );
    }

    public function test_download_icon_tries_second_source_on_failure(): void {
        $svg_content = '<svg><rect/></svg>';

        // First source fails (403).
        Functions\expect( 'wp_remote_get' )->twice()->andReturnUsing(
            function ( string $url ) use ( $svg_content ) {
                if ( str_contains( $url, 'assets.xbo.com' ) ) {
                    return array( 'response' => array( 'code' => 403 ), 'body' => '' );
                }
                return array( 'response' => array( 'code' => 200 ), 'body' => $svg_content );
            }
        );
        Functions\expect( 'is_wp_error' )->twice()->andReturn( false );
        Functions\expect( 'wp_remote_retrieve_response_code' )->twice()->andReturnUsing(
            function () {
                static $call = 0;
                return ++$call === 1 ? 403 : 200;
            }
        );
        Functions\expect( 'wp_remote_retrieve_body' )->twice()->andReturnUsing(
            function () use ( $svg_content ) {
                static $call = 0;
                return ++$call === 1 ? '' : $svg_content;
            }
        );

        $resolver = new IconResolver( $this->icons_dir, 'http://example.com/icons' );
        $sync = new IconSync( $resolver );
        $result = $sync->download_icon( 'UNKNOWN' );

        $this->assertTrue( $result );
        $this->assertFileExists( $this->icons_dir . '/unknown.svg' );
    }

    public function test_download_icon_generates_placeholder_when_all_sources_fail(): void {
        // Both sources fail.
        Functions\expect( 'wp_remote_get' )->twice()->andReturn(
            array( 'response' => array( 'code' => 403 ), 'body' => '' )
        );
        Functions\expect( 'is_wp_error' )->twice()->andReturn( false );
        Functions\expect( 'wp_remote_retrieve_response_code' )->twice()->andReturn( 403 );

        $resolver = new IconResolver( $this->icons_dir, 'http://example.com/icons' );
        $sync = new IconSync( $resolver );
        $result = $sync->download_icon( 'ZZZZZ' );

        $this->assertTrue( $result );
        $this->assertFileExists( $this->icons_dir . '/zzzzz.svg' );
        $this->assertStringContainsString( '>Z<', file_get_contents( $this->icons_dir . '/zzzzz.svg' ) );
    }

    public function test_sync_missing_skips_existing_icons(): void {
        // Pre-create an icon.
        file_put_contents( $this->icons_dir . '/btc.svg', '<svg></svg>' );

        $resolver = new IconResolver( $this->icons_dir, 'http://example.com/icons' );
        $sync = new IconSync( $resolver );

        // sync_missing with only BTC — should skip it.
        $result = $sync->sync_missing( array( 'BTC' ) );
        $this->assertSame( 0, $result['downloaded'] );
        $this->assertSame( 1, $result['skipped'] );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=IconSyncTest`
Expected: FAIL — class `IconSync` not found

**Step 3: Write minimal implementation**

Create `includes/Icons/IconSync.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Icons;

class IconSync {
    private const SOURCES = array(
        'https://assets.xbo.com/token-icons/svg/%s.svg',
        'https://cdn.jsdelivr.net/npm/cryptocurrency-icons@0.18.1/svg/color/%s.svg',
    );

    private IconResolver $resolver;

    public function __construct( IconResolver $resolver ) {
        $this->resolver = $resolver;
    }

    public function download_icon( string $symbol ): bool {
        $path = $this->resolver->path( $symbol );
        $dir  = dirname( $path );
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        // Source 1: XBO CDN (uppercase symbol).
        $url = sprintf( self::SOURCES[0], rawurlencode( strtoupper( $symbol ) ) );
        $svg = $this->fetch_svg( $url );
        if ( $svg ) {
            return (bool) file_put_contents( $path, $svg );
        }

        // Source 2: jsDelivr (lowercase symbol).
        $url = sprintf( self::SOURCES[1], rawurlencode( strtolower( $symbol ) ) );
        $svg = $this->fetch_svg( $url );
        if ( $svg ) {
            return (bool) file_put_contents( $path, $svg );
        }

        // Fallback: generate placeholder SVG.
        return (bool) file_put_contents( $path, $this->resolver->placeholder_svg( $symbol ) );
    }

    public function sync_all( array $symbols, bool $force = false ): array {
        $result = array( 'downloaded' => 0, 'skipped' => 0, 'failed' => 0 );
        foreach ( $symbols as $symbol ) {
            if ( ! $force && $this->resolver->exists( $symbol ) ) {
                ++$result['skipped'];
                continue;
            }
            if ( $this->download_icon( $symbol ) ) {
                ++$result['downloaded'];
            } else {
                ++$result['failed'];
            }
        }
        return $result;
    }

    public function sync_missing( array $symbols ): array {
        return $this->sync_all( $symbols, false );
    }

    private function fetch_svg( string $url ): ?string {
        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
        if ( is_wp_error( $response ) ) {
            return null;
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return null;
        }
        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) || ! str_contains( $body, '<svg' ) ) {
            return null;
        }
        return $body;
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test -- --filter=IconSyncTest`
Expected: PASS — all 4 tests green

**Step 5: Run phpcs + phpstan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs -- includes/Icons/IconSync.php && composer run phpstan -- --no-progress`

**Step 6: Commit**

```bash
git add includes/Icons/IconSync.php tests/Unit/Icons/IconSyncTest.php
git commit -m "feat(icons): add IconSync cascade download engine"
```

---

### Task 3: WP-CLI command `wp xbo icons sync`

**Files:**
- Create: `includes/Cli/IconsCommand.php`

**Step 1: Write the implementation**

Create `includes/Cli/IconsCommand.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Cli;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Cache\CacheManager;
use XboMarketKit\Icons\IconResolver;
use XboMarketKit\Icons\IconSync;

class IconsCommand {
    public function sync( array $args, array $assoc_args ): void {
        $force = isset( $assoc_args['force'] );
        $cache = new CacheManager();
        $api   = new ApiClient( $cache );

        $icons_dir = XBO_MARKET_KIT_DIR . 'assets/images/icons';
        $icons_url = XBO_MARKET_KIT_URL . 'assets/images/icons';
        $resolver  = new IconResolver( $icons_dir, $icons_url );
        $sync      = new IconSync( $resolver );

        \WP_CLI::log( 'Fetching currency list from XBO API...' );
        $response = $api->get_currencies();
        if ( ! $response->success ) {
            \WP_CLI::error( 'Failed to fetch currencies: ' . ( $response->error_message ?? 'unknown error' ) );
            return;
        }

        $symbols = array_column( $response->data, 'code' );
        \WP_CLI::log( sprintf( 'Found %d currencies. %s...', count( $symbols ), $force ? 'Force re-downloading all' : 'Downloading missing' ) );

        $result = $sync->sync_all( $symbols, $force );
        \WP_CLI::success( sprintf(
            'Done. Downloaded: %d, Skipped: %d, Failed: %d',
            $result['downloaded'],
            $result['skipped'],
            $result['failed']
        ) );
    }

    public function status( array $args, array $assoc_args ): void {
        $cache = new CacheManager();
        $api   = new ApiClient( $cache );

        $icons_dir = XBO_MARKET_KIT_DIR . 'assets/images/icons';
        $icons_url = XBO_MARKET_KIT_URL . 'assets/images/icons';
        $resolver  = new IconResolver( $icons_dir, $icons_url );

        $response = $api->get_currencies();
        if ( ! $response->success ) {
            \WP_CLI::error( 'Failed to fetch currencies.' );
            return;
        }

        $symbols   = array_column( $response->data, 'code' );
        $existing  = 0;
        $missing   = array();
        foreach ( $symbols as $symbol ) {
            if ( $resolver->exists( $symbol ) ) {
                ++$existing;
            } else {
                $missing[] = $symbol;
            }
        }

        \WP_CLI::log( sprintf( 'Total currencies: %d', count( $symbols ) ) );
        \WP_CLI::log( sprintf( 'Icons present:    %d', $existing ) );
        \WP_CLI::log( sprintf( 'Icons missing:    %d', count( $missing ) ) );
        if ( $missing ) {
            \WP_CLI::log( 'Missing: ' . implode( ', ', array_slice( $missing, 0, 20 ) ) );
            if ( count( $missing ) > 20 ) {
                \WP_CLI::log( sprintf( '... and %d more', count( $missing ) - 20 ) );
            }
        }
    }
}
```

**Step 2: Register WP-CLI command in bootstrap**

Add to `xbo-market-kit.php` after activation/deactivation hooks:

```php
// Register WP-CLI commands.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    \WP_CLI::add_command( 'xbo icons', \XboMarketKit\Cli\IconsCommand::class );
}
```

**Step 3: Run phpcs + phpstan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs -- includes/Cli/IconsCommand.php && composer run phpstan -- --no-progress`

**Step 4: Manual test**

Run: `wp xbo icons status` — should show 205 currencies, 0 icons present
Run: `wp xbo icons sync` — should download all 205 icons
Run: `wp xbo icons status` — should show 205 present, 0 missing

**Step 5: Commit**

```bash
git add includes/Cli/IconsCommand.php xbo-market-kit.php
git commit -m "feat(icons): add WP-CLI command for icon sync"
```

---

### Task 4: WP-Cron daily sync

**Files:**
- Modify: `xbo-market-kit.php` (activation/deactivation hooks)
- Modify: `includes/Plugin.php` (register cron hook)

**Step 1: Add cron hook handler in Plugin.php**

Add to `Plugin::init()`:

```php
add_action( 'xbo_market_kit_sync_icons', array( $this, 'cron_sync_icons' ) );
```

Add new method to `Plugin`:

```php
public function cron_sync_icons(): void {
    $icons_dir = XBO_MARKET_KIT_DIR . 'assets/images/icons';
    $icons_url = XBO_MARKET_KIT_URL . 'assets/images/icons';
    $resolver  = new Icons\IconResolver( $icons_dir, $icons_url );
    $sync      = new Icons\IconSync( $resolver );

    $response = $this->api_client->get_currencies();
    if ( ! $response->success ) {
        return;
    }

    $symbols = array_column( $response->data, 'code' );
    $sync->sync_missing( $symbols );
}
```

**Step 2: Schedule/unschedule cron in activation/deactivation hooks**

In `xbo-market-kit.php`, update activation hook:

```php
function xbo_market_kit_activate(): void {
    \XboMarketKit\Admin\DemoPage::create();
    if ( ! wp_next_scheduled( 'xbo_market_kit_sync_icons' ) ) {
        wp_schedule_event( time(), 'daily', 'xbo_market_kit_sync_icons' );
    }
}
```

Update deactivation hook:

```php
function xbo_market_kit_deactivate(): void {
    \XboMarketKit\Admin\DemoPage::delete();
    wp_clear_scheduled_hook( 'xbo_market_kit_sync_icons' );
}
```

**Step 3: Run phpcs + phpstan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs && composer run phpstan -- --no-progress`

**Step 4: Commit**

```bash
git add xbo-market-kit.php includes/Plugin.php
git commit -m "feat(icons): add daily WP-Cron icon sync"
```

---

### Task 5: Wire IconResolver into TickerShortcode

**Files:**
- Modify: `includes/Shortcodes/TickerShortcode.php:75-84`

**Step 1: Replace CDN URL construction with IconResolver**

In `TickerShortcode::render()`, replace lines 75-84 with:

```php
$icons_dir = XBO_MARKET_KIT_DIR . 'assets/images/icons';
$icons_url = XBO_MARKET_KIT_URL . 'assets/images/icons';
$icon_resolver = new \XboMarketKit\Icons\IconResolver( $icons_dir, $icons_url );
```

Move the resolver creation before the foreach loop. Then inside the loop, replace:

```php
$icon_url     = 'https://assets.xbo.com/token-icons/svg/' . rawurlencode( strtoupper( $base ) ) . '.svg';
$fallback_url = 'https://cdn.jsdelivr.net/npm/cryptocurrency-icons@0.18.1/svg/color/' . rawurlencode( strtolower( $base ) ) . '.svg';
```

with:

```php
$icon_url = $icon_resolver->url( $base );
```

And replace the `<img>` tag (lines 82-84) with:

```php
$cards .= '<img class="xbo-mk-ticker__icon-img" src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $base ) . '"'
    . ' width="40" height="40" loading="lazy" decoding="async">';
```

Remove the `onerror` attribute — no longer needed since all icons exist locally.

**Step 2: Run phpcs + phpstan + tests**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs && composer run phpstan -- --no-progress && composer run test`

**Step 3: Commit**

```bash
git add includes/Shortcodes/TickerShortcode.php
git commit -m "refactor(icons): use local IconResolver in TickerShortcode"
```

---

### Task 6: Wire IconResolver into Movers widget (JS)

**Files:**
- Modify: `includes/Shortcodes/MoversShortcode.php` (pass icons base URL via context)
- Modify: `assets/js/interactivity/movers.js` (use local path)

**Step 1: Pass icon base URL in context**

In `MoversShortcode::render()`, add to `$context` array:

```php
$context = array(
    'mode'     => $mode,
    'limit'    => $limit,
    'items'    => array(),
    'iconsUrl' => XBO_MARKET_KIT_URL . 'assets/images/icons',
);
```

**Step 2: Update movers.js to use local path**

In `movers.js`, replace lines 50-51:

```javascript
iconUrl: `https://assets.xbo.com/token-icons/svg/${ encodeURIComponent( ( item.base || '' ).toUpperCase() ) }.svg`,
iconFallbackUrl: `https://cdn.jsdelivr.net/npm/cryptocurrency-icons@0.18.1/svg/color/${ encodeURIComponent( ( item.base || '' ).toLowerCase() ) }.svg`,
```

with:

```javascript
iconUrl: `${ state._moversIconsUrl }/${ encodeURIComponent( ( item.base || '' ).toLowerCase() ) }.svg`,
```

Also remove the `iconFallbackUrl` property (dead code).

In `initMovers()`, add:

```javascript
state._moversIconsUrl = ctx.iconsUrl || '';
```

**Step 3: Run phpcs + phpstan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs && composer run phpstan -- --no-progress`

**Step 4: Commit**

```bash
git add includes/Shortcodes/MoversShortcode.php assets/js/interactivity/movers.js
git commit -m "refactor(icons): use local icon path in Movers widget"
```

---

### Task 7: Run initial icon sync + commit icons

**Step 1: Ensure icons directory exists**

Run: `mkdir -p wp-content/plugins/xbo-market-kit/assets/images/icons`

**Step 2: Run WP-CLI sync**

Run: `wp xbo icons sync`
Expected: "Downloaded: ~205, Skipped: 0, Failed: 0"

**Step 3: Verify**

Run: `wp xbo icons status`
Expected: "Total: 205, Present: 205, Missing: 0"

Run: `ls wp-content/plugins/xbo-market-kit/assets/images/icons/ | wc -l`
Expected: 205

**Step 4: Remove old .gitkeep**

Run: `rm -f wp-content/plugins/xbo-market-kit/assets/images/.gitkeep`

**Step 5: Commit icons to git**

```bash
git add wp-content/plugins/xbo-market-kit/assets/images/icons/
git rm -f wp-content/plugins/xbo-market-kit/assets/images/.gitkeep 2>/dev/null || true
git commit -m "feat(icons): add 205 local crypto icon SVGs"
```

---

### Task 8: Visual verification in browser

**Step 1: Open the demo page**

Navigate to: `http://claude-code-hackathon-xbo-market-kit.local/?page_id=6&preview=true`

**Step 2: Verify Ticker icons**

All 4 ticker cards (BTC, ETH, SOL, XRP) should show local icons.
Check img src — should point to `/wp-content/plugins/xbo-market-kit/assets/images/icons/btc.svg` etc.

**Step 3: Verify Movers icons**

All 20 rows in Top Movers (10 gainers + 10 losers) should show icons.
Previously broken icons (UP, VIRTUAL, KAITO, WET, WLD, etc.) should now show either real icon or generated placeholder.

**Step 4: Check no 403 errors in console**

No network errors for icon loading.

**Step 5: Commit any fixes if needed**

---

### Task 9: Run full quality checks

**Step 1: Run all tests**

Run: `cd wp-content/plugins/xbo-market-kit && composer run test`
Expected: All tests pass

**Step 2: Run phpcs**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpcs`
Expected: No errors

**Step 3: Run phpstan**

Run: `cd wp-content/plugins/xbo-market-kit && composer run phpstan`
Expected: No errors at level 6
