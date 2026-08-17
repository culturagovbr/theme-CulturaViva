app.component('rcv-countries', {
    template: $TEMPLATES['rcv-countries'],

    emits: ['update:modelCountries1', 'update:modelCountries2'],

    props: {
        modelCountries1: {
            type: Array,
            default: [],
        },
        modelCountries2: {
            type: Array,
            default: [],
        },
    },

    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        return { hasSlot };
    },

    data() {
        return {
            selectedCountries: [],
        };
    },

    watch: {
        selectedCountries: {
            handler(value) {
                this.$emit('update:modelCountries1', value);
                if (this.modelCountries2 !== null) {
                    this.$emit('update:modelCountries2', value);
                }
            },
            deep: true,
        },

        modelCountries1: {
            handler(value) {
                this.selectedCountries = value;
            },
            deep: true,
        },

        modelCountries2: {
            handler(value) {
                if (this.modelCountries2 !== null) {
                    this.selectedCountries = value;
                }
            },
            deep: true,
        },
    },

    computed: {
        countries() {
            const values = $MAPAS.config.countries.paises
                .filter(country => country.value)
                .map(country => country.value);

            return $RCV.filterItems(values);
        },
    },
});