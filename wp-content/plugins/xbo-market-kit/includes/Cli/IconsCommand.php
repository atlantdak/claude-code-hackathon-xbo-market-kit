<?php
/**
 * WP-CLI command for icon sync.
 *
 * @package XboMarketKit\Cli
 */

declare(strict_types=1);

namespace XboMarketKit\Cli;

use XboMarketKit\Api\ApiClient;
use XboMarketKit\Cache\CacheManager;
use XboMarketKit\Icons\IconResolver;
use XboMarketKit\Icons\IconSync;

/**
 * Manage XBO cryptocurrency icons.
 */
class IconsCommand {

	/**
	 * Sync crypto icons from CDN sources.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Re-download all icons, even if they already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     wp xbo icons sync
	 *     wp xbo icons sync --force
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function sync( array $args, array $assoc_args ): void {
		$force = isset( $assoc_args['force'] );
		$cache = new CacheManager();
		$api   = new ApiClient( $cache );

		$icons_dir = XBO_MARKET_KIT_DIR . 'assets/images/icons';
		$icons_url = XBO_MARKET_KIT_URL . 'assets/images/icons';
		$resolver  = new IconResolver( $icons_dir, $icons_url );
		$sync      = new IconSync( $resolver );

		\WP_CLI::log( 'Fetching currency list from XBO API...' );
		$response = $api->get_currencies();
		if ( ! $response->success ) {
			\WP_CLI::error( 'Failed to fetch currencies: ' . ( $response->error_message ?? 'unknown error' ) );
			return;
		}

		$symbols = array_column( $response->data, 'code' );
		\WP_CLI::log( sprintf( 'Found %d currencies. %s...', count( $symbols ), $force ? 'Force re-downloading all' : 'Downloading missing' ) );

		$result = $sync->sync_all( $symbols, $force );
		\WP_CLI::success(
			sprintf(
				'Done. Downloaded: %d, Skipped: %d, Failed: %d',
				$result['downloaded'],
				$result['skipped'],
				$result['failed']
			)
		);
	}

	/**
	 * Show icon sync status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp xbo icons status
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function status( array $args, array $assoc_args ): void {
		$cache = new CacheManager();
		$api   = new ApiClient( $cache );

		$icons_dir = XBO_MARKET_KIT_DIR . 'assets/images/icons';
		$icons_url = XBO_MARKET_KIT_URL . 'assets/images/icons';
		$resolver  = new IconResolver( $icons_dir, $icons_url );

		$response = $api->get_currencies();
		if ( ! $response->success ) {
			\WP_CLI::error( 'Failed to fetch currencies.' );
			return;
		}

		$symbols  = array_column( $response->data, 'code' );
		$existing = 0;
		$missing  = array();
		foreach ( $symbols as $symbol ) {
			if ( $resolver->exists( $symbol ) ) {
				++$existing;
			} else {
				$missing[] = $symbol;
			}
		}

		\WP_CLI::log( sprintf( 'Total currencies: %d', count( $symbols ) ) );
		\WP_CLI::log( sprintf( 'Icons present:    %d', $existing ) );
		\WP_CLI::log( sprintf( 'Icons missing:    %d', count( $missing ) ) );
		if ( $missing ) {
			\WP_CLI::log( 'Missing: ' . implode( ', ', array_slice( $missing, 0, 20 ) ) );
			if ( count( $missing ) > 20 ) {
				\WP_CLI::log( sprintf( '... and %d more', count( $missing ) - 20 ) );
			}
		}
	}
}
