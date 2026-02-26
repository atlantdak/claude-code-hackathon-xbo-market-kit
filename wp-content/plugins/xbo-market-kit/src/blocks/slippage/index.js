import { registerBlockType } from '@wordpress/blocks';
import { TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbol, side, amount } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Slippage Calculator', 'xbo-market-kit' ) }
				icon="calculator"
				inspectorControls={
					<>
						<TextControl
							label={ __(
								'Trading Pair',
								'xbo-market-kit'
							) }
							help={ __(
								'Use underscore format: BTC_USDT',
								'xbo-market-kit'
							) }
							value={ symbol }
							onChange={ ( val ) =>
								setAttributes( { symbol: val } )
							}
						/>
						<SelectControl
							label={ __( 'Side', 'xbo-market-kit' ) }
							value={ side }
							options={ [
								{
									label: __(
										'Buy',
										'xbo-market-kit'
									),
									value: 'buy',
								},
								{
									label: __(
										'Sell',
										'xbo-market-kit'
									),
									value: 'sell',
								},
							] }
							onChange={ ( val ) =>
								setAttributes( { side: val } )
							}
						/>
						<TextControl
							label={ __(
								'Default Amount',
								'xbo-market-kit'
							) }
							help={ __(
								'Leave empty to let user input amount on frontend',
								'xbo-market-kit'
							) }
							value={ amount }
							onChange={ ( val ) =>
								setAttributes( { amount: val } )
							}
						/>
					</>
				}
			/>
		);
	},
} );
