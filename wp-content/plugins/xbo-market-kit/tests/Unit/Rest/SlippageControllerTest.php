<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use XboMarketKit\Rest\SlippageController;

class SlippageControllerTest extends TestCase {

	public function test_slippage_exact_fill_at_best_price(): void {
		$book   = array( array( '100.00', '10.0' ) );
		$result = SlippageController::calculate_slippage( $book, 5.0 );
		$this->assertSame( 100.0, $result['avg_price'] );
		$this->assertSame( 0.0, $result['slippage_pct'] );
		$this->assertSame( 500.0, $result['total_cost'] );
	}

	public function test_slippage_across_multiple_levels(): void {
		$book = array(
			array( '100.00', '5.0' ),
			array( '101.00', '5.0' ),
			array( '102.00', '5.0' ),
		);
		$result = SlippageController::calculate_slippage( $book, 10.0 );
		// 5 * 100 + 5 * 101 = 1005, avg = 100.5.
		$this->assertSame( 100.5, $result['avg_price'] );
		$this->assertSame( 1005.0, $result['total_cost'] );
		$this->assertSame( 10.0, $result['depth_used'] );
		$this->assertGreaterThan( 0, $result['slippage_pct'] );
	}

	public function test_slippage_insufficient_liquidity(): void {
		$book   = array( array( '100.00', '2.0' ) );
		$result = SlippageController::calculate_slippage( $book, 5.0 );
		// Only 2.0 filled out of 5.0.
		$this->assertSame( 2.0, $result['depth_used'] );
		$this->assertSame( 200.0, $result['total_cost'] );
	}

	public function test_slippage_empty_book(): void {
		$result = SlippageController::calculate_slippage( array(), 1.0 );
		$this->assertSame( 0.0, $result['avg_price'] );
	}

	public function test_slippage_zero_amount(): void {
		$book   = array( array( '100.00', '10.0' ) );
		$result = SlippageController::calculate_slippage( $book, 0.0 );
		$this->assertSame( 0.0, $result['avg_price'] );
	}
}
