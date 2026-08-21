import { registerBlockType } from '@wordpress/blocks';
import { registerBlockVariation } from '@wordpress/blocks';

import Edit from './edit';
import save from './save';
import './style.scss';
import './editor.scss';
import '../bookshelf-item';
import '../bookshelf-container';

import variations from './variations';

registerBlockType(
    'stagekitwp/bookshelf-container',
    {
        edit: Edit,
        save,
    }
);

variations.forEach(
    (variation) => {
        registerBlockVariation(
            'stagekitwp/bookshelf-container',
            variation
        );
    }
);