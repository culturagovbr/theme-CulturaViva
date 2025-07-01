<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Pontos e Pontões')],
    ['label' => i::__('Atualização de cadastro')],
];

$this->import('
    mc-accordion
    mc-breadcrumb
    rcv-registration-update
    rcv-send-question
    rcv-transfer-ownership
');

if (!$app->config['rcv.disableRegistrationUpdateButtons']) {
    $this->import('
        rcv-deactivate-point
        rcv-registration-update-cnpj
        rcv-registration-update-type
    ');
}
?>


<div class="static-page static-page--update">
    <div class="static-page__breadcrumb">
        <mc-breadcrumb></mc-breadcrumb>
    </div>

    <div class="static-page__title">
        <h1 class="bold"> <?= i::__('Atualização de cadastro') ?> </h1>
        <p class="semibold"> <?= i::__('Atualize ou altere as informações do Ponto ou Pontão de Cultura') ?> </p>
        <div class="static-page__title-background">
            <img class="static-page__first-background" src="<?php $this->asset('img/update-register/primeiroFundo.svg'); ?>" />
            <img class="static-page__second-background" src="<?php $this->asset('img/update-register/segundoFundo.svg'); ?>" />
            <img class="static-page__third-background" src="<?php $this->asset('img/update-register/terceiroFundo.svg'); ?>" />
        </div>
    </div>

    <div class="static-page__content">

        <h3 class="bold"> <?= i::__('Atualize o cadastro') ?> </h2>

            <p>
                <?= i::__('Pontos e Pontões de Cultura certificados devem fazer a atualização do seu cadastro pelo menos uma vez por ano. Atualize sempre que necessário e mantenha seu cadastro ativo!') ?>
            </p>

            <div class="static-page__section">
                <rcv-registration-update></rcv-registration-update>
            </div>

            <?php if (!$app->config['rcv.disableRegistrationUpdateButtons']): ?>
                <h3 class="bold">
                    <?= i::__('Altere dados relativos à entidade') ?>
                </h3>

                <p>
                    <?= i::__('Atenção: a alteração desses dados passará por verificação e poderá levar alguns dias para ser processada e atualizada no perfil da organização.') ?>
                </p>

                <div class="static-page__section grid-12">
                    <div class="col-6 sm:col-12 grid-12 v-top">
                        <rcv-registration-update-type></rcv-registration-update-type>

                        <small class="col-12">
                            <?= i::__("Caso a organização agora tenha CNPJ ou deixado de tê-lo , modifique  o tipo de entidade.") ?>
                        </small>
                    </div>

                    <div class="col-6 sm:col-12 grid-12 v-top">
                        <rcv-registration-update-cnpj></rcv-registration-update-cnpj>

                        <small class="col-12">
                            <?= i::__("Caso a organização tenha mudado de CNPJ, selecione esta opção.") ?>
                        </small>
                    </div>

                    <div class="col-12 h-center">
                        <small>
                            <?= i::__("* Caso a organização já seja Ponto Entidade e queira se tornar também Pontão, faça um novo Cadastro.") ?>
                        </small>
                    </div>

                </div>
            <?php endif ?>

            <h3 class="bold">
                <?= i::__('Altere a representação da organização') ?>
            </h3>

            <p>
                <?= i::__('Se a pessoa que representa a organização mudou, é possível ceder ou solicitar a propriedade de Pontos e Pontões de Cultura; para isso, o agente que vai receber a propriedade deve estar ativo no sistema.') ?>
            </p>

            <rcv-transfer-ownership></rcv-transfer-ownership>

            <?php if (!$app->config['rcv.disableRegistrationUpdateButtons']): ?>
                <h3 class="bold">
                    <?= i::__('Desative seu Ponto ou Pontão de Cultura') ?>
                </h3>

                <p>
                    <?= i::__('Caso sua organização tenha deixado de executar atividades, ou deseje cancelar a certificação como Ponto ou Pontão de Cultura, entre em contato com o suporte.') ?>
                </p>

                <rcv-deactivate-point></rcv-deactivate-point>
            <?php endif ?>
    </div>

    <rcv-send-question></rcv-send-question>
</div>