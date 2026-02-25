<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Sparkline;

use PHPUnit\Framework\Attributes\DataProvider;
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

	public function test_svg_points_within_viewbox(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$result = $this->generator->render_svg_points( $prices );

		$pairs = explode( ' ', $result['polyline_points'] );
		foreach ( $pairs as $pair ) {
			[ $x, $y ] = explode( ',', $pair );
			$this->assertGreaterThanOrEqual( 0.0, (float) $x );
			$this->assertLessThanOrEqual( 100.0, (float) $x );
			$this->assertGreaterThanOrEqual( 0.0, (float) $y );
			$this->assertLessThanOrEqual( 30.0, (float) $y );
		}
	}

	public function test_svg_polygon_closes_path(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT' );
		$result = $this->generator->render_svg_points( $prices );

		$this->assertStringEndsWith( '100,30 0,30', $result['polygon_points'] );
	}

	public function test_renderer_handles_empty_prices(): void {
		$result = $this->generator->render_svg_points( array() );

		$this->assertSame( '', $result['polyline_points'] );
		$this->assertSame( '', $result['polygon_points'] );
	}

	public function test_renderer_handles_equal_prices(): void {
		$prices = array_fill( 0, 40, 100.0 );
		$result = $this->generator->render_svg_points( $prices );

		// All y values should be at midpoint (15.0) for flat line.
		$pairs = explode( ' ', $result['polyline_points'] );
		foreach ( $pairs as $pair ) {
			[ , $y ] = explode( ',', $pair );
			$this->assertEqualsWithDelta( 15.0, (float) $y, 0.1 );
		}
	}

	public function test_handles_zero_range(): void {
		$prices = $this->generator->generate_prices( 100.0, 100.0, 100.0, 0.0, 'STABLE/USDT' );
		$this->assertCount( 40, $prices );
		foreach ( $prices as $price ) {
			$this->assertGreaterThan( 0, $price );
			$this->assertTrue( is_finite( $price ) );
		}
	}

	public function test_handles_zero_price(): void {
		$prices = $this->generator->generate_prices( 0.0, 100.0, 50.0, 5.0, 'ZERO/USDT' );
		$this->assertSame( array(), $prices );
	}

	public function test_handles_negative_change(): void {
		$prices = $this->generator->generate_prices( 68900.0, 72000.0, 65000.0, -50.0, 'BTC/USDT' );
		$this->assertCount( 40, $prices );
		foreach ( $prices as $price ) {
			$this->assertGreaterThan( 0, $price );
		}
	}

	public function test_handles_extreme_change(): void {
		$prices = $this->generator->generate_prices( 0.001, 0.005, 0.0001, 500.0, 'MEME/USDT' );
		$this->assertCount( 40, $prices );
		foreach ( $prices as $price ) {
			$this->assertGreaterThan( 0, $price );
			$this->assertTrue( is_finite( $price ) );
		}
	}

	public function test_handles_inverted_range(): void {
		// high < low — bad API data.
		$prices = $this->generator->generate_prices( 68900.0, 65000.0, 72000.0, 2.5, 'BTC/USDT' );
		$this->assertCount( 40, $prices );
		$this->assertSame( 68900.0, $prices[39] );
	}

	public function test_handles_tiny_range(): void {
		$prices = $this->generator->generate_prices( 1.00001, 1.00002, 1.00000, 0.01, 'PEG/USDT' );
		$this->assertCount( 40, $prices );
		foreach ( $prices as $price ) {
			$this->assertGreaterThan( 0, $price );
		}
	}

	public function test_count_minimum(): void {
		$prices = $this->generator->generate_prices( 68900.0, 69500.0, 68000.0, 2.5, 'BTC/USDT', 1 );
		$this->assertSame( array(), $prices );
	}

	#[DataProvider( 'trend_direction_provider' )]
	public function test_trend_direction( float $change, string $expected ): void {
		$this->assertSame( $expected, $this->generator->get_trend_direction( $change ) );
	}

	/**
	 * @return array<string, array{float, string}>
	 */
	public static function trend_direction_provider(): array {
		return array(
			'positive' => array( 5.0, 'positive' ),
			'negative' => array( -3.0, 'negative' ),
			'zero'     => array( 0.0, 'positive' ),
		);
	}
}
