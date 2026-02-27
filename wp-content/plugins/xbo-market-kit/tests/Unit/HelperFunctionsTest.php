<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class HelperFunctionsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_refresh_interval_returns_default(): void {
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'xbo_market_kit/refresh_interval', 15 )
			->andReturn( 15 );

		$interval = \xbo_market_kit_get_refresh_interval();
		$this->assertSame( 15, $interval );
	}

	public function test_get_refresh_interval_respects_filter(): void {
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'xbo_market_kit/refresh_interval', 15 )
			->andReturn( 30 );

		$interval = \xbo_market_kit_get_refresh_interval();
		$this->assertSame( 30, $interval );
	}

	public function test_get_refresh_interval_casts_to_int(): void {
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'xbo_market_kit/refresh_interval', 15 )
			->andReturn( '45' );

		$interval = \xbo_market_kit_get_refresh_interval();
		$this->assertSame( 45, $interval );
		$this->assertIsInt( $interval );
	}
}
