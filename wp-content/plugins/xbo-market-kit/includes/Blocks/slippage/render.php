<?php
/**
 * Render callback for the XBO Market Kit slippage block.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\SlippageShortcode();
$atts      = array(
	'symbol' => $attributes['symbol'] ?? 'BTC_USDT',
	'side'   => $attributes['side'] ?? 'buy',
	'amount' => ! empty( $attributes['amount'] ) ? $attributes['amount'] : '1',
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped output.
echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
