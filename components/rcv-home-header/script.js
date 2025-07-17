app.component('rcv-home-header', {
    template: $TEMPLATES['rcv-home-header'],

    setup() { 
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('rcv-home-header')
        return { text }
    },

    data() {
        return {
            cards: [{
                    title: 'Novo cadastro',
                    description: 'Quer ser certificado como Ponto ou Pontão de Cultura? Entre aqui!',
                    link: Utils.createUrl('opportunity', 'single', [$MAPAS.config.rcvHomeHeader.rcvOpportunityId]) 
                },
                {
                    title: 'Atualização de cadastro',
                    description: 'Renove os dados do Ponto ou Pontão e esteja em dia para novas oportunidades!',
                    link: '/site/atualizacao-cadastral' 
                },
                {
                    title: 'Registro do gestor',
                    description: 'Lorem ipsum dolor sit amet consectetur. Semper urna gravida et donec libero at.',
                    link: '/registro-gestor' 
                }
            ]
        }
    }
});
