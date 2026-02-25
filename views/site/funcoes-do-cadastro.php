<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Funções do cadastro')],
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
                <img src="<?php $this->asset('img/rcv-static-pages/certificado.png') ?>" />
            </div>
        </div>
        <h2 class="bold"> <?= i::__('Funções do cadastro') ?> </h2>
    </div>

    <div class="static-page__content">
        <h3 class="bold">
            <?= i::__('Funções do Cadastro Nacional') ?>
        </h3>
        <p>
            <?= i::__('A Cultura Viva é uma política cultural voltada para a valorização da cultura de base comunitária, o reconhecimento e apoio às atividades e processos 
            culturais já desenvolvidos, estimulando a participação social, a colaboração e a gestão compartilhada de políticas públicas no campo da cultura.') ?>
        </p>

        <p>
            <?= i::__('O Cadastro Nacional de Pontos e Pontões de Cultura, instituído pela Lei Cultura Viva nº 13.018/2014, regulamentada pela Instrução Normativa 
            MinC nº 08/2016 e pela Instrução Normativa MinC nº 12/2024, é a ferramenta que abarca as informações de entidades e coletivos culturais para o reconhecimento 
            e mapeamento por parte do Estado, estabelecendo uma relação direta de interação, articulação e comunicação entre os Pontos e Pontões de Cultura, o Ministério 
            da Cultura, os entes federados parceiros, os produtores e fazedores e fazedoras da cultura brasileira e os cidadãos beneficiários da política pública no Brasil e no exterior.') ?>
        </p>

        <p>
            <?= i::__('Para quem já é Ponto ou Pontão de Cultura, o Cadastro é uma forma de manter os dados atualizados, enviar informações sobre atividades desenvolvidas, além de possibilitar a articulação e integração em rede.') ?>
        </p>

        <p>
            <?= i::__('Para quem quer ser Ponto ou Pontão de Cultura, é só chegar! Faça o cadastro da sua entidade ou do seu coletivo cultural, insira as informações sobre 
            ações culturais que desenvolve nas comunidades e em rede, fotos e vídeos de divulgação e, a partir de uma seleção simplificada e da adesão à Política Nacional de 
            Cultura Viva, a entidade ou o coletivo cultural poderá ser reconhecido como Ponto ou Pontão de Cultura, passando a integrar a Rede Cultura Viva.') ?>
        </p>

        <br>

        <h4 class="bold">
            <?= i::__('Curtiu? Então cadastre-se para ser mais um Ponto/Pontão de Cultura nesta rede da diversidade cultural brasileira!') ?>
        </h4>

        <p>
            <a href="https://www.gov.br/culturaviva/pt-br/biblioteca-cultura-viva/normativos/portaria-scdc-minc-no-05-de-11-06-2024-comissao-de-certificacao-cadastro-nacional.pdf"><?= i::__('Acesse AQUI'); ?></a> <?= i::__('a Portaria SCDC/MinC nº 05, de 11/06/2024, que recria a Comissão de Certificação Cadastro Nacional de Pontos e Pontões de Cultura, para avaliação das inscrições e emissão da Certificação Simplificada.') ?>
        </p>

        <p>
            <a href="https://www.gov.br/culturaviva/pt-br/biblioteca-cultura-viva/documentos-e-publicacoes/manuais-2/manual-do-agente-certificador-maio2024.pdf"><?= i::__('Acesse AQUI'); ?></a> <?= i::__('o Manual do Agente Certificador.') ?> 
        </p>

        <p>
            <a href="https://www.gov.br/culturaviva/pt-br/acesso-a-informacao/perguntas-frequentes"><?= i::__('SAIBA MAIS'); ?></a> <?= i::__('sobre a Política Nacional de Cultura Viva.:') ?> 
        </p>
    </div>
</div>