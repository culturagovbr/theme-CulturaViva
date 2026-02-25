app.component('rcv-registration-update-cnpj', {
    template: $TEMPLATES['rcv-registration-update-cnpj'],
    
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
        const text = Utils.getTexts('rcv-registration-update-cnpj')
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
            registrationInfo: null,
            cnpj: '',
            invalidCNPJ: false,
            disableButton: true,
            loading: false,
            situacaoCadastralError: '',
            apiInfo: null,
            opportunity: $MAPAS.config.rcvRegistrationUpdateCnpj.opportunity,
            status: $MAPAS.config.rcvRegistrationUpdateCnpj.status,
        };
    },

    watch: {
        'registrationInfo'(_new, _old) {
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

        stepTitle() {
            const global = useGlobalState();

            if (global.auth.isLoggedIn) {
                switch (this.step) {
                    case 'get-cnpj':
                        return 'Informe o novo CNPJ da organização';
    
                    case 'situacao-cadastral':
                        return 'Situação cadastral inválida';
    
                    case 'natureza-juridica':
                        return 'Sua organização não pode se inscrever no Cadastro Nacional de Pontos e Pontões da Cultura';
    
                    case 'confirm':
                        return 'Deseja alterar o CNPJ para este verificado?';
    
                    case 'success':
                        return 'Solicitação concluída';
    
                    default:
                        return 'Selecione a organização que você deseja alterar o CNPJ';
                }
            }

            return 'Ops! Você precisa estar logado';
        },

        query() {
            const query = {
                'opportunity': `EQ(${this.opportunity})`, 
                'status': `EQ(${this.status})`, 
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
            const steps = ['get-cnpj', 'confirm', 'natureza-juridica', 'situacao-cadastral', 'success'];
            
            if (steps.includes(step)) {
                this.step = step;
            }
        },

        closeModal () {
            this.step = '';
            this.registrationInfo = null;
            this.cnpj = ''
            this.invalidCNPJ = false;
            this.situacaoCadastralError = '';
            this.apiInfo = null;
        },

        saveRegistrationInfo(registration) {
            this.registrationInfo = registration;
        },

        verifyCNPJ() {
            let returnApi = false;

            let url = Utils.createUrl('site/valida-cnpj', '');
            let api = new API();
            let data = { cnpj: this.cnpj };
            
            this.disableButton = true;
            api.POST(url, data).then(res => res.json()).then(data => {
                returnApi = data?.data ?? data;
                this.hasError = data?.error || false;
                this.disableButton = true;
                
                if(!returnApi) {
                    this.invalidCNPJ = true;
                } else if(returnApi == 'natureza-juridica-invalida') {
                    this.changeStep('natureza-juridica');
                } else if(typeof returnApi == 'object') {
                    this.invalidCNPJ = false;
                    this.apiInfo = returnApi;
                    this.changeStep('confirm');
                } else {
                    this.situacaoCadastralError = returnApi;
                    this.changeStep('situacao-cadastral');
                }
            })
        },

        updateCNPJ(modal) {
            let url = Utils.createUrl('site/alterar-cnpj', '');
            let api = new API();
            let data = { 
                cnpj: this.cnpj,
                registration: this.registrationInfo,
                apiInfo: this.apiInfo
            };
            
            this.loading = true;
            this.disableButton = true;
            api.POST(url, data).then(res => res.json()).then(data => {
                this.loading = false;
                this.disableButton = false;
                this.changeStep('success');
            })
        },

        url(registration) {
            return Utils.createUrl('registration', 'single', [registration.id]).toString();
        },
    },
});
