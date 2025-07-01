<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Política Nacional de Cultura Viva')],
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
                <img src="<?php $this->asset('img/rcv-static-pages/politica-nacional.png') ?>" />
            </div>
        </div>
        <h2 class="bold"> <?= i::__('Política Nacional de Cultura Viva') ?> </h2>
    </div>

    <div class="static-page__content">
        <h3 class="bold"> <?= i::__('O que é a Política Nacional de Cultura Viva?') ?> </h3>

        <p>
            <?= i::__('A Política Nacional de Cultura Viva - PNCV é uma política pública de Estado gerida de forma compartilhada pela SCDC/MinC em parcerias intergovernamentais e 
            com governos estaduais, distrital, municipais, grupos e instituições culturais, gestores e produtores culturais e sociedade civil, para articular, capacitar e fomentar 
            ações realizadas por entidades, coletivos e agentes culturais em suas comunidades, bem como apoiar, valorizar, reconhecer, dimensionar e divulgar as culturas e os fazeres 
            culturais em seus diferentes territórios.') ?>
        </p>

        <p>
            <?= i::__('Surgiu com o antigo Programa Cultura Viva (2004) e sua origem remonta à Constituição Federal de 1988, que estabelece o dever do Estado (Poder Público de todas as esferas) 
            em garantir ao cidadão o pleno exercício dos direitos culturais e o acesso às fontes da cultura nacional, além do fomento, valorização e incentivo à produção, difusão e circulação 
            de conhecimento, a universalização do acesso aos bens e serviços culturais e a cooperação entre os entes federados, os agentes públicos e privados atuantes na área cultural, 
            entre outros (CF/88, art. 216-A, § 1º, incisos I a IV).') ?>
        </p>

        <p>
            <?= i::__('Assim, por meio da Lei nº 13.018, de 22/07/2014, que institui a PNCV, a Cultura Viva caracteriza-se como a primeira política de base comunitária do Sistema Nacional 
            da Cultura (SNC) e alcança grande dimensão nacional e internacional, sendo ainda a base para a criação do Programa IberCultura Viva em 2014 - sendo, atualmente, uma referência 
            para políticas culturais de base comunitária em vários países da América Latina.') ?>
        </p>

        <p>
            <?= i::__('Desenhada para valorizar a cultura de base comunitária, a articulação em rede e a gestão compartilhada, com base nos princípios da autonomia, protagonismo e 
            empoderamento da sociedade civil, a PNCV contempla iniciativas ligadas à economia solidária, produção cultural urbana e periférica, cultura digital, cultura popular, 
            às comunidades indígenas, quilombolas, de matriz africana, aos segmentos da infância e juventude, abrangendo todos os tipos de linguagem artística e cultural como: 
            artesanato, música, artes cênicas, artes visuais, cinema, circo, literatura, entre outras.') ?>
        </p>

        <p>
            <?= i::__('Tem o objetivo de promover a articulação dessas iniciativas em rede e contribuir para a inclusão social, o combate ao preconceito e a todas as formas de discriminação 
            e intolerância, o reconhecimento e a valorização da diversidade cultural brasileira e o pleno exercício dos direitos culturais. Além de garantir a continuidade das ações em âmbito 
            nacional e internacional, a Lei Cultura Viva cria outras formas de apoio financeiro a iniciativas culturais, simplifica e desburocratiza os processos de prestação de contas e o 
            repasse de recursos para as organizações da sociedade civil.') ?>
        </p>

        <p>
            <?= i::__('Atualmente está presente nos 27 estados brasileiros, em cerca de mil municípios e no exterior, sendo uma política pública de grande capilaridade e visibilidade do 
            Ministério da Cultura, atualmente com mais de 4.300 Pontos e Pontões georreferenciados no Mapa da Rede Cultura Viva.') ?>
        </p>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Como fazer parte da Rede Cultura Viva?') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('A Rede Cultura Viva é composta por todos os parceiros e atores da Política Nacional de Cultura Viva (PNCV): União, Estados, DF, Municípios, Entidades Culturais, 
                        Coletivos Culturais, Agentes / Gestores / Fazedores de Cultura e Sociedade Civil.') ?>
                    </p>
                    <ul>
                        <li>
                            <?= i::__('Estados, DF e Municípios:  celebram parceria com a União, com recurso (Convênio: por meio de Programa específico na Plataforma+Brasil ou emendas parlamentares / orçamento 
                            impositivo) ou sem recurso (Acordo de Cooperação Federativa – ACF: por meio de demanda espontânea) e formam as Redes Estaduais, Distrital e Municipais de Pontos de Cultura, visando 
                            ampliar o acesso da população brasileira às condições de exercício dos direitos culturais, por meio de seleção, apoio financeiro, capacitação e articulação de entidades e coletivos 
                            culturais (Pontos e Pontões de Cultura), bem como registro e divulgação de suas atividades nas comunidades e territórios.') ?>
                        </li>
                        <li>
                            <?= i::__('Órgãos e Entidades da Administração Pública Federal: integrantes dos Orçamentos Fiscal e da Seguridade Social da União, celebram parceria (Termo de Execução Descentralizada)
                            para mapeamento, pesquisa, capacitação, entre outras ações de parceria na gestão e implantação da PNCV nos territórios.') ?>
                        </li>
                        <li>
                            <?= i::__('Entidades e Coletivos Culturais (Pontos/Pontões de Cultura): (1) recebem a Certificação Simplificada como Ponto/Pontão de Cultura pelo Ministério da Cultura para fazerem 
                            parte do Cadastro Nacional de Pontos e Pontões de Cultura – em que há o reconhecimento, georreferenciamento e divulgação das atividades culturais desenvolvidas em suas comunidades e 
                            em rede, sem envolvimento de repasse de recursos; (2) participam de seleções públicas para apoio financeiro (Termo de Compromisso Cultural e Prêmio) em prol das diretrizes e objetivos 
                            da PNCV, recebendo também  a Certificação Simplificada como Ponto/Pontão de Cultura para fazerem parte do Cadastro Nacional de Pontos e Pontões de Cultura.') ?>
                        </li>
                        <li>
                            <?= i::__('Pessoas Físicas (fazedores de cultura representantes da Matriz da Diversidade Cultural e Agentes Cultura Viva): participam de seleções públicas para apoio financeiro (Prêmio e 
                            Bolsa) em prol das diretrizes e objetivos da PNCV e da valorização da Matriz da Diversidade Cultural. Não podem ser certificadas como Ponto/Pontão de Cultura.') ?>
                        </li>
                        <li>
                            <?= i::__('Sociedade Civil: maior beneficiária das ações da PNCV, atua como multiplicadora de cultura juntamente com os Pontos/Pontões de Cultura e realiza o controle social das 
                            atividades fomentadas pelos entes federados e realizadas pelos ponteiros, agentes e fazedores de cultura, visando o bom e regular uso dos recursos públicos.') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Quais são as formas de apoio financeiro da Cultura Viva?') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('Por meio de Edital de Seleção publicado diretamente pela União ou pelos Estados, DF e Municípios (com recursos da Administração Direta, do Fundo Nacional da Cultura ou de Emendas Parlamentares / Orçamento Impositivo), para:') ?>
                    </p>
                    <ul>
                        <li>
                            <?= i::__('omento a projetos culturais de Pontos e Pontões de Cultura juridicamente constituídos (com CNPJ), por meio da celebração de Termo de Compromisso Cultural (TCC), para 
                            realizarem atividades culturais nas comunidades e em rede, a partir da execução de projetos culturais com período mínimo de 12 meses e máximo de 3 anos de execução;') ?>
                        </li>
                        <li>
                            <?= i::__('premiação de projetos, iniciativas, atividades, ou ações de Pontos e Pontões de Cultura, pessoas físicas, entidades e coletivos culturais, no âmbito das ações 
                            estruturantes da PNCV, para reconhecimento e valorização de atividades culturais já realizadas nas comunidades e em rede;') ?>
                        </li>
                        <li>
                            <?= i::__('concessão de bolsas a pessoas físicas, visando o desenvolvimento de atividades culturais que colaborem para as finalidades da PNCV.') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>
    </div>
</div>