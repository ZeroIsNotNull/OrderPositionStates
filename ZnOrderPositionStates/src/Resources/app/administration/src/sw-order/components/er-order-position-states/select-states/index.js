import template from './select-states.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.register('select-states', {
    template,
    props: ['item', 'itemid'],

    inject: [
        'repositoryFactory',
        'positionStateService',
    ],

    data() {
        return {
            result: null,
            orderPositionStates: [],
            selectedLabel: null,
            selectedValue: null,
            type: null,
        };
    },
    computed: {
        orderLineItemStatesRepository() {
            return this.repositoryFactory.create('order_position_states');
        }
    },
    created() {
        this.defaultLabel();
    },
    methods: {
        defaultLabel() {
            this.type = this.item.type;
            this.getStateNameOfItem(this.item);
            this.getPositionStates();
            this.selectedValue = this.getStateIdOfItem(this.item);
        },
        getPositionStates() {
            const criteria = new Criteria();
            try {
                this.orderLineItemStatesRepository
                    .search(criteria, Shopware.Context.api)
                    .then(result => {
                        this.orderPositionStates = result;
                    });
            } catch (error) {
                console.log(error);
            }
        },
        getStateIdOfItem(item) {
            if (item.extensions && item.extensions.orderLineItemStates && item.extensions.orderLineItemStates.length > 0) {
                let id  = item.extensions.orderLineItemStates[0].id;
                return id;
            } else {
                return false;
            }
        },
        getNameByStateId(id) {
            let state = this.orderPositionStates.filter(state =>
                state.id === id
            );
            return (state.length > 0)? state[0]['technicalName'] : '';
        },
        async getStateName(itemId) {
            try {
                let response = await this.positionStateService.getPositionState(itemId, {}, {});
                return this.getTranslation(response.state.positionStatesName);
            } catch(error) {
                console.log(error);
            }
        },
        setNewState(stateId, itemId) {
            this.selectedLabel = this.getNameByStateId(stateId);
            this.selectedValue = stateId;
            try {
                this.positionStateService.setPositionState(itemId, stateId, {}, {});
            } catch(error) {
                console.log(error);
            }
        },
        getStateNameOfItem(item) {
            if (item.extensions && item.extensions.orderLineItemStates && item.extensions.orderLineItemStates.length > 0) {
                let techName  = item.extensions.orderLineItemStates[0].technicalName;
                let translation = this.getTranslation(techName);
                this.selectedLabel  = this.$t(translation);
            } else {
                this.selectedLabel = 'n.A.';
            }
        },
        getTranslation(techName) {
            let translation = 'orderPositionStatesTranslations.' + techName;
            return this.$t(translation);
        }
    }
});