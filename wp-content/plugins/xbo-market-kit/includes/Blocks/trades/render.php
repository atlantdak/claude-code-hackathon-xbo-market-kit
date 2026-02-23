<?php
declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\TradesShortcode();
$atts      = array(
	'symbol'  => $attributes['symbol'] ?? 'BTC/USDT',
	'limit'   => $attributes['limit'] ?? '20',
	'refresh' => $attributes['refresh'] ?? '10',
);

echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
