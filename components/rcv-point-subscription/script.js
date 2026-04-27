app.component('rcv-point-subscription' , {
    template: $TEMPLATES['rcv-point-subscription'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
        
        entities: {
            type: Array,
            required: false
        },
    },

    setup() {
        const text = Utils.getTexts('rcv-point-subscription');
        return { text }
    },

    data () {
        const pontoQuery = {'type': 'EQ(2)', '@permissions': '@control', 'status': 'GTE(0)'};
        const global = useGlobalState();
        if (global.auth.is('admin')) {
            pontoQuery.user = 'EQ(@me)';
        }
        return {
            isLoading: false,
            selectedEntity: null,
            hasError: false,
            relatedAgent: null,
            agent: null,
            category: null,
            nextStep: false,
            cnpj: '',
            verifiedCNPJ: false, // CNPJ foi verificado na receita
            naturezaJuridicaInvalida: false, // CNPJ verificado na receita mas possui a natureza jurídica inválida
            invalidCNPJ: false, // CNPJ invalidado na receita
            withoutOrganizations: false,
            loggedOut: false,
            subscriptionType: null,
            rcvSeals: $MAPAS.config.rcvPointSubscription.rcvSeals,
            categoriesMap: $MAPAS.config.rcvPointSubscription.categoriesMap,
            hasRegistrations: $MAPAS.config.rcvPointSubscription.has_registrations,
            rfData: null,
            loading: true,
            isCertificated: false,
            createNewOrganization: true,
            getAgent: null,
            pontoQuery,
            invalidPrincipalAgent: false,
            agentType: $MAPAS.user.profile?.type?.id,
            invalidAgentType: this.agentType === 2,
            hasCnpjExternalOrgConflict: false,
        };
    },

    mounted() {
        this.modalLoggedOut();
    },

    computed: {
        apiReturnedFieldsUsed() {
            return $MAPAS.config.rcvPointSubscription.apiReturnedFieldsUsed;
        },
        apiReturnedLockedFields() {
            return $MAPAS.config.rcvPointSubscription.apiReturnedLockedFields;
        },
        errorSituacaoCadastral() {
            return this.nextStep && this.verifiedCNPJ && !this.naturezaJuridicaInvalida && this.invalidCNPJ;
        },
        modalTitle() {
            const registrationCategory = this.subscriptionType;
            const useCNPJ = registrationCategory !== 'ponto-coletivo';
            const selectEntityStep = (useCNPJ && this.nextStep || !useCNPJ && !this.nextStep);

            const errorSituacaoCadastral = this.nextStep && this.verifiedCNPJ && !this.naturezaJuridicaInvalida && this.invalidCNPJ;
            const errorNaturezaJuridica = this.nextStep && this.verifiedCNPJ && this.naturezaJuridicaInvalida;
            
            const error = errorSituacaoCadastral || errorNaturezaJuridica || this.invalidPrincipalAgent || this.invalidAgentType;

            if (this.loggedOut) {
                return this.text('Ops! Você precisa fazer login para acessar o Cadastro');

            } else if (this.hasCnpjExternalOrgConflict) {
                return this.text('CNPJ já vinculado a outra organização');

            } else if(!error && this.loading) {
                return this.text('Buscando por suas organizações');

            } else if (!error && selectEntityStep && !this.withoutOrganizations) {
                return this.text('Seu Ponto de Cultura é alguma das organizações abaixo?');

            } else if (!error && selectEntityStep && this.withoutOrganizations) {
                return this.text('Você ainda não possui organizações cadastradas');

            } else if (!error && !this.nextStep && !this.verifiedCNPJ) {
                return this.text('Informe o CNPJ da sua organização');

            } else if (errorSituacaoCadastral) {
                return this.text('Situação cadastral inválida')

            } else if (errorNaturezaJuridica) {
                return this.text('Sua organização não pode se inscrever no Cadastro Nacional de Pontos e Pontões da Cultura');

            } else if (this.invalidPrincipalAgent) {
                return this.text('Ops! Identificamos uma inconsistência nos seus dados');
            } else if (this.invalidAgentType) {
                return this.text('Não é possível continuar o cadastro');
            }
        },
    },

    methods: {
        hasCNPJ(entity) {
            return entity.cnpj && entity.cnpj.trim() !== '';
        },
        hasRegistrationCategory(entity) {
            let type = this.subscriptionType;
            let category = this.categoriesMap[type];
            let result = null;
            if(this.hasRegistrations) {
                for (item of this.hasRegistrations) {
                    if (item.agentId == entity.id && item.category == category) {
                        result = item;
                        break;
                    }
                }
            }

            return result;
        },

        disabledEntity(entity, entities) {
            if(!this.hasRegistrationCategory(entity) && this.subscriptionType == 'ponto-coletivo') {
                return false;
            }

            if (entity.cnpj !== this.cnpj && this.hasCNPJ(entity)) {
                return true; 
            }

            for(let _entity of entities) {
                if(_entity.cnpj === this.cnpj) {
                    return true;
                }
            }

            if(this.hasRegistrationCategory(entity)) {
                return true;
            }
           

            return false; 
        },
    
        checkedEntity(entity) {
            if (entity.cnpj === this.cnpj) {
                this.selectedEntity = entity;
                this.createNewOrganization = false;
                return true;
            }
            return false;
        },
    
        isNoneSelected() {
            return !this.selectedEntity;
        },

        closeModal() {
            this.relatedAgent = null;
            this.agent = null;
            this.category = null;
            this.nextStep = false;
            this.cnpj = '';
            this.verifiedCNPJ = false;
            this.naturezaJuridicaInvalida = false;
            this.invalidCNPJ = false;
            this.withoutOrganizations = false;
            this.subscriptionType = null;
            this.rfData = null;
            this.loading = true;
            this.hasError = false;
            this.isCertificated = false;
            this.invalidPrincipalAgent = false;
            this.invalidAgentType = false;
            this.hasCnpjExternalOrgConflict = false;

            this.modalLoggedOut();
        },

        openModalTitle(type) {
            if (this.agentType == 2) {
                this.invalidAgentType = true;
                return;
            }
            this.subscriptionType = type;
            this.selectedEntity = null;
        },

        modalLoggedOut() {
            const global = useGlobalState();
            if (!global.auth.isLoggedIn) {
                this.loggedOut = true;
            } else {
                this.loggedOut = false;
            }
        },

        // Antes da Receita: conflito de CNPJ conforme regra no endpoint (inscrições RCV)
        async verifyCNPJ() {
            let returnApi = false;

            const checkConflictUrl = Utils.createUrl('site/check-cnpj-org-conflict', '');
            const validateCnpjUrl = Utils.createUrl('site/valida-cnpj', '');
            const api = new API();

            this.isLoading = true;
            this.hasCnpjExternalOrgConflict = false;

            try {
                const checkPayload = { cnpj: this.cnpj };
                if (this.subscriptionType === 'ponto-entidade' || this.subscriptionType === 'pontao') {
                    checkPayload.subscriptionType = this.subscriptionType;
                }
                const checkRes = await api.POST(checkConflictUrl, checkPayload);
                const checkData = await checkRes.json();
                if (checkData.conflict) {
                    this.hasCnpjExternalOrgConflict = true;
                    this.isLoading = false;

                    return;
                }

                const res = await api.POST(validateCnpjUrl, { cnpj: this.cnpj });
                const data = await res.json();
                this.isLoading = false;
                returnApi = data?.data ?? data;
                this.hasError = data?.error || false;

                if(!returnApi) {
                    this.nextStep = false;
                    this.verifiedCNPJ = false;
                    this.naturezaJuridicaInvalida = false;
                    this.invalidCNPJ = true;
                    this.rfData = null;
                } else if(returnApi == 'natureza-juridica-invalida') {
                    this.nextStep = true;
                    this.verifiedCNPJ = true;
                    this.naturezaJuridicaInvalida = true;
                    this.invalidCNPJ = false;
                    this.rfData = null;
                } else if(typeof returnApi == 'object') {
                    this.nextStep = true;
                    this.verifiedCNPJ = true;
                    this.naturezaJuridicaInvalida = false;
                    this.invalidCNPJ = false;
                    this.rfData = returnApi;
                } else {
                    this.nextStep = true;
                    this.verifiedCNPJ = true;
                    this.naturezaJuridicaInvalida = false;
                    this.invalidCNPJ = returnApi;
                    this.rfData = returnApi;
                }
            } catch (error) {
                console.error(error);
                this.isLoading = false;
            }
        },

        async createOrganization(returnApi = false) {
            this.isLoading = true;
            let data = {};

            if (returnApi) { // Caso seja um retorno da AIP da receita
                let response = this.rfData;
                for(_field in this.apiReturnedFieldsUsed) {
                    let field = this.apiReturnedFieldsUsed[_field];
                    let val = eval(`response.${field.rfDataField}`);

                    if(_field == 'telefone1') {
                        let telephone = val;
                        let telNumber = telephone?.numero;
                        
                        if (telephone?.numero.replace(/\D/g,'').length === 8) {
                            data[_field] = `(${telephone.ddd}) ${telNumber.slice(0, 4)}${telNumber.slice(4)}`;
                        } else if (telephone?.numero.replace(/\D/g,'').length === 9) {
                            data[_field] = `(${telephone.ddd}) ${telNumber.slice(0, 5)}${telNumber.slice(5)}`;
                        }
                    } else {
                        data[_field] = val;
                    }
                    
                }
                // data.nomeCompleto = this.rfData.nomeEmpresarial;
                // data.emailPublico = this.rfData.correioEletronico;
                // data.cnpj = this.formatCNPJ(this.rfData.ni);
                // data.En_Municipio = this.rfData.endereco.municipio.descricao;
                // data.En_Estado = this.rfData.endereco.uf;

                // Formatação para o número de telefone
                // let telephone = this.rfData.telefones[0];
                // let telNumber = telephone.numero;
                // if (telNumber.length === 8) {
                //     data.telefone1 = `(${telephone.ddd}) ${telNumber.slice(0, 4)}-${telNumber.slice(4)}`;
                // } else if (telNumber.length === 9) {
                //     data.telefone1 = `(${telephone.ddd}) ${telNumber.slice(0, 5)}-${telNumber.slice(5)}`;
                // }
            } 

            let url = Utils.createUrl('agent/register-organization', '');
            let api = new API();

            try{
                const response = await api.POST(url, data);
                const agentData = await response.json();
                const agent = new Entity('agent', agentData.id)
                agent.populate(agentData);
                this.relatedAgent = agent;
                await this.subscribe();
            } catch (error) {
                console.error(error);
                this.isLoading = false;
            }
        },

        async subscribe() {
            this.isLoading = true;
            const global = useGlobalState();
            const messages = useMessages();

            const registration = new Entity('registration');
            registration.opportunity = this.entity;
            registration.owner = global.user?.profile;
            registration.range = 'Cadastro';

            if (this.subscriptionType === 'pontao') {
                const pontao = this.categoriesMap[this.subscriptionType];
                registration.category = pontao;
                registration.proponentType = 'Pessoa Jurídica';

            }

            if (this.subscriptionType === 'ponto-entidade') {
                const pontoEntidade = this.categoriesMap[this.subscriptionType];
                registration.category = pontoEntidade;
                registration.proponentType = 'Pessoa Jurídica';
            }

            if (this.subscriptionType === 'ponto-coletivo') {
                const pontoColetivo = this.categoriesMap[this.subscriptionType];
                registration.category = pontoColetivo;
                registration.proponentType = 'Coletivo';
            }

            const _agent = this.relatedAgent || this.selectedEntity;

            let hasRegistration = this.hasRegistrationCategory(_agent);
            
            if(hasRegistration && hasRegistration.status !== 3) {
                let location = hasRegistration.status == 0 ? hasRegistration.editUrl : hasRegistration.singleUrl;
                window.location.href = location;
            } else {
                if(!_agent) {
                    messages.error(this.text('Cadastre uma organização ou selecione uma já existente'));
                    this.isLoading = false;
                    return;
                } else {

                    // Garante que tipoPonto seja array
                    if (!_agent.tipoPonto ) {
                        _agent.tipoPonto = [];
                    }

                    const tipoMap = {
                        'ponto-coletivo': 'ponto_coletivo',
                        'ponto-entidade': 'ponto_entidade',
                        'pontao': 'pontao'
                    };

                    const typeSelected = tipoMap[this.subscriptionType];

                    if (typeSelected && !_agent.tipoPonto.includes(typeSelected)) {
                        _agent.tipoPonto.push(typeSelected);
                    }

                     _agent.rcv_tipo = 'ponto';
                }
    
                if(this.subscriptionType != "ponto-coletivo") {
                    _agent.cnpj = this.formatCNPJ(this.rfData.ni);
                    _agent.rcv_rfData = this.rfData;
                    
                    if(this.relatedAgent) {
                        registration.rcv_locked_fields = this.apiReturnedLockedFields;
                    } else {
                        registration.rcv_locked_fields = ['cnpj'];
                    }
                }
    
                await _agent.save();
    
                try {
                    this.entity.__processing = 'Criando nova inscrição...'
                    await registration.save();
                    await registration.addRelatedAgent('coletivo', _agent);
                    await registration.save();

                    this.entity.__processing = 'Redirecionando...'
                    window.location.href = registration.editUrl;
                } catch (error) {
                    this.isLoading = false;
                    console.error(error);
                }
            }

        },

        confirmAndSubscribe(hasCNPJ = true) {
            const userProfileType = $MAPAS.user.profile.type.id;
            
            if (this.createNewOrganization) {
                if(userProfileType == 2) {
                    this.invalidPrincipalAgent = true;
                    return;
                }

                if(hasCNPJ) {
                    this.entity.__processing = 'Criando nova organização...'
                    this.createOrganization(true);
                } else {
                    this.entity.__processing = 'Criando nova organização...'
                    this.createOrganization();
                }
            } else {
                this.subscribe();
            }
        },

        redirectLogin() {
            let url = Utils.createUrl('auth', '') + `?redirectTo=${this.entity.singleUrl.pathname}`;
            window.location.href = url;
        },

        handleEntities(entities) {
            if (!entities || entities.length === 0) {
                this.withoutOrganizations = true;
            } else {
                this.withoutOrganizations = false;
    
                entities.forEach((entity) => {
                    const sealCertified = entity.seals?.find((seal) => seal.sealId === this.rcvSeals);
                    if (entity.cnpj === this.cnpj && sealCertified) {
                        this.isCertificated = true;
                    }
                });
            }
            this.loading = false;
        },

        getValueRadio(event, agent) {
            let val = event.target ? event.target.value : event;

            this.getAgent = agent;

            if(val == 'new-organization') {
                this.createNewOrganization = true;
                return;
            } else {
                this.createNewOrganization = false;
            }
            
            this.selectedEntity = agent;
            const sealCertified = agent.seals.find((seal) => seal.sealId === this.rcvSeals);

            this.isCertificated = agent.seals && sealCertified;
        },

        formatCNPJ(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
        
            if (cnpj.length === 14) {
                return cnpj.replace(
                    /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
                    '$1.$2.$3/$4-$5'
                );
            }
        }
    }
});