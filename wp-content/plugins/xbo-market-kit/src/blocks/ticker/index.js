import { registerBlockType } from '@wordpress/blocks';
import { TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

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
						<TextControl
							label={ __( 'Trading Pairs', 'xbo-market-kit' ) }
							help={ __(
								'Comma-separated, e.g. BTC/USDT,ETH/USDT',
								'xbo-market-kit'
							) }
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
						/>
						<RangeControl
							label={ __( 'Columns', 'xbo-market-kit' ) }
							value={ parseInt( columns, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { columns: String( val ) } )
							}
							min={ 1 }
							max={ 4 }
						/>
					</>
				}
			/>
		);
	},
} );
