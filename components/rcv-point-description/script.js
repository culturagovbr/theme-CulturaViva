app.component('rcv-point-description', {
    template: $TEMPLATES['rcv-point-description'],

    setup() { 
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-point-description')
        return { text }
    },

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    data() {
        return {
            longDescription: true,
        }
    },

    methods: {
        showLongDescription() {
            return this.longDescription = !this.longDescription;
        },

        formatDate(dateString) {        
            try {
                const formattedDate = new Date(dateString);
        
                if (isNaN(formattedDate.getTime())) {
                    return 'Data inválida';
                }
        
                const result = formattedDate.toLocaleString('pt-BR', { 
                    dateStyle: 'short', 
                    timeStyle: 'short' 
                });
                return result;
        
            } catch (error) {
                return 'Erro ao formatar data';
            }
        }  
    },
    computed: {
        readMoreText() {
            return this.longDescription ? 'ler menos' : 'ler mais';
        },
    },


});
