<?php
/**
 * BlockRegistrar class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Blocks;

/**
 * Registers all Gutenberg blocks and the custom block category.
 */
class BlockRegistrar {

	/**
	 * List of block directory names to register.
	 *
	 * @var array
	 */
	private const BLOCKS = array(
		'ticker',
		'movers',
		'orderbook',
		'trades',
		'slippage',
		'refresh-timer',
	);

	/**
	 * Register block category and all plugin blocks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'block_categories_all', array( $this, 'register_category' ) );
		foreach ( self::BLOCKS as $block ) {
			register_block_type( XBO_MARKET_KIT_DIR . 'includes/Blocks/' . $block );
		}
	}

	/**
	 * Register custom block category.
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	public function register_category( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'xbo-market-kit',
				'title' => __( 'XBO Market Kit', 'xbo-market-kit' ),
				'icon'  => 'chart-line',
			)
		);
		return $categories;
	}
}
