import template from './sdg-api-test.html.twig';

Shopware.Component.register('sdg-api-test', {
    template,

    inject: ['systemConfigApiService'],

    data() {
        return {
            isLoading: false,
            testResult: null,
        };
    },

    methods: {
        async testConnection() {
            this.isLoading = true;
            this.testResult = null;

            try {
                const config = await this.systemConfigApiService.getValues('SvenDasGoogle.config');
                const values = config || {};

                const apiKey = values['SvenDasGoogle.config.googleApiKey'] || '';
                const placeId = values['SvenDasGoogle.config.googlePlaceId'] || '';

                if (!apiKey) {
                    this.testResult = {
                        success: false,
                        message: 'Bitte zuerst einen API Key eintragen und speichern.',
                    };
                    this.isLoading = false;
                    return;
                }

                if (!placeId) {
                    this.testResult = {
                        success: false,
                        message: 'Bitte zuerst eine Place ID eintragen und speichern.',
                    };
                    this.isLoading = false;
                    return;
                }

                const httpClient = Shopware.Application.getContainer('init').httpClient;
                const response = await httpClient.post(
                    'sven-das-google/test-connection',
                    { salesChannelId: null },
                );

                this.testResult = response.data;
            } catch (error) {
                const data = error.response?.data;
                const message = data?.message
                    || data?.errors?.[0]?.detail
                    || 'Verbindungsfehler. Bitte Netzwerkverbindung pruefen.';
                this.testResult = {
                    success: false,
                    message: message,
                };
            }

            this.isLoading = false;
        },
    },
});
