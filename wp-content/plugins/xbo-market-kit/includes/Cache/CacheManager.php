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
 * Provides get/set/delete/flush operations with direct TTL control.
 */
class CacheManager {

	/**
	 * Transient key prefix for plugin cache entries.
	 *
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'xbo_mk_';

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
	 * @param int    $ttl  Time-to-live in seconds.
	 * @return void
	 */
	public function set( string $key, mixed $data, int $ttl ): void {
		set_transient( $key, $data, max( $ttl, 1 ) );
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
