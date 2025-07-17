<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$route = $app->createUrl('auth');
?>

<mc-modal classes="rcv-registration-update__modal" button-label="label do botão" :title="stepTitle" ref="updateModal" @close="closeModal()">
    <template #default="modal">
        <div v-if="!step">
            <mc-entities v-if="global.auth.isLoggedIn" type="registration" :query="query" select="*" :limit="10" watch-query>
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
                        <li v-for="registration in entities" :key="registration.id">
                            <div :key="registration.id"  class="field">
                                <label class="input__label input__radioLabel">
                                    <input type="radio" name="organization" :value="registration.id" @change="saveRegistrationInfo(registration)">
                                    
                                    <span v-if="registration.agentsData?.coletivo">
                                        <a :href="url(registration)" target="_blank">
                                            #{{registration.id}}
                                        </a> - {{registration.agentsData.coletivo.name || registration.agentsData.coletivo.nomeCompleto}} 
                                        <small v-if="registration.agentsData.coletivo.cnpj">(CNPJ: {{registration.agentsData.coletivo.cnpj}})</small>
                                        <small v-if="!registration.agentsData.coletivo.cnpj">(CNPJ não informado)</small>
                                    </span>

                                    <span v-if="!registration.agentsData?.coletivo">
                                        <a :href="url(registration)" target="_blank">
                                            #{{registration.id}}
                                        </a> - <?= i::__('Sem agente relacionado') ?>
                                    </span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </template>

                <template #empty>
                    <div class="rcv-registration-update modal-options">
                        <?= i::__('Ops! Não encontramos organizações disponíveis para atualização.') ?>
                    </div>
                </template>
            </mc-entities>
        </div>

        <div v-if="step=='type'" class="rcv-registration-update__modal-content">
            <p><?= i::__('Atenção! A alteração deste dado fará que sua inscrição seja novamente avaliada.') ?></p>

            <div class="grid-12">
                <div class="field col-12">
                    <label class="input__label input__radioLabel"><input type="radio" name="organization" value="1" v-model="selectedOption" :disabled="disableOption('Ponto de Cultura (coletivo sem CNPJ)')"><?= i::__('De Ponto Coletivo (sem CNPJ) para Ponto Entidade (com CNPJ)') ?></label>
                </div>

                <div class="field col-12">
                    <label class="input__label input__radioLabel"><input type="radio" name="organization" value="2" v-model="selectedOption" :disabled="disableOption('Ponto de Cultura (coletivo sem CNPJ)')" ><?= i::__('De Ponto Coletivo (sem CNPJ) para Pontão (com CNPJ)') ?></label>
                </div>

                <div class="field col-12">
                    <label class="input__label input__radioLabel"><input type="radio" name="organization" value="3" v-model="selectedOption" :disabled="disableOption('Ponto de Cultura (entidade com CNPJ)')"><?= i::__('De Ponto Entidade (com CNPJ) para Pontão (com CNPJ)') ?></label>
                </div>

                <div class="field col-12">
                    <label class="input__label input__radioLabel"><input type="radio" name="organization" value="4" v-model="selectedOption" :disabled="disableOption('Ponto de Cultura (entidade com CNPJ)')" ><?= i::__('De Ponto Entidade (com CNPJ) para Ponto Coletivo (sem CNPJ)') ?></label>
                </div>

                <div class="field col-12">
                    <label class="input__label input__radioLabel"><input type="radio" name="organization" value="5" v-model="selectedOption" :disabled="disableOption('Pontão de Cultura (entidade com CNPJ)')" ><?= i::__('De Pontão (com CNPJ) para Ponto Coletivo (sem CNPJ)') ?></label>
                </div>
            </div>
        </div>

        <div v-if="step=='get-cnpj'" class="rcv-registration-update__modal-content">
            <div class="field">
                <input type="text" v-maska data-maska="##.###.###/####-##" v-model="cnpj"/>
                <span v-if="invalidCNPJ" class="field__error"><?= i::__('As entidades que podem ser cadastradas como Ponto ou Pontão de Cultura devem ser sem fins lucrativos e estar com a situação cadastral ativa. As naturezas jurídicas aceitas são: 399-9, 306-9, 313-1, 323-9, 330-1, 322-0 e 214-3. Por favor, verifique seu CNPJ') ?></span>
            </div>
        </div>
        
        <div v-if="step=='situacao-cadastral'" class="rcv-registration-update__modal-content">
            <p>{{situacaoCadastralError}}</p>
        </div>
        
        <div v-if="step=='natureza-juridica'" class="rcv-registration-update__modal-content">
            <p><?= i::__('As entidades que podem ser cadastradas como Ponto ou Pontão de Cultura devem ser sem fins lucrativos e estar com a situação cadastral ativa. As naturezas jurídicas aceitas são: <strong>399-9, 306-9, 313-1, 323-9, 330-1, 322-0 e 214-3</strong>. Por favor, verifique o seu CNPJ.') ?></p>
        </div>

        <div v-if="step=='confirm'" class="rcv-registration-update__modal-content">
            <p>
                <span class="semibold"> <?= i::__('Você confirma a atualização para o novo CNPJ?') ?> </span>
                <br>
                <small> {{cnpj}} </small>
            </p>
        </div>

        <div v-if="step == 'success'" class="rcv-transfer-ownership__modal-content">
            <div class="grid-12">
                <div class="col-12">
                    <p><?= i::__('Sua solicitação de alteração do tipo da organização foi enviada com sucesso!') ?></p>
                    <p><?= i::__('Você será redirecionado ao formulário.') ?></p>
                </div>
            </div>
        </div>

        <span v-if="!global.auth.isLoggedIn">
            <?= i::__('Efetue o login no <strong>Mapa da Cultura Viva</strong> para alterar o tipo do seu Ponto ou Pontão de cultura') ?>
        </span>

    </template>

    <template #button="modal">
        <button class="button button--xbg button--large button--blue static-page__big-button col-12" @click="modal.open()">
            <?= i::__("Alterar o tipo de organização") ?>
        </button>
    </template>


    <template #actions="modal">
        <button v-if="global.auth.isLoggedIn && !step" class="button button--primary" :class="[{'disabled' : disableButton}]" @click="changeStep('type')">
            <?= i::__('Continuar') ?>
        </button>

        <button v-if="global.auth.isLoggedIn && (step=='type' || step=='confirm')" class="button button--primary" :class="[{'disabled' : disableButton}]" @click="updateOrganization(modal)">
            <?= i::__('Confirmar') ?>
        </button>

        <button v-if="global.auth.isLoggedIn && step=='get-cnpj' && needCnpjVerification" class="button button--primary" :class="[{'disabled' : disableButton}]" @click="verifyCNPJ()">
            <?= i::__('Validar CNPJ') ?>
        </button>

        <template v-if="global.auth.isLoggedIn && step == 'success'">
            <mc-loading :condition="!!loading"><?= i::__('Redirecionando') ?></mc-loading>
        </template>

        <a v-if="!global.auth.isLoggedIn" href="<?= $route ?>?redirectTo=/site/atualizacao-cadastral" class="button button--icon rcv-point-subscription__subscription__card__verify"><?= i::__('Fazer login') ?></a>
    </template>
</mc-modal>