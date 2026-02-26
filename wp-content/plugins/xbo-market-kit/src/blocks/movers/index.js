import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { mode, limit } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Top Movers', 'xbo-market-kit' ) }
				icon="sort"
				inspectorControls={
					<>
						<SelectControl
							label={ __( 'Default Tab', 'xbo-market-kit' ) }
							value={ mode }
							options={ [
								{
									label: __(
										'Gainers',
										'xbo-market-kit'
									),
									value: 'gainers',
								},
								{
									label: __(
										'Losers',
										'xbo-market-kit'
									),
									value: 'losers',
								},
							] }
							onChange={ ( val ) =>
								setAttributes( { mode: val } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __(
								'Number of Items',
								'xbo-market-kit'
							) }
							value={ parseInt( limit, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { limit: String( val ) } )
							}
							min={ 1 }
							max={ 50 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
