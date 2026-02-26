<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Blocks;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RefreshTimerRenderTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Common mocks for all render tests.
		Functions\when( 'wp_style_is' )->justReturn( false );
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'wp_enqueue_script_module' )->justReturn( null );
		Functions\when( 'wp_unique_id' )->justReturn( 'xbo-uid-1' );
		Functions\when( 'get_block_wrapper_attributes' )->justReturn(
			'class="wp-block-xbo-market-kit-refresh-timer"'
		);
		Functions\when( 'wp_json_encode' )->alias(
			function ( $data ) {
				return json_encode( $data );
			}
		);
		Functions\when( 'esc_attr' )->alias(
			function ( $text ) {
				return $text;
			}
		);
		Functions\when( 'esc_html' )->alias(
			function ( $text ) {
				return $text;
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Include render.php with given attributes and capture output.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	private function render( array $attributes ): string {
		ob_start();
		include dirname( __DIR__, 3 ) . '/includes/Blocks/refresh-timer/render.php';
		return (string) ob_get_clean();
	}

	public function test_default_attributes_produce_medium_circle(): void {
		$attributes = array();
		$html       = $this->render( $attributes );

		// Medium diameter is 120px.
		$this->assertStringContainsString( 'width="120"', $html );
		$this->assertStringContainsString( 'height="120"', $html );
		$this->assertStringContainsString( 'viewBox="0 0 120 120"', $html );
		$this->assertStringContainsString( 'xbo-mk-timer--medium', $html );
	}

	public function test_small_size_variant(): void {
		$attributes = array( 'size' => 'small' );
		$html       = $this->render( $attributes );

		// Small diameter is 80px.
		$this->assertStringContainsString( 'width="80"', $html );
		$this->assertStringContainsString( 'height="80"', $html );
		$this->assertStringContainsString( 'viewBox="0 0 80 80"', $html );
		$this->assertStringContainsString( 'xbo-mk-timer--small', $html );
	}

	public function test_large_size_variant(): void {
		$attributes = array( 'size' => 'large' );
		$html       = $this->render( $attributes );

		// Large diameter is 160px.
		$this->assertStringContainsString( 'width="160"', $html );
		$this->assertStringContainsString( 'height="160"', $html );
		$this->assertStringContainsString( 'viewBox="0 0 160 160"', $html );
		$this->assertStringContainsString( 'xbo-mk-timer--large', $html );
	}

	public function test_interval_attribute_passed_to_json_context(): void {
		$attributes = array( 'interval' => 30 );
		$html       = $this->render( $attributes );

		// The JSON context should contain the interval value.
		$this->assertStringContainsString( '"interval":30', $html );
		$this->assertStringContainsString( '"remaining":30', $html );
		$this->assertStringContainsString( '"displayText":"30s"', $html );
	}

	public function test_show_seconds_false_hides_display_span(): void {
		$attributes = array( 'showSeconds' => false );
		$html       = $this->render( $attributes );

		// The display span should NOT be present.
		$this->assertStringNotContainsString( 'xbo-mk-timer__display', $html );
	}

	public function test_show_seconds_true_shows_display_span(): void {
		$attributes = array( 'showSeconds' => true );
		$html       = $this->render( $attributes );

		// The display span should be present.
		$this->assertStringContainsString( 'xbo-mk-timer__display', $html );
	}

	public function test_label_is_rendered(): void {
		$attributes = array( 'label' => 'Custom Label' );
		$html       = $this->render( $attributes );

		$this->assertStringContainsString( 'xbo-mk-timer__label', $html );
		$this->assertStringContainsString( 'Custom Label', $html );
	}

	public function test_empty_label_hides_label_span(): void {
		$attributes = array( 'label' => '' );
		$html       = $this->render( $attributes );

		$this->assertStringNotContainsString( 'xbo-mk-timer__label', $html );
	}

	public function test_default_label_is_rendered(): void {
		$attributes = array();
		$html       = $this->render( $attributes );

		$this->assertStringContainsString( 'Next data refresh', $html );
	}

	public function test_zero_interval_defaults_to_fifteen(): void {
		$attributes = array( 'interval' => 0 );
		$html       = $this->render( $attributes );

		// remaining should be max(0, 15) = 15.
		$this->assertStringContainsString( '"remaining":15', $html );
		$this->assertStringContainsString( '"displayText":"15s"', $html );
	}
}
