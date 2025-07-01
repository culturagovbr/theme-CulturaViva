<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Comissões')],
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
                <img src="<?php $this->asset('img/rcv-static-pages/comissao.png') ?>" />
            </div>
        </div>
        <h2 class="bold"> <?= i::__('Comissões') ?> </h2>
    </div>

    <div class="static-page__content">

        <p>
            <?= i::__('A Política Nacional Cultura Viva (PNCV) conta com três comissões para realizar a gestão compartilhada junto à sociedade civil e os entes federados. Conheça cada uma delas, seus objetivos e funções.') ?>
        </p>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Comissão Nacional de Pontos e Pontões de Cultura') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('A Comissão Nacional de Pontos de Cultura (CNPdC) é um colegiado autônomo, de caráter representativo de Pontos e Pontões de Cultura, instituído por 
                    iniciativa destes, e integrada por representantes eleitos em Fórum Nacional de Pontos de Cultura.') ?>
                    </p>

                    <p>
                        <?= i::__('Possui representantes regionais, estaduais e temáticos, que são inicialmente indicados em Encontros Regionais ou Fóruns Estaduais de Pontos de Cultura 
                    e, posteriormente, eleitos no Fórum Nacional de Pontos de Cultura, com a participação da sociedade civil, agentes culturais, produtores, fazedores de cultura, 
                    lideranças e produções dos Pontos de Cultura e Poder Público das esferas federal, estadual e municipal.') ?>
                    </p>

                    <p>
                        <?= i::__('Seu trabalho abarca a articulação e o engajamento de Pontos e Pontões de Cultura, visando a atuação em rede pautada na Política Nacional de Cultura Viva, 
                    por meio de troca de experiências, capacitações, representações, bem como, da participação na gestão compartilhada da política pública junto à União, aos estados, 
                    ao DF e aos Municípios e à sociedade civil, maior beneficiária das ações culturais de base comunitária.') ?>
                    </p>

                    <p>
                        <?= i::__('O Fórum Nacional, chamado de TEIA Nacional, é o encontro desses atores envolvidos na implementação da PNCV nos territórios, para a realização de ações de 
                    integração, reflexão, capacitação e difusão, com o objetivo comum de se reunirem para o fortalecimento da Rede Cultura Viva.') ?>
                    </p>

                    <p>
                        <?= i::__('Historicamente, houve a realização de cinco TEIAs Nacionais, com a consolidação da CNPdC e a mobilização dos Pontos de Cultura em âmbito nacional e internacional') ?>
                    </p>

                    <ul>
                        <li>
                            <?= i::__('TEIA Nacional 2006 – Venha ver e ser visto;') ?>
                        </li>
                        <li>
                            <?= i::__('TEIA Nacional 2007 – Tudo de Todos;') ?>
                        </li>
                        <li>
                            <?= i::__('TEIA Nacional 2008 – Iguais na Diferença;') ?>
                        </li>
                        <li>
                            <?= i::__('TEIA Nacional 2010 – Tambores Digitais;') ?>
                        </li>
                        <li>
                            <?= i::__('TEIA Nacional 2014 – Diversidade.') ?>
                        </li>
                    </ul>

                    <p>
                        <?= i::__('Acesse') ?> <a href="https://www.gov.br/culturaviva/pt-br/rede-cultura-viva/comissao-nacional-de-pontos-de-cultura-cnpdc/RegimentoInternodaCNPdC.pdf"><?= i::__("AQUI") ?></a> <?= i::__('o Regimento Interno da CNPdC.') ?>
                    </p>

                    <p>
                        <?= i::__('Entre em contato com a CNPdC por meio do endereço eletrônico:') ?>
                        <a href="mailto:cnpdc@iteia.org.br">cnpdc@iteia.org.br</a>
                    </p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Comissão de Certificação') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('Ser um Agente Certificador significa colaborar para a implementação da Política Nacional de Cultura Viva-PNCV, a partir da gestão pública compartilhada e participativa, com o objetivo de:') ?>
                    </p>

                    <ul>
                        <li>
                            <?= i::__('Valorizar a cultura de base comunitária;') ?>
                        </li>

                        <li>
                            <?= i::__('Reconhecer e estimular o protagonismo social e as iniciativas culturais já existentes;') ?>
                        </li>

                        <li>
                            <?= i::__('Cooperar para que seja assegurado o respeito à cultura como direito de cidadania e à diversidade cultural como expressão simbólica e atividade econômica.') ?>
                        </li>
                    </ul>

                    <p>
                        <?= i::__('Os cadastros são distribuídos aos Agentes Certificadores de forma aleatória para avaliação, em atendimento aos princípios da isonomia e da imparcialidade.') ?>
                    </p>

                    <p>
                        <?= i::__('Cada Cadastro será avaliado por 2 Agentes Certificadores e, após as análises, a Plataforma Rede Cultura Viva encaminhará um e-mail ao usuário (entidade/coletivo cultural) 
                        acerca da aprovação ou do indeferimento do Cadastro.') ?>
                    </p>

                    <p>
                        <?= i::__('Importante a leitura do Manual do Agente Certificador com o passo-a-passo do processo de análise e certificação simplificada de Pontos e Pontões de Cultura!') ?>
                    </p>

                    <p>
                        <?= i::__('Quaisquer dúvidas, sugestões e/ou reclamações:') ?>
                        <a href="mailto:culturaviva@cultura.gov.br">culturaviva@cultura.gov.br</a>
                    </p>

                    <p>
                        <?= i::__('Secretaria de Cidadania e Diversidade Cultural') ?>
                    </p>

                    <p>
                        <?= i::__('Ministério da Cultura') ?>
                    </p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Comissão de Gestão do Cadastro') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('Falta conteúdo') ?>
                    </p>
                </div>
            </template>
        </mc-accordion>
    </div>
</div>