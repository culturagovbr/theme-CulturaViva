app.component('rcv-certification-date', {
    template: $TEMPLATES['rcv-certification-date'],

    props: {
        // id da relação entre a organização e o selo de verificação
        relationId: {
            type: [Number, String],
            required: true,
        },
        // data atual, já formatada, só para exibir no modal
        currentDate: {
            type: String,
            default: '',
        },
        // ano mínimo aceito pelo servidor
        minYear: {
            type: [Number, String],
            default: 2004,
        },
        // data de hoje no fuso do servidor (aaaa-mm-dd), que é quem valida se a data está no futuro
        today: {
            type: String,
            default: '',
        },
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente
        const text = Utils.getTexts('rcv-certification-date');
        const messages = useMessages();

        return { text, messages };
    },

    data() {
        return {
            date: '',
            password: '',
            loading: false,
        };
    },

    computed: {
        maxDate() {
            if (this.today) {
                return this.today;
            }

            const now = new Date();
            const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
            return local.toISOString().slice(0, 10);
        },

        minDate() {
            return `${this.minYear}-01-01`;
        },

        canConfirm() {
            return Boolean(this.date) && Boolean(this.password) && !this.loading;
        },
    },

    methods: {
        open(modal) {
            this.date = '';
            this.password = '';
            modal.open();
        },

        async confirm(modal) {
            if (!this.canConfirm) {
                return;
            }

            this.loading = true;

            try {
                const api = new API();
                const response = await api.POST(Utils.createUrl('site', 'rcv-alterar-data-certificacao'), {
                    relation: this.relationId,
                    date: this.date,
                    password: this.password,
                });
                // sessão expirada ou erro do servidor chegam como texto/HTML, não como JSON
                if (response.status === 401) {
                    throw new Error(this.text('sessionExpired'));
                }

                const contentType = response.headers.get('content-type') || '';
                const json = contentType.includes('application/json') ? await response.json() : null;

                if (!json || json.error) {
                    throw new Error(typeof json?.data === 'string' ? json.data : this.text('error'));
                }

                this.messages.success(this.text('success', { date: json.date }));
                modal.close();

                // o certificado é renderizado no servidor: recarrega para mostrar a nova data,
                // com um intervalo para a mensagem de sucesso ser lida
                setTimeout(() => window.location.reload(), 1500);
            } catch (error) {
                this.messages.error(error.message || this.text('error'));
            } finally {
                this.password = '';
                this.loading = false;
            }
        },
    },
});
