app.component('rcv-tab-public', {
    template: $TEMPLATES['rcv-tab-public'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        return {
            fields: {
                rcv_faixa_etaria: "Qual a faixa etária do público que participa das ações da organização?",
                rcv_deficiencia: "Dentre o público que participa das ações da organização há pessoas com Deficiência - PCD?",
                rcv_deficiencia_outros: "Dentre o público que participa das ações da organização há pessoas com Deficiência - PCD?",
                rcv_publicos_prioritarios: "Indique se estes públicos específicos estão entre os prioritários na atuação da organização?",
                rcv_publico_outros: "Indique se estes públicos específicos estão entre os prioritários na atuação da organização?",
                rcv_publico_comunidades_tradicionais: "Se escolheu Povos e comunidades tradicionais, identifique qual(is)?",
                rcv_publico_outros_descricao: "Indique se estes públicos específicos estão entre os prioritários na atuação da organização?",
                rcv_publico_envolvido: "Gostaria de fazer alguma outra observação que descreva o público envolvido nas ações culturais da organização?",
                rcv_acoes_da_organizacao: "Quantas pessoas, em média, participam das ações da organização POR MÊS?",
                rcv_meses_media_ano_org: "Quantas pessoas, em média, participam das ações da organização POR ANO?",
                rcv_meses_regulares_organização: "Em quantos meses por ano há atividades regulares da organização?",
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
            return this.entity.hasOwnProperty(prop) &&
                   this.entity[prop] !== undefined &&
                   this.entity[prop] !== null &&
                   this.entity[prop] !== '' &&
                   !(Array.isArray(this.entity[prop]) && this.entity[prop].length === 0);
        }
    },
});
