<?php
/**
 * AdminSettings class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Admin;

/**
 * Plugin admin settings page under Settings > XBO Market Kit.
 *
 * Handles settings registration, rendering, sanitization, and AJAX cache clearing.
 */
class AdminSettings {

	/**
	 * Settings option group name.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'xbo_market_kit_settings';

	/**
	 * Settings option name in the database.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'xbo_market_kit_settings';

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'xbo-market-kit';

	/**
	 * Register the admin settings page, settings fields, and AJAX handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_options_page(
			__( 'XBO Market Kit', 'xbo-market-kit' ),
			__( 'XBO Market Kit', 'xbo-market-kit' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_xbo_market_kit_clear_cache', array( $this, 'ajax_clear_cache' ) );
	}

	/**
	 * Register settings, sections, and fields with the WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( self::OPTION_GROUP, self::OPTION_NAME, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );

		add_settings_section(
			'xbo_market_kit_general',
			__( 'General Settings', 'xbo-market-kit' ),
			'__return_empty_string',
			self::PAGE_SLUG
		);

		add_settings_field(
			'default_symbols',
			__( 'Default Trading Pairs', 'xbo-market-kit' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'xbo_market_kit_general',
			array(
				'label_for' => 'default_symbols',
				'default'   => 'BTC/USDT,ETH/USDT',
			)
		);

		add_settings_field(
			'cache_mode',
			__( 'Cache Mode', 'xbo-market-kit' ),
			array( $this, 'render_select_field' ),
			self::PAGE_SLUG,
			'xbo_market_kit_general',
			array(
				'label_for' => 'cache_mode',
				'options'   => array(
					'fast'   => __( 'Fast (shorter TTL)', 'xbo-market-kit' ),
					'normal' => __( 'Normal', 'xbo-market-kit' ),
					'slow'   => __( 'Slow (longer TTL)', 'xbo-market-kit' ),
				),
				'default'   => 'normal',
			)
		);

		add_settings_field(
			'enable_tailwind',
			__( 'Enable Tailwind CSS', 'xbo-market-kit' ),
			array( $this, 'render_checkbox_field' ),
			self::PAGE_SLUG,
			'xbo_market_kit_general',
			array(
				'label_for' => 'enable_tailwind',
				'default'   => '1',
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize( array $input ): array {
		$output = array();

		$output['default_symbols'] = sanitize_text_field( $input['default_symbols'] ?? 'BTC/USDT,ETH/USDT' );
		$output['cache_mode']      = in_array( $input['cache_mode'] ?? '', array( 'fast', 'normal', 'slow' ), true )
			? $input['cache_mode'] : 'normal';
		$output['enable_tailwind'] = isset( $input['enable_tailwind'] ) ? '1' : '0';

		return $output;
	}

	/**
	 * Render a text input settings field.
	 *
	 * @param array $args Field arguments including label_for and default.
	 * @return void
	 */
	public function render_text_field( array $args ): void {
		$options = get_option( self::OPTION_NAME, array() );
		$value   = $options[ $args['label_for'] ] ?? $args['default'];
		printf(
			'<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" />',
			esc_attr( $args['label_for'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['label_for'] ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a select dropdown settings field.
	 *
	 * @param array $args Field arguments including label_for, options, and default.
	 * @return void
	 */
	public function render_select_field( array $args ): void {
		$options = get_option( self::OPTION_NAME, array() );
		$value   = $options[ $args['label_for'] ] ?? $args['default'];

		printf( '<select id="%s" name="%s[%s]">', esc_attr( $args['label_for'] ), esc_attr( self::OPTION_NAME ), esc_attr( $args['label_for'] ) );
		foreach ( $args['options'] as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Render a checkbox settings field.
	 *
	 * @param array $args Field arguments including label_for and default.
	 * @return void
	 */
	public function render_checkbox_field( array $args ): void {
		$options = get_option( self::OPTION_NAME, array() );
		$value   = $options[ $args['label_for'] ] ?? $args['default'];
		printf(
			'<input type="checkbox" id="%s" name="%s[%s]" value="1"%s />',
			esc_attr( $args['label_for'] ),
			esc_attr( self::OPTION_NAME ),
			esc_attr( $args['label_for'] ),
			checked( $value, '1', false )
		);
	}

	/**
	 * Render the full admin settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script(
			'xbo-market-kit-admin',
			XBO_MARKET_KIT_URL . 'assets/js/admin/settings.js',
			array(),
			XBO_MARKET_KIT_VERSION,
			true
		);

		wp_localize_script(
			'xbo-market-kit-admin',
			'xboMarketKit',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'xbo_market_kit_clear_cache' ),
			)
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<form action="options.php" method="post">';
		settings_fields( self::OPTION_GROUP );
		do_settings_sections( self::PAGE_SLUG );
		submit_button();
		echo '</form>';
		echo '<hr />';
		echo '<h2>' . esc_html__( 'Cache Management', 'xbo-market-kit' ) . '</h2>';
		echo '<p><button type="button" class="button" id="xbo-mk-clear-cache">' . esc_html__( 'Clear Cache', 'xbo-market-kit' ) . '</button>';
		echo ' <span id="xbo-mk-cache-status"></span></p>';
		echo '</div>';
	}

	/**
	 * AJAX handler to clear all plugin cache entries.
	 *
	 * @return void
	 */
	public function ajax_clear_cache(): void {
		check_ajax_referer( 'xbo_market_kit_clear_cache', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'xbo-market-kit' ) );
		}

		$deleted = \XboMarketKit\Plugin::instance()->get_cache_manager()->flush_all();
		wp_send_json_success(
			sprintf(
				/* translators: %d: number of cache entries deleted */
				__( 'Cleared %d cache entries.', 'xbo-market-kit' ),
				$deleted
			)
		);
	}
}
