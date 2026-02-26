<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use XboMarketKit\Rest\TradingPairsController;

class TradingPairsControllerTest extends TestCase {

	public function test_extract_symbols_returns_sorted_slash_symbols(): void {
		$data = [
			[ 'symbol' => 'ETH/USDT', 'baseAsset' => 'ETH' ],
			[ 'symbol' => 'BTC/USDT', 'baseAsset' => 'BTC' ],
			[ 'symbol' => 'SOL/USDT', 'baseAsset' => 'SOL' ],
		];
		$result = TradingPairsController::extract_symbols( $data );
		$this->assertSame( [ 'BTC/USDT', 'ETH/USDT', 'SOL/USDT' ], $result );
	}

	public function test_extract_symbols_skips_empty_symbol(): void {
		$data = [
			[ 'symbol' => 'BTC/USDT' ],
			[ 'baseAsset' => 'ETH' ],       // no symbol key
			[ 'symbol' => '' ],              // empty symbol
		];
		$result = TradingPairsController::extract_symbols( $data );
		$this->assertSame( [ 'BTC/USDT' ], $result );
	}

	public function test_extract_symbols_empty_data(): void {
		$result = TradingPairsController::extract_symbols( [] );
		$this->assertSame( [], $result );
	}
}
