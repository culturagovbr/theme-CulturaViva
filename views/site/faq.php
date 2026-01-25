<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Perguntas frequentes')],
];

$this->import('
    mc-accordion
    mc-breadcrumb
');

$opportunity_url = $app->createUrl('opportunity', 'single', [$app->config['rcv.opportunityId']]);
?>

<div class="static-page">
    <mc-breadcrumb></mc-breadcrumb>

    <div class="static-page__title">
        <div class="static-page__background">
            <div class="img">
                <img src="<?php $this->asset('img/rcv-static-pages/politica-nacional.png') ?>" />
            </div>
        </div>
        <h2 class="bold"> <?= i::__('Perguntas frequentes') ?> </h2>
    </div>

    <div class="static-page__content">
        <h3 class="bold"> <?= i::__('Encontre aqui as respostas para as dúvidas mais comuns sobre a Rede Cultura Viva e saiba como participar, acessar benefícios e fortalecer sua atuação cultural.') ?> </h3>

        <br>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Cadastro Nacional de Pontos e Pontões de Cultura: passo a passo') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('Para acessar o Cadastro Nacional de Pontos e Pontões de Cultura, você tem 3 opções:') ?></p>
                    <ul>
                        <li>
                            <?= i::__('Diretamente pelo link do Cadastro Nacional (https://culturaviva.cultura.gov.br/)') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.1.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Pelo Portal da Cultura Viva, clique no botão “Cadastro Nacional: seja Ponto ou Pontão de Cultura”') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.2.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Pelo Mapa da Cultura (https://mapa.cultura.gov.br/), clique no ícone da Cultura Viva') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.3.png', true, true) ?>" />
                        </li>
                    </ul>

                    <p><?= i::__('Para cadastrar um Ponto ou Pontão de Cultura, é preciso antes criar uma conta pessoal (de agente individual) no Cadastro Nacional de Pontos e Pontões de Cultura, e também se você for solicitar a propriedade de Ponto ou Pontão já certificado.') ?></p>

                    <p><?= i::__('É obrigatório o preenchimento das seguintes informações:') ?></p>
                    <ul>
                        <li><?= i::__('Email') ?></li>
                        <li><?= i::__('CPF') ?></li>
                        <li><?= i::__('Nome') ?></li>
                        <li><?= i::__('Senha') ?></li>
                        <li><?= i::__('Área de Atuação') ?></li>
                        <li><?= i::__('Minibio') ?></li>
                    </ul>

                    <p><?= i::__('Caso você já tenha uma conta no Mapa da Cultura, utilize o usuário já cadastrado, informando seu e-mail e senha.') ?></p>

                    <h5><?= i::__('PASSO A PASSO – CRIAR CONTA') ?></h5>
                    <ul>
                        <li>
                            <?= i::__('1.1 Clique no botão ENTRAR no cabeçalho') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.4.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.2 Preencha seu email ou CPF') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.5.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.3 Crie uma conta, caso ainda não tenha') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.6.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.4 Informe seus dados e escolha uma senha forte') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.7.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.5 Aceite os Termos e Condições de Uso rolando até o final da página') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.8.png', true, true) ?>" />
                            <img src="<?php $this->asset('/img/rcv-faq/1.9.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.6 Aceite a Política de Privacidade rolando até o final da página') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.10.png', true, true) ?>" />
                            <img src="<?php $this->asset('/img/rcv-faq/1.11.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.7 Autorize o Uso de Imagem') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.12.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.8 Preencha os demais campos obrigatórios e clique em criar conta') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.13.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.9 Abra seu email para validar a conta') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.14.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.10 Clique no botão “Acessar minha conta” para entrar no sistema') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.15.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.11 O sistema mostrará novamente a tela de email ou CPF') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.16.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.12 Em seguida pedirá sua senha') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.17.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('1.13 Caso ainda não tenha validado sua conta por email, o sistema te informará que é preciso fazer isso') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/1.18.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Email ou CPF já cadastrados e senha esquecida.') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('Neste caso é necessário ativar a recuperação de senha.') ?></p>

                    <p><?= i::__('Tanto email como CPF são chaves únicas de identificação e não podem ser duplicados. Clique em “Esqueci minha senha”:') ?></p>

                    <p>
                        <?= i::__('Em seguida, acesse seu email por onde receberá um link para recuperação de senha.') ?>
                        <img src="<?php $this->asset('/img/rcv-faq/2.1.png', true, true) ?>" />
                    </p>

                    <p><?= i::__('Senhas fortes possuem mais de 08 caracteres, pelo menos uma letra maiúscula e misturam números, letras e caracteres especiais.') ?></p>

                    <p><?= i::__('Se não receber o e-mail de confirmação, verifique a caixa de spam ou tente reenviar a confirmação pelo botão no sistema.') ?></p>

                    <p><?= i::__('Se você esqueceu o e-mail que está vinculado ao seu CPF será preciso entrar em contato com o suporte pelo email suporte.culturaviva@cultura.gov.br informando no campo assunto: “Atualizar email vinculado ao CPF”') ?></p>

                    <p><?= i::__('Em última instância, acione o suporte pelo e-mail suporte.culturaviva@cultura.gov.br') ?></p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Criar novo Cadastro') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('A criação de Novo Cadastro deve ser realizada quando há interesse em submeter uma organização ao processo de avaliação para certificação como Ponto ou Pontão de Cultura.') ?></p>

                    <h5><?= i::__('PASSO A PASSO') ?></h5>
                    <ul>
                        <li>
                            <?= i::__('1. Acesse a Página Principal e clique no botão Novo Cadastro') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/3.1.png', true, true) ?>" />
                        </li>

                        <li>
                            <?= i::__('2. Selecione o tipo de Ponto da organização') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/3.2.png', true, true) ?>" />
                        </li>

                        <li>
                            <?= i::__('Verificação de CNPJ (se você selecionou um tipo de Ponto que possui CNPJ): A verificação garante a regularidade. CNPJs irregulares com a Receita Federal não podem realizar o processo.') ?>
                            <br />
                            <img src="<?php $this->asset('/img/rcv-faq/3.3.png', true, true) ?>" />
                            <img src="<?php $this->asset('/img/rcv-faq/3.4.png', true, true) ?>" />
                            <img src="<?php $this->asset('/img/rcv-faq/3.5.png', true, true) ?>" />
                            <small><?= i::__('Exemplo de mensagem de retorno para CNPJ irregular') ?></small>
                        </li>

                        <li>
                            <?= i::__('3. Seleção de organização já cadastrada ou cadastro de nova organização (se você selecionou um tipo de Ponto sem CNPJ)') ?>
                            <ul>
                                <li>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.6.png', true, true) ?>" />
                                    <small><?= i::__('Mensagem de retorno quando não há organização previamente vinculada ao agente') ?></small>
                                </li>
                                <li>
                                    <?= i::__('Caso existam organizações já vinculadas ao seu Agente Individual, você será convidado a escolher entre organizações já existentes ou cadastro de nova organização.') ?>
                                </li>
                                <li>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.7.png', true, true) ?>" />
                                    <small><?= i::__('Mensagem de retorno quando não há organização previamente vinculada ao agente') ?></small>
                                </li>
                            </ul>
                        </li>

                        <li>
                            <?= i::__('4. Preencha o formulário') ?>
                            <p><?= i::__('O formulário está organizado por etapas, que contêm campos obrigatórios em todas elas. Durante o processo de preenchimento, é possível:') ?></p>
                            <ul>
                                <li>
                                    <?= i::__('Iniciar o cadastro e retomar depois, clicando em “Salvar para depois”') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.8.png', true, true) ?>" />
                                </li>
                                <li>
                                    <?= i::__('Pular etapas sem que todos os campos obrigatórios estejam preenchidos, clicando em “Próxima Etapa” ou “Etapa Anterior”') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.9.png', true, true) ?>" />
                                </li>
                                <li>
                                    <?= i::__('Pular etapas sem que todos os campos obrigatórios estejam preenchidos, clicando nas bolinhas da linha de visualização das etapas') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.10.png', true, true) ?>" />
                                </li>
                                <li>
                                    <?= i::__('Verificar quais campos obrigatórios não foram preenchidos, ao clicar em “Enviar formulário” na última etapa') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.11.png', true, true) ?>" />
                                </li>
                                <li>
                                    <?= i::__('Ir direto para o campo obrigatório não preenchido, clicando no título em negrito do campo que aparece no quadro de erros no preenchimento da inscrição') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/3.12.png', true, true) ?>" />
                                </li>
                            </ul>
                        </li>

                        <li>
                            <?= i::__('5. Envie o cadastro') ?>
                            <p><?= i::__('Lembre-se que após clicar em “Enviar formulário” e confirmar o envio, o formulário será encerrado e não haverá a possibilidade de realizar novas edições.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/3.13.png', true, true) ?>" />
                        </li>

                        <li>
                            <?= i::__('6. Acompanhe seu cadastro e acesse a Ficha do Cadastro') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Acompanhamento do Cadastro') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('Após o envio do cadastro, você poderá acessar e acompanhar as informações sobre seu cadastro por três caminhos:') ?></p>

                    <ul>
                        <li>
                            <?= i::__('Logo após o envio do formulário, você será direcionado para uma página de acompanhamento e acesso à Ficha de Inscrição.') ?>
                            <ul>
                                <li><?= i::__('Nesta página será exibido um resumo do seu cadastro, com número de inscrição, categoria do cadastro, status do cadastro e data de envio.') ?></li>
                                <li><?= i::__('Também será dado acesso à linha do tempo do cadastro (na aba Acompanhamento) e à ficha cadastral completa (na aba Ficha de Inscrição).') ?></li>
                                <li>
                                    <img src="<?php $this->asset('/img/rcv-faq/4.1.png', true, true) ?>" />
                                    <small><?= i::__('Linha do tempo do cadastro exibida na aba de acompanhamento') ?></small>
                                </li>
                                <li>
                                    <img src="<?php $this->asset('/img/rcv-faq/4.2.png', true, true) ?>" />
                                    <small><?= i::__('Ficha cadastral completa, com todas as informações preenchidas no momento do cadastro, exibidas na aba Ficha de Inscrição.') ?></small>
                                </li>
                            </ul>
                        </li>

                        <li>
                            <?= i::__('Pela página do Cadastro (link), também é possível acessar os dados do cadastro.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/4.3.png', true, true) ?>" />
                        </li>

                        <li>
                            <?= i::__('Pelo Painel de Controle, na opção “Meus Cadastros”, também é possível visualizar os dados e acessar as demais informações completas.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/4.4.png', true, true) ?>" />
                        </li>

                        <li>
                            <?= i::__('Ao clicar no Menu do Cultura Viva, é possível navegar entre “Cadastros enviados” e “Cadastros não enviados” para acessar a lista.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/4.5.png', true, true) ?>" />
                        </li>
                    </ul>

                    <ul>
                        <li>
                            <h4><?= i::__('Status dos Cadastros') ?></h4>
                        </li>
                        <ul>
                            <li>
                                <strong><?= i::__('Enviada:') ?></strong>
                                <?= i::__('inscrições que foram finalizadas, enviadas e entraram para avaliação.') ?>
                            </li>
                            <li>
                                <strong><?= i::__('Inabilitada:') ?></strong>
                                <?= i::__('inscrições que foram finalizadas, enviadas, avaliadas e tiveram como resultado a inabilitação – ou seja, não serão certificadas como Ponto ou Pontão de Cultura.') ?>
                            </li>
                            <li>
                                <strong><?= i::__('Habilitada:') ?></strong>
                                <?= i::__('inscrições que foram finalizadas, enviadas, avaliadas e tiveram como resultado a habilitação – ou seja, serão certificadas como Ponto ou Pontão de Cultura.') ?>
                            </li>
                            <li>
                                <strong><?= i::__('Rascunho:') ?></strong>
                                <?= i::__('inscrições que não foram enviadas para avaliação, seja porque faltam campos obrigatórios a serem preenchidos, seja porque a pessoa responsável pelo cadastro não clicou no botão de “enviar”.') ?>
                            </li>
                        </ul>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Cadastro indeferido') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('Você receberá um e-mail caso a Comissão de Certificação tenha indeferido o cadastro da organização') ?>.</p>

                    <img src="<?php $this->asset('/img/rcv-faq/10.1.png', true, true) ?>" />

                    <p><?= i::__('Clique no link para saber os motivos do indeferimento.') ?></p>
                    <p><?= i::__('O resultado aparecerá na ficha de acompanhamento da inscrição no cadastro:') ?></p>

                    <img src="<?php $this->asset('/img/rcv-faq/10.2.png', true, true) ?>" />

                    <p><?= i::__('Clique em exibir detalhamento para saber os motivos:') ?></p>

                    <img src="<?php $this->asset('/img/rcv-faq/10.3.png', true, true) ?>" />

                    <p>
                        <?= sprintf(
                            i::__('Certifique-se de atender a todos os critérios e %s.'),
                            '<a href="' . $opportunity_url . '">' . i::__('inicie uma nova inscrição no cadastro') . '</a>'
                        ) ?>
                    </p>
                    <p><?= i::__('As informações preenchidas foram salvas e você só precisará modificar as questões não atendidas na análise.') ?></p>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Editar portfólio da organização') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p>
                        <?= i::__('A edição de informações da organização pode ser feita diretamente em sua página pública.') ?>
                    </p>
                    <p>
                        <?= i::__('Nela é possível inserir informações relacionadas ao portfólio da organização, como galeria de imagens, fotografia, links de perfis em ferramentas de rede social, etc.') ?>
                    </p>
                    <p>
                        <?= i::__('As demais informações que aparecem como bloqueadas devem ser editadas pelo fluxo de Atualização Cadastral.') ?>
                    </p>

                    <h5><?= i::__('PASSO A PASSO') ?></h5>
                    <ul>
                        <li>
                            <strong><?= i::__('1. Acessar o perfil da organização') ?></strong>
                            <ul>
                                <li>
                                    <strong><?= i::__('1.1 Clique em “Entrar”') ?></strong><br />
                                    <?= i::__('Uma vez logado com e-mail/CPF e senha, clique no botão “Entrar”, localizado à direita do menu, no cabeçalho da plataforma.') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/5.1.png', true, true) ?>" />
                                </li>

                                <li>
                                    <strong><?= i::__('1.2 Selecionar Agente') ?></strong><br />
                                    <?= i::__('Para edição de Agente Coletivo, clique no card de Agentes ou diretamente na guia “Meus Agentes” no Menu do Painel de Controle.') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/5.3.png', true, true) ?>" />
                                    <img src="<?php $this->asset('/img/rcv-faq/5.2.png', true, true) ?>" />
                                </li>

                                <li>
                                    <strong><?= i::__('1.3 Escolha a organização que será editada') ?></strong><br />
                                    <?= i::__('Em “Meus Agentes”, são listados os agentes publicados, em rascunho, agentes com permissão de acesso e aqueles na lixeira. Em cada card de agente haverá o botão “Editar”.') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/5.4.png', true, true) ?>" />
                                </li>

                                <li>
                                    <strong><?= i::__('1.4 Edite') ?></strong><br />
                                    <?= i::__('Para agentes coletivos, existe uma limitação nas edições. Os campos bloqueados devem ser atualizados por meio do fluxo de Atualização Cadastral.') ?>
                                    <p><?= i::__('Informações que podem ser editadas diretamente no perfil do Agente Coletivo:') ?></p>
                                    <ul>
                                        <li><?= i::__('Imagem de capa') ?></li>
                                        <li><?= i::__('Imagem de avatar') ?></li>
                                        <li><?= i::__('Links') ?></li>
                                        <li><?= i::__('Galeria de fotos') ?></li>
                                        <li><?= i::__('Galeria de vídeos') ?></li>
                                        <li><?= i::__('Galeria de arquivos para download') ?></li>
                                        <li><?= i::__('Administração') ?></li>
                                        <li><?= i::__('Agentes relacionados') ?></li>
                                        <li><?= i::__('Propriedade do agente') ?></li>
                                    </ul>
                                </li>

                                <li>
                                    <strong><?= i::__('1.5 Salvar') ?></strong><br />
                                    <?= i::__('Para que suas informações sejam salvas e publicadas conforme suas preferências marcadas na edição de páginas, clique em “Salvar”.') ?>
                                    <img src="<?php $this->asset('/img/rcv-faq/5.5.png', true, true) ?>" />
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Editar portfólio') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <p> <?= i::__('Na página de edição da organização clique em “Editar Perfil”, que está localizado no rodapé:') ?> </p>
                            <img src="<?php $this->asset('/img/rcv-faq/6.1.png', true, true) ?>" />
                        </li>
                        <li>
                            <p> <?= i::__('Ao abrir a edição, a página será exibida assim:') ?> </p>
                            <img src="<?php $this->asset('/img/rcv-faq/6.2.png', true, true) ?>" />
                        </li>
                        <li>
                            <p> <?= i::__('Role para baixo até visualizar a opção de “Outros administradores”.') ?> </p>
                            <img src="<?php $this->asset('/img/rcv-faq/6.3.png', true, true) ?>" />
                        </li>
                        <li>
                            <p> <?= i::__('Clique em “Adicionar administrador” e pesquise o nome da pessoa que deseja adicionar.') ?> </p>
                            <img src="<?php $this->asset('/img/rcv-faq/6.4.png', true, true) ?>" />
                        </li>
                        <li>
                            <p> <?= i::__('Selecione e confirme a ação. Após esse processo a nova pessoa aparece listada como novo/a administrador/a.') ?> </p>
                            <img src="<?php $this->asset('/img/rcv-faq/6.5.png', true, true) ?>" />
                        </li>
                        <li>
                            <p> <?= i::__('É possível remover o novo administrador clicando no X vermelho no canto superior direito do avatar da pessoa.') ?> </p>
                        </li>
                        <li>
                            <p> <?= i::__('Para mais informações clique no ícone de pergunta.') ?> </p>
                            <img src="<?php $this->asset('/img/rcv-faq/6.6.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Atualização Cadastral') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('A Atualização Cadastral deve ser realizada pelo menos uma vez por ano. Organizações sem atualização por um período superior a cinco anos podem ser desativadas pela administração da plataforma.') ?></p>

                    <p><strong><?= i::__('PASSO A PASSO') ?></strong></p>

                    <ul>
                        <li>
                            <p><?= i::__('Acesse a página de Atualização Cadastral pela Página Principal.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/7.1.png', true, true) ?>" />
                        </li>
                        <li>
                            <p><?= i::__('Selecione a opção “Atualizar dados cadastrais”.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/7.2.png', true, true) ?>" />
                        </li>
                        <li>
                            <p><?= i::__('Selecione a organização que deseja atualizar.') ?></p>
                            <p><?= i::__('Só é possível editar organizações pelas quais o Agente Individual, com a conta logada, é responsável/proprietário.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/7.3.png', true, true) ?>" />
                        </li>
                        <li>
                            <p><?= i::__('Edite as informações no formulário de cadastro, onde as opções disponíveis para edição estarão abertas.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/7.4.png', true, true) ?>" />
                        </li>
                        <li>
                            <p><?= i::__('Clique em “Salvar e finalizar” para confirmar a atualização.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/7.5.png', true, true) ?>" />
                            <p><?= i::__('O sistema retornará com a confirmação e, ao final, você será direcionado para a página da organização.') ?></p>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Alteração cadastral') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><?= i::__('A Alteração Cadastral só pode ocorrer nos seguintes casos:') ?></p>

                    <ul>
                        <li><?= i::__('Mudança no Tipo de Ponto:') ?>
                            <ul>
                                <li><?= i::__('Ponto de Cultura (coletivo sem CNPJ) se torna Ponto de Cultura (entidade com CNPJ)') ?></li>
                                <li><?= i::__('Ponto de Cultura (coletivo sem CNPJ) se torna Pontão (com CNPJ)') ?></li>
                                <li><?= i::__('Ponto de Cultura (entidade com CNPJ) se torna Ponto de Cultura (coletivo sem CNPJ)') ?></li>
                                <li><?= i::__('Pontão (com CNPJ) se torna Ponto de Cultura (coletivo sem CNPJ)') ?></li>
                                <li><?= i::__('Pontão (com CNPJ) se torna Ponto de Cultura (entidade com CNPJ)') ?></li>
                            </ul>
                        </li>
                        <li><?= i::__('Mudança de CNPJ atrelado à organização certificada') ?></li>
                        <li><?= i::__('Mudança de representação (Pessoa responsável pela gestão do Cadastro por meio da solicitação ou cessão de propriedade)') ?></li>
                        <li><?= i::__('Desativação da organização') ?></li>
                    </ul>

                    <p><?= i::__('Nos casos de Mudança do Tipo de Ponto, CNPJ e Desativação, a solicitação passa por uma análise da administração da plataforma.') ?></p>

                    <p><?= i::__('No caso de mudança de representação, a análise é necessária quando a pessoa responsável não tem condições de acessar a plataforma para executar a ação de “Ceder propriedade”.') ?></p>

                    <p><strong><?= i::__('PASSO A PASSO') ?></strong></p>
                    <ul>
                        <li>
                            <?= i::__('Acesse a página de Atualização Cadastral.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.1.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione a opção desejada:') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.2.png', true, true) ?>" />

                            <ul>
                                <li>
                                    <?= i::__('Alterar tipo') ?>
                                </li>
                                <li>
                                    <?= i::__('Alterar CNPJ') ?>
                                </li>
                                <li>
                                    <?= i::__('Alterar representação') ?>
                                </li>
                                <li>
                                    <?= i::__('Desativar Ponto ou Pontão') ?>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Alterar tipo de organização') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <?= i::__('Nesta página, selecione a opção “Alterar o tipo de organização”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.3.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione a organização que deseja alterar e clique em “Continuar”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.4.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione o novo tipo da organização.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.5.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Informe o CNPJ da organização e clique em “Verificar CNPJ”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.6.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Após validar o CNPJ, clique em “Confirmar”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.7.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Em seguida, você será direcionado para completar a atualização cadastral.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.8.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Alterar CNPJ da organização') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <?= i::__('Selecione a opção “Alterar CNPJ”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.9.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Escolha a organização que deseja alterar e clique em “Confirmar”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.10.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Informe o novo CNPJ e clique em “Verificar”.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.11.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Confirme a solicitação de mudança.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.12.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Após confirmar você receberá uma mensagem de conclusão da solicitação.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.13.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Representação') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <p><?= i::__('Se a pessoa que representa a organização mudou, é possível ceder ou solicitar a propriedade de Pontos e Pontões de Cultura; para isso, o agente que vai receber a propriedade deve estar ativo no sistema.') ?></p>
                        </li>
                        <li>
                            <p><?= i::__('Clique em “Alterar representação”.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/8.14.png', true, true) ?>" />
                        </li>
                        <li>
                            <p><?= i::__('Em seguida selecione uma das opções de acordo com seu caso: “Ceder propriedade” ou “Solicitar propriedade”.') ?></p>
                            <img src="<?php $this->asset('/img/rcv-faq/8.15.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Ceder propriedade') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <img src="<?php $this->asset('/img/rcv-faq/8.16.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione a organização vinculada à conta logada que deseja alterar a propriedade.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.17.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione o Agente Individual que receberá a propriedade. Apenas agentes com conta publicada na plataforma Cultura Viva poderão receber a propriedade.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.18.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Confirme.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.19.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('O Agente Individual selecionado para receber a propriedade receberá uma notificação por e-mail com o seguinte assunto: "Requisição de mudança de propriedade".') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.20.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('A solicitação também aparecerá nas Notificações do sistema.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.21.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Solicitar propriedade') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <img src="<?php $this->asset('/img/rcv-faq/8.22.png', true, true) ?>" />
                        </li>
                    </ul>

                    <h5><?= i::__('Solicitar propriedade') ?></h5>
                    <ul>
                        <li>
                            <?= i::__('Selecione a organização para a qual deseja solicitar propriedade.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.23.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Clique em continuar e confirme sua solicitação.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.24.png', true, true) ?>" />
                        </li>
                        <li>
                            <small><?= i::__('A pessoa responsável pela organização receberá uma notificação para ceder a propriedade.') ?></small>
                        </li>
                    </ul>

                    <p><?= i::__('Se essa pessoa não finalizar o processo, você pode solicitar a mudança de administração enviando um e-mail para suporte.culturaviva@cultura.gov.br com a seguinte documentação:') ?></p>

                    <ul>
                        <li><?= i::__('Fotografia do responsável pela organização segurando, perto do rosto, seu documento de identidade com foto;') ?></li>
                        <li><?= i::__('Cópia do documento de identidade com foto (frente e verso);') ?></li>
                        <li><?= i::__('Caso seja entidade (organização com CNPJ): Estatuto ou ata de posse da diretoria atualizada, indicando a pessoa responsável pela organização;') ?></li>
                        <li><?= i::__('Caso seja coletivo cultural (organização sem CNPJ): Carta de representação em que os membros do coletivo autorizam a pessoa solicitante de propriedade a representá-lo como responsável pela organização.') ?></li>
                    </ul>

                    <ul>
                        <li>
                            <?= i::__('Clique em confirmar.') ?>
                        </li>
                        <li>
                            <?= i::__('A Solicitação aparecerá em suas notificações.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.25.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('O Agente Individual selecionado para receber a propriedade receberá uma notificação por e-mail com o seguinte assunto: "Requisição de mudança de propriedade".') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.26.png', true, true) ?>" />
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion class="static-page__sub">
            <template #title>
                <h4 class="bold">
                    <?= i::__('Desativar Ponto ou Pontão') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <ul>
                        <li>
                            <?= i::__('Selecione a opção:') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.27.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione a organização que deseja desativar.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.28.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Clique em continuar. Em seguida, justifique o motivo da solicitação.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/8.29.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Clique em enviar.') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

        <mc-accordion>
            <template #title>
                <h4 class="bold">
                    <?= i::__('Exportar planilha') ?>
                </h4>
            </template>
            <template #content>
                <div class="static-page__accordion">
                    <p><strong><?= i::__('Antes de iniciar:') ?></strong> <?= i::__('certifique-se de que você já criou sua conta e está logado no sistema, pois a planilha só pode ser baixada por pessoas com conta no Cadastro Nacional.') ?></p>
                    <p><strong><?= i::__('PASSO A PASSO') ?></strong></p>
                    <ul>
                        <li>
                            <p> <?= i::__('Acesse a página do Mapa clicando no ícone do Menu superior:') ?><a href="https://culturaviva.cultura.gov.br/#map" target="_blank">https://culturaviva.cultura.gov.br/#map</a></p>
                            <img src="<?php $this->asset('/img/rcv-faq/9.1.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Clique em Tabela (exportar)') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/9.2.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('E você será direcionado para a página com a lista.') ?><br>
                            <img src="<?php $this->asset('/img/rcv-faq/9.3.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Clique em selecionar dados para marcar as opções desejadas.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/9.4.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Em seguida clique em “Exportar planilha”.') ?><br>
                            <img src="<?php $this->asset('/img/rcv-faq/9.5.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Selecione o formato desejado.') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/9.6.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Em seguida você receberá a confirmação da solicitação:') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/9.7.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Os dados chegarão por email com o título do assunto “[Cultura Viva] Planilha disponível”') ?>
                            <img src="<?php $this->asset('/img/rcv-faq/9.8.png', true, true) ?>" />
                        </li>
                        <li>
                            <?= i::__('Certifique-se que você entrou no sistema para baixar o documento e clique no link.') ?>
                        </li>
                    </ul>
                </div>
            </template>
        </mc-accordion>

    </div>
</div>