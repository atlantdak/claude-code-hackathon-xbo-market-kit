<?php
/**
 * AbstractShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

/**
 * Abstract base class for all plugin shortcodes.
 *
 * Provides shared functionality for asset enqueuing, attribute handling,
 * and Interactivity API wrapper rendering.
 */
abstract class AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	abstract protected function get_tag(): string;

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	abstract protected function get_defaults(): array;

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	abstract protected function render( array $atts ): string;

	/**
	 * Register the shortcode with WordPress.
	 *
	 * @return void
	 */
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

	/**
	 * Enqueue common frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		$this->enqueue_widget_css();
	}

	/**
	 * Enqueue the widget stylesheet and web fonts.
	 *
	 * @return void
	 */
	protected function enqueue_widget_css(): void {
		if ( ! wp_style_is( 'xbo-market-kit-widgets', 'enqueued' ) ) {
			wp_enqueue_style(
				'xbo-market-kit-fonts',
				'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap',
				array(),
				null
			);

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style(
				'xbo-market-kit-widgets',
				XBO_MARKET_KIT_URL . 'assets/css/dist/widgets' . $suffix . '.css',
				array( 'xbo-market-kit-fonts' ),
				XBO_MARKET_KIT_VERSION
			);
		}
	}

	/**
	 * Enqueue a WordPress Interactivity API script module.
	 *
	 * @param string $handle   Script handle.
	 * @param string $filename JavaScript filename inside the interactivity directory.
	 * @return void
	 */
	protected function enqueue_interactivity_script( string $handle, string $filename ): void {
		wp_enqueue_script_module(
			$handle,
			XBO_MARKET_KIT_URL . 'assets/js/interactivity/' . $filename,
			array( '@wordpress/interactivity' ),
			XBO_MARKET_KIT_VERSION
		);
	}

	/**
	 * Wrap content in an Interactivity API container div with optional context.
	 *
	 * @param string $content Inner HTML content.
	 * @param array  $context Interactivity API context data.
	 * @return string Wrapped HTML string.
	 */
	protected function render_wrapper( string $content, array $context = array() ): string {
		$json = ! empty( $context ) ? wp_json_encode( $context ) : '';
		$data = $json ? ' data-wp-context=\'' . esc_attr( $json ) . '\'' : '';
		return '<div data-wp-interactive="xbo-market-kit"' . $data . '>' . $content . '</div>';
	}
}
