<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Plugin;

class PluginTest extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		// Reset singleton for test isolation.
		$reflection = new \ReflectionClass( Plugin::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );
		parent::tearDown();
	}

	public function test_instance_returns_singleton(): void {
		Functions\expect( 'get_option' )->andReturn( array() );
		$a = Plugin::instance();
		$b = Plugin::instance();
		$this->assertSame( $a, $b );
	}

	public function test_init_registers_hooks(): void {
		Functions\expect( 'get_option' )->andReturn( array() );
		Functions\expect( 'add_action' )->times( 5 );
		Plugin::instance()->init();
	}
}
