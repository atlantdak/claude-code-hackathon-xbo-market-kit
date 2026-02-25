<?php
/**
 * SparklineGenerator class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Sparkline;

/**
 * Generates synthetic sparkline price data from market snapshot indicators.
 *
 * Uses a constrained random walk algorithm seeded deterministically
 * per symbol to produce realistic trend visualizations.
 */
class SparklineGenerator {

	/**
	 * Default number of sparkline data points.
	 */
	private const DEFAULT_COUNT = 40;

	/**
	 * Noise amplitude as fraction of price range.
	 */
	private const NOISE_SCALE = 0.012;

	/**
	 * Drift amplitude as fraction of price range.
	 */
	private const DRIFT_SCALE = 0.005;

	/**
	 * Maximum step as fraction of price range.
	 */
	private const MAX_STEP_SCALE = 0.05;

	/**
	 * Minimum price floor to prevent zero/negative values.
	 */
	private const PRICE_FLOOR = 1e-9;

	/**
	 * Generate synthetic price points for a sparkline.
	 *
	 * @param float  $last_price     Current price.
	 * @param float  $high_24h       24-hour high price.
	 * @param float  $low_24h        24-hour low price.
	 * @param float  $change_pct_24h 24-hour percent change.
	 * @param string $symbol         Trading pair symbol (e.g. 'BTC/USDT').
	 * @param int    $count          Number of points to generate.
	 * @return array<int, float> Array of price points. Empty if last_price <= 0.
	 */
	public function generate_prices(
		float $last_price,
		float $high_24h,
		float $low_24h,
		float $change_pct_24h,
		string $symbol,
		int $count = self::DEFAULT_COUNT
	): array {
		if ( $last_price <= 0.0 || $count < 2 ) {
			return array();
		}

		// Ensure high >= low.
		if ( $high_24h < $low_24h ) {
			[ $low_24h, $high_24h ] = [ $high_24h, $low_24h ];
		}

		$price_range = max( $high_24h - $low_24h, $last_price * 0.001 );
		$trend24     = tanh( $change_pct_24h / 10.0 );
		$noise_scale = $price_range * self::NOISE_SCALE;
		$drift       = -$trend24 * $price_range * self::DRIFT_SCALE;
		$max_step    = $price_range * self::MAX_STEP_SCALE;

		$seed = $this->compute_seed( $symbol, $change_pct_24h );
		$rng  = $seed;

		$prices              = array_fill( 0, $count, 0.0 );
		$prices[ $count - 1 ] = $last_price;
		$price               = $last_price;

		for ( $i = $count - 2; $i >= 0; $i-- ) {
			$rng   = $this->next_random( $rng );
			$rand  = ( $this->random_float( $rng ) - 0.5 ) * 2.0 * $noise_scale;
			$step  = max( -$max_step, min( $max_step, $drift + $rand ) );
			$price = max( self::PRICE_FLOOR, $price + $step );
			$prices[ $i ] = $price;
		}

		return $prices;
	}

	/**
	 * Compute a deterministic seed from symbol and trend bucket.
	 *
	 * @param string $symbol         Trading pair symbol.
	 * @param float  $change_pct_24h 24-hour percent change.
	 * @return int Seed value.
	 */
	private function compute_seed( string $symbol, float $change_pct_24h ): int {
		$day_stamp    = (int) floor( time() / 86400 );
		$trend_bucket = (int) round( $change_pct_24h );
		return crc32( $symbol . $day_stamp . $trend_bucket );
	}

	/**
	 * Advance the seeded RNG state (xorshift32).
	 *
	 * @param int $state Current RNG state.
	 * @return int Next RNG state.
	 */
	private function next_random( int $state ): int {
		$state ^= ( $state << 13 ) & 0xFFFFFFFF;
		$state ^= ( $state >> 17 );
		$state ^= ( $state << 5 ) & 0xFFFFFFFF;
		return $state & 0xFFFFFFFF;
	}

	/**
	 * Convert RNG state to a float in [0, 1).
	 *
	 * @param int $state Current RNG state.
	 * @return float Random float in [0, 1).
	 */
	private function random_float( int $state ): float {
		return ( $state & 0x7FFFFFFF ) / 2147483648.0;
	}
}
