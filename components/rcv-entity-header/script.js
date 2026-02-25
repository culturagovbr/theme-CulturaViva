app.component('rcv-entity-header', {
    template: $TEMPLATES['rcv-entity-header'],
    setup() { 
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-entity-header')
        return { text }
    },
    data() {
        return {
            titleEdit: '',
        }
    },
    props: {
        editable: {
            type: Boolean,
            default: false
        },
        entity: {
            type: Entity,
            required: true
        },

        showField: {
            type: Boolean,
            required: false,
        }
    },
    created() {
        switch(this.entity.__objectType) {
            case 'agent': 
                this.titleEdit = (this.entity.type?.id == 1) ?  this.text('title agent-1') : this.text('title agent-2');
                break;
            case 'project':
                this.titleEdit = this.text('title project');
                break;
            case 'space':
                this.titleEdit = this.text('title space');
                break;
            case 'opportunity':
                this.titleEdit = this.text('title opportunity');
                break;
            case 'event':
                this.titleEdit = this.text('title event');
                break;
            case 'seal':
                this.titleEdit = this.text('title seal');
                break;
        }
    },
    computed: {
        sealCertifierIdPonto() {
            return $MAPAS.config.rcvEntityHeader.sealCertifierIdPonto
        },

        sealCertifierIdPontao() {
            return $MAPAS.config.rcvEntityHeader.sealCertifierIdPontao
        },

        filterSeals() {
            const seals = this.entity?.seals ?? [];
            const verifiedSeals = [this.sealCertifierIdPontao, this.sealCertifierIdPonto];
            
            return seals.filter(seal => verifiedSeals.includes(seal.sealId));
        }
    },
    methods: {
        url (source) {
            return `url(${source})`
        },
        buildSocialMediaLink(socialMedia){
            return Utils.buildSocialMediaLink(this.entity, socialMedia);
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
})
