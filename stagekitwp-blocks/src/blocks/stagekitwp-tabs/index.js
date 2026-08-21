import { registerBlockType } from '@wordpress/blocks';
import './style.scss'; // <--- THIS LINE COMPILES THE CSS
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: Save,
} );