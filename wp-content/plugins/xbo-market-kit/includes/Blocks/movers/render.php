<?php
/**
 * Render callback for the XBO Market Kit movers block.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

$shortcode = new \XboMarketKit\Shortcodes\MoversShortcode();
$atts      = array(
	'mode'  => $attributes['mode'] ?? 'gainers',
	'limit' => $attributes['limit'] ?? '10',
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped output.
echo '<div ' . get_block_wrapper_attributes() . '>';
echo $shortcode->handle( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode handles escaping.
echo '</div>';
