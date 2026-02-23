<?php
declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\OrderbookShortcode();
$atts      = array(
	'symbol'  => $attributes['symbol'] ?? 'BTC_USDT',
	'depth'   => $attributes['depth'] ?? '20',
	'refresh' => $attributes['refresh'] ?? '5',
);

echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
