<?php

declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XboMarketKit\Admin\PageManager;

class PageManagerTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Set up common mocks used by create() tests.
	 */
	private function mock_common_create_functions(): void {
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'delete_option' )->justReturn( true );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_create_nav_menu' )->justReturn( 1 );
		Functions\when( 'wp_update_nav_menu_item' )->justReturn( 1 );
		Functions\when( 'wp_get_nav_menu_object' )->justReturn( false );
		Functions\when( 'wp_delete_nav_menu' )->justReturn( true );
		Functions\when( 'get_theme_mod' )->justReturn( array() );
		Functions\when( 'set_theme_mod' )->justReturn( true );
		Functions\when( 'get_nav_menu_locations' )->justReturn( array() );
		Functions\when( 'esc_html' )->alias(
			function ( $text ) {
				return $text;
			}
		);
		Functions\when( 'esc_attr' )->alias(
			function ( $text ) {
				return $text;
			}
		);
		Functions\when( '__' )->alias(
			function ( $text ) {
				return $text;
			}
		);
	}

	public function test_create_calls_wp_insert_post_for_each_page(): void {
		$call_count = 0;

		// get_option: return false for the pages key (no existing pages),
		// also return false for the old demo page key.
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'get_post' )->justReturn( null );
		Functions\when( 'wp_trash_post' )->justReturn( null );

		$this->mock_common_create_functions();

		// wp_insert_post should be called 9 times (once per page).
		Functions\expect( 'wp_insert_post' )
			->times( 9 )
			->andReturnUsing(
				function () use ( &$call_count ) {
					return ++$call_count;
				}
			);

		PageManager::create();
	}

	public function test_create_skips_if_pages_already_exist(): void {
		$existing_pages = array(
			'xbo-home'           => 10,
			'xbo-showcase'       => 11,
			'xbo-demos'          => 12,
			'xbo-demo-ticker'    => 13,
			'xbo-demo-movers'    => 14,
			'xbo-demo-orderbook' => 15,
			'xbo-demo-trades'    => 16,
			'xbo-demo-slippage'  => 17,
			'xbo-api-docs'       => 18,
		);

		// get_option returns the existing pages array for the pages key,
		// and false for the old demo page key.
		Functions\expect( 'get_option' )
			->andReturnUsing(
				function ( string $key ) use ( $existing_pages ) {
					if ( 'xbo_market_kit_pages' === $key ) {
						return $existing_pages;
					}
					return false;
				}
			);

		// get_post returns a truthy value (existing pages found).
		Functions\when( 'get_post' )->justReturn( (object) array( 'ID' => 10 ) );
		Functions\when( 'wp_trash_post' )->justReturn( null );
		Functions\when( 'delete_option' )->justReturn( true );

		$this->mock_common_create_functions();

		// wp_insert_post should NOT be called.
		Functions\expect( 'wp_insert_post' )->never();

		PageManager::create();
	}

	public function test_delete_trashes_all_pages(): void {
		$existing_pages = array(
			'xbo-home'           => 10,
			'xbo-showcase'       => 11,
			'xbo-demos'          => 12,
			'xbo-demo-ticker'    => 13,
			'xbo-demo-movers'    => 14,
			'xbo-demo-orderbook' => 15,
			'xbo-demo-trades'    => 16,
			'xbo-demo-slippage'  => 17,
			'xbo-api-docs'       => 18,
		);

		Functions\expect( 'get_option' )
			->andReturnUsing(
				function ( string $key ) use ( $existing_pages ) {
					if ( 'xbo_market_kit_pages' === $key ) {
						return $existing_pages;
					}
					return false;
				}
			);

		Functions\when( 'delete_option' )->justReturn( true );
		Functions\when( 'wp_get_nav_menu_object' )->justReturn( false );
		Functions\when( 'update_option' )->justReturn( true );

		// wp_trash_post should be called 9 times (once per page).
		Functions\expect( 'wp_trash_post' )->times( 9 );

		PageManager::delete();
	}

	public function test_page_definitions_contain_nine_pages(): void {
		$reflection = new \ReflectionMethod( PageManager::class, 'get_page_definitions' );
		$reflection->setAccessible( true );
		$definitions = $reflection->invoke( null );

		$this->assertCount( 9, $definitions );
	}

	public function test_demo_pages_have_correct_parent(): void {
		$reflection = new \ReflectionMethod( PageManager::class, 'get_page_definitions' );
		$reflection->setAccessible( true );
		$definitions = $reflection->invoke( null );

		$demo_slugs = array(
			'xbo-demo-ticker',
			'xbo-demo-movers',
			'xbo-demo-orderbook',
			'xbo-demo-trades',
			'xbo-demo-slippage',
		);

		$child_pages = array_filter(
			$definitions,
			function ( $def, $slug ) use ( $demo_slugs ) {
				return in_array( $slug, $demo_slugs, true );
			},
			ARRAY_FILTER_USE_BOTH
		);

		$this->assertCount( 5, $child_pages );

		foreach ( $child_pages as $slug => $definition ) {
			$this->assertArrayHasKey( 'parent', $definition, "Page $slug should have a parent key." );
			$this->assertSame( 'xbo-demos', $definition['parent'], "Page $slug should have parent 'xbo-demos'." );
		}
	}
}
