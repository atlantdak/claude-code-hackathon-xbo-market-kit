import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => <p>Ticker block placeholder</p>,
} );
