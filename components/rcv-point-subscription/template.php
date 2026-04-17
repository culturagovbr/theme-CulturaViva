<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-modal
    mc-entities
    mc-loading
');
?>
<div class="grid-12 rcv-point-subscription">
    <div class="col-12 rcv-point-subscription__info">
        <p class="title">
            <?= i::__("Cadastre seu Ponto ou Pontão de Cultura") ?>
        </p>

        <div class="content">
            <p class="content__description">
                <?= i::__("Para inscrever selecione o que melhor representa a sua organização.") ?>
            </p>
        </div>
    </div>

    <div class="col-12 rcv-point-subscription__subscription">
        <div class="rcv-point-subscription__subscription__wrapper">
            <div class="rcv-point-subscription__subscription__card">
                <mc-modal :title="modalTitle" @open="openModalTitle('ponto-entidade')" @close="closeModal()">
                    <!-- deslogado -->
                    <template v-if="!global.auth.isLoggedIn && loggedOut" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p>
                                <?= i::__("Efetue o login no <strong>Mapa da Cultura Viva</strong> antes de cadastrar seu Ponto ou Pontão de Cultura. Se você ainda não possui login, crie um perfil para você agora mesmo!") ?>
                            </p>
                        </div>
                    </template>

                    <!-- logado -->
                    <template v-if="global.auth.isLoggedIn && hasCnpjExternalOrgConflict && !nextStep && !verifiedCNPJ" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Este CNPJ já está vinculado a outra organização.<br> Se precisar de ajuda para regularizar o cadastro, entre em contato com <a href="mailto:suporte.culturaviva@cultura.gov.br" class="rcv-cnpj-conflict-modal__support-mail"><strong>suporte.culturaviva@cultura.gov.br</strong></a>.') ?></p>
                        </div>
                    </template>

                    <template v-else-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ" #default>
                        <div class="field">
                            <div class="field">
                                <label><?= i::__("CNPJ da instituição") ?>
                                    <span class="required"><?= I::__('*obrigatório') ?></span>
                                </label>
                            </div>
                            <div class="field">
                                <input type="text" v-maska data-maska="##.###.###/####-##" v-model="cnpj" placeholder="Digite o CNPJ da instituição" class="cnpj-input" />
                                <span v-if="invalidCNPJ" class="field__error"><?= i::__('Ops! Não foi possível fazer a consulta. Tente novamente mais tarde') ?></span>
                            </div>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && nextStep && !verifiedCNPJ" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Nosso sistema não identificou nenhuma organização vinculada a este CNPJ.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && nextStep && verifiedCNPJ && !naturezaJuridicaInvalida && !invalidCNPJ" #default>
                        <div class="rcv-point-subscription__fields field">
                            <mc-entities type="agent" select="id,name,cnpj,seals" :query="pontoQuery" order="name ASC" @loading="loading=true" @fetch="handleEntities($event)">
                                <template #default='{ entities }'>
                                    <label class="input__label input__radioLabel" v-for="entity in entities" :key="entity.__objectId">
                                        <input type="radio" name="entity" :value="entity.id" @change="getValueRadio($event, entity)" :checked="checkedEntity(entity)" :disabled="disabledEntity(entity,entities)"><?= i::__('ID ') ?>{{entity.id}} - {{entity.name}} {{entity.cnpj ? '(CNPJ ' + entity.cnpj + ')' : '(Sem CNPJ informado)'}}
                                    </label>

                                    <label class="input__label input__radioLabel" v-if="!selectedEntity || !hasRegistrationCategory(entity)">
                                        <input type="radio" name="entity" value="new-organization" :disabled="selectedEntity && selectedEntity.cnpj === this.cnpj" :checked="isNoneSelected()" @change="getValueRadio($event, entity)">
                                        <?= i::__('Nenhum dos acima, cadastrar nova organização') ?>
                                    </label>
                                </template>

                                <template #empty='{ entities }'>
                                    <div class="field">
                                        <p><?= i::__('O CNPJ desta organização está válido!') ?></p>
                                    </div>
                                </template>
                            </mc-entities>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && (nextStep && verifiedCNPJ && naturezaJuridicaInvalida || nextStep && verifiedCNPJ && !naturezaJuridicaInvalida && invalidCNPJ)" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p v-if="invalidCNPJ">{{invalidCNPJ}}</p>
                            <p v-else><?= i::__('As entidades que podem ser cadastradas como Ponto ou Pontão de Cultura devem ser sem fins lucrativos e estar com a situação cadastral ativa. As naturezas jurídicas aceitas são: <strong>399-9, 306-9, 313-1, 323-9, 330-1, 322-0 e 214-3</strong>. Por favor, verifique o seu CNPJ.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && invalidPrincipalAgent" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Por favor, entre em contato via email suporte.culturaviva@cultura.gov.br para reguralizar sua situação.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && invalidAgentType" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p>
                                <?= i::__("Entre em contato com o suporte por meio do e-mail suporte.culturaviva@cultura.gov.br  informando que precisa atualizar o <strong>tipo de agente do seu cadastro.</strong>") ?>
                            </p>
                        </div>
                    </template>

                    <template #actions="modal">
                        <div v-if="global.auth.isLoggedIn && nextStep && verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">
                            <mc-loading v-if="isLoading" :condition="isLoading" :entity="entity"></mc-loading>
                            <button v-if="global.auth.isLoggedIn && errorSituacaoCadastral && !isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__verify" @click="modal.close()"> <?= i::__('Ok') ?></button>
                            <button v-if="global.auth.isLoggedIn && nextStep && verifiedCNPJ && !naturezaJuridicaInvalida && !isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__verify" :class="{'disabled' : hasError}" @click="confirmAndSubscribe()"><?= i::__('Cadastrar') ?></button>
                        </div>
                        <div v-if="global.auth.isLoggedIn && hasCnpjExternalOrgConflict && !nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">
                            <button type="button" class="button button--icon button--md rcv-point-subscription__subscription__card__verify" @click="hasCnpjExternalOrgConflict = false">
                                <?= i::__('Ok') ?>
                            </button>
                        </div>
                        <div v-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType && !hasCnpjExternalOrgConflict" class="rcv-point-subscription__subscription__card__buttons">
                            <mc-loading v-if="isLoading" :condition="isLoading" :entity="entity"></mc-loading>
                            <button v-if="!isLoading" @click="verifyCNPJ" class="button button--icon button--md rcv-point-subscription__subscription__card__verify"><?= i::__('Verificar CNPJ') ?></button>
                        </div>
                        <div v-if="global.auth.isLoggedIn && nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">
                            
                            <button v-if="!isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__register rcv-point-subscription__subscription__card__register--color" :class="{'disabled': hasError || isCertificated}" @click="createOrganization()">
                                <?= i::__('Cadastrar organização') ?>
                            </button>
                        </div>

                        <div v-if="(global.auth.isLoggedIn && invalidPrincipalAgent) || (global.auth.isLoggedIn && invalidAgentType) " class="rcv-point-subscription__subscription__card__buttons">
                            <button v-if="!isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__register rcv-point-subscription__subscription__card__register--color" @click="modal.close()">
                                <?= i::__('Ok') ?>
                            </button>
                        </div>

                        <!-- deslogado -->
                        <div v-if="!global.auth.isLoggedIn" class="rcv-point-subscription__subscription__card__buttons">
                            <button class="button button--icon button--md rcv-point-subscription__subscription__card__verify" @click="redirectLogin()"><?= i::__('Fazer login') ?></button>
                        </div>
                    </template>

                    <template #button="modal">
                        <slot :modal="modal">
                            <button @click="modal.open()" class="rcv-point-subscription__subscription__card__open button button--icon">
                                <p><?= i::__("Ponto de Cultura <span>(entidade com CNPJ)</span>") ?></p>
                            </button>
                        </slot>
                    </template>
                </mc-modal>
            </div>
            <span class="card-description"><?= i::__("Entidade de natureza ou finalidade cultural, com CNPJ, sem fins lucrativos, que desenvolve e articula atividades culturais em sua comunidade e em rede, há mais de dois anos.") ?></span>
        </div>

        <div class="rcv-point-subscription__subscription__wrapper">
            <div class="rcv-point-subscription__subscription__card">
                <mc-modal @open="openModalTitle('ponto-coletivo')" :title="modalTitle" @close="closeModal()">
                    <!-- deslogado -->
                    <template v-if="!global.auth.isLoggedIn && loggedOut" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p>
                                <?= i::__("Efetue o login no <strong>Mapa da Cultura Viva</strong> antes de cadastrar seu Ponto ou Pontão de Cultura. Se você ainda não possui login, crie um perfil para você agora mesmo!") ?>
                            </p>
                        </div>
                    </template>

                    <!-- logado -->
                    <template v-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" #default>
                        <div class="rcv-point-subscription__fields field">
                            <mc-entities type="agent" select="id,name,seals,cnpj" :query="pontoQuery" order="name ASC" @loading="loading=true" @fetch="handleEntities($event)">
                                <template #default='{ entities }'>
                                    <label class="input__label input__radioLabel" v-for="entity in entities" :key="entity.__objectId">
                                        <input type="radio" name="entity" :value="entity.id" @change="getValueRadio($event, entity)" :disabled="disabledEntity(entity,entities)"><?= i::__('ID: ') ?>{{entity.id}} <span v-if="entity.name">- {{entity.name}}</span>
                                    </label>

                                    <label class="input__label input__radioLabel">
                                        <input type="radio" name="entity" value="new-organization" :disabled="selectedEntity && selectedEntity.cnpj === this.cnpj" :checked="isNoneSelected()" @change="getValueRadio($event, entity)">
                                        <?= i::__('Nenhum dos acima, cadastrar nova organização') ?>
                                    </label>
                                </template>

                                <template #empty='{ entities }'>
                                    <div class="field">
                                        <p><?= i::__('Nosso sistema não identificou nenhuma organização vinculada a seu cadastro') ?></p>
                                        <p><?= i::__('Para continuar, clique em "Confirmar" para criar uma nova organização.') ?></p>
                                    </div>
                                </template>
                            </mc-entities>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && invalidPrincipalAgent" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Por favor, entre em contato via email suporte.culturaviva@cultura.gov.br para reguralizar sua situação.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && invalidAgentType" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p>
                                <?= i::__("Entre em contato com o suporte por meio do e-mail suporte.culturaviva@cultura.gov.br  informando que precisa atualizar o <strong>tipo de agente do seu cadastro.</strong>") ?>
                            </p>
                        </div>
                    </template>

                    <template #actions="modal">
                        <div v-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">
                            
                            <mc-loading v-if="isLoading" :condition="isLoading" :entity="entity"></mc-loading>

                            <button
                                v-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ && !isLoading" 
                                class="button button--icon button--md rcv-point-subscription__subscription__card__verify" 
                                :class="{'disabled' : hasError || isLoading}" 
                                @click="confirmAndSubscribe(false)"
                                :disabled="isLoading"
                                >
                                {{isLoading ? '<?= i::__('Aguarde') ?>' : '<?= i::__('Confirmar') ?>'}}
                            </button>
                        </div>

                        <div v-if="(global.auth.isLoggedIn && invalidPrincipalAgent) || (global.auth.isLoggedIn && invalidAgentType)" class="rcv-point-subscription__subscription__card__buttons">
                            <button v-if="!isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__register rcv-point-subscription__subscription__card__register--color" @click="modal.close()">
                                <?= i::__('Ok') ?>
                            </button>
                        </div>
                        <!-- deslogado -->
                        <div v-if="!global.auth.isLoggedIn && !isLoading" class="rcv-point-subscription__subscription__card__buttons">
                            <button class="button button--icon button--md rcv-point-subscription__subscription__card__verify" @click="redirectLogin()"><?= i::__('Fazer login') ?></button>
                        </div>
                    </template>

                    <template #button="modal">
                        <slot :modal="modal">
                            <button @click="modal.open()" class="rcv-point-subscription__subscription__card__open button button--icon">
                                <p><?= i::__("Ponto de Cultura <span>(coletivo sem CNPJ)</span>") ?></p>
                            </button>
                        </slot>
                    </template>
                </mc-modal>
            </div>
            <span class="card-description"><?= i::__("Grupo ou coletivo de natureza ou finalidade cultural, sem CNPJ, que desenvolve e articula atividades culturais em sua comunidade e em rede, há mais de dois anos, formado por no mínimo duas pessoas.") ?></span>
        </div>

        <div class="rcv-point-subscription__subscription__wrapper">
            <div class="rcv-point-subscription__subscription__card rcv-point-subscription__subscription__card--color">
                <mc-modal :title="modalTitle" @open="openModalTitle('pontao')" @close="closeModal()">
                    <!-- deslogado -->
                    <template v-if="!global.auth.isLoggedIn && loggedOut" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p>
                                <?= i::__("Efetue o login no <strong>Mapa da Cultura Viva</strong> antes de cadastrar seu Ponto ou Pontão de Cultura. Se você ainda não possui login, crie um perfil para você agora mesmo!") ?>
                            </p>
                        </div>
                    </template>

                    <!-- logado -->
                    <template v-if="global.auth.isLoggedIn && hasCnpjExternalOrgConflict && !nextStep && !verifiedCNPJ" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Este CNPJ já está vinculado a outra organização.<br> Se precisar de ajuda para regularizar o cadastro, entre em contato com <a href="mailto:suporte.culturaviva@cultura.gov.br" class="rcv-cnpj-conflict-modal__support-mail"><strong>suporte.culturaviva@cultura.gov.br</strong></a>.') ?></p>
                        </div>
                    </template>

                    <template v-else-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ" #default>
                        <div class="field">
                            <div class="field">
                                <label><?= i::__("CNPJ da instituição") ?>
                                    <span class="required"><?= I::__('*obrigatório') ?></span>
                                </label>
                            </div>
                            <div class="field">
                                <input type="text" v-maska data-maska="##.###.###/####-##" v-model="cnpj" placeholder="Digite o CNPJ da instituição" class="cnpj-input" />
                                <span v-if="invalidCNPJ" class="field__error"><?= i::__('Ops! Não foi possível fazer a consulta. Tente novamente mais tarde') ?></span>
                            </div>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && nextStep && !verifiedCNPJ" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Nosso sistema não identificou nenhuma organização vinculada a este CNPJ.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && nextStep && verifiedCNPJ && !naturezaJuridicaInvalida" #default>
                        <div class="rcv-point-subscription__fields field">
                            <mc-entities type="agent" select="id,name,cnpj,seals" :query="pontoQuery" order="name ASC" @loading="loading=true" @fetch="handleEntities($event)">
                                <template #default='{ entities }'>
                                    <label class="input__label input__radioLabel" v-for="entity in entities" :key="entity.__objectId">
                                        <input type="radio" name="entity" :value="entity.id" @input="getValueRadio($event, entity)" :checked="checkedEntity(entity)" :disabled="disabledEntity(entity,entities)"><?= i::__('ID ') ?>{{entity.id}} - {{entity.name}} {{entity.cnpj ? '(CNPJ ' + entity.cnpj + ')' : '(Sem CNPJ informado)'}}
                                    </label>

                                    <label class="input__label input__radioLabel" v-if="!selectedEntity || !hasRegistrationCategory(entity)">
                                        <input type="radio" name="entity" value="new-organization" :disabled="selectedEntity && selectedEntity.cnpj === this.cnpj" :checked="isNoneSelected()" @change="getValueRadio($event, entity)">
                                        <?= i::__('Nenhum dos acima, cadastrar nova organização') ?>
                                    </label>
                                </template>

                                <template #empty='{ entities }'>
                                    <div class="field">
                                        <p><?= i::__('O CNPJ desta organização está válido!') ?></p>
                                        <p><?= i::__('Deseja criar um Cadastro e uma organização com este CNPJ nesta plataforma?') ?></p>
                                    </div>
                                </template>
                            </mc-entities>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && (nextStep && verifiedCNPJ && naturezaJuridicaInvalida || nextStep && verifiedCNPJ && !naturezaJuridicaInvalida && invalidCNPJ)" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p v-if="invalidCNPJ">{{invalidCNPJ}}</p>
                            <p v-else><?= i::__('As entidades que podem ser cadastradas como Ponto ou Pontão de Cultura devem ser sem fins lucrativos e estar com a situação cadastral ativa. As naturezas jurídicas aceitas são: <strong>399-9, 306-9, 313-1, 323-9, 330-1, 322-0 e 214-3</strong>. Por favor, verifique o seu CNPJ.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && invalidPrincipalAgent" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p><?= i::__('Por favor, entre em contato via email suporte.culturaviva@cultura.gov.br para reguralizar sua situação.') ?></p>
                        </div>
                    </template>

                    <template v-if="global.auth.isLoggedIn && invalidAgentType" #default>
                        <div class="rcv-point-subscription__subscription__card__modal">
                            <p>
                                <?= i::__("Entre em contato com o suporte por meio do e-mail suporte.culturaviva@cultura.gov.br  informando que precisa atualizar o <strong>tipo de agente do seu cadastro.</strong>") ?>
                            </p>
                        </div>
                    </template>

                    <template #actions="modal">
                        <div v-if="global.auth.isLoggedIn && nextStep && verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">    
                            <mc-loading v-if="isLoading" :condition="isLoading" :entity="entity"></mc-loading>
                            <button
                                v-if="global.auth.isLoggedIn && nextStep && verifiedCNPJ && !naturezaJuridicaInvalida && !isLoading" 
                                class="button button--icon button--md rcv-point-subscription__subscription__card__verify" 
                                :class="{'disabled' : hasError || isLoading}" 
                                @click="confirmAndSubscribe()"
                                :disabled="isLoading"
                                >
                                {{isLoading ? '<?= i::__('Aguarde') ?>' : '<?= i::__('Confirmar') ?>'}}
                            </button>
                        </div>

                        <div v-if="global.auth.isLoggedIn && hasCnpjExternalOrgConflict && !nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">
                            <button type="button" class="button button--icon rcv-point-subscription__subscription__card__verify" @click="hasCnpjExternalOrgConflict = false">
                                <?= i::__('Ok') ?>
                            </button>
                        </div>
                        <div v-if="global.auth.isLoggedIn && !nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType && !hasCnpjExternalOrgConflict" class="rcv-point-subscription__subscription__card__buttons">
                            <mc-loading v-if="isLoading" :condition="isLoading" :entity="entity"></mc-loading>
                            <button v-if="!isLoading" @click="verifyCNPJ" class="button button--icon rcv-point-subscription__subscription__card__verify"><?= i::__('Verificar CNPJ') ?></button>
                        </div>
                        
                        <div v-if="global.auth.isLoggedIn && nextStep && !verifiedCNPJ && !invalidPrincipalAgent && !invalidAgentType" class="rcv-point-subscription__subscription__card__buttons">
                            <button v-if="!isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__register rcv-point-subscription__subscription__card__register--color" :class="{'disabled': hasError || isCertificated}" @click="createOrganization()">
                                <?= i::__('Cadastrar organização') ?>
                            </button>
                        </div>

                        <div v-if="(global.auth.isLoggedIn && invalidPrincipalAgent) || (global.auth.isLoggedIn && invalidAgentType)" class="rcv-point-subscription__subscription__card__buttons">
                            <button v-if="!isLoading" class="button button--icon button--md rcv-point-subscription__subscription__card__register rcv-point-subscription__subscription__card__register--color" @click="modal.close()">
                                <?= i::__('Ok') ?>
                            </button>
                        </div>

                        <!-- deslogado -->
                        <div v-if="!global.auth.isLoggedIn" class="rcv-point-subscription__subscription__card__buttons">
                            <button class="button button--icon button--md rcv-point-subscription__subscription__card__verify" @click="redirectLogin()"><?= i::__('Fazer login') ?></button>
                        </div>
                    </template>

                    <template #button="modal">
                        <slot :modal="modal">
                            <button @click="modal.open()" class="rcv-point-subscription__subscription__card__open rcv-point-subscription__subscription__card__open--color button button--icon">
                                <p><?= i::__("Pontão de Cultura <span>(entidade com CNPJ)</span>") ?></p>
                            </button>
                        </slot>
                    </template>
                </mc-modal>
            </div>
            <span class="card-description"><?= i::__("Entidade de natureza ou finalidade cultural e/ou educativa, com CNPJ, sem fins lucrativos, que atua em rede, há mais de dois anos, desenvolvendo ações de mobilização, formação, mediação e articulação de uma determinada rede de Pontos de Cultura e demais iniciativas culturais, seja em âmbito territorial ou com um recorte temático ou identitário.") ?></span>
        </div>
    </div>
</div>