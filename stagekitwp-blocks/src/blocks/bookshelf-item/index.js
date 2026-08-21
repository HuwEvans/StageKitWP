import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import save from './save';

registerBlockType(
    'stagekitwp/bookshelf-item',
    {
        edit: Edit,
        save,
    }
);