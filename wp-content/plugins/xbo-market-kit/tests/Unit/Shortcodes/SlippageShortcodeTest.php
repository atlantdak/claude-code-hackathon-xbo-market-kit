<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Shortcodes;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Shortcodes\SlippageShortcode;

class SlippageShortcodeTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_defaults_include_amount_one(): void {
		$shortcode = new SlippageShortcode();
		$reflection = new \ReflectionMethod( $shortcode, 'get_defaults' );
		$reflection->setAccessible( true );
		$defaults = $reflection->invoke( $shortcode );

		$this->assertSame( '1', $defaults['amount'] );
		$this->assertSame( 'BTC_USDT', $defaults['symbol'] );
		$this->assertSame( 'buy', $defaults['side'] );
	}

	public function test_parse_symbol_underscore_format(): void {
		$shortcode  = new SlippageShortcode();
		$reflection = new \ReflectionMethod( $shortcode, 'parse_symbol' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke( $shortcode, 'BTC_USDT' );

		$this->assertSame( 'BTC', $result['base'] );
		$this->assertSame( 'USDT', $result['quote'] );
	}

	public function test_parse_symbol_slash_format(): void {
		$shortcode  = new SlippageShortcode();
		$reflection = new \ReflectionMethod( $shortcode, 'parse_symbol' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke( $shortcode, 'ETH/USD' );

		$this->assertSame( 'ETH', $result['base'] );
		$this->assertSame( 'USD', $result['quote'] );
	}

	public function test_parse_symbol_invalid_returns_defaults(): void {
		$shortcode  = new SlippageShortcode();
		$reflection = new \ReflectionMethod( $shortcode, 'parse_symbol' );
		$reflection->setAccessible( true );
		$result = $reflection->invoke( $shortcode, 'INVALID' );

		$this->assertSame( 'BTC', $result['base'] );
		$this->assertSame( 'USDT', $result['quote'] );
	}
}
