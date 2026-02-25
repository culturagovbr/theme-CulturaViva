app.component('rcv-registration-update', {
    template: $TEMPLATES['rcv-registration-update'],

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
        const text = Utils.getTexts('rcv-registration-update')
        return { text, hasSlot }
    },

    data() {
        return {
            registrationInfo: null,
            disableButton: true,
            opportunity: $MAPAS.config.rcvRegistrationUpdate.opportunity,
            status: $MAPAS.config.rcvRegistrationUpdate.status,
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

        registrations() {
            return $MAPAS.config.rcvRegistrationUpdate.registrations || [];
        },

        hasRegistrations() {
            return Object.keys(this.registrations).length > 0 ? true : false;
        },

        query() {
            const query = {
                'opportunity': `EQ(${this.opportunity})`, 
                'status': `EQ(${this.status})`, 
            };

            return query;
        },

        modalTitle() {
            const global = useGlobalState();
            return global.auth.isLoggedIn ? 'Selecione a organização que você deseja atualizar' : 'Ops! Você precisa estar logado';
        }
    },
    
    methods: {
        saveRegistrationInfo(registration) {
            this.registrationInfo = registration;
        },

        updateOrganization(modal) {
            let url = Utils.createUrl('site/atualizar-cadastro', '');
            let api = new API();
            let data = { 
                registration: this.registrationInfo,
            };

            api.POST(url, data).then(res => res.json()).then(data => {
                modal.close();
                window.location.href = data;
            })
        },

        url(registration) {
            return Utils.createUrl('registration', 'single', [registration.id]).toString();
        },
    },
});
