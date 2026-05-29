import './page/sdg-review-list';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Shopware.Module.register('sven-das-google', {
    type:        'plugin',
    name:        'sven-das-google',
    title:       'sven-das-google.general.mainMenuItemGeneral',
    description: 'sven-das-google.general.description',
    color:       '#4285F4',
    icon:        'regular-star',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        index: {
            component: 'sdg-review-list',
            path:      'index',
        },
    },

    navigation: [
        {
            id:       'sven-das-google',
            label:    'sven-das-google.general.mainMenuItemGeneral',
            color:    '#4285F4',
            icon:     'regular-star',
            path:     'sven.das.google.index',
            position: 105,
            parent:   'sw-marketing',
        },
    ],
});
