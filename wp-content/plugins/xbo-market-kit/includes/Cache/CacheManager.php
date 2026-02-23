<?php
/**
 * CacheManager class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Cache;

/**
 * Caching layer using WordPress transients.
 *
 * Provides get/set/delete/flush operations with a configurable
 * TTL multiplier based on the plugin cache mode setting.
 */
class CacheManager {

	/**
	 * Transient key prefix for plugin cache entries.
	 *
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'xbo_mk_';

	/**
	 * TTL multiplier based on cache mode setting.
	 *
	 * @var float
	 */
	private float $ttl_multiplier;

	/**
	 * Constructor. Reads cache mode from plugin settings.
	 */
	public function __construct() {
		$options              = get_option( 'xbo_market_kit_settings', array() );
		$mode                 = $options['cache_mode'] ?? 'normal';
		$this->ttl_multiplier = match ( $mode ) {
			'fast'  => 0.5,
			'slow'  => 2.0,
			default => 1.0,
		};
	}

	/**
	 * Get a cached value by key.
	 *
	 * @param string $key Cache key.
	 * @return mixed Cached value or null if not found.
	 */
	public function get( string $key ): mixed {
		$value = get_transient( $key );
		return false === $value ? null : $value;
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $key  Cache key.
	 * @param mixed  $data Data to cache.
	 * @param int    $ttl  Time-to-live in seconds (before multiplier adjustment).
	 * @return void
	 */
	public function set( string $key, mixed $data, int $ttl ): void {
		$adjusted_ttl = (int) round( $ttl * $this->ttl_multiplier );
		set_transient( $key, $data, max( $adjusted_ttl, 1 ) );
	}

	/**
	 * Delete a cached value by key.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public function delete( string $key ): void {
		delete_transient( $key );
	}

	/**
	 * Flush all plugin cache entries from the database.
	 *
	 * @return int Number of deleted cache entries.
	 */
	public function flush_all(): int {
		global $wpdb;
		$prefix = '_transient_' . self::TRANSIENT_PREFIX;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$prefix . '%',
				'_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
			)
		);
		return (int) $deleted;
	}
}
