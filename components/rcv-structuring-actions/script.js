app.component('rcv-structuring-actions', {
    template: $TEMPLATES['rcv-structuring-actions'],
    
    // define os eventos que este componente emite
    emits: ['update:modelActions', 'update:modelOtherActions', 'changeActions', 'changeOtherActions'],

    props: {
        modelActions: {
            type: Array,
            default: [],
        },

        modelOtherActions: {
            type: Array,
            default: [],
        },

        fieldClass: {
            type: String || Array,
            default: '',
        },

        hideLabels: {
            type: Boolean,
            default: false,
        },

        hideTags: {
            type: Boolean,
            default: false,
        },

        actionsPlaceholder: {
            type: String,
            default: 'Busque ou selecione as ações estruturantes',
        },

        otherActionsPlaceholder: {
            type: String,
            default: 'Busque ou selecione as demais ações estruturantes',
        },
    },

    data() {
        return {
            selectedActions: [],
            selectedOtherActions: [],
        }
    },

    watch: {
        selectedActions: {
            handler(value) {
                this.$emit('update:modelActions', value);
                this.$emit('changeActions', value);
            },
            deep: true,
        },
        modelActions: {
            handler(value) {
                this.selectedActions = this.modelActions;
                this.$emit('update:modelActions', value);
                this.$emit('changeActions', value);
            },
            deep: true,
        },

        selectedOtherActions: {
            handler(value) {
                this.$emit('update:modelOtherActions', value);
                this.$emit('changeOtherActions', value);
            },
            deep: true,
        },
        modelOtherActions: {
            handler(value) {
                this.selectedOtherActions = this.modelOtherActions;
                this.$emit('update:modelOtherActions', value);
                this.$emit('changeOtherActions', value);
            },
            deep: true,
        },
    },

    computed: {
        actions() {
            return $RCV.taxonomyFilterItems('acao_estruturante');
        },

        otherActions() {
            return $RCV.taxonomyFilterItems('acao_estruturante_outra');
        }
    },
});
