<?php
declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\SlippageShortcode();
$atts      = array(
	'symbol' => $attributes['symbol'] ?? 'BTC_USDT',
	'side'   => $attributes['side'] ?? 'buy',
	'amount' => $attributes['amount'] ?? '',
);

echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
