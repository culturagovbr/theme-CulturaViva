app.component('rcv-save-later-modal', {
    template: $TEMPLATES['rcv-save-later-modal'],
    
    // define os eventos que este componente emite
    emits: ['save'],
    
    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-save-later-modal')
        return { text, hasSlot }
    },

    data() {
        return {
            isOpen: false, // Estado do modal
            registrationInfo: null,
        };
    },
    
    methods: {
        save(modal) {
            this.$emit('save');
            modal.close();
        }
    },
});
