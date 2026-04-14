Shopware.Component.register('sw-cms-el-google-reviews', () => import('./component'));
Shopware.Component.register('sw-cms-el-preview-google-reviews', () => import('./preview'));
Shopware.Component.register('sw-cms-el-config-google-reviews', () => import('./config'));

Shopware.Service('cmsService').registerCmsElement({
    name: 'google-reviews',
    label: 'Google Bewertungen',
    component: 'sw-cms-el-google-reviews',
    configComponent: 'sw-cms-el-config-google-reviews',
    previewComponent: 'sw-cms-el-preview-google-reviews',
    defaultConfig: {
        maxReviews: {
            source: 'static',
            value: 5,
        },
        showHeader: {
            source: 'static',
            value: true,
        },
    },
});
