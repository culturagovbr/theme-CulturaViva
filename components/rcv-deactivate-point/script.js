app.component('rcv-deactivate-point', {
    template: $TEMPLATES['rcv-deactivate-point'],
    
    // define os eventos que este componente emite
    emits: ['namesDefined'],

    props: {
        buttonLabel: {
            type: String,
            default: 'Abrir Modal'
        },
        title: {
            type: String,
            default: 'Título do Modal'
        }

    },
    
    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-deactivate-point')
        return { text, hasSlot }
    },

    beforeCreate() { },
    created() { },

    beforeMount() { },
    mounted() { },

    beforeUpdate() { },
    updated() { },

    beforeUnmount() {},
    unmounted() {},

    data() {
        return {
            isOpen: false, // Estado do modal
            step: '',
            disableCulturalHub: '',
            organization: null,
            disableButton: true,
            loading: false,
        };
    },

    watch: {
        'organization'(_new, _old) {
            if(_new) {
                this.disableButton = false;
            }
        },
    },

    computed: {
        isAdmin() {
            const global = useGlobalState();
            return global.auth.is('admin');
        },

        compareDisplayName() {
            return this.entity.name == this.displayName;
        },

        compareFullname() {
            return this.entity.nomeCompleto == this.fullname;
        },

        agents() {
            return $MAPAS.config.rcvDeactivatePoint.agents || [];
        },

        agentUser() {
            return $MAPAS.user.profile;
        },

        stepTitle() {
            switch (this.step) {
                case 'message':
                    return 'Desativação da organização'
                case 'success':
                    return 'Desativação solicitada'
                default:
                    return 'Selecione a organização que deseja desativar';
            }
        },

        query() {
            const query = {
                '@select': 'id,name,cnpj', 
                '@permissions': '@control', 
                '@order': 'id ASC', 
                'type': 'EQ(2)', 
                '@verified': 1
            };

            return query;
        },
    },
    
    methods: {
        defineNames () {
            this.entity.name = this.displayName;
            this.entity.nomeCompleto = this.fullname;

            // emite o evento enviando o data
            this.$emit('namesDefined', this.entity);
        },

        changeStep (step) {
            const steps = ['message', 'success'];
            
            if (steps.includes(step)) {
                this.step = step;
            }
        },

        sendMessage(modal) {
            const messages = useMessages();
            let url = Utils.createUrl('site/desativar-ponto', '');
            let api = new API();
            let data = { 
                message: this.disableCulturalHub,
                organization: this.organization.id,
                user: this.agentUser._id
            };
            
            this.disableButton = true;
            this.loading = true;
            api.POST(url, data).then(res => res.json()).then(data => {
                this.disableButton = false;
                this.loading = false;
                messages.success("Sua solicitação de desativação da organização foi enviada.", 5000)
                this.changeStep('success');
            })
        },

        closeModal (modal = false) {
            this.step = '';
            this.disableCulturalHub = '';
            this.organization = null;

            if (modal) {
                modal.close();
            }
        },

        url(agent) {
            return Utils.createUrl('agent', 'single', [agent.id]).toString();
        },
    },
});
