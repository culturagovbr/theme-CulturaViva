app.component('rcv-transfer-ownership', {
    template: $TEMPLATES['rcv-transfer-ownership'],
    
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
        const text = Utils.getTexts('rcv-transfer-ownership')
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
            isLoading: false,
            isOpen: false, // Estado do modal
            step: '',
            organization: null,
            destination: null,
            disableButton: true
        };
    },

    watch: {
        'organization'(_new, _old) {
            if(_new) {
                this.disableButton = false;
            }
        },
        'destination'(_new, _old) {
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

        modalTitle() {
            const global = useGlobalState();

            if (global.auth.isLoggedIn) {
                switch (this.step) {
                    case 'transfer':
                        return this.text('Selecione a organização que terá a representação alterada');
                    case 'reassign':
                        return this.text('Selecione a pessoa que irá se tornar representante da organização');
                    case 'request':
                        return this.text('Encontre a organização que você deseja solicitar a propriedade para se tornar representante');
                    case 'confirm-transfer':
                    case 'confirm-request':
                        return this.text('Você confirma essa alteração?');
                        
                    default:
                        return this.text('Alteração de responsável pela organização');
                }
            }

            return 'Ops! Você precisa estar logado';
        },

        parent() {
            return $MAPAS.config.rcvTransferOwnership.parent || [];
        },

        transferQuery() {
            const query = {
                '@permissions': '@control',
                '@order': 'id ASC',
                '@select': '*',
            }

            if (this.parent && !this.isAdmin) {
                query['parent'] = `EQ(${this.parent.id})`;
            }

            return query;
        },

        reasignQuery() {
            const query = {
                'type': 'EQ(1)', 
                'user': '!EQ(@me)', 
                '@permissions': 'view',
                '@order': 'id ASC',
                '@select': '*',
            };

            return query;
        },

        requestQuery() {
            const query = {
                'type': 'EQ(2)', 
                'user': '!EQ(@me)', 
                '@permissions': 'view',
                '@order': 'id ASC',
                '@select': '*',
            }

            return query;
        },

        agentUser() {
            return $MAPAS.user.profile;
        }
    },
    
    methods: {
        defineNames () {
            this.entity.name = this.displayName;
            this.entity.nomeCompleto = this.fullname;

            this.$emit('namesDefined', this.entity);
        },

        url(agent) {
            return Utils.createUrl('agent', 'single', [agent.id]).toString();
        },

        changeStep (step) {
            const steps = ['transfer', 'request', 'reassign', 'confirm-transfer', 'confirm-request'];
            this.disableButton = true;

            if (steps.includes(step)) {
                this.step = step;
            }
        },

        closeModal (modal) {
            this.step = '';
            this.organization = null;
            this.destination = null;
            this.disableButton = true;

            if (modal) {
                modal.close();
            }
        },

        confirmRequest(modal) {
            this.isLoading = true;
            
            let agent = new Entity('agent', this.organization.id);
            agent.parent = this.agentUser;
            agent.save().then(() => {
                this.isLoading = false;
                modal.close();
            });
        },

        confirmTransfer(modal) {
            this.isLoading = true;
            
            let agent = new Entity('agent', this.organization.id);
            agent.parent = this.destination;
            agent.save().then(() => {
                this.isLoading = false;
                modal.close();
            });
        }
    },
});
