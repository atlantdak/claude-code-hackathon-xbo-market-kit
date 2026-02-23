<?php
declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\MoversShortcode();
$atts      = array(
	'mode'  => $attributes['mode'] ?? 'gainers',
	'limit' => $attributes['limit'] ?? '10',
);

echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
