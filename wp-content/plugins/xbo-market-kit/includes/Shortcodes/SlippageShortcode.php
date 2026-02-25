<?php
/**
 * SlippageShortcode class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Shortcodes;

/**
 * Shortcode handler for the [xbo_slippage] shortcode.
 *
 * Renders an interactive slippage calculator with real-time order book data.
 */
class SlippageShortcode extends AbstractShortcode {

	/**
	 * Get the shortcode tag name.
	 *
	 * @return string Shortcode tag.
	 */
	protected function get_tag(): string {
		return 'xbo_slippage';
	}

	/**
	 * Get default shortcode attributes.
	 *
	 * @return array Default attribute values.
	 */
	protected function get_defaults(): array {
		return array(
			'symbol' => 'BTC_USDT',
			'side'   => 'buy',
			'amount' => '1',
		);
	}

	/**
	 * Parse a trading pair symbol into base and quote currencies.
	 *
	 * @param string $symbol Symbol in underscore (BTC_USDT) or slash (BTC/USDT) format.
	 * @return array{base: string, quote: string} Parsed currencies.
	 */
	protected function parse_symbol( string $symbol ): array {
		$separator = str_contains( $symbol, '/' ) ? '/' : '_';
		$parts     = explode( $separator, $symbol, 2 );

		if ( count( $parts ) === 2 && '' !== $parts[0] && '' !== $parts[1] ) {
			return array(
				'base'  => strtoupper( trim( $parts[0] ) ),
				'quote' => strtoupper( trim( $parts[1] ) ),
			);
		}

		return array(
			'base'  => 'BTC',
			'quote' => 'USDT',
		);
	}

	/**
	 * Enqueue slippage-specific frontend assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets(): void {
		parent::enqueue_assets();
		$this->enqueue_interactivity_script( 'xbo-market-kit-slippage', 'slippage.js' );
		$this->enqueue_pair_catalog();
	}

	/**
	 * Schedule pair catalog JSON output in wp_footer (once per page).
	 *
	 * @return void
	 */
	private function enqueue_pair_catalog(): void {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;
		add_action( 'wp_footer', array( $this, 'render_pair_catalog_json' ) );
	}

	/**
	 * Output the pair catalog JSON script tag in the footer.
	 *
	 * @return void
	 */
	public function render_pair_catalog_json(): void {
		$api     = \XboMarketKit\Plugin::instance()->get_api_client();
		$icons   = \XboMarketKit\Plugin::instance()->get_icon_resolver();
		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		echo '<script type="application/json" id="xbo-mk-pairs-catalog">'
			. wp_json_encode( $data )
			. '</script>';
	}

	/**
	 * Render the slippage calculator shortcode HTML output.
	 *
	 * @param array $atts Processed shortcode attributes.
	 * @return string Rendered HTML.
	 */
	protected function render( array $atts ): string {
		$symbol = sanitize_text_field( $atts['symbol'] );
		$parsed = $this->parse_symbol( $symbol );
		$base   = $parsed['base'];
		$quote  = $parsed['quote'];
		$side   = in_array( $atts['side'], array( 'buy', 'sell' ), true ) ? $atts['side'] : 'buy';
		$amount = $atts['amount'];

		// Load catalog for icon URLs in trigger buttons.
		$api     = \XboMarketKit\Plugin::instance()->get_api_client();
		$icons   = \XboMarketKit\Plugin::instance()->get_icon_resolver();
		$catalog = new PairCatalog( $api, $icons );
		$data    = $catalog->build();

		$base_icon  = $data['icons'][ $base ] ?? '';
		$quote_icon = $data['icons'][ $quote ] ?? '';

		// All base currencies sorted alphabetically.
		$all_bases = array_keys( $data['pairs_map'] );

		// Available quotes for the default base.
		$available_quotes = $data['pairs_map'][ $base ] ?? array();

		$context = array(
			'base'        => $base,
			'quote'       => $quote,
			'side'        => $side,
			'amount'      => $amount,
			'result'      => null,
			'loading'     => false,
			'error'       => '',
			'baseOpen'    => false,
			'quoteOpen'   => false,
			'baseSearch'  => '',
			'quoteSearch' => '',
		);

		$html = '<div class="xbo-mk-slippage" data-wp-init="actions.initSlippage">';

		// Header.
		$html .= '<div class="xbo-mk-slippage__header">';
		$html .= '<h3 class="xbo-mk-slippage__title">'
			. esc_html__( 'Slippage Calculator', 'xbo-market-kit' ) . '</h3>';
		$html .= '</div>';

		// Form.
		$html .= '<div class="xbo-mk-slippage__form">';
		$html .= '<div class="xbo-mk-slippage__fields">';

		// Pair selectors (first column).
		$html .= '<div class="xbo-mk-slippage__field">';
		$html .= '<label class="xbo-mk-slippage__label">'
			. esc_html__( 'Pair', 'xbo-market-kit' ) . '</label>';
		$html .= '<div class="xbo-mk-slippage__pair">';

		// Base dropdown.
		$html .= $this->render_selector(
			'base',
			$base,
			$base_icon,
			$all_bases,
			$data['icons']
		);

		// Separator.
		$html .= '<span class="xbo-mk-slippage__pair-separator">/</span>';

		// Quote dropdown.
		$html .= $this->render_selector(
			'quote',
			$quote,
			$quote_icon,
			$available_quotes,
			$data['icons']
		);

		$html .= '</div></div>';

		// Amount input (second column).
		$html .= '<div class="xbo-mk-slippage__field">';
		$html .= '<label class="xbo-mk-slippage__label">'
			. esc_html__( 'Amount', 'xbo-market-kit' ) . '</label>';
		$html .= '<input type="number" step="0.001" min="0.001"'
			. ' value="' . esc_attr( $amount ) . '"'
			. ' placeholder="1.0"'
			. ' class="xbo-mk-slippage__input"'
			. ' data-wp-on--input="actions.slippageAmountChange" />';
		$html .= '</div>';

		// Buy/Sell toggle (full-width row).
		$html .= '<div class="xbo-mk-slippage__field">';
		$html .= '<div class="xbo-mk-slippage__toggle">';
		$html .= '<button type="button" class="xbo-mk-slippage__toggle-btn"'
			. ' data-wp-class--xbo-mk-slippage__toggle-btn--buy-active="state.slippageIsBuy"'
			. ' data-wp-on--click="actions.slippageSetBuy">'
			. esc_html__( 'Buy', 'xbo-market-kit' ) . '</button>';
		$html .= '<button type="button" class="xbo-mk-slippage__toggle-btn"'
			. ' data-wp-class--xbo-mk-slippage__toggle-btn--sell-active="state.slippageIsSell"'
			. ' data-wp-on--click="actions.slippageSetSell">'
			. esc_html__( 'Sell', 'xbo-market-kit' ) . '</button>';
		$html .= '</div></div>';

		$html .= '</div></div>'; // fields + form.

		// Error message.
		$html .= '<div class="xbo-mk-slippage__error"'
			. ' data-wp-class--xbo-mk-hidden="!state.slippageHasError"'
			. ' data-wp-text="state.slippageError"></div>';

		// Partial fill warning.
		$html .= '<div class="xbo-mk-slippage__warning"'
			. ' data-wp-class--xbo-mk-hidden="!state.slippageIsPartialFill"'
			. ' data-wp-text="state.slippagePartialFillText"></div>';

		// Results.
		$html .= '<div class="xbo-mk-slippage__results"'
			. ' data-wp-class--xbo-mk-hidden="!state.slippageHasResult">';
		$html .= '<div class="xbo-mk-slippage__metrics">';

		$metrics = array(
			'avg_price'    => __( 'Avg Price', 'xbo-market-kit' ),
			'slippage_pct' => __( 'Slippage', 'xbo-market-kit' ),
			'spread'       => __( 'Spread', 'xbo-market-kit' ),
			'spread_pct'   => __( 'Spread %', 'xbo-market-kit' ),
			'depth_used'   => __( 'Depth Used', 'xbo-market-kit' ),
			'total_cost'   => __( 'Total Cost', 'xbo-market-kit' ),
		);

		foreach ( $metrics as $key => $label ) {
			$html .= '<div class="xbo-mk-slippage__metric">';
			$html .= '<div class="xbo-mk-slippage__metric-label">'
				. esc_html( $label ) . '</div>';
			$html .= '<div class="xbo-mk-slippage__metric-value"'
				. ' data-wp-text="state.slippageResult_' . esc_attr( $key ) . '">--</div>';
			$html .= '</div>';
		}

		$html .= '</div></div>';

		// Loading indicator.
		$html .= '<div class="xbo-mk-slippage__loading"'
			. ' data-wp-class--xbo-mk-hidden="!state.slippageLoading">';
		$html .= esc_html__( 'Calculating...', 'xbo-market-kit' );
		$html .= '</div>';

		$html .= '</div>'; // slippage container.

		return $this->render_wrapper( $html, $context );
	}

	/**
	 * Render a custom dropdown selector for base or quote currency.
	 *
	 * @param string               $type          Selector type: 'base' or 'quote'.
	 * @param string               $selected      Currently selected symbol.
	 * @param string               $selected_icon URL of the selected symbol's icon.
	 * @param array<int, string>   $options       Available options (symbol strings).
	 * @param array<string,string> $icons_map     Map of symbol to icon URL.
	 * @return string HTML output.
	 */
	private function render_selector(
		string $type,
		string $selected,
		string $selected_icon,
		array $options,
		array $icons_map
	): string {
		$open_state     = $type . 'Open';
		$search_state   = $type . 'Search';
		$toggle_action  = 'actions.slippageToggle' . ucfirst( $type );
		$select_action  = 'actions.slippageSelect' . ucfirst( $type );
		$search_action  = 'actions.slippageSearch' . ucfirst( $type );
		$keydown_action = 'actions.slippageSelectorKeydown';

		$html = '<div class="xbo-mk-slippage__selector"'
			. ' data-wp-class--xbo-mk-slippage__selector--open="context.' . $open_state . '"'
			. ' data-wp-on-document--click="actions.slippageCloseDropdowns">';

		// Trigger button.
		$html .= '<button type="button" class="xbo-mk-slippage__selector-trigger"'
			. ' data-wp-on--click="' . $toggle_action . '"'
			. ' aria-haspopup="listbox"'
			. ' data-wp-bind--aria-expanded="context.' . $open_state . '"'
			. ' aria-label="' . esc_attr(
				/* translators: %s: selector type (base or quote) */
				sprintf( __( 'Select %s currency', 'xbo-market-kit' ), $type )
			) . '">';
		$icon_state = 'base' === $type ? 'state.slippageBaseIcon' : 'state.slippageQuoteIcon';
		$html      .= '<img class="xbo-mk-slippage__selector-icon"'
			. ' src="' . esc_url( $selected_icon ) . '"'
			. ' data-wp-bind--src="' . $icon_state . '"'
			. ' alt="" width="20" height="20" />';
		$html .= '<span class="xbo-mk-slippage__selector-text"'
			. ' data-wp-text="context.' . $type . '">'
			. esc_html( $selected ) . '</span>';
		$html .= '<span class="xbo-mk-slippage__selector-chevron" aria-hidden="true">&#9662;</span>';
		$html .= '</button>';

		// Dropdown panel.
		$html .= '<div class="xbo-mk-slippage__selector-dropdown">';
		$html .= '<input type="text"'
			. ' class="xbo-mk-slippage__selector-search"'
			. ' placeholder="' . esc_attr__( 'Search...', 'xbo-market-kit' ) . '"'
			. ' data-wp-on--input="' . $search_action . '"'
			. ' data-wp-on--keydown="' . $keydown_action . '"'
			. ' data-wp-bind--value="context.' . $search_state . '"'
			. ' role="searchbox"'
			. ' aria-label="' . esc_attr__( 'Filter currencies', 'xbo-market-kit' ) . '" />';

		// Options list.
		$html .= '<ul class="xbo-mk-slippage__selector-list" role="listbox">';
		foreach ( $options as $symbol ) {
			$icon_url = $icons_map[ $symbol ] ?? '';
			$html    .= '<li class="xbo-mk-slippage__selector-item"'
				. ' role="option"'
				. ' data-symbol="' . esc_attr( $symbol ) . '"'
				. ' data-wp-on--click="' . $select_action . '">';
			$html    .= '<img src="' . esc_url( $icon_url ) . '"'
				. ' alt="" width="20" height="20" loading="lazy" />';
			$html    .= '<span>' . esc_html( $symbol ) . '</span>';
			$html    .= '</li>';
		}
		$html .= '<li class="xbo-mk-slippage__selector-empty" aria-disabled="true">'
			. esc_html__( 'No results', 'xbo-market-kit' ) . '</li>';
		$html .= '</ul>';

		$html .= '</div>'; // dropdown.
		$html .= '</div>'; // selector.

		return $html;
	}
}
