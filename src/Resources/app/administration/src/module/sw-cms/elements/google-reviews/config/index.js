import template from './sw-cms-el-config-google-reviews.html.twig';

const { Mixin } = Shopware;

export default {
    template,

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    created() {
        this.initElementConfig('google-reviews');
    },

    methods: {
        onMaxReviewsChange(value) {
            this.element.config.maxReviews.value = value;
            this.$emit('element-update', this.element);
        },

        onShowHeaderChange(value) {
            this.element.config.showHeader.value = value;
            this.$emit('element-update', this.element);
        },
    },
};
