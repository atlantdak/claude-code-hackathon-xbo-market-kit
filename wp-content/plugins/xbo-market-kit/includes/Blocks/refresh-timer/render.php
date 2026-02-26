<?php
/**
 * Render callback for the XBO Market Kit refresh timer block.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

// Size configuration: diameter, stroke width, font size.
$sizes = array(
	'small'  => array(
		'diameter' => 80,
		'stroke'   => 3,
		'font'     => '18px',
	),
	'medium' => array(
		'diameter' => 120,
		'stroke'   => 4,
		'font'     => '26px',
	),
	'large'  => array(
		'diameter' => 160,
		'stroke'   => 5,
		'font'     => '34px',
	),
);

$size_key    = $attributes['size'] ?? 'medium';
$size_config = $sizes[ $size_key ] ?? $sizes['medium'];
$diameter    = $size_config['diameter'];
$stroke_w    = $size_config['stroke'];
$font_size   = $size_config['font'];
$radius      = ( $diameter - 8 ) / 2;
$circumf     = round( 2 * M_PI * $radius, 2 );

$interval     = (int) ( $attributes['interval'] ?? 0 );
$label        = $attributes['label'] ?? 'Next data refresh';
$show_seconds = $attributes['showSeconds'] ?? true;

// Interactivity API context.
$context = array(
	'interval'    => $interval,
	'remaining'   => max( $interval, 15 ),
	'circumf'     => $circumf,
	'dashOffset'  => '0',
	'isPulsing'   => false,
	'displayText' => ( $interval > 0 ? $interval : 15 ) . 's',
);

// Enqueue widget CSS.
if ( ! wp_style_is( 'xbo-market-kit-widgets', 'enqueued' ) ) {
	wp_enqueue_style(
		'xbo-market-kit-fonts',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	wp_enqueue_style(
		'xbo-market-kit-widgets',
		XBO_MARKET_KIT_URL . 'assets/css/dist/widgets' . $suffix . '.css',
		array( 'xbo-market-kit-fonts' ),
		XBO_MARKET_KIT_VERSION
	);
}

// Enqueue Interactivity API script module.
wp_enqueue_script_module(
	'xbo-market-kit-refresh-timer',
	XBO_MARKET_KIT_URL . 'assets/js/interactivity/refresh-timer.js',
	array( '@wordpress/interactivity' ),
	XBO_MARKET_KIT_VERSION
);

$gradient_id  = 'xbo-timer-grad-' . wp_unique_id();
$wrapper_attr = get_block_wrapper_attributes();
$half         = $diameter / 2;
?>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns pre-escaped output. ?>
<div <?php echo $wrapper_attr; ?>>
<div
	data-wp-interactive="xbo-market-kit"
	data-wp-context='<?php echo esc_attr( (string) wp_json_encode( $context ) ); ?>'
	data-wp-init="actions.initRefreshTimer"
	data-wp-class--xbo-mk-timer--pulsing="context.isPulsing"
	class="xbo-mk-timer__wrap xbo-mk-timer--<?php echo esc_attr( $size_key ); ?>"
>
	<svg
		class="xbo-mk-timer__svg"
		width="<?php echo esc_attr( (string) $diameter ); ?>"
		height="<?php echo esc_attr( (string) $diameter ); ?>"
		viewBox="0 0 <?php echo esc_attr( (string) $diameter ); ?> <?php echo esc_attr( (string) $diameter ); ?>"
	>
		<defs>
			<linearGradient id="<?php echo esc_attr( $gradient_id ); ?>" x1="0%" y1="0%" x2="100%" y2="0%">
				<stop offset="0%" stop-color="var(--xbo-mk--color-primary, #6319ff)" />
				<stop offset="100%" stop-color="var(--xbo-mk--color-positive, #49b47a)" />
			</linearGradient>
		</defs>
		<circle
			class="xbo-mk-timer__track"
			cx="<?php echo esc_attr( (string) $half ); ?>"
			cy="<?php echo esc_attr( (string) $half ); ?>"
			r="<?php echo esc_attr( (string) $radius ); ?>"
			fill="none"
			stroke="var(--xbo-mk--color-border-light, #f0edf7)"
			stroke-width="<?php echo esc_attr( (string) $stroke_w ); ?>"
		/>
		<circle
			class="xbo-mk-timer__progress"
			cx="<?php echo esc_attr( (string) $half ); ?>"
			cy="<?php echo esc_attr( (string) $half ); ?>"
			r="<?php echo esc_attr( (string) $radius ); ?>"
			fill="none"
			stroke="url(#<?php echo esc_attr( $gradient_id ); ?>)"
			stroke-width="<?php echo esc_attr( (string) $stroke_w ); ?>"
			stroke-linecap="round"
			stroke-dasharray="<?php echo esc_attr( (string) $circumf ); ?>"
			stroke-dashoffset="0"
			data-wp-style--stroke-dashoffset="context.dashOffset"
			transform="rotate(-90 <?php echo esc_attr( (string) $half ); ?> <?php echo esc_attr( (string) $half ); ?>)"
		/>
	</svg>
	<?php if ( $show_seconds ) : ?>
		<span
			class="xbo-mk-timer__display"
			style="font-size: <?php echo esc_attr( $font_size ); ?>"
			data-wp-text="context.displayText"
		><?php echo esc_html( $context['displayText'] ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $label ) ) : ?>
		<span class="xbo-mk-timer__label"><?php echo esc_html( $label ); ?></span>
	<?php endif; ?>
</div>
</div>
