<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Cache;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Cache\CacheManager;

class CacheManagerTest extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_returns_null_on_miss(): void {
		Functions\expect( 'get_option' )->andReturn( array() );
		Functions\expect( 'get_transient' )->with( 'xbo_mk_test' )->andReturn( false );

		$cache = new CacheManager();
		$this->assertNull( $cache->get( 'xbo_mk_test' ) );
	}

	public function test_get_returns_data_on_hit(): void {
		$data = array( 'price' => '65000' );
		Functions\expect( 'get_option' )->andReturn( array() );
		Functions\expect( 'get_transient' )->with( 'xbo_mk_test' )->andReturn( $data );

		$cache = new CacheManager();
		$this->assertSame( $data, $cache->get( 'xbo_mk_test' ) );
	}

	public function test_set_uses_ttl_multiplier_normal(): void {
		Functions\expect( 'get_option' )->andReturn( array( 'cache_mode' => 'normal' ) );
		Functions\expect( 'set_transient' )->with( 'key', 'data', 30 )->once();

		$cache = new CacheManager();
		$cache->set( 'key', 'data', 30 );
	}

	public function test_set_uses_ttl_multiplier_fast(): void {
		Functions\expect( 'get_option' )->andReturn( array( 'cache_mode' => 'fast' ) );
		Functions\expect( 'set_transient' )->with( 'key', 'data', 15 )->once();

		$cache = new CacheManager();
		$cache->set( 'key', 'data', 30 );
	}

	public function test_delete_calls_delete_transient(): void {
		Functions\expect( 'get_option' )->andReturn( array() );
		Functions\expect( 'delete_transient' )->with( 'xbo_mk_test' )->once();

		$cache = new CacheManager();
		$cache->delete( 'xbo_mk_test' );
	}
}
