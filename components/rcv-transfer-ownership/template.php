<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-entities
    mc-icon
    mc-loading
    select-entity
');

$route = $app->createUrl('auth');
?>

<mc-modal classes="rcv-transfer-ownership__modal" button-label="label do botão" :title="modalTitle" ref="updateModal" @close="closeModal()">
    <template #default="modal">

        <span v-if="global.auth.isLoggedIn">
            <div v-if="!step">
                <p>
                    <?= i::__('Se você é o representante atual da organização e deseja transferir a propriedade para outro agente cadastrado no sistema, clique em <b>"Ceder propriedade"</b>.
                Caso queira se tornar o responsável por uma organização atualmente representada por outra pessoa, clique em <b>"Solicitar propriedade"</b>.
                Uma solicitação será enviada ao responsável atual, e você será notificado assim que uma decisão for tomada.') ?>
                </p>
            </div>


            <div v-if="step=='transfer'">
                <mc-entities type="agent" :query="transferQuery" :limit="10" watch-query>
                    <template #header="{entities}">
                        <form class="select-entity__form" @submit="entities.refresh(); $event.preventDefault();">
                            <input ref="searchKeyword" placeholder="Digite o nome de cadastro da pessoa" v-model="entities.query['@keyword']" type="text" class="select-entity__form--input" name="searchKeyword" :placeholder="placeholder" @keyup="entities.refresh(500)" />
                            <button type="button" class="select-entity__form--button">
                                <mc-icon name="search"></mc-icon>
                            </button>
                        </form>
                    </template>

                    <template #default="{entities}">
                        <ul class="select-entity__results scrollbar">
                            <li v-for="agent in entities" :key="agent.id">
                                <div class="field">
                                    <label class="input__label input__radioLabel">
                                        <input type="radio" name="organization" v-model="organization" :value="agent">
                                        <a :href="url(agent)" target="_blank">
                                            #{{agent.id}}
                                        </a>
                                        <span v-if="agent.name"> {{ agent.name }} </span>
                                        <i v-if="!agent.name"><small> <?= i::__('Nome não definido') ?> </small></i>
                                    </label>
                                </div>
                            </li>
                        </ul>
                    </template>

                    <template #empty>
                        <div class="rcv-registration-update modal-options">
                            <?= i::__('Ops! Não encontramos inscrições para ceder a propriedade.') ?>
                        </div>
                    </template>

                    <template #load-more="{ entities, loadMore }">
                        <div class="load-more">
                            <mc-loading :condition="entities.loadingMore"></mc-loading>
                            <button class="button--large button button--primary-outline" v-if="!entities.loadingMore" @click="loadMore()"><?php i::_e('Carregar Mais') ?></button>
                        </div>
                    </template>
                </mc-entities>
            </div>

            <div v-if="step=='reassign'" class="rcv-transfer-ownership__modal-content">
                <p> <?= i::__('Essa pessoa irá receber uma notificação para aceitar ou não a propriedade da organização. 
                Ela deverá estar cadastrada, com uma conta ativa de Agente Individual, na plataforma Cultura Viva.') ?></p>
                
                <div>
                    <mc-entities type="agent" :query="reasignQuery" :limit="10" watch-query>
                        <template #header="{entities}">
                            <form class="select-entity__form" @submit="entities.refresh(); $event.preventDefault();">
                                <input ref="searchKeyword" placeholder="Digite o nome de cadastro da pessoa" v-model="entities.query['@keyword']" type="text" class="select-entity__form--input" name="searchKeyword" :placeholder="placeholder" @keyup="entities.refresh(500)" />
                                <button type="button" class="select-entity__form--button">
                                    <mc-icon name="search"></mc-icon>
                                </button>
                            </form>
                        </template>

                        <template #default="{entities}">
                            <ul class="select-entity__results scrollbar">
                                <li v-for="agent in entities" :key="agent.id">
                                    <div class="field">
                                        <label class="input__label input__radioLabel">
                                            <input type="radio" name="destination" v-model="destination" :value="agent">
                                            <a :href="url(agent)" target="_blank">
                                                #{{agent.id}}
                                            </a>
                                            <span v-if="agent.name"> {{ agent.name }} </span>
                                            <i v-if="!agent.name"><small> <?= i::__('Nome não definido') ?> </small></i>
                                        </label>
                                    </div>
                                </li>
                            </ul>
                        </template>
                        <template #empty>
                            <div class="panel__row noEntity">
                                <p><?= i::__('Ops! Nenhuma pessoa encontrada') ?></p>
                            </div>
                        </template>
                    </mc-entities>
                </div>
            </div>

            <div v-if="step=='confirm-transfer'" class="rcv-transfer-ownership__modal-content">
                <p><?= i::__('Essa ação é irreversível') ?></p>
                <mc-loading :condition="isLoading">
                    <template v-slot:default="{ entity }">
                        <div v-if="isLoading">
                            Carregando dados ...
                        </div>
                        <div v-else>
                            Dados carregados ...
                        </div>
                    </template>
                </mc-loading>
            </div>


            <div v-if="step=='request'" class="rcv-transfer-ownership__modal-content">
                <mc-entities type="agent" :query="requestQuery" :limit="10" watch-query>
                    <template #header="{entities}">
                        <form class="select-entity__form" @submit="entities.refresh(); $event.preventDefault();">
                            <input ref="searchKeyword" placeholder="Digite o nome da organização" v-model="entities.query['@keyword']" type="text" class="select-entity__form--input" name="searchKeyword" :placeholder="placeholder" @input="entities.refresh(500)" />
                            <button type="button" class="select-entity__form--button">
                                <mc-icon name="search"></mc-icon>
                            </button>
                        </form>
                    </template>

                    <template #default="{entities}">
                        <ul class="select-entity__results scrollbar">
                            <li v-for="agent in entities" :key="agent.id">
                                <div class="field">
                                    <label class="input__label input__radioLabel">
                                        <input type="radio" name="organization" v-model="organization" :value="agent">
                                        <a :href="url(agent)" target="_blank">
                                            #{{agent.id}}
                                        </a>
                                        <span v-if="agent.name"> {{ agent.name }} </span>
                                        <i v-if="!agent.name"><small> <?= i::__('Nome não definido') ?> </small></i>
                                    </label>
                                </div>
                            </li>
                        </ul>
                    </template>

                    <template #empty>
                        <div class="rcv-registration-update modal-options">
                            <?= i::__('Ops! Não encontramos inscrições para solicitar a propriedade.') ?>
                        </div>
                    </template>
                </mc-entities>
            </div>

            <div v-if="step=='confirm-request'" class="rcv-transfer-ownership__modal-content">
                <p><?= i::__('Essa ação é irreversível') ?></p>
                <p><?= i::__('A pessoa responsável pela organização receberá uma notificação para ceder a propriedade.') ?></p> 
                <p><?= i::__('Se essa pessoa não finalizar o processo, você pode solicitar a mudança de administração enviando um e-mail para: <a href="mailto:suporte.culturaviva@cultura.gov.br">suporte.culturaviva@cultura.gov.br</a>') ?></p>
                
                <mc-loading :condition="isLoading">
                    <template v-slot:default="{ entity }">
                        {{isLoading ? 'Carregando dados...' : 'Dados carregados.'}}    
                    </template>
                </mc-loading>
            </div>
        </span>


        <span v-if="!global.auth.isLoggedIn">
            <?= i::__("Efetue o login no <strong>Mapa da Cultura Viva</strong> para alterar representação do seu Ponto ou Pontão de Cultura") ?>
        </span>
    </template>

    <template #button="modal">
        <button class="button button--large button--blue static-page__normal-button" @click="modal.open()">
            <?= i::__("Alterar representação") ?>
        </button>
    </template>

    <template #actions="modal">
        <a v-if="!global.auth.isLoggedIn" href="<?= $route ?>?redirectTo=/site/atualizacao-cadastral" class="button button--icon rcv-point-subscription__subscription__card__verify"><?= i::__('Fazer login') ?></a>

        <template v-if="global.auth.isLoggedIn && !step">
            <button class="button button--primary" @click="changeStep('transfer')">
                <?= i::__('Ceder propriedade') ?>
            </button>

            <button class="button button--primary" @click="changeStep('request')">
                <?= i::__('Solicitar propriedade') ?>
            </button>
        </template>

        <template v-if="global.auth.isLoggedIn && step=='transfer'">
            <button class="button button--primary" :disabled="disableButton" :class="[{'disabled' : disableButton}]" @click="changeStep('reassign')">
                <?= i::__('Continuar') ?>
            </button>
        </template>

        <template v-if="global.auth.isLoggedIn && step=='reassign'">
            <button class="button button--primary" :disabled="disableButton" :class="[{'disabled' : disableButton}]" @click="changeStep('confirm-transfer')">
                <?= i::__('Continuar') ?>
            </button>
        </template>


        <template v-if="global.auth.isLoggedIn && step=='request'">
            <button class="button button--primary" :disabled="disableButton" :class="[{'disabled' : disableButton}]" @click="changeStep('confirm-request')">
                <?= i::__('Continuar') ?>
            </button>
        </template>


        <template v-if="global.auth.isLoggedIn && step=='confirm-transfer'">
            <button v-if="!isLoading" class="button button--primary" @click="confirmTransfer(modal)">
                <?= i::__('Confirmar') ?>
            </button>
        </template>

        <template v-if="global.auth.isLoggedIn && step=='confirm-request'">
            <button v-if="!isLoading" class="button button--primary" @click="confirmRequest(modal)">
                <?= i::__('Confirmar') ?>
            </button>
        </template>
    </template>
</mc-modal>