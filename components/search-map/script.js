app.component('search-map', {
    template: $TEMPLATES['search-map'],

    // define os eventos que este componente emite
    emits: ['ready', 'openPopup', 'closePopup'],
    
    async mounted(){
        this.api = new API(this.type);
        this.fetchEntities();
    },

    watch: {
        pseudoQuery: {
            handler(pseudoQuery){
                this.entities = [];
                this.loading = true;
                clearTimeout(this.refreshTimeout);

                this.refreshTimeout = setTimeout(() => {
                    this.fetchEntities();
                }, 500)
            },
            deep: true,
        }
    },
    
    data() {
        return {
            loading: false,
            entities: [],
            abortController: null,
        }
    },
    
    props: {
        type: {
            type: String,
            required: true,
        },
        pseudoQuery: {
            type: Object,
            default: {}
        },
        endpoint: {
            type: String,
            default: 'find'
        },
        entityRawProcessor: {
            type: Function,
            default: (entity) => Utils.entityRawProcessor(entity) 
        }
    },

    methods: {
        async fetchEntities() {
            const query = Utils.parsePseudoQuery(this.pseudoQuery);
            query['@select'] = 'id,type,name,location,singleUrl';
            query['location'] = query['location'] || '!EQ([0,0])';
            
            // cancela a busca anterior para uma resposta atrasada não sobrescrever a mais recente
            this.abortController?.abort();
            this.abortController = new AbortController();
            const signal = this.abortController.signal;

            this.entities = [];
            this.loading = true;

            try {
                this.entities = await this.api.fetch(this.endpoint, query, {
                    raw: true,
                    rawProcessor: this.entityRawProcessor,
                    signal
                });
            } catch (error) {
                // a busca cancelada não mexe no estado, que já pertence à busca seguinte
                if (signal.aborted) {
                    return;
                }

                throw error;
            }

            this.loading = false;
        },
        openPopUp(event) {
            this.$emit('openPopup', event);
            window.dispatchEvent(new CustomEvent('mc-pin-click', {detail:event}));
            
        },
        closePopUp(event) {
            this.$emit('closePopup', event);
        },
    },
});
