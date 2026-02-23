<?php
/**
 * Render callback for the XBO Market Kit ticker block.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\TickerShortcode();
$atts      = array(
	'symbols' => $attributes['symbols'] ?? 'BTC/USDT,ETH/USDT',
	'refresh' => $attributes['refresh'] ?? '15',
	'columns' => $attributes['columns'] ?? '4',
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped output.
echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
