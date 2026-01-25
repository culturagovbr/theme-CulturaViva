app.component('rcv-registration-update-type', {
    template: $TEMPLATES['rcv-registration-update-type'],
    
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
        const text = Utils.getTexts('rcv-registration-update-type')
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
            selectedOption: null,
            disableButton: true,
            cnpj: '',
            invalidCNPJ: false,
            situacaoCadastralError: '',
            apiInfo: null,
            opportunity: $MAPAS.config.rcvRegistrationUpdateType.opportunity,
            status: $MAPAS.config.rcvRegistrationUpdateType.status,
            loading: false,
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
                    case 'type':
                        return 'Informe o novo tipo da sua organização';

                    case 'situacao-cadastral':
                    case 'natureza-juridica':
                        return 'Ops!';

                    case 'confirm':
                        return 'CNPJ válido!';

                    case 'get-cnpj':
                        return "Informe o CNPJ da organização";

                    case 'success':
                        return 'Solicitação concluída';

                    default:
                        return 'Selecione a organização que você deseja alterar';
                }
            }

            return 'Ops! Você precisa estar logado';
        },

        query() {
            let query = {
                'opportunity': `EQ(${this.opportunity})`, 
                'status': `EQ(${this.status})`, 
            };

            return query;
        },

        needCnpjVerification() {
            const whoNeeds = ['1', '2', '3'];
            return whoNeeds.includes(this.selectedOption);
        }
    },
    
    methods: {
        defineNames () {
            this.entity.name = this.displayName;
            this.entity.nomeCompleto = this.fullname;

            // emite o evento enviando o data
            this.$emit('namesDefined', this.entity);
        },

        changeStep (step) {
            const steps = ['type', 'get-cnpj', 'natureza-juridica', 'confirm' ,'situacao-cadastral', 'success'];
            
            if (steps.includes(step)) {
                this.step = step;
            }
        },

        verifyCNPJ() {
            let returnApi = false;

            let url = Utils.createUrl('site/valida-cnpj', '');
            let api = new API();
            let data = { cnpj: this.cnpj };
            
            this.disableButton = true;
            api.POST(url, data).then(res => res.json()).then(data => {
                this.disableButton = false;
                returnApi = data?.data ?? data;
                this.hasError = data?.error || false;

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

        closeModal () {
            this.step = '';
            this.registrationInfo = null;
            this.selectedOption = null;
            this.cnpj = ''
            this.invalidCNPJ = false;
            this.situacaoCadastralError = '';
            this.apiInfo = null;
        },

        saveRegistrationInfo(registration) {
            this.registrationInfo = registration;
        },

        disableOption(category) {
            const currentCategory = this.registrationInfo.category;
            if (currentCategory == category) {
                return false;
            }

            return true;
        },

        updateOrganization(modal) {
            const needsCnpjVerification = ['1', '2', '3']
            if (needsCnpjVerification.includes(this.selectedOption) && this.cnpj == '') {
                this.changeStep('get-cnpj');
                return
            } 

            let url = Utils.createUrl('site/alterar-organizacao', '');
            let api = new API();
            let data = { 
                cnpj: this.cnpj,
                apiInfo: this.apiInfo,
                registration: {
                    id: this.registrationInfo.id || this.registrationInfo._id,
                    category: this.registrationInfo.category,
                },
                option: this.selectedOption,
            };
            
            this.loading = true;
            this.disableButton = true;
            this.changeStep('success');
            api.POST(url, data).then(res => res.json()).then(response => {
                setTimeout(() => {
                    this.disableButton = false;
                    window.location.href = response;
                }, 500);
            })
        },

        url(registration) {
            return Utils.createUrl('registration', 'single', [registration.id]).toString();
        },
    },
});
