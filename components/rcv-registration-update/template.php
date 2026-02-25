<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$route = $app->createUrl('auth');
?>

<mc-modal classes="rcv-registration-update__modal" button-label="label do botão" :title="modalTitle" ref="updateModal">
    <template #default="modal">
        <div class="required-auth" v-if="!global.auth.isLoggedIn">
            <div>
                <?= i::__('Efetue o login no <strong>Mapa da Cultura Viva</strong> para atualizar seu Ponto ou Pontão de Cultura') ?>
            </div>
        </div>

        <div v-if="global.auth.isLoggedIn && !isAdmin">
            <div class="rcv-registration-update modal-options grid-12" v-if="hasRegistrations">
                <div v-for="registration in registrations" :key="registration.id" class="field col-12">
                    <label class="input__label input__radioLabel">
                        <input type="radio" name="organization" :value="registration.id" @change="saveRegistrationInfo(registration)">
                        <span v-if="registration.relatedAgents['coletivo'][0].name || registration.relatedAgents['coletivo'][0].nomeCompleto || registration.relatedAgents['coletivo'][0].cnpj">
                            <a :href="url(registration)" target="_blank">
                                #{{registration.id}} ({{registration.category}})
                            </a> - {{registration.relatedAgents['coletivo'][0].name || registration.relatedAgents['coletivo'][0].nomeCompleto}} 
                            <small v-if="registration.category != 'Ponto de Cultura (coletivo sem CNPJ)' && registration.relatedAgents['coletivo'][0].cnpj">(CNPJ: {{registration.relatedAgents['coletivo'][0].cnpj}})</small>
                            <small v-else>(CNPJ não informado)</small>
                        </span>
                        
                        <span v-if="!registration.relatedAgents['coletivo'][0].name && !registration.relatedAgents['coletivo'][0].nomeCompleto && !registration.relatedAgents['coletivo'][0].cnpj">
                            <a :href="url(registration)" target="_blank">
                                #{{registration.id}}
                            </a> - <?= i::__('Sem agente relacionado') ?>
                        </span>
                    </label>
                </div>
            </div>

            <div class="rcv-registration-update modal-options grid-12" v-if="!hasRegistrations">
                <?= i::__('Não há organizações disponíveis para atualização') ?>
            </div>
        </div>

        <mc-entities v-if="global.auth.isLoggedIn && isAdmin" type="registration" :query="query" select="category,relatedAgents" :limit="10" watch-query>
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
                        <div class="field">
                            <label class="input__label input__radioLabel">
                                <input type="radio" name="organization" :value="registration.id" @change="saveRegistrationInfo(registration)">
                                <span v-if="registration.relatedAgents?.['coletivo']?.[0]">
                                    <a :href="url(registration)" target="_blank">
                                        #{{registration.id}} ({{registration.category}})
                                    </a> - {{registration.relatedAgents['coletivo'][0].name || registration.relatedAgents['coletivo'][0].nomeCompleto}} 
                                    <small v-if="registration.category != 'Ponto de Cultura (coletivo sem CNPJ)' && registration.relatedAgents['coletivo'][0].cnpj">(CNPJ: {{registration.relatedAgents['coletivo'][0].cnpj}})</small>
                                    <small v-else>(CNPJ não informado)</small>
                                </span>
                                <span v-if="!registration.relatedAgents?.['coletivo']?.[0]">
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
                    <?= i::__('Ops! Não encontramos organizações disponíveis para atualização.') ?>
                </div>
            </template>
        </mc-entities>

    </template>

    <template #button="modal">
        <button class="button button--xbg button--large button--primary static-page__big-button static-page__big-button--update" @click="modal.open()">
            <?= i::__("Atualizar dados cadastrais") ?>
        </button>
    </template>


    <template #actions="modal">
        <button v-if="global.auth.isLoggedIn" :disabled="disableButton" class="button button--primary" :class="[{'disabled' : disableButton}]" @click="updateOrganization(modal)">
            <?= i::__('Confirmar') ?>
        </button>

        <a v-if="!global.auth.isLoggedIn" href="<?= $route ?>?redirectTo=/site/atualizacao-cadastral" class="button button--icon rcv-point-subscription__subscription__card__verify"><?= i::__('Fazer login') ?></a>
    </template>
</mc-modal>