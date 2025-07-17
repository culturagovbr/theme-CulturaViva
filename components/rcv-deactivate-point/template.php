<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$route = $app->createUrl('auth');

$this->import('
    mc-entities
');
?>

<mc-modal classes="rcv-registration-update__modal" button-label="label do botão" :title="stepTitle" ref="updateModal" @close="closeModal()">
    <template #default="modal">

        <div v-if="!step">
            <mc-entities type="agent" :query="query" :limit="10" watch-query>
                <template #header="{entities}">
                    <form class="select-entity__form" @submit="entities.refresh(); $event.preventDefault();">
                        <input ref="searchKeyword" placeholder="Digite para pesquisar" v-model="entities.query['@keyword']" type="text" class="select-entity__form--input" name="searchKeyword" :placeholder="placeholder" @keyup="entities.refresh(500)" />
                        <button type="button" class="select-entity__form--button">
                            <mc-icon name="search"></mc-icon>
                        </button>
                    </form>
                </template>

                <template #default="{entities}">
                    <ul class="select-entity__results scrollbar">
                        <li v-for="agent in entities" :key="agent.id">
                            <div :key="agent.id"  class="field">
                                <label class="input__label input__radioLabel">
                                    <input type="radio" name="organization" v-model="organization" :value="agent">
                                    
                                    <span v-if="agent">
                                        <a :href="url(agent)" target="_blank">
                                            #{{agent.id}}
                                        </a> - {{agent.name || agent.nomeCompleto}} 
                                        <small v-if="agent.cnpj">(CNPJ: {{agent.cnpj}})</small>
                                        <small v-if="!agent.cnpj">(CNPJ não informado)</small>
                                    </span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </template>

                <template #empty>
                    <div class="rcv-registration-update modal-options grid-12">
                        <?= i::__('Ops! Não encontramos organizações para desativar.') ?>
                    </div>
                </template>
            </mc-entities>
        </div>

        <div v-if="step == 'message'" class="rcv-transfer-ownership__modal-content">
            <p><?= i::__('A solicitação passará por análise e caso aceita ou indeferida, você receberá uma notificação por email.') ?></p>

            <div class="grid-12">
                <div class="field col-12">
                    <label for="">Descreva os motivos da nova desativação</label>
                    <textarea v-model="disableCulturalHub"></textarea>
                </div>
            </div>
        </div>

        <div v-if="step == 'success'" class="rcv-transfer-ownership__modal-content">
            <div class="grid-12">
                <div class="col-12">
                    <p><?= i::__('Sua solicitação de desativação da organização foi enviada com sucesso!') ?></p>
                    <p><?= i::__('Acompanhe sua caixa de entrada para atualizações.') ?></p>
                </div>
            </div>
        </div>

        <span v-if="!global.auth.isLoggedIn">
            <?= i::__('Efetue o login no <strong>Mapa da Cultura Viva</strong> para desativar o seu Ponto ou Pontão de cultura') ?>
        </span>

    </template>

    <template #button="modal">
        <button class="button button--large button--blue static-page__normal-button" @click="modal.open()">
            <?= i::__("Desativar Ponto ou Pontão") ?>
        </button>
    </template>

    <template #actions="modal">
        <mc-loading :condition="!!loading"><?= i::__('Redirecionando') ?></mc-loading>

        <template v-if="global.auth.isLoggedIn && !step">
            <button class="button button--primary" :class="[{'disabled' : disableButton}]" @click="changeStep('message')">
                <?= i::__('Continuar') ?>
            </button>
        </template>

        <template v-if="global.auth.isLoggedIn && step == 'message'">
            <button class="button button--primary" :class="[{'disabled' : disableButton}]" @click="sendMessage(modal)">
                <?= i::__('Enviar') ?>
            </button>
        </template>

        <template v-if="global.auth.isLoggedIn && step == 'success'">
            <button class="button button--primary" @click="closeModal(modal)">
                <?= i::__('Sair') ?>
            </button>
        </template>

        <a v-if="!global.auth.isLoggedIn" href="<?= $route ?>?redirectTo=/site/atualizacao-cadastral" class="button button--icon rcv-point-subscription__subscription__card__verify"><?= i::__('Fazer login') ?></a>
    </template>
</mc-modal>