app.component('rcv-registration-trash', {
    template: $TEMPLATES['rcv-registration-trash'],

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente
        const text = Utils.getTexts('rcv-registration-trash');
        const messages = useMessages();

        // substitui cada `%s` do texto pelo próximo argumento
        const formatar = (chave, ...valores) => valores.reduce((s, v) => s.replace('%s', v), text(chave));

        return { text, formatar, messages };
    },

    data() {
        return {
            situacoes: ['liberada', 'aviso', 'bloqueada'],
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

        contagem() {
            return this.situacoes.reduce((total, situacao) => {
                total[situacao] = (this.analise?.itens || []).filter(item => item.situacao === situacao).length;
                return total;
            }, {});
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

        mudarAba(aba) {
            this.resultado = null;

            if (aba === 'lixeira') {
                this.carregarLista();
            }
        },

        analisar() {
            return this.executar(async () => {
                this.resultado = null;
                this.analise = await this.post('rcv-lixeira-analisar', { numeros: this.numeros });
            });
        },

        enviar(modal) {
            modal.close();

            return this.executar(async () => {
                const numeros = this.enviaveis.map(item => item.numero).join(';');
                const { itens } = await this.post('rcv-lixeira-enviar', { numeros, motivo: this.motivo });

                this.resultado = itens;
                this.analise = null;
                this.numeros = '';
                this.motivo = '';
                this.messages.success(this.formatar('enviadas', itens.filter(item => item.resultado === 'enviada').length));
            });
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

        restaurar(modal) {
            modal.close();

            return this.executar(async () => {
                const { itens } = await this.post('rcv-lixeira-restaurar', { numeros: this.selecionados.join(';') });

                this.resultado = itens;
                this.messages.success(this.formatar('restauradas', itens.filter(item => item.resultado === 'restaurada').length));
                this.lista = (await this.post('rcv-lixeira-listar')).itens;
                this.selecionados = [];
            });
        },

        // tom do mc-status para a situação ou o resultado
        tom(situacao) {
            return {
                liberada: 'success',
                enviada: 'success',
                restaurada: 'success',
                aviso: 'warning',
                bloqueada: 'error',
                ignorada: 'error',
            }[situacao] || 'draft';
        },

        motivos(item) {
            return (item.motivos || []).map(motivo => this.text(motivo)).join(' ');
        },

        avaliacoes(fase) {
            const nomes = { 0: 'avaliacoesIniciadas', 1: 'avaliacoesConcluidas', 2: 'avaliacoesEnviadas' };
            const partes = Object.entries(fase.avaliacoes || {}).map(([status, total]) => this.formatar(nomes[status] || 'avaliacoes', total));

            return partes.join(', ') || this.text('semAvaliacoes');
        },

        quando(data) {
            return data ? new Date(data.replace(' ', 'T')).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '';
        },

        urlInscricao(id) {
            return Utils.createUrl('registration', 'view', [id]);
        },
    },
});
