<?php
/**
 * Render callback for the XBO Market Kit trades block.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\TradesShortcode();
$atts      = array(
	'symbol'  => $attributes['symbol'] ?? 'BTC/USDT',
	'limit'   => $attributes['limit'] ?? '20',
	'refresh' => $attributes['refresh'] ?? '10',
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped output.
echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
