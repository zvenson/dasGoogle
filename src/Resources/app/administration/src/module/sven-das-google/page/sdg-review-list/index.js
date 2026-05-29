import template from './sdg-review-list.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.register('sdg-review-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Shopware.Mixin.getByName('notification')],

    data() {
        return {
            reviews:        null,
            total:          0,
            isLoading:      true,
            isRefreshing:   false,
            filterMinRating: 0,
            placeId:        '',
            configured:     false,
            confirmDelete:  null,
            isDeleting:     false,
        };
    },

    computed: {
        reviewRepository() {
            return this.repositoryFactory.create('sven_das_google_review');
        },

        httpClient() {
            return Shopware.Application.getContainer('init').httpClient;
        },

        ratingOptions() {
            return [
                { value: 0, label: this.$tc('sven-das-google.list.filterAll') },
                { value: 5, label: '5 ★' },
                { value: 4, label: '4 ★+' },
                { value: 3, label: '3 ★+' },
                { value: 2, label: '2 ★+' },
                { value: 1, label: '1 ★+' },
            ];
        },

        averageRating() {
            if (!this.reviews || this.reviews.length === 0) return '–';
            const sum = this.reviews.reduce((acc, r) => acc + (r.rating || 0), 0);
            return (sum / this.reviews.length).toFixed(2);
        },

        criteria() {
            const criteria = new Criteria(1, 500);
            criteria.addSorting(Criteria.sort('reviewTime', 'DESC'));

            if (this.filterMinRating > 0) {
                criteria.addFilter(Criteria.range('rating', { gte: this.filterMinRating }));
            }

            return criteria;
        },
    },

    created() {
        this.loadStats();
        this.loadReviews();
    },

    methods: {
        async loadStats() {
            try {
                const res = await this.httpClient.get('sven-das-google/stats');
                this.placeId = res.data.placeId || '';
                this.configured = !!res.data.configured;
                if (typeof res.data.total === 'number') {
                    this.total = res.data.total;
                }
            } catch (e) {
                this.configured = false;
            }
        },

        async loadReviews() {
            this.isLoading = true;
            try {
                const result = await this.reviewRepository.search(this.criteria, Shopware.Context.api);
                this.reviews = result;
                this.total = result.total ?? result.length;
            } catch (e) {
                this.createNotificationError({ message: this.$tc('sven-das-google.list.loadError') });
            }
            this.isLoading = false;
        },

        onFilterChange(value) {
            this.filterMinRating = parseInt(value, 10) || 0;
            this.loadReviews();
        },

        async onRefresh() {
            this.isRefreshing = true;
            try {
                const res = await this.httpClient.post('sven-das-google/refresh-reviews', {
                    salesChannelId: null,
                });

                if (res.data.success) {
                    this.createNotificationSuccess({
                        message: this.$tc('sven-das-google.list.refreshSuccess', 0, {
                            added: res.data.added ?? 0,
                            total: res.data.total ?? 0,
                        }),
                    });
                    await this.loadReviews();
                    await this.loadStats();
                } else {
                    this.createNotificationError({
                        message: this.$tc('sven-das-google.list.refreshError', 0, {
                            message: res.data.message ?? 'unknown',
                        }),
                    });
                }
            } catch (e) {
                this.createNotificationError({
                    message: this.$tc('sven-das-google.list.refreshError', 0, {
                        message: e.response?.data?.message || e.message,
                    }),
                });
            }
            this.isRefreshing = false;
        },

        previewText(text) {
            if (!text) return '–';
            return text.length > 120 ? text.substring(0, 120) + '…' : text;
        },

        formatDate(unixTime) {
            if (!unixTime) return '–';
            const d = new Date(unixTime * 1000);
            return d.toLocaleDateString();
        },

        askDelete(item) {
            this.confirmDelete = item;
        },

        cancelDelete() {
            this.confirmDelete = null;
        },

        async doDelete() {
            if (!this.confirmDelete) return;
            const id = this.confirmDelete.id;
            this.isDeleting = true;
            try {
                await this.reviewRepository.delete(id, Shopware.Context.api);
                this.createNotificationSuccess({
                    message: this.$tc('sven-das-google.list.deleteSuccess'),
                });
                this.confirmDelete = null;
                await this.loadReviews();
                await this.loadStats();
            } catch (e) {
                this.createNotificationError({
                    message: this.$tc('sven-das-google.list.deleteError') + ' ' + (e.message || ''),
                });
            }
            this.isDeleting = false;
        },
    },
});
