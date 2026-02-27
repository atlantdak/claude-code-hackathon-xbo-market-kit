<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Cache;

use XboMarketKit\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class CacheManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_set_stores_data_with_exact_ttl(): void {
		$cache = new CacheManager();
		$key   = 'test_key';
		$data  = array( 'foo' => 'bar' );
		$ttl   = 15;

		Functions\expect( 'set_transient' )
			->once()
			->with( $key, $data, 15 )
			->andReturn( true );

		$cache->set( $key, $data, $ttl );
	}

	public function test_set_enforces_minimum_ttl_of_one(): void {
		$cache = new CacheManager();

		Functions\expect( 'set_transient' )
			->once()
			->with( 'key', 'data', 1 )
			->andReturn( true );

		$cache->set( 'key', 'data', 0 );
	}

	public function test_get_returns_cached_value(): void {
		$cache = new CacheManager();

		Functions\expect( 'get_transient' )
			->once()
			->with( 'test_key' )
			->andReturn( array( 'cached' => 'data' ) );

		$result = $cache->get( 'test_key' );
		$this->assertSame( array( 'cached' => 'data' ), $result );
	}

	public function test_get_returns_null_when_not_found(): void {
		$cache = new CacheManager();

		Functions\expect( 'get_transient' )
			->once()
			->with( 'missing_key' )
			->andReturn( false );

		$result = $cache->get( 'missing_key' );
		$this->assertNull( $result );
	}

	public function test_delete_removes_transient(): void {
		$cache = new CacheManager();

		Functions\expect( 'delete_transient' )
			->once()
			->with( 'test_key' )
			->andReturn( true );

		$cache->delete( 'test_key' );
	}
}
