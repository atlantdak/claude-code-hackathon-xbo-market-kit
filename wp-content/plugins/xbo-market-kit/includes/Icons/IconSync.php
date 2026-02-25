<?php
/**
 * Icon Sync — cascade download engine for crypto icons.
 *
 * @package XboMarketKit\Icons
 */

declare(strict_types=1);

namespace XboMarketKit\Icons;

/**
 * Downloads crypto icons from CDN sources with placeholder fallback.
 */
class IconSync {

	/**
	 * CDN source URL patterns (sprintf format, %s = symbol).
	 *
	 * @var array<int, string>
	 */
	private const SOURCES = array(
		'https://assets.xbo.com/token-icons/svg/%s.svg',
		'https://cdn.jsdelivr.net/npm/cryptocurrency-icons@0.18.1/svg/color/%s.svg',
	);

	/**
	 * Icon resolver instance.
	 *
	 * @var IconResolver
	 */
	private IconResolver $resolver;

	/**
	 * Constructor.
	 *
	 * @param IconResolver $resolver Icon resolver instance.
	 */
	public function __construct( IconResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * Download a single icon using cascade sources.
	 *
	 * @param string $symbol Currency symbol (e.g. BTC).
	 * @return bool True if icon was saved successfully.
	 */
	public function download_icon( string $symbol ): bool {
		$path = $this->resolver->path( $symbol );
		$dir  = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Source 1: XBO CDN (uppercase symbol).
		$url = sprintf( self::SOURCES[0], rawurlencode( strtoupper( $symbol ) ) );
		$svg = $this->fetch_svg( $url );
		if ( $svg ) {
			return (bool) file_put_contents( $path, $svg );
		}

		// Source 2: jsDelivr (lowercase symbol).
		$url = sprintf( self::SOURCES[1], rawurlencode( strtolower( $symbol ) ) );
		$svg = $this->fetch_svg( $url );
		if ( $svg ) {
			return (bool) file_put_contents( $path, $svg );
		}

		// Fallback: generate placeholder SVG.
		return (bool) file_put_contents( $path, $this->resolver->placeholder_svg( $symbol ) );
	}

	/**
	 * Sync all symbols, optionally forcing re-download.
	 *
	 * @param array<int, string> $symbols List of currency symbols.
	 * @param bool               $force   Force re-download even if icon exists.
	 * @return array{downloaded: int, skipped: int, failed: int} Sync results.
	 */
	public function sync_all( array $symbols, bool $force = false ): array {
		$result = array(
			'downloaded' => 0,
			'skipped'    => 0,
			'failed'     => 0,
		);
		foreach ( $symbols as $symbol ) {
			if ( ! $force && $this->resolver->exists( $symbol ) ) {
				++$result['skipped'];
				continue;
			}
			if ( $this->download_icon( $symbol ) ) {
				++$result['downloaded'];
			} else {
				++$result['failed'];
			}
		}
		return $result;
	}

	/**
	 * Sync only missing icons.
	 *
	 * @param array<int, string> $symbols List of currency symbols.
	 * @return array{downloaded: int, skipped: int, failed: int} Sync results.
	 */
	public function sync_missing( array $symbols ): array {
		return $this->sync_all( $symbols, false );
	}

	/**
	 * Fetch SVG content from a URL.
	 *
	 * @param string $url URL to fetch.
	 * @return string|null SVG content or null on failure.
	 */
	private function fetch_svg( string $url ): ?string {
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return null;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) || ! str_contains( $body, '<svg' ) ) {
			return null;
		}
		return $body;
	}
}
