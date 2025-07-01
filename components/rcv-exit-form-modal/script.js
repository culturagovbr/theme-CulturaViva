app.component('rcv-exit-form-modal', {
    template: $TEMPLATES['rcv-exit-form-modal'],
    
    // define os eventos que este componente emite
    emits: ['exit'],
    
    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-exit-form-modal')
        return { text, hasSlot }
    },

    data() {
        return {
            isOpen: false, // Estado do modal
            registrationInfo: null,
        };
    },
    
    methods: {
        exit(modal) {
            this.$emit('exit');
            modal.close();
        },
    },
});
