<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Rede de pontos e pontões')],
];

$this->import('
    mc-accordion
    mc-breadcrumb
')
?>

<div class="static-page">
    <mc-breadcrumb></mc-breadcrumb>

    <div class="static-page__title">
        <div class="static-page__background">
            <div class="img">
                <img src="<?php $this->asset('img/rcv-static-pages/rede-de-pontos.png') ?>" />
            </div>
        </div>
        <h2 class="bold"> <?= i::__('Redes de pontos e pontões') ?> </h2>
    </div>

    <div class="static-page__content">

        <h3 class="bold"> <?= i::__('Pontões de Cultura Selecionados no Edital nº 09/2023') ?> </h2>

            <p>
                <?= i::__('O Ministério da Cultura formalizou parceria com 42 pontões de cultura para articular, mapear e capacitar as redes territoriais e temáticas de pontos de cultura no país. 
            As entidades foram selecionadas por meio do Edital nº 09/2023, publicado em agosto de 2023.') ?>
            </p>

            <h3 class="bold">
                <?= i::__('Territoriais e Temáticos') ?>
            </h3>

            <p>
                <?= i::__('Dos 42 pontões fomentados, são 27 territoriais, que atuam em 22 estados e no Distrito Federal. São Paulo, Rio de Janeiro, Minas Gerais e Bahia contam com dois pontões. 
            Somente Alagoas, Mato Grosso, Amazonas e Paraná não tiveram entidades selecionadas no eixo pontão estadual.') ?>
            </p>

            <p>
                <?= i::__('Em relação ao eixo temático, setorial e identitário, 15 pontões desenvolvem projetos nas seguintes áreas:') ?>
            </p>

            <ul>
                <li>
                    <?= i::__('Culturas Indígenas e Mãe Terra') ?>
                </li>
                <li>
                    <?= i::__('Povos e Comunidades Tradicionais de Matriz Africana ( 2 pontões selecionados)') ?>
                </li>
                <li>
                    <?= i::__('Culturas Populares e Tradicionais') ?>
                </li>
                <li>
                    <?= i::__('Cultura Digital, Comunicação e Mídia Livre (2 pontões selecionados') ?>
                </li>
                <li>
                    <?= i::__('Patrimônio e Memória') ?>
                </li>
                <li>
                    <?= i::__('Livro, Leitura e Literatura') ?>
                </li>
                <li>
                    <?= i::__('Gênero, Diversidade e Direitos Humanos') ?>
                </li>
                <li>
                    <?= i::__('Acessibilidade Cultural e Equidade') ?>
                </li>
                <li>
                    <?= i::__('Cultura Infância') ?>
                </li>
                <li>
                    <?= i::__('Formação e Educação Cultural') ?>
                </li>
                <li>
                    <?= i::__('Territórios Rurais e Cultura Alimentar') ?>
                </li>
                <li>
                    <?= i::__('Cultura Urbana, Direito à Cidade e Juventudes') ?>
                </li>
                <li>
                    <?= i::__('Cultura, Territórios de Fronteira e Integração Latinoamericana') ?>
                </li>
            </ul>

            <h3 class="bold">
                <?= i::__('Investimento') ?>
            </h3>

            <p>
                <?= i::__('Com o investimento previsto de R$ 28 milhões, a ação faz parte da estratégia de reativação e de fortalecimento da Política Nacional Cultura Viva (PNCV), 
            iniciada com a recriação do ministério. As iniciativas selecionadas receberam um repasse de R$ 400 mil a R$ 800 mil para a execução de um projeto cultural pelo período de 12 meses.') ?>
            </p>

            <h3 class="bold">
                <?= i::__('Comitê Gestor') ?>
            </h3>

            <p>
                <?= i::__('Cada Pontão tem um Comitê Gestor, composto por, no mínimo, cinco Pontos de Cultura para a realização das ações do projeto de forma compartilhada.') ?>
            </p>

            <h3 class="bold">
                <?= i::__('Agentes Cultura Viva') ?>
            </h3>

            <p>
                <?= i::__('Os pontões fomentados pelo Ministério da Cultura contam com a participação de Agentes Cultura Viva. São jovens com idades entre 18 e 24 anos, 
            selecionados para apoio e assistência técnica no projeto desenvolvido pelas entidades.') ?>
            </p>

            <p>
                <?= i::__('Os agentes recebem uma bolsa mensal no valor de R$ 900,00, pelo período mínimo de oito meses. Deverão atender uma carga horária de 20 horas semanais, 
            sendo o máximo de seis horas diárias.') ?>
            </p>

            <p>
                <?= i::__('Em relação à quantidade de agentes, o Edital definiu o mínimo de 10 jovens para cada pontão territorial e 20 para os temáticos.') ?>
            </p>

            <h3 class="bold">
                <?= i::__('Metas') ?>
            </h3>

            <p>
                <?= i::__('Os pontões atuam de acordo com as seguintes metas:') ?>
            </p>

            <ul>
                <li>
                    <?= i::__('Mapeamento e Diagnóstico') ?>
                </li>
                <li>
                    <?= i::__('Formação e Capacitação') ?>
                </li>
                <li>
                    <?= i::__('Articulação e Mobilização da Rede') ?>
                </li>
                <li>
                    <?= i::__('Agentes Cultura Viva') ?>
                </li>
            </ul>
    </div>
</div>