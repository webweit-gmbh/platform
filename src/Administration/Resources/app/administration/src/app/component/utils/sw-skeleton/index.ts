import template from './sw-skeleton.html.twig';
import './sw-skeleton.scss';

const { Component } = Shopware;

/**
 * @private
 */
Component.register('sw-skeleton', {
    template,

    props: {
        variant: {
            type: String,
            required: true,
            validator(value) {
                const variants = [
                    'gallery',
                    'detail',
                    'category',
                    'listing',
                    'media',
                ];

                return variants.includes(value);
            },
        },
    },

    computed: {
        classList() {
            return {
                'sw-skeleton__gallery': this.variant === 'gallery',
                'sw-skeleton__detail': this.variant === 'detail',
                'sw-skeleton__category': this.variant === 'category',
                'sw-skeleton__listing': this.variant === 'listing',
                'sw-skeleton__media': this.variant === 'media',
            };
        },
    },
});
