import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairSelector from '../shared/PairSelector';

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
						<PairSelector
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
							__next40pxDefaultSize
							__nextHasNoMarginBottom
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
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
