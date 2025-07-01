app.component('rcv-rf-data', {
    template: $TEMPLATES['rcv-rf-data'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        let rfData = this.entity.rcv_rfData;

        return {
            rfData
        }
    },

    methods: {
        formatDate(value) {
            if(value) {
                let date = new McDate(value);
                return `${date.date('numeric year')}`;
            }
        },

        formatCNPJ(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
        
            if (cnpj.length === 14) {
                return cnpj.replace(
                    /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
                    '$1.$2.$3/$4-$5'
                );
            }
        },

        getTaxRegime(regime) {
            if(regime == 'Sim') {
                return 'Simples Nacional';
            }
             
            return 'Lucro presumido / Real';
        },

        getBusinessSize(value) {
            if(value == 'Sim') {
                return 'MEI';
            } 
              
            return 'ME';
        }
    },
});
