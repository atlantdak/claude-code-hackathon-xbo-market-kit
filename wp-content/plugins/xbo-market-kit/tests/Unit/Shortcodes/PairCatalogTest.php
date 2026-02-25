<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Shortcodes;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Api\ApiClient;
use XboMarketKit\Api\ApiResponse;
use XboMarketKit\Icons\IconResolver;
use XboMarketKit\Shortcodes\PairCatalog;

class PairCatalogTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_build_returns_pairs_map_and_icons(): void {
		$api = \Mockery::mock( ApiClient::class );
		$api->shouldReceive( 'get_trading_pairs' )
			->once()
			->andReturn( ApiResponse::success( array(
				array( 'symbol' => 'BTC/USDT' ),
				array( 'symbol' => 'BTC/EUR' ),
				array( 'symbol' => 'ETH/USDT' ),
			) ) );

		$icons = \Mockery::mock( IconResolver::class );
		$icons->shouldReceive( 'url' )->with( 'BTC' )->andReturn( '/icons/btc.svg' );
		$icons->shouldReceive( 'url' )->with( 'USDT' )->andReturn( '/icons/usdt.svg' );
		$icons->shouldReceive( 'url' )->with( 'EUR' )->andReturn( '/icons/eur.svg' );
		$icons->shouldReceive( 'url' )->with( 'ETH' )->andReturn( '/icons/eth.svg' );

		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$this->assertSame( array( 'EUR', 'USDT' ), $data['pairs_map']['BTC'] );
		$this->assertSame( array( 'USDT' ), $data['pairs_map']['ETH'] );
		$this->assertSame( '/icons/btc.svg', $data['icons']['BTC'] );
		$this->assertSame( '/icons/usdt.svg', $data['icons']['USDT'] );
	}

	public function test_build_returns_empty_on_api_failure(): void {
		$api = \Mockery::mock( ApiClient::class );
		$api->shouldReceive( 'get_trading_pairs' )
			->once()
			->andReturn( ApiResponse::error( 'API down' ) );

		$icons = \Mockery::mock( IconResolver::class );

		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$this->assertSame( array(), $data['pairs_map'] );
		$this->assertSame( array(), $data['icons'] );
	}

	public function test_pairs_map_quotes_are_sorted_alphabetically(): void {
		$api = \Mockery::mock( ApiClient::class );
		$api->shouldReceive( 'get_trading_pairs' )
			->once()
			->andReturn( ApiResponse::success( array(
				array( 'symbol' => 'BTC/USD' ),
				array( 'symbol' => 'BTC/EUR' ),
				array( 'symbol' => 'BTC/USDT' ),
			) ) );

		$icons = \Mockery::mock( IconResolver::class );
		$icons->shouldReceive( 'url' )->andReturn( '/icons/placeholder.svg' );

		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$this->assertSame( array( 'EUR', 'USD', 'USDT' ), $data['pairs_map']['BTC'] );
	}
}
