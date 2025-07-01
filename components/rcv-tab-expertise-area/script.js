app.component('rcv-tab-expertise-area', {
    template: $TEMPLATES['rcv-tab-expertise-area'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        return {
            fields: {
                terms: {
                    acao_estruturante: "A organização atua em qual ação estruturante da Política Nacional Cultura Viva, conforme a Lei 13.018/2014 (Art. 5º), prioritariamente?",
                    acao_estruturante_outra: "Atua em outra ação que considera estruturante?",
                    rcv_principais_segmentos: "Quais são os 3 principais segmentos de atuação da organização no campo artístico-cultural?",
                    rcv_atuacao_demais_segmentos: "Caso necessário, indique os demais segmentos de atuação da organização no campo artístico-cultural?",
                },
                data: {
                    rcv_atuacao_ods: "A organização contribui para os Objetivos de Desenvolvimento Sustentável (ODS)?"
                }
            }
        }
    },

    computed: {
        showTab() {
            for (const type in this.fields) {
                for (const data in this.fields[type]) {
                    if (this.isValid(type, data)) {
                        return true;
                    }
                }
            }
            return false;
        }
    },
    
    methods: {
        isValid(type, prop) {
            if (type == 'terms') {                                
                return this.entity.terms[prop] !== null && 
                       this.entity.terms[prop] !== '' && 
                       !(Array.isArray(this.entity.terms[prop]) && !this.entity.terms[prop].length);
            } 

            if (type == 'data') {
                return this.entity[prop] !== null && 
                       this.entity[prop] !== '' && 
                       !(Array.isArray(this.entity[prop]) && !this.entity[prop].length);
            }
        }
    },
});
