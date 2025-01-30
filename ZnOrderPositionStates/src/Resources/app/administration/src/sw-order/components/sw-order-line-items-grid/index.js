import template from './sw-order-line-items-grid.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.override('sw-order-line-items-grid', {
    template,

    inject: [
        'repositoryFactory',
    ],
    data() {
        return {
            items: undefined,
        };
    }
});