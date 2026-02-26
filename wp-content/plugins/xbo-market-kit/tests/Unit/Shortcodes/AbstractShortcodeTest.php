<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Shortcodes;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Concrete test double for AbstractShortcode.
 */
class ConcreteShortcode extends \XboMarketKit\Shortcodes\AbstractShortcode {
	protected function get_tag(): string {
		return 'test_shortcode';
	}

	protected function get_defaults(): array {
		return array();
	}

	protected function render( array $atts ): string {
		return '<div class="test-content">hello</div>';
	}

	/**
	 * Expose render_wrapper for testing.
	 */
	public function test_render_wrapper( string $content, array $context = array() ): string {
		return $this->render_wrapper( $content, $context );
	}
}

class AbstractShortcodeTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_render_wrapper_includes_inner_container(): void {
		$shortcode = new ConcreteShortcode();
		$html      = $shortcode->test_render_wrapper( '<div>content</div>' );

		$this->assertStringContainsString( 'xbo-mk-inner', $html );
		$this->assertStringContainsString( 'data-wp-interactive="xbo-market-kit"', $html );
	}

	public function test_render_wrapper_inner_wraps_content(): void {
		$shortcode = new ConcreteShortcode();
		$html      = $shortcode->test_render_wrapper( '<div>content</div>' );

		// Inner wrapper should contain the content
		$this->assertMatchesRegularExpression(
			'/<div class="xbo-mk-inner">.*<div>content<\/div>.*<\/div>/s',
			$html
		);
	}

	public function test_render_wrapper_with_context(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'esc_attr' )->returnArg();

		$shortcode = new ConcreteShortcode();
		$html      = $shortcode->test_render_wrapper(
			'<div>content</div>',
			array( 'key' => 'value' )
		);

		$this->assertStringContainsString( 'xbo-mk-inner', $html );
		$this->assertStringContainsString( 'data-wp-context', $html );
	}
}
