import { registerBlockType } from '@wordpress/blocks';
import { TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbol, limit, refresh } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Recent Trades', 'xbo-market-kit' ) }
				icon="list-view"
				inspectorControls={
					<>
						<TextControl
							label={ __(
								'Trading Pair',
								'xbo-market-kit'
							) }
							help={ __(
								'Use slash format: BTC/USDT',
								'xbo-market-kit'
							) }
							value={ symbol }
							onChange={ ( val ) =>
								setAttributes( { symbol: val } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __(
								'Number of Trades',
								'xbo-market-kit'
							) }
							value={ parseInt( limit, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { limit: String( val ) } )
							}
							min={ 1 }
							max={ 100 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __(
								'Refresh Interval (seconds)',
								'xbo-market-kit'
							) }
							value={ parseInt( refresh, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { refresh: String( val ) } )
							}
							min={ 1 }
							max={ 60 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
