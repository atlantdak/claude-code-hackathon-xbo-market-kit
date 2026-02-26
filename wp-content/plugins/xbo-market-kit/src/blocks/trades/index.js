import { registerBlockType } from '@wordpress/blocks';
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairSelector from '../shared/PairSelector';

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
						<PairSelector
							value={ symbol }
							onChange={ ( val ) =>
								setAttributes( { symbol: val } )
							}
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
