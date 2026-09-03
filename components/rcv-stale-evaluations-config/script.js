app.component('rcv-stale-evaluations-config', {
    template: $TEMPLATES['rcv-stale-evaluations-config'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        enabledProp: {
            type: String,
            required: true,
        },
        daysProp: {
            type: String,
            required: true,
        },
        min: {
            type: Number,
            default: 1,
        },
        max: {
            type: Number,
            default: 180,
        },
    },

    data() {
        return {
            armed: !!this.entity[this.enabledProp],
        };
    },

    computed: {
        deactivated() {
            return this.entity.distributionConfiguration === 'deactivate';
        },
    },

    watch: {
        // proteção: ao desativar a distribuição, desmarca e limpa
        deactivated(isDeactivated) {
            if (isDeactivated) {
                this.clearConfig();
            }
        },
    },

    created() {
        // acompanha o valor vindo do servidor (populate) ou de outros pontos
        this.$watch(() => this.entity[this.enabledProp], (value) => {
            this.armed = !!value;
        });
    },

    methods: {
        onToggle(event) {
            this.armed = event.target.checked;

            if (!this.armed) {
                this.clearConfig();
                return;
            }

            this.entity[this.enabledProp] = true;

            // garante que o campo de dias vá no payload, para a validação padrão
            // devolver o erro e impedir o salvamento enquanto estiver vazio
            const days = this.entity[this.daysProp];
            if (days === null || days === undefined || days === '') {
                this.entity[this.daysProp] = '';
            }

            this.entity.save(300);
        },

        onDaysChange() {
            // o autosave do entity-field envia a quantidade de dias já com a opção ligada
            this.entity[this.enabledProp] = this.armed;
        },

        clearConfig() {
            const had_config = !!this.entity[this.enabledProp] || this.entity[this.daysProp] != null;

            this.armed = false;
            this.entity[this.enabledProp] = false;
            this.entity[this.daysProp] = null;

            if (this.entity.__validationErrors) {
                delete this.entity.__validationErrors[this.daysProp];
            }

            if (had_config) {
                this.entity.save(300);
            }
        },
    },
});
