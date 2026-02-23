<?php
declare(strict_types=1);

namespace XboMarketKit\Cache;

class CacheManager {

	private const TRANSIENT_PREFIX = 'xbo_mk_';
	private float $ttl_multiplier;

	public function __construct() {
		$options              = get_option( 'xbo_market_kit_settings', array() );
		$mode                 = $options['cache_mode'] ?? 'normal';
		$this->ttl_multiplier = match ( $mode ) {
			'fast'  => 0.5,
			'slow'  => 2.0,
			default => 1.0,
		};
	}

	public function get( string $key ): mixed {
		$value = get_transient( $key );
		return false === $value ? null : $value;
	}

	public function set( string $key, mixed $data, int $ttl ): void {
		$adjusted_ttl = (int) round( $ttl * $this->ttl_multiplier );
		set_transient( $key, $data, max( $adjusted_ttl, 1 ) );
	}

	public function delete( string $key ): void {
		delete_transient( $key );
	}

	public function flush_all(): int {
		global $wpdb;
		$prefix  = '_transient_' . self::TRANSIENT_PREFIX;
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
