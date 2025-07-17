app.component('rcv-mc-share-links', {
    template: $TEMPLATES['rcv-mc-share-links'],

    data() {
        return {
            showOptions: false, // Controla a exibição do modal
        };
    },

    props: {
        title: {
            type: String,
            default: 'Compartilhar'
        },
        text: {
            type: String,
            default: 'Confira este conteúdo incrível!'
        },
        classes: {
            type: [String, Array, Object],
            required: false
        },
    },

    methods: {
        toggleShareOptions() {
            this.showOptions = !this.showOptions;
        },

        share(platform) {
            const url = encodeURIComponent(document.URL);
            const text = encodeURIComponent(this.text);

            switch (platform) {
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`);
                    break;
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`);
                    break;
                case 'whatsapp':
                    window.open(`https://api.whatsapp.com/send?text=${text}%20${url}`);
                    break;
                case 'telegram':
                    window.open(`https://t.me/share/url?url=${url}&text=${text}`);
                    break;
                default:
                    console.error('Plataforma não suportada:', platform);
            }

            this.showOptions = false; // Fecha o modal após o clique
        },
    },
});
