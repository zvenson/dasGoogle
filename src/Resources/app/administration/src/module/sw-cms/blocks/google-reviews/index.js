import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'google-reviews',
    label: 'Google Bewertungen',
    category: 'text',
    component: 'sw-cms-block-google-reviews',
    previewComponent: 'sw-cms-preview-google-reviews',
    defaultConfig: {
        marginBottom: '28px',
        marginTop: '28px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'boxed',
    },
    slots: {
        'content': 'google-reviews',
    },
});
