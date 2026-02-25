<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Sparkline;

use PHPUnit\Framework\TestCase;
use XboMarketKit\Sparkline\SparklineGenerator;

class SparklineGeneratorTest extends TestCase {

	private SparklineGenerator $generator;

	protected function setUp(): void {
		parent::setUp();
		$this->generator = new SparklineGenerator();
	}

	public function test_generates_correct_count(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$this->assertCount( 40, $prices );
	}

	public function test_last_point_equals_last_price(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$this->assertSame( 68900.0, $prices[39] );
	}

	public function test_deterministic_output(): void {
		$a = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$b = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$this->assertSame( $a, $b );
	}

	public function test_different_seeds_different_curves(): void {
		$btc = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$eth = $this->generator->generate_prices( 3800.0, 3900.0, 3700.0, -1.2, 'ETH/USDT' );
		$this->assertNotSame( $btc, $eth );
	}

	public function test_all_prices_positive(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, -50.0, 'BTC/USDT' );
		foreach ( $prices as $price ) {
			$this->assertGreaterThan( 0, $price );
		}
	}

	public function test_all_prices_finite(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		foreach ( $prices as $price ) {
			$this->assertIsFloat( $price );
			$this->assertTrue( is_finite( $price ) );
		}
	}
}
