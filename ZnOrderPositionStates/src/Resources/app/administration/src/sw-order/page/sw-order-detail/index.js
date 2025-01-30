import template from './sw-order-detail.html.twig';

const { Criteria } = Shopware.Data;
const { State, Mixin } = Shopware;


Shopware.Component.override('sw-order-detail', {
    template,

    computed: {
        orderCriteria() {
           let criteria = this.$super('orderCriteria');
           criteria.getAssociation('lineItems.orderLineItemStates');
           return criteria;
        }
    }
});