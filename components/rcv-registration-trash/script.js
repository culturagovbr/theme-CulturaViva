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
            total: 0,
            totais: {},
            pagina: 1,
            paginas: 1,
            busca: '',
            numerosBuscados: 0,
            foraDaLixeira: [],
            filtro: 'todas',
            filtros: ['todas', 'restauraveis', 'sem_backup'],
            selecionados: [],
            carregando: false,
            limite: 200,
            atrasoBusca: null,
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

        // restauráveis entre as já carregadas
        restauraveis() {
            return this.lista.filter(item => item.restauravel);
        },

        carregadasSelecionadas() {
            return this.restauraveis.length > 0 && this.restauraveis.every(item => this.selecionados.includes(item.numero));
        },

        acimaDoLimite() {
            return this.selecionados.length > this.limite;
        },

        // lote pela busca: exige busca e usa o total de restauráveis que ela encontra
        loteDisponivel() {
            return Boolean(this.busca.trim()) && (this.totais.restauraveis || 0) > 0;
        },

        loteTamanho() {
            return Math.min(this.totais.restauraveis || 0, this.limite);
        },

        filtroAtivo() {
            return Boolean(this.busca.trim()) || this.filtro !== 'todas';
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

        // primeira página com a busca e o filtro atuais; a seleção é mantida
        carregarLista() {
            return this.executar(async () => {
                const resposta = await this.consultarLista(1);
                this.lista = resposta.itens;
            });
        },

        carregarMais() {
            return this.executar(async () => {
                const resposta = await this.consultarLista(this.pagina + 1);
                this.lista = this.lista.concat(resposta.itens);
            });
        },

        async consultarLista(pagina) {
            const resposta = await this.post('rcv-lixeira-listar', { busca: this.busca, filtro: this.filtro, pagina });

            this.total = resposta.total;
            this.totais = resposta.totais;
            this.pagina = resposta.pagina;
            this.paginas = resposta.paginas;
            this.numerosBuscados = resposta.numeros_buscados;
            this.foraDaLixeira = resposta.fora_da_lixeira;

            return resposta;
        },

        buscar() {
            clearTimeout(this.atrasoBusca);
            this.atrasoBusca = setTimeout(() => this.carregarLista(), 400);
        },

        escolherFiltro(filtro) {
            this.filtro = filtro;
            this.carregarLista();
        },

        limparFiltros() {
            this.busca = '';
            this.filtro = 'todas';
            this.carregarLista();
        },

        // marca ou desmarca as restauráveis carregadas, sem mexer nas demais selecionadas
        alternarCarregadas() {
            const numeros = this.restauraveis.map(item => item.numero);

            this.selecionados = this.carregadasSelecionadas
                ? this.selecionados.filter(numero => !numeros.includes(numero))
                : [...new Set([...this.selecionados, ...numeros])];
        },

        limparSelecao() {
            this.selecionados = [];
        },

        restaurar(modal) {
            modal.close();

            return this.executar(async () => {
                const { itens } = await this.post('rcv-lixeira-restaurar', { numeros: this.selecionados.join(';') });

                this.resultado = itens;
                this.messages.success(this.formatar('restauradas', itens.filter(item => item.resultado === 'restaurada').length));
                this.selecionados = [];
                this.lista = (await this.consultarLista(1)).itens;
            });
        },

        restaurarBusca(modal) {
            modal.close();

            return this.executar(async () => {
                const { itens, restantes } = await this.post('rcv-lixeira-restaurar-busca', { busca: this.busca });

                this.resultado = itens;
                this.messages.success(this.formatar('restauradas', itens.filter(item => item.resultado === 'restaurada').length));

                if (restantes > 0) {
                    this.messages.warning(this.formatar('loteRestantes', restantes));
                }

                this.selecionados = [];
                this.lista = (await this.consultarLista(1)).itens;
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
