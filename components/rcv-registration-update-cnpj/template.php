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
            <mc-entities v-if="global.auth.isLoggedIn" type="registration" :query="query" select="id,category,relatedAgents" :limit="10" watch-query>
                <template #header="{entities}">
                    <form class="select-entity__form" @submit="entities.refresh(); $event.preventDefault();">
                        <input ref="searchKeyword" placeholder="Digite para pesquisar" v-model="entities.query['@keyword']" type="text" class="select-entity__form--input" name="searchKeyword" :placeholder="placeholder" @keyup="entities.refresh(500)" />
                        <button type="button" class="select-entity__form--button">
                            <mc-icon name="search"></mc-icon>
                        </button>
                    </form>
                </template>

                <template #default="{entities}">
                    <p class="select-entity__description"> <?= i::__('Selecione apenas um') ?> </p>
                    <ul class="select-entity__results scrollbar">
                        <li v-for="registration in entities" :key="registration.id">
                            <div :key="registration.id"  class="field">
                                <label class="input__label input__radioLabel">
                                    <input type="radio" name="organization" :value="registration.id" @change="saveRegistrationInfo(registration)">
                                    
                                    <span v-if="registration.relatedAgents?.coletivo?.[0]">
                                        <a :href="url(registration)" target="_blank">
                                            #{{registration.id}} ({{registration.category}})
                                        </a> - {{registration.relatedAgents.coletivo[0].name || registration.relatedAgents.coletivo[0].nomeCompleto}} 
                                        <small v-if="registration.category != 'Ponto de Cultura (coletivo sem CNPJ)' && registration.relatedAgents.coletivo[0].cnpj">(CNPJ: {{registration.relatedAgents.coletivo[0].cnpj}})</small>
                                        <small v-else>(CNPJ não informado)</small>
                                    </span>

                                    <span v-if="!registration.relatedAgents?.coletivo?.[0]">
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
                    <div class="rcv-registration-update modal-options grid-12">
                        <?= i::__('Ops! Não encontramos organizações disponíveis para alteração do CNPJ.') ?>
                    </div>
                </template>
            </mc-entities>
        </div>

        <div v-if="step=='get-cnpj' && hasCnpjExternalOrgConflict" class="rcv-registration-update__modal-content">
            <p><?= i::__('Este CNPJ já está vinculado a outra organização.<br> Se precisar de ajuda para regularizar o cadastro, entre em contato com <a href="mailto:suporte.culturaviva@cultura.gov.br" class="rcv-cnpj-conflict-modal__support-mail"><strong>suporte.culturaviva@cultura.gov.br</strong></a>.') ?></p>
        </div>

        <div v-else-if="step=='get-cnpj'" class="rcv-registration-update__modal-content">
            <div class="field">
                <input type="text" v-maska data-maska="##.###.###/####-##" v-model="cnpj"/>
                <span v-if="invalidCNPJ" class="field__error"><?= i::__('Ops! Não foi possível fazer a consulta. Tente novamente mais tarde') ?></span>
            </div>
        </div>
        
        <div v-if="step=='situacao-cadastral'" class="rcv-registration-update__modal-content">
            <p>{{situacaoCadastralError}}</p>
        </div>
        
        <div v-if="step=='natureza-juridica'" class="rcv-registration-update__modal-content">
            <p><?= i::__('As entidades que podem ser cadastradas como Ponto ou Pontão de Cultura devem ser sem fins lucrativos e estar com a situação cadastral ativa. As naturezas jurídicas aceitas são: <strong>399-9, 306-9, 313-1, 323-9, 330-1, 322-0 e 214-3</strong>. Por favor, verifique o seu CNPJ.') ?></p>
        </div>

        <div v-if="step=='confirm'" class="rcv-registration-update__modal-content">
            <small>
                {{cnpj}}
            </small>
        </div>

        <div v-if="step == 'success'" class="rcv-transfer-ownership__modal-content">
            <div class="grid-12">
                <div class="col-12">
                    <p><?= i::__('Sua solicitação de alteração do CNPJ da organização foi enviada com sucesso!') ?></p>
                </div>
            </div>
        </div>

        <span v-if="!global.auth.isLoggedIn">
            <?= i::__('Efetue o login no <strong>Mapa da Cultura Viva</strong> para alterar o CNPJ do seu Ponto ou Pontão de cultura') ?>
        </span>

    </template>

    <template #button="modal">
        <button class="col-12 button button--xbg button--large button--blue static-page__big-button" @click="modal.open()">
            <?= i::__("Alterar o CNPJ") ?>
        </button>
    </template>

    <template #actions="modal">
        <button v-if="global.auth.isLoggedIn && !step" class="button button--primary" :class="[{'disabled' : disableButton}]" @click="changeStep('get-cnpj')">
            <?= i::__('Confirmar') ?>
        </button>

        <button v-if="global.auth.isLoggedIn && step=='get-cnpj' && hasCnpjExternalOrgConflict" type="button" class="button button--icon button--md rcv-point-subscription__subscription__card__verify" @click="hasCnpjExternalOrgConflict = false">
            <?= i::__('Ok') ?>
        </button>

        <button v-if="global.auth.isLoggedIn && step=='get-cnpj' && !hasCnpjExternalOrgConflict" class="button button--icon button--md rcv-point-subscription__subscription__card__verify" :class="[{'disabled' : disableButton}]" @click="verifyCNPJ()">
            <?= i::__('Verificar CNPJ') ?>
        </button>

        <template v-if="global.auth.isLoggedIn && step=='confirm' && !loading">
            <button class="button button--blue" @click="modal.close()">
                <?= i::__('Não') ?>
            </button>

            <button class="button button--primary" @click="updateCNPJ(modal);">
                <?= i::__('Sim') ?>
            </button>
        </template>

        <template v-if="global.auth.isLoggedIn && step == 'success'">
            <button v-if="!loading" class="button button--blue" :class="[{'disabled' : disableButton}]" @click="modal.close()">
                <?= i::__('Fechar') ?>
            </button>
        </template>
        
        <mc-loading :condition="!!loading"><?= i::__('Fazendo solicitação...') ?></mc-loading>
        
        <a v-if="!global.auth.isLoggedIn" href="<?= $route ?>?redirectTo=/site/atualizacao-cadastral" class="button button--icon rcv-point-subscription__subscription__card__verify"><?= i::__('Fazer login') ?></a>
    </template>
</mc-modal>