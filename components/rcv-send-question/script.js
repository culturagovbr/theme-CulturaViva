/**
 * Vue Lifecycle
 * 1. setup
 * 2. beforeCreate
 * 3. created
 * 4. beforeMount
 * 5. mounted
 * 
 * // sempre que há modificação nos dados
 *  - beforeUpdate
 *  - updated
 * 
 * 6. beforeUnmount
 * 7. unmounted                  
 */

app.component('rcv-send-question', {
    template: $TEMPLATES['rcv-send-question'],
    
    // define os eventos que este componente emite
    emits: ['namesDefined'],

    props: {
        buttonLabel: {
            type: String,
            default: 'Abrir Modal'
        },
        title: {
            type: String,
            default: 'Título do Modal'
        }

    },
    
    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-send-question')
        return { text, hasSlot }
    },

    beforeCreate() { },
    created() { },

    beforeMount() { },
    mounted() { },

    beforeUpdate() { },
    updated() { },

    beforeUnmount() {},
    unmounted() {},

    data() {
        return {
            isOpen: false, // Estado do modal
            name: '',
            email: '',
            question: '',
        };
    },

    computed: {
        stepTitle() {
            return 'Enviar mensagem';
        },
        isFormValid() {
            return this.name.trim() !== '' &&
                   this.email.trim() !== '' &&
                   this.validateEmail(this.email) &&
                   this.question.trim() !== '';
        }
    },
    
    
    methods: {
        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        sendMessage(modal) {
            let url = Utils.createUrl('site/enviar-duvida', '');
            let api = new API();
            let data = { 
                name: this.name,
                email: this.email,
                question: this.question,
            };
            
            api.POST(url, data).then(res => res.json()).then(data => {
                modal.close();
            })
        },

        closeModal () {
            this.name = '';
            this.email = '';
            this.question = '';
        },
    },
});
