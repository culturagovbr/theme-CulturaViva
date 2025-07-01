app.component('rcv-tab-history', {
    template: $TEMPLATES['rcv-tab-history'],
    
    props: {
        entity: {
            type: Entity,
            required: true
        },
    },

    data() {
        return {
            fields: {
                comunidadesTradicional: "Povo / Comunidade Tradicional",
                comunidadesTradicionalOutros: "Outro",
                rcv_etnia: "Etnia",
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
