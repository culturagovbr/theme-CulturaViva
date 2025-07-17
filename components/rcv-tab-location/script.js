app.component('rcv-tab-location', {
    template: $TEMPLATES['rcv-tab-location'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        return {
            fields: {
                group1: {
                    rcv_org_brasil: null,
                },
                group2: {
                    subgroup21: {
                        paisPontaPontao: "País",
                        En_EstadoPontaPontao: "Estado",
                    },
                    subgroup22: {
                        cepPontaPontao: "CEP",
                        En_Nome_LogradouroPontaPontao: "Logradouro",
                        En_NumPontaPontao: "Número",
                        En_MunicipioPontaPontao: "Cidade",
                    }
                },
                group3: {
                    subgroup31: {
                        En_Pais: "País",
                        En_Estado: "Estado",
                        En_Complemento: "Complemento",
                        En_Bairro: "Bairro",
                    }, 
                    subgroup32: {
                        En_CEP: "CEP",
                        En_Nome_Logradouro: null,
                        En_Num: null,
                        En_Municipio: null,
                    }
                },
                group4: {
                    sede_realizaAtividades: null,
                    rcv_sede_realizaAtividades_outros: "A organização realiza atividades em outro espaço?",
                },
                group5: {
                    rcv_sede_realizaAtividades_outros_lista: "Outro(s) espaços onde realiza atividades",
                    rcv_territorio_direto_org: "Território(s) de atuação",
                    rcv_local_atuacao_org: "Local(is) de atuação",
                },
                group6: {
                    rcv_localizacao_sede_org: "Localização da sede ou espaço de encontro",
                    rcv_atividades_realizadas_neste_territorio: "Atuação em territórios vulneráveis",
                    rcv_medidas_acessibilidade_disponiveis: "Acessibilidade arquitetônica",
                },
                group7: {
                    rcv_medidas_acessibilidade_disponiveis_outras: "Na sede ou espaço de atuação, há medidas de acessibilidade arquitetônica, para inclusão de pessoas com deficiência e/ou mobilidade reduzida? Se sim, indique quais.",
                    rcv_medidas_acessibilidade_comunicacional_outras: "Nas atividades da organização, são utilizadas medidas de acessibilidade comunicacional? Se sim, indique quais.",
                    rcv_medidas_acessibilidade_comunicacional: "Acessibilidade comunicacional",
                },
                group8: {
                    rcv_medidas_acessibilidade_atitudinal: "Acessibilidade atitudinal",
                    rcv_medidas_acessibilidade_atitudinal_outras: "Nas ações da organização, são utilizadas medidas de acessibilidade atitudinal? Se sim, indique quais.",
                },

            }
        }
    },

    computed: {
        showTab() {
            for (const prop in this.fields) {
                if (this.isValid(prop)) {
                    return true;
                }
            }
            return false;
        }
    },
    
    methods: {
        isValid(prop) {
            return  this.entity[prop] !== null && 
                    this.entity[prop] !== '' && 
                    !(Array.isArray(this.entity[prop]) && !this.entity[prop].length);
        },

        showGroup(group) {
            switch (group) {
                case 'group2':
                    return this.entity.rcv_org_brasil == "Não";
                case 'group3':
                    return this.entity.rcv_org_brasil == "Sim";
                case 'group4':
                    return  this.isValid('sede_realizaAtividades') || 
                            this.isValid('rcv_sede_realizaAtividades_outros');
                case 'group5':
                    return  this.isValid('rcv_sede_realizaAtividades_outros_lista') ||
                            this.isValid('rcv_territorio_direto_org') ||
                            this.isValid('rcv_local_atuacao_org');
                case 'group6':
                    return  this.isValid('rcv_localizacao_sede_org') ||
                            this.isValid('rcv_atividades_realizadas_neste_territorio') ||
                            this.isValid('rcv_medidas_acessibilidade_disponiveis');
                case 'group7':
                    return  this.isValid('rcv_medidas_acessibilidade_disponiveis_outras') ||
                            this.isValid('rcv_medidas_acessibilidade_comunicacional_outras') ||
                            this.isValid('rcv_medidas_acessibilidade_comunicacional');
                case 'group8':
                    return  this.isValid('rcv_medidas_acessibilidade_atitudinal') ||
                            this.isValid('rcv_medidas_acessibilidade_atitudinal_outras');               
            }
        },

        hasSubgroups(group) {
            return Object.values(group).some(value => typeof value === 'object' && value !== null && !Array.isArray(value));
        },
    },
});
