import { registerBlockType } from '@wordpress/blocks';
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairsSelector from '../shared/PairsSelector';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbols, refresh, columns } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Ticker', 'xbo-market-kit' ) }
				icon="chart-line"
				inspectorControls={
					<>
						<PairsSelector
							value={ symbols }
							onChange={ ( val ) =>
								setAttributes( { symbols: val } )
							}
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
							min={ 5 }
							max={ 60 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __( 'Columns', 'xbo-market-kit' ) }
							value={ parseInt( columns, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { columns: String( val ) } )
							}
							min={ 1 }
							max={ 4 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
