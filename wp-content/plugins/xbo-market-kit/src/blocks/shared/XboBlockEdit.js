/**
 * Shared block edit component wrapper for all XBO Market Kit blocks.
 *
 * Provides InspectorControls panel + ServerSideRender preview.
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, Placeholder, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function XboBlockEdit( {
	blockName,
	attributes,
	inspectorControls,
	title,
	icon,
} ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'xbo-market-kit' ) }
					initialOpen={ true }
				>
					{ inspectorControls }
				</PanelBody>
			</InspectorControls>
			<ServerSideRender
				block={ blockName }
				attributes={ attributes }
				LoadingResponsePlaceholder={ () => (
					<Placeholder icon={ icon } label={ title }>
						<Spinner />
					</Placeholder>
				) }
				ErrorResponsePlaceholder={ () => (
					<Placeholder icon={ icon } label={ title }>
						<p>{ __( 'Error loading preview.', 'xbo-market-kit' ) }</p>
					</Placeholder>
				) }
			/>
		</div>
	);
}
