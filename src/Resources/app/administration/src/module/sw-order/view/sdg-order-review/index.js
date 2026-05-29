import template from './sdg-order-review.html.twig';

const { Component } = Shopware;

Component.override('sw-order-detail', {
    template,

    mixins: [Shopware.Mixin.getByName('notification')],

    data() {
        return {
            sdgIsSending:  false,
            sdgShowModal:  false,
        };
    },

    computed: {
        sdgReviewMailSentAt() {
            return this.order?.customFields?.sdg_review_mail_sent_at || null;
        },

        sdgFormattedSentAt() {
            if (!this.sdgReviewMailSentAt) return null;
            try {
                return new Date(this.sdgReviewMailSentAt).toLocaleString();
            } catch (e) {
                return this.sdgReviewMailSentAt;
            }
        },

        sdgHttpClient() {
            return Shopware.Application.getContainer('init').httpClient;
        },
    },

    methods: {
        sdgOpenModal() {
            this.sdgShowModal = true;
        },

        sdgCloseModal() {
            this.sdgShowModal = false;
        },

        async sdgSendReviewMail() {
            if (!this.order?.id) return;

            this.sdgIsSending = true;
            try {
                const res = await this.sdgHttpClient.post(
                    `_action/sven-das-google/send-review-mail/${this.order.id}`,
                    {}
                );

                if (res.data.success) {
                    this.createNotificationSuccess({ message: res.data.message });
                    // Reload to pick up the new custom field timestamp
                    if (typeof this.loadOrder === 'function') {
                        this.loadOrder();
                    } else if (typeof this.reloadEntityData === 'function') {
                        this.reloadEntityData();
                    }
                } else {
                    this.createNotificationError({ message: res.data.message || 'Versand fehlgeschlagen.' });
                }
            } catch (e) {
                this.createNotificationError({
                    message: 'Versand fehlgeschlagen: ' + (e.response?.data?.message || e.message),
                });
            }
            this.sdgIsSending = false;
            this.sdgShowModal = false;
        },
    },
});
