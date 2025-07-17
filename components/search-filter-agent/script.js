app.component('search-filter-agent', {
    template: $TEMPLATES['search-filter-agent'],

    setup() { 
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('search-filter-agent')
        return { text }
    },

    props: {
        count: {
            type: Number,
            default: null,
        },
        position: {
            type: String,
            default: 'list'
        },
        pseudoQuery: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            terms: $TAXONOMIES.area.terms,
        }
    },

    methods: {
        clearFilters() {
            const types = ['string', 'boolean'];
            this.pseudoQuery['@verified'] = true;

            for (const key in this.pseudoQuery) {
                if (Array.isArray(this.pseudoQuery[key])) {
                    this.pseudoQuery[key] = [];
                } else if (types.includes(typeof this.pseudoQuery[key])) {
                    if(key != '@verified') {
                        delete this.pseudoQuery[key];
                    }
                }
            }
        }
    },
});
