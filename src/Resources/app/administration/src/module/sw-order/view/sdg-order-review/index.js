import template from './sdg-order-review.html.twig';

const { Component } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    inject: ['systemConfigApiService'],

    mixins: [Shopware.Mixin.getByName('notification')],

    data() {
        return {
            sdgIsSending:  false,
            sdgShowConfirm: false,
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
    },

    methods: {
        sdgOpenConfirm() {
            this.sdgShowConfirm = true;
        },

        sdgCloseConfirm() {
            this.sdgShowConfirm = false;
        },

        async sdgSendReviewMail() {
            if (!this.order?.id) return;

            this.sdgIsSending = true;
            try {
                const res = await this.systemConfigApiService.httpClient.post(
                    `/api/_action/sven-das-google/send-review-mail/${this.order.id}`,
                    {},
                    {
                        headers: this.systemConfigApiService.getBasicHeaders(),
                    }
                );

                if (res.data.success) {
                    this.createNotificationSuccess({ message: res.data.message });
                    // Reload to pick up the new custom field timestamp
                    if (typeof this.reloadEntityData === 'function') {
                        this.reloadEntityData();
                    } else if (typeof this.$emit === 'function') {
                        this.$emit('order-edit-save');
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
            this.sdgShowConfirm = false;
        },
    },
});
