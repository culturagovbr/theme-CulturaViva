<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Certificados')],
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
        <h2 class="bold"> <?= i::__('Certificados') ?> </h2>
    </div>
    
    <div class="static-page__content">
        <h3 class="bold">
            <?= i::__('Como obter certificado de Ponto ou Pontão de Cultura') ?>
        </h3>
        <p>
            <?= i::__('A Plataforma Rede Cultura Viva é o sistema responsável pelo registro, identificação, reconhecimento, divulgação, georreferenciamento, comunicação, 
            interação e articulação da Rede Cultura Viva. Hospeda o Cadastro Nacional de Pontos e Pontões de Cultura, onde as entidades e coletivos culturais podem obter a 
            Certificação Simplificada como Ponto/Pontão de Cultura, nos termos da Lei n. 13.018, de 22 de julho de 2014, que trata da Política Nacional de Cultura Viva - PNCV, 
            regulamentada pela Instrução Normativa nº 08, de 11 de maio de 2016, e Instrução Normativa nº 12, de 28 de maio de 2024, do Ministério da Cultura.')?>
        </p>

        <p>
            <?= i::__('A Certificação Simplificada como Ponto ou Pontão de Cultura é o reconhecimento de entidades e coletivos culturais como Pontos ou Pontões de Cultura pela 
            Secretaria de Cidadania e Diversidade Cultural do Ministério da Cultura, em prol das ações da Política Nacional de Cultura Viva (PNCV). A emissão da Certificação 
            ocorre virtualmente, de forma digital pela Plataforma Rede Cultura Viva e, após emitido o certificado digital, o Ponto/Pontão de Cultura estará georreferenciado 
            no Mapa da Rede Cultura Viva.') ?>
        </p>

        <h4 class="bold">
            <?= i::__('Quem pode utilizar este serviço?') ?>
        </h4>

        <ul>
            <li><?= i::__('Cidadãos') ?></li>
            <li><?= i::__('Organizações sem fins lucrativos') ?></li>
            <li><?= i::__('Coletivos Culturais') ?></li>
        </ul>

        <h4 class="bold">
            <?= i::__('Quanto tempo leva?') ?>
        </h4>

        <p><?= i::__('Entre 0 e 3 mês(es) é o tempo estimado para a prestação deste serviço.') ?></p>
        
        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Critérios para a emissão da Certificação Simplificada') ?>
                </h4>
            </template>
            <template #content>
            <div class="static-page__accordion">
                <ul>
                    <li>
                        <?= i::__('Formulário específico preenchido, contendo o histórico atualizado e a comprovação de atuação da entidade ou coletivo cultural no campo da cultura e as 
                        atividades desenvolvidas em rede, incluindo informações que demonstrem seu alinhamento à definição de Ponto ou Pontão de Cultura;') ?>
                    </li>
                    <li>
                        <?= i::__('Termos de Adesão à PNCV, documento no qual a entidade ou coletivo cultural afirmará seu compromisso com os objetivos da PNCV, com os objetivos específicos 
                        do Ponto ou Pontão de Cultura, entre outras condições em atendimento à Lei Cultura Viva nº 13.018/2014;') ?>
                    </li>
                    <li>
                        <?= i::__('Termos de Uso e Privacidade, documento no qual a entidade ou coletivo cultural autorizará à Secretaria de Cidadania e Diversidade Cultural do Ministério da 
                        Cultura e aos entes federados parceiros o uso dos materiais e informações disponibilizadas, entre outras condições vinculadas à Certificação Simplificada.') ?>
                    </li>
                </ul>
            </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Vedações para a emissão da Certificação Simplificada') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><strong><?= i::__('Não') ?></strong> <?= i::__('serão certificados como Pontos e Pontões de Cultura:') ?></p>
    
                    <ul>
                        <li>
                            <?= i::__('Órgãos e entidades públicas não qualificadas como instituições públicas de ensino;') ?>
                        </li>
                        <li>
                            <?= i::__('Instituições com fins lucrativos;') ?>
                        </li>
                        <li>
                            <?= i::__('Fundações, sociedades e associações de apoio a instituições públicas;') ?>
                        </li>
                        <li>
                            <?= i::__('Fundações e institutos criados ou mantidos por empresas ou grupos de empresas;') ?>
                        </li>
                        <li>
                            <?= i::__('Entidades paraestatais integrantes do “Sistema S” (SESC, SENAC, SESI, SENAI, SEST, SENAT, SEBRAE, SENAR e outros).') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Georreferenciamento no Mapa da Rede Cultura Viva') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('Após aprovado o cadastro, o sistema emite automaticamente a Certificação Simplificada pela Secretaria de Cidadania e Diversidade Cultural do Ministério da Cultura e 
                        o Ponto/Pontão de Cultura aparece no Mapa da Plataforma Rede Cultura Viva, georreferenciado de acordo com as informações disponíveis em seu cadastro.') ?>
                    </p>
    
                    <p>
                        <?= i::__('O Certificado Digital poderá ser acessado pelo Mapa da Plataforma Rede Cultura Viva, de acordo com o passo a passo no link:') ?>
                        <br>
                        <a href="http://www.gov.br/culturaviva/pt-br/biblioteca-cultura-viva/documentos-e-publicacoes/documentos/passo-a-passo-para-acessar-o-certificado-digital-do-ponto-pontao-de-cultura-no-mapa-da-rede-cultura-viva.pdf/view">
                            http://www.gov.br/culturaviva/pt-br/biblioteca-cultura-viva/documentos-e-publicacoes/documentos/passo-a-passo-para-acessar-o-certificado-digital-do-ponto-pontao-de-cultura-no-mapa-da-rede-cultura-viva.pdf/view
                        </a>
                    </p>
    
                    <p>
                        <?= i::__('Ressalta-se a importância de a instituição ou coletivo cultural anotar em seus registros o login e senha do cadastro no ID Cultura, para que possa realizar as 
                        atualizações anuais com maior autonomia e efetividade, evitando assim a necessidade de criar novo cadastro futuramente.') ?>
                    </p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Informações adicionais') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('Este serviço é gratuito ao cidadão e funciona em fluxo contínuo. O usuário deverá receber, conforme os princípios expressos na lei nº 13.460/17, um atendimento pautado nas seguintes diretrizes:') ?>
                    </p>

                    <ul>
                        <li>
                            <?= i::__('Urbanidade;') ?>
                        </li>
                        <li>
                            <?= i::__('Respeito;') ?>
                        </li>
                        <li>
                            <?= i::__('Acessibilidade;') ?>
                        </li>
                        <li>
                            <?= i::__('Cortesia;') ?>
                        </li>
                        <li>
                            <?= i::__('Presunção da boa-fé do usuário;') ?>
                        </li>
                        <li>
                            <?= i::__('Igualdade;') ?>
                        </li>
                        <li>
                            <?= i::__('Eficiência;') ?>
                        </li>
                        <li>
                            <?= i::__('Segurança;') ?>
                        </li>
                        <li>
                            <?= i::__('Ética.') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Informações sobre as condições de acessibilidade, sinalização, limpeza e conforto dos locais de atendimento') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('O usuário do serviço público, conforme estabelecido pela lei nº13.460/17, tem direito a atendimento presencial, quando necessário, em instalações salubres, seguras, sinalizadas, acessíveis e adequadas ao serviço e ao atendimento.') ?>
                    </p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Informação sobre quem tem direito a tratamento prioritário') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('Tem direito a atendimento prioritário às pessoas com deficiência, os idosos com idade igual ou superior a 60 anos, as gestantes, as lactantes, as pessoas com crianças de colo e os obesos, conforme estabelecido pela lei 10.048, de 8 de novembro de 2000.') ?>
                    </p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Funções do Cadastro Nacional') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('A Cultura Viva é uma política cultural voltada para a valorização da cultura de base comunitária, o reconhecimento e apoio às atividades e processos culturais já desenvolvidos,
                        estimulando a participação social, a colaboração e a gestão compartilhada de políticas públicas no campo da cultura.') ?>
                    </p>

                    <p>
                        <?= i::__('O Cadastro Nacional de Pontos e Pontões de Cultura, instituído pela Lei Cultura Viva nº 13.018/2014, regulamentada pela Instrução Normativa MinC nº 08/2016 
                        e pela Instrução Normativa MinC nº 12/2024, é a ferramenta que abarca as informações de entidades e coletivos culturais para o reconhecimento e mapeamento por parte do
                        Estado, estabelecendo uma relação direta de interação, articulação e comunicação entre os Pontos e Pontões de Cultura, o Ministério da Cultura, os entes federados 
                        parceiros, os produtores e fazedores e fazedoras da cultura brasileira e os cidadãos beneficiários da política pública no Brasil e no exterior.') ?>
                    </p>
                    <p>
                        <?= i::__('Para quem já é Ponto ou Pontão de Cultura, o Cadastro é uma forma de manter os dados atualizados, enviar informações sobre atividades desenvolvidas, 
                        além de possibilitar a articulação e integração em rede.') ?>
                    </p>
                    <p>
                        <?= i::__('Para quem quer ser Ponto ou Pontão de Cultura, é só chegar! Faça o cadastro da sua entidade ou do seu coletivo cultural, insira as informações sobre ações 
                        culturais que desenvolve nas comunidades e em rede, fotos e vídeos de divulgação e, a partir de uma seleção simplificada e da adesão à Política Nacional de Cultura Viva, 
                        a entidade ou o coletivo cultural poderá ser reconhecido como Ponto ou Pontão de Cultura, passando a integrar a Rede Cultura Viva.') ?>
                    </p>

                    <p>
                        <?= i::__('Curtiu? Então cadastre-se para ser mais um Ponto/Pontão de Cultura nesta rede da diversidade cultural brasileira!') ?>
                    </p>
                </div>
            </template>
        </mc-accordion>

        <br>

        <h4 class="bold">
            <?= i::__('Para mais informações ou dúvidas sobre este serviço, entre em contato:') ?>
        </h4>

        <p>
            <?= i::__('Email:') ?> <a href="mailto:culturaviva@cultura.gov.br">culturaviva@cultura.gov.br</a> <?= i::__('ou') ?> <a href="mailto:suporte.culturaviva@cultura.gov.br">suporte.culturaviva@cultura.gov.br</a>
        </p>

        <p>
            <?= i::__('Este é um serviço do(a) Ministério da Cultura.') ?> <br>
            <?= i::__('Em caso de dúvidas, reclamações ou sugestões favor contactá-lo.') ?>
        </p>

        <p>
            <?= i::__('Legislação') ?>
        </p>
        
        <ul>
            <li>
                <a href="http://www.planalto.gov.br/ccivil_03/_ato2011-2014/2014/lei/l13018.htm" >
                    <?= i::__('Lei n. 13.018, de 22 de julho de 2014') ?>
                </a>
            </li>
            <li>
                <a href="http://www.cultura.gov.br/legislacao/-/asset_publisher/siXI1QMnlPZ8/content/instrucao-normativa-n%C2%BA-8-2016-minc/10937?redirect=http%3A%2F%2Fwww.cultura.gov.br%2Flegislacao%3Fp_p_id%3D101_INSTANCE_siXI1QMnlPZ8%26p_p_lifecycle%3D0%26p_p_state%3Dnormal%26p_p_mode%3Dview%26p_p_col_id%3D_118_INSTANCE_UFVehMS15laT__column-1%26p_p_col_pos%3D1%26p_p_col_count%3D2" >
                    <?= i::__('Instrução Normativa n. 08, de 11 de maio de 2016') ?>
                </a>
            </li>
            <li>
                <a href="https://www.gov.br/culturaviva/pt-br/biblioteca-cultura-viva/normativos/instrucao-normativa_in-minc-no-12_28-05-2024-altera-in-minc-no-08-2016-e-regulamenta-premios-e-bolsas-cultura-viva.pdf" >
                    <?= i::__('Instrução Normativa - IN/MinC nº 12, de 28 de maio de 2024') ?>
                </a>
            </li>
        </ul>

    </div>
</div>


