app.component('rcv-registration-trash', {
    template: $TEMPLATES['rcv-registration-trash'],

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente
        const text = Utils.getTexts('rcv-registration-trash');
        const messages = useMessages();

        return { text, messages };
    },

    data() {
        return {
            aba: 'enviar',
            numeros: '',
            motivo: '',
            analise: null,
            resultado: null,
            lista: [],
            selecionados: [],
            carregando: false,
        };
    },

    computed: {
        enviaveis() {
            return (this.analise?.itens || []).filter(item => item.situacao !== 'bloqueada');
        },

        motivoValido() {
            return this.motivo.trim().length >= 10;
        },

        restauraveis() {
            return this.lista.filter(item => item.restauravel);
        },

        todosSelecionados() {
            return this.restauraveis.length > 0 && this.selecionados.length === this.restauraveis.length;
        },
    },

    methods: {
        async post(rota, dados = {}) {
            const api = new API();
            const resposta = await api.POST(Utils.createUrl('site', rota), dados);
            const json = await resposta.json();

            if (json?.error) {
                throw new Error(typeof json.data === 'string' ? json.data : this.text('erro'));
            }

            return json;
        },

        async executar(acao) {
            this.carregando = true;
            try {
                await acao();
            } catch (erro) {
                this.messages.error(erro.message || this.text('erro'));
            } finally {
                this.carregando = false;
            }
        },

        analisar() {
            return this.executar(async () => {
                this.resultado = null;
                this.analise = await this.post('rcv-lixeira-analisar', { numeros: this.numeros });
            });
        },

        enviar() {
            return this.executar(async () => {
                const numeros = this.enviaveis.map(item => item.numero).join(';');
                const { itens } = await this.post('rcv-lixeira-enviar', { numeros, motivo: this.motivo });

                this.resultado = itens;
                this.analise = null;
                this.motivo = '';
                this.messages.success(this.text('enviadas').replace('%s', itens.filter(item => item.resultado === 'enviada').length));
            });
        },

        mudarAba(aba) {
            this.aba = aba;

            if (aba === 'lixeira') {
                this.carregarLista();
            }
        },

        carregarLista() {
            return this.executar(async () => {
                this.lista = (await this.post('rcv-lixeira-listar')).itens;
                this.selecionados = [];
            });
        },

        alternarTodos() {
            this.selecionados = this.todosSelecionados ? [] : this.restauraveis.map(item => item.numero);
        },

        restaurar() {
            return this.executar(async () => {
                const { itens } = await this.post('rcv-lixeira-restaurar', { numeros: this.selecionados.join(';') });

                this.resultado = itens;
                this.messages.success(this.text('restauradas').replace('%s', itens.filter(item => item.resultado === 'restaurada').length));
                this.lista = (await this.post('rcv-lixeira-listar')).itens;
                this.selecionados = [];
            });
        },

        motivos(item) {
            return (item.motivos || []).map(motivo => this.text(motivo)).join(' ');
        },

        avaliacoes(fase) {
            const nomes = { 0: 'iniciadas', 1: 'concluídas', 2: 'enviadas' };

            return Object.entries(fase.avaliacoes || {})
                .map(([status, total]) => `${nomes[status] || status}: ${total}`)
                .join(', ') || '-';
        },

        urlInscricao(id) {
            return Utils.createUrl('registration', 'view', [id]);
        },
    },
});
