/**
 * PairSelector component.
 *
 * Renders a searchable ComboboxControl populated with all available
 * XBO trading pairs. Used in blocks that need a single pair setting.
 */
import { ComboboxControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useTradingPairs from './useTradingPairs';

/**
 * @param {Object}   props
 * @param {string}   props.value       Current symbol value (e.g. "BTC/USDT").
 * @param {Function} props.onChange    Called with new symbol string on change.
 * @param {string}   [props.label]     Control label. Defaults to "Trading Pair".
 */
export default function PairSelector( { value, onChange, label } ) {
	const { pairs, loading } = useTradingPairs();

	const options = pairs.map( ( symbol ) => ( {
		value: symbol,
		label: symbol,
	} ) );

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<ComboboxControl
			label={ label ?? __( 'Trading Pair', 'xbo-market-kit' ) }
			value={ value }
			options={ options }
			onChange={ ( val ) => onChange( val ?? value ) }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
