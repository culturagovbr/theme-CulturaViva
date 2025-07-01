app.component('rcv-tab-cultural-economy', {
    template: $TEMPLATES['rcv-tab-cultural-economy'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        return {
            fields: {
                rcv_quantidade_trabalhadores: "Quantos trabalhadores da cultura estão diretamente envolvidos nas atividades da organização?",
                rcv_deficientes_organizacao: "Há Pessoas com Deficiência entre os trabalhadores diretamente envolvidos nas atividades da organização?",
                rcv_quantidade_deficientes: "Quantas pessoas com deficiência?",
                rcv_ocupacoes_organizacao: "Quais são as principais ocupações/profissões no campo artístico e cultural dos trabalhadores envolvidos?",
                rcv_ocupacoes_organizacao_outra: "Quais são as principais ocupações/profissões no campo artístico e cultural dos trabalhadores envolvidos?",
                rcv_faixa_etaria_organizacao: "Qual a faixa etária da maioria da equipe envolvida na organização?",
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
        }
    },
});
