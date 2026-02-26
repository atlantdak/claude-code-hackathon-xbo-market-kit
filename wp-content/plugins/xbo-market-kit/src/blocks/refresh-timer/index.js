import { registerBlockType } from '@wordpress/blocks';
import { RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { interval, label, size, showSeconds } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Refresh Timer', 'xbo-market-kit' ) }
				icon="clock"
				inspectorControls={
					<>
						<RangeControl
							label={ __( 'Interval (seconds, 0 = auto)', 'xbo-market-kit' ) }
							value={ interval }
							onChange={ ( val ) => setAttributes( { interval: val } ) }
							min={ 0 }
							max={ 60 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextControl
							label={ __( 'Label', 'xbo-market-kit' ) }
							value={ label }
							onChange={ ( val ) => setAttributes( { label: val } ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<SelectControl
							label={ __( 'Size', 'xbo-market-kit' ) }
							value={ size }
							options={ [
								{ label: 'Small (80px)', value: 'small' },
								{ label: 'Medium (120px)', value: 'medium' },
								{ label: 'Large (160px)', value: 'large' },
							] }
							onChange={ ( val ) => setAttributes( { size: val } ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<ToggleControl
							label={ __( 'Show seconds', 'xbo-market-kit' ) }
							checked={ showSeconds }
							onChange={ ( val ) => setAttributes( { showSeconds: val } ) }
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
