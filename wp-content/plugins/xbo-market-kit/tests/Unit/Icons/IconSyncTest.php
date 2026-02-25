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
		$sync     = new IconSync( $resolver );
		$result   = $sync->download_icon( 'BTC' );

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
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( $svg_content );

		$resolver = new IconResolver( $this->icons_dir, 'http://example.com/icons' );
		$sync     = new IconSync( $resolver );
		$result   = $sync->download_icon( 'UNKNOWN' );

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
		$sync     = new IconSync( $resolver );
		$result   = $sync->download_icon( 'ZZZZZ' );

		$this->assertTrue( $result );
		$this->assertFileExists( $this->icons_dir . '/zzzzz.svg' );
		$this->assertStringContainsString( '>Z<', file_get_contents( $this->icons_dir . '/zzzzz.svg' ) );
	}

	public function test_sync_missing_skips_existing_icons(): void {
		// Pre-create an icon.
		file_put_contents( $this->icons_dir . '/btc.svg', '<svg></svg>' );

		$resolver = new IconResolver( $this->icons_dir, 'http://example.com/icons' );
		$sync     = new IconSync( $resolver );

		// sync_missing with only BTC — should skip it.
		$result = $sync->sync_missing( array( 'BTC' ) );
		$this->assertSame( 0, $result['downloaded'] );
		$this->assertSame( 1, $result['skipped'] );
	}
}
