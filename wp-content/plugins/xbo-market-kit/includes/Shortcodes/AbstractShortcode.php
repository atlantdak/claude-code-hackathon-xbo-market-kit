<?php
declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

abstract class AbstractShortcode {

	abstract protected function get_tag(): string;

	abstract protected function get_defaults(): array;

	abstract protected function render( array $atts ): string;

	public function register(): void {
		add_shortcode( $this->get_tag(), array( $this, 'handle' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function handle( $atts ): string {
		$atts = shortcode_atts( $this->get_defaults(), $atts, $this->get_tag() );
		$this->enqueue_assets();
		return $this->render( $atts );
	}

	protected function enqueue_assets(): void {
		$this->enqueue_tailwind();
		$this->enqueue_widget_css();
	}

	protected function enqueue_tailwind(): void {
		if ( ! wp_script_is( 'xbo-market-kit-tailwind', 'enqueued' ) ) {
			$settings = get_option( 'xbo_market_kit_settings', array() );
			if ( ( $settings['enable_tailwind'] ?? '1' ) === '1' ) {
				wp_enqueue_script(
					'xbo-market-kit-tailwind',
					'https://cdn.tailwindcss.com',
					array(),
					null,
					false
				);
			}
		}
	}

	protected function enqueue_widget_css(): void {
		if ( ! wp_style_is( 'xbo-market-kit-widgets', 'enqueued' ) ) {
			wp_enqueue_style(
				'xbo-market-kit-widgets',
				XBO_MARKET_KIT_URL . 'assets/css/widgets.css',
				array(),
				XBO_MARKET_KIT_VERSION
			);
		}
	}

	protected function enqueue_interactivity_script( string $handle, string $filename ): void {
		wp_enqueue_script_module(
			$handle,
			XBO_MARKET_KIT_URL . 'assets/js/interactivity/' . $filename,
			array( '@wordpress/interactivity' ),
			XBO_MARKET_KIT_VERSION
		);
	}

	protected function render_wrapper( string $content, array $context = array() ): string {
		$json = ! empty( $context ) ? wp_json_encode( $context ) : '';
		$data = $json ? ' data-wp-context=\'' . esc_attr( $json ) . '\'' : '';
		return '<div data-wp-interactive="xbo-market-kit"' . $data . '>' . $content . '</div>';
	}
}
