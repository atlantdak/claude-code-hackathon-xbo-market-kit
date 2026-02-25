<?php
/**
 * Icon Resolver — resolves local icon URLs and paths.
 *
 * @package XboMarketKit\Icons
 */

declare(strict_types=1);

namespace XboMarketKit\Icons;

/**
 * Resolves crypto icon URLs from local storage with placeholder fallback.
 */
class IconResolver {

	/**
	 * Absolute path to the icons directory.
	 *
	 * @var string
	 */
	private string $icons_dir;

	/**
	 * Public URL to the icons directory.
	 *
	 * @var string
	 */
	private string $icons_url;

	/**
	 * Constructor.
	 *
	 * @param string $icons_dir Absolute path to icons directory.
	 * @param string $icons_url Public URL to icons directory.
	 */
	public function __construct( string $icons_dir, string $icons_url ) {
		$this->icons_dir = rtrim( $icons_dir, '/' );
		$this->icons_url = rtrim( $icons_url, '/' );
	}

	/**
	 * Get the URL for a currency icon.
	 *
	 * @param string $symbol Currency symbol (e.g. BTC).
	 * @return string Local URL or data URI placeholder.
	 */
	public function url( string $symbol ): string {
		$file = strtolower( $symbol ) . '.svg';
		if ( file_exists( $this->icons_dir . '/' . $file ) ) {
			return $this->icons_url . '/' . $file;
		}
		return 'data:image/svg+xml,' . rawurlencode( $this->placeholder_svg( $symbol ) );
	}

	/**
	 * Get the absolute file path for a currency icon.
	 *
	 * @param string $symbol Currency symbol (e.g. ETH).
	 * @return string Absolute file path.
	 */
	public function path( string $symbol ): string {
		return $this->icons_dir . '/' . strtolower( $symbol ) . '.svg';
	}

	/**
	 * Check if a local icon exists for the given symbol.
	 *
	 * @param string $symbol Currency symbol.
	 * @return bool True if the icon file exists.
	 */
	public function exists( string $symbol ): bool {
		return file_exists( $this->path( $symbol ) );
	}

	/**
	 * Generate a placeholder SVG with the first letter of the symbol.
	 *
	 * @param string $symbol Currency symbol.
	 * @return string SVG markup.
	 */
	public function placeholder_svg( string $symbol ): string {
		$letter = mb_strtoupper( mb_substr( $symbol, 0, 1 ) );
		$letter = htmlspecialchars( $letter, ENT_XML1, 'UTF-8' );
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">'
			. '<circle cx="20" cy="20" r="20" fill="#6366f1"/>'
			. '<text x="20" y="20" text-anchor="middle" dominant-baseline="central"'
			. ' fill="#fff" font-size="18" font-family="system-ui, sans-serif">'
			. $letter . '</text></svg>';
	}
}
