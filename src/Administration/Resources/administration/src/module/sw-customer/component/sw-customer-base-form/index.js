import template from './sw-customer-base-form.html.twig';

const { Component } = Shopware;
const { mapApiErrors } = Shopware.Component.getComponentHelper();

Component.register('sw-customer-base-form', {
    template,

    props: {
        customer: {
            type: Object,
            required: true
        }
    },

    computed: {
        ...mapApiErrors('customer', [
            'salutationId',
            'firstName',
            'lastName',
            'email',
            'groupId',
            'salesChannelId',
            'defaultPaymentMethodId',
            'customerNumber',
            'password'
        ])
    },

    created() {
        this.createdComponent();
    },

    methods: {
        swCustomerCreateOnChangeSalesChannel(salesChannelId) {
            this.$emit('sales-channel-changed', salesChannelId);
        }
    }
});
