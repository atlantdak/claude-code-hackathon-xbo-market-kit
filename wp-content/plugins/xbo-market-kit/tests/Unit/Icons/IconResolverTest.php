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

		// Should contain data URI SVG with letter X.
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
