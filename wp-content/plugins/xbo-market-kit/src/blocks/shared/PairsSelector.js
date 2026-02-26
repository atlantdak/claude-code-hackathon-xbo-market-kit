/**
 * PairsSelector component.
 *
 * Renders a FormTokenField for selecting multiple trading pairs.
 * Used by the Ticker block which supports a comma-separated list.
 *
 * Storage format: comma-separated string "BTC/USDT,ETH/USDT"
 */
import { FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useTradingPairs from './useTradingPairs';

/**
 * @param {Object}   props
 * @param {string}   props.value     Comma-separated symbols string.
 * @param {Function} props.onChange  Called with new comma-separated string.
 * @param {string}   [props.label]   Control label.
 */
export default function PairsSelector( { value, onChange, label } ) {
	const { pairs } = useTradingPairs();

	// Convert storage string to token array for FormTokenField.
	const tokens = value
		? value.split( ',' ).map( ( s ) => s.trim() ).filter( Boolean )
		: [];

	const handleChange = ( newTokens ) => {
		// Validate each token against known pairs (case-insensitive).
		const upperPairs = pairs.map( ( p ) => p.toUpperCase() );
		const valid = newTokens.filter( ( t ) =>
			upperPairs.includes( t.toUpperCase() )
		);
		onChange( valid.join( ',' ) );
	};

	return (
		<FormTokenField
			label={ label ?? __( 'Trading Pairs', 'xbo-market-kit' ) }
			value={ tokens }
			suggestions={ pairs }
			onChange={ handleChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			__experimentalExpandOnFocus
		/>
	);
}
