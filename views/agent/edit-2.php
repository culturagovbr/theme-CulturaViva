<?php

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    confirm-before-exit 
    entity-actions
    entity-admins
    entity-cover
    entity-field
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-header
    entity-links
    entity-location
    entity-owner
    entity-profile
    entity-related-agents
    entity-renew-lock
    entity-seals
    entity-social-media
    entity-status
    entity-terms
    mc-breadcrumb
    mc-card
    mc-container
    mc-tabs
    mc-tab
');

$label = $this->isRequestedEntityMine() ? i::__('Meus agentes') : i::__('Agentes');
$this->breadcrumb = [
    ['label' => i::__('Painel'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => $label, 'url' => $app->createUrl('panel', 'agents')],
    ['label' => $entity->name, 'url' => $app->createUrl('agent', 'edit', [$entity->id])],
];
?>

<div class="main-app">
    <entity-renew-lock :entity="entity"></entity-renew-lock>
    <mc-breadcrumb></mc-breadcrumb>
    <entity-header :entity="entity" editable></entity-header>

    <mc-tabs class="tabs" sync-hash>
        <?php $this->applyTemplateHook('tabs', 'begin') ?>
        <mc-tab label="<?= i::_e('Informações') ?>" slug="info">
            <mc-container>
                <entity-status :entity="entity"></entity-status>
                <mc-card class="feature">
                    <template #title>
                        <label><?php i::_e("Informações de Apresentação") ?></label>
                        <!-- <p><?php i::_e("Os dados inseridos abaixo serão exibidos para todos os usuários") ?></p> -->
                    </template>
                    <template #content>
                        <div class="left">
                            <div class="grid-12 v-bottom">
                                <div class="field col-12">
                                    <label class="banner"><?php i::_e("Banner de destaque") ?></label>
                                    <entity-cover :entity="entity" classes="col-12"></entity-cover>
                                </div>
                                <div class="col-12 grid-12">
                                    <?php $this->applyTemplateHook('entity-info', 'begin') ?>
                                    <div class="col-3 sm:col-12 field">
                                        <label class="avatar"><?php i::_e("Foto de avatar") ?></label>
                                        <entity-profile :entity="entity"></entity-profile>
                                    </div>
                                    <div class="col-9 sm:col-12 grid-12 v-bottom">
                                        <entity-field :entity="entity" classes="col-12" prop="name" :max-length="100" label="<?php i::_e('Nome do Ponto ou Pontão de Cultura') ?>" :disabled="!global.auth.is('admin')"></entity-field>
                                        <entity-field :entity="entity" classes="col-12" prop="site" ></entity-field>
                                    </div>
                                    <?php $this->applyTemplateHook('edit2-entity-info-taxonomie-area', 'before') ?>
                                    <entity-terms :entity="entity" taxonomy="area" editable classes="col-12" title="<?php i::_e('Quais são os 3 principais segmentos de atuação da organização no campo artístico-cultural?'); ?>"></entity-terms>
                                    <!-- <select class="col-9" name="select" id="select"></select> -->
                                    <?php $this->applyTemplateHook('edit2-entity-info-taxonomie-area', 'after') ?>
                                    <?php $this->applyTemplateHook('entity-info', 'end') ?>
                                </div>
                                <!-- <entity-field :entity="entity" classes="col-12" prop="shortDescription" :max-length="400"></entity-field> -->
                            </div>
                        </div>
                        <div class="divider"></div>
                        <div class="right">
                            <div class="grid-12">
                                <entity-social-media :entity="entity" classes="col-12" editable></entity-social-media>
                            </div>
                        </div>
                    </template>
                </mc-card>

                <main>
                    <mc-card>
                        <template #title>
                            <label><?php i::_e("Informações institucionais"); ?></label>
                            <p class="data-subtitle"><?php i::_e("Os dados inseridos abaixo serão registrados apenas no sistemas e não serão exibidos publicamente") ?></p>
                        </template>
                        <template #content>
                            <div class="grid-12">
                                <entity-field :entity="entity" classes="col-12" prop="cnpj" label="CNPJ" :disabled="!global.auth.is('admin')"></entity-field>
                                <entity-field :entity="entity" classes="col-9 sm:col-12" prop="nomeCompleto" label="<?php i::_e('Razão social') ?>" disabled></entity-field>
                                <entity-field :entity="entity" classes="col-12" prop="emailPublico" label="<?= i::__('E-mail público') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-12" prop="telefonePublico" label="<?= i::__('Telefone público com DDD') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-12 sm:col-12" prop="telefone1" label="<?= i::__('Telefone privado 1 com DDD') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-12 sm:col-12" prop="telefone2" label="<?= i::__('Telefone privado 2 com DDD') ?>"></entity-field>
                                <entity-field :entity="entity" classes="col-12" prop="longDescription" editable></entity-field>
                                <!-- <entity-field v-if="global.auth.is('admin')" :entity="entity" prop="type" @change="entity.save(true).then(() => global.reload())" classes="col-12"></entity-field> -->
                                <!-- <entity-field :entity="entity" classes="col-12" prop="dataDeNascimento" label="<?= i::__('Data de fundação') ?>"></entity-field> -->
                                <!-- <entity-field :entity="entity" classes="col-12" prop="emailPrivado" label="<?= i::__('E-mail privado ') ?>"></entity-field> -->
                                <entity-links :entity="entity" classes="col-12 field__title" title="<?php i::_e('Outros Links da organização'); ?>" editable></entity-links>
                                <div class="col-12 divider"></div>
                            </div>
                        </template>
                    </mc-card>

                    <mc-card>
                        <template #title>
                            <label class="sede"><?php i::_e("Informações da sede"); ?></label>
                            <!-- <p class="data-subtitle"><?php i::_e("Os dados inseridos abaixo serão registrados apenas no sistemas e não serão exibidos publicamente") ?></p> -->
                            <!-- <p class="field__title field__title-sede"><?php i::_e("Sua organização está sediada no Brasil?") ?></p>
                            <div class="radio-group">
                                <label class="radio-options" for="">
                                    <input type="radio" name="organization-location" value="Sim" v-model="sede" disabled>
                                    <p class="sim" for=""><?php i::_e("Sim") ?></p>
                                </label>
                                <label class="radio-options" for="">
                                    <input type="radio" name="organization-location" value="Não" v-model="sede" disabled>
                                    <p class="nao" for=""><?php i::_e("Não") ?></p>
                                </label>
                            </div> -->
                            <!-- <p class="entity-location__address"><?php i::_e("Qual o endereço fixo ou da ede da organização, ou endereço de referência?") ?></p> -->
                            <div v-if="global.auth.is('admin')" class="grid-12">
                                <entity-field :entity="entity" classes="col-12" prop="rcv_org_brasil" label="<?php i::_e('Sua organização está sediada no Brasil?') ?>"></entity-field>
                                <div class="col-12" v-if="entity.rcv_org_brasil === 'Sim'">
                                    <entity-location :entity="entity" classes="col-12" editable></entity-location>
                                </div>
                                <div class="col-12">
                                    <div class="grid-12" v-if="entity.rcv_org_brasil === 'Não'">
                                        <entity-field classes="col-4 sm:col-12" :entity="entity" prop="cepPontaPontao" label="<?php i::_e('Código Postal') ?>"></entity-field>
                                        <entity-field classes="col-8 sm:col-12" :entity="entity" prop="En_Nome_LogradouroPontaPontao" label="<?php i::_e('Logradouro') ?>"></entity-field>
                                        <entity-field classes="col-2 sm:col-4" :entity="entity" prop="En_NumPontaPontao" label="<?php i::_e('Número') ?>"></entity-field>
                                        <entity-field classes="col-10 sm:col-8" :entity="entity" prop="En_MunicipioPontaPontao" label="<?php i::_e('Município') ?>"></entity-field>
                                        <entity-field classes="col-10 sm:col-8" :entity="entity" prop="En_EstadoPontaPontao" label="<?php i::_e('Estado') ?>"></entity-field>
                                        <entity-field classes="col-12" :entity="entity" prop="paisPontaPontao" label="<?php i::_e('País') ?>"></entity-field>
                                        <entity-field classes="col-12" :entity="entity" prop="En_publicLocationPontaPontao"></entity-field>
                                    </div>
                                </div>
                            </div>
                            <div class="grid-12" v-else>
                                <entity-location :entity="entity" classes="col-12"></entity-location>
                            </div>
                        </template>
                    </mc-card>
                    <mc-card>
                        <!-- <template #title>
                            <label><?php i::_e("informações públicas"); ?></label>
                            <p><?php i::_e("Os dados inseridos abaixo assim como as informações de apresentação também são exibidos publicamente"); ?></p>
                        </template> -->
                        <template #content>
                            <div class="grid-12">
                                <entity-gallery title="<?php i::_e('Galeria de fotos') ?>" :entity="entity" classes="col-12" editable></entity-gallery>
                                <entity-gallery-video title="<?php i::_e('Galeria de vídeos') ?>" :entity="entity" classes="col-12" editable></entity-gallery-video>
                                <entity-files-list :entity="entity" classes="col-12" group="downloads" title="Galeria de arquivos para download" editable></entity-files-list>
                            </div>
                        </template>
                    </mc-card>
                </main>

                <aside>
                    <div class="aside-container">
                        <mc-card>
                            <template #content>
                                <div class="grid-12"> <?php $this->applyTemplateHook('single2-entity-info-entity-seals', 'before') ?>
                                    <div class="col-12 entity-calendar">
                                        <div class="entity-calendar__header" v-if="false">
                                            <h4 class="entity-calendar__title">
                                                <mc-icon name="event"></mc-icon>
                                                <?php i::esc_attr_e('Agenda'); ?>
                                            </h4>
                                        </div>
                                        <div class="col-12 entity-calendar__cards" v-if="false">
                                            <div class="entity-calendar__card">
                                                <div class="entity-calendar__card-title">
                                                    <?php i::esc_attr_e('Festival Cultural Capoeira no Sangue'); ?>
                                                </div>
                                                <div class="entity-calendar__card-calendar">
                                                    <mc-icon name="date"></mc-icon>
                                                    <div class="entity-calendar__card-date">
                                                        <?php i::esc_attr_e(' 11 de Junho às 18:00'); ?>
                                                    </div>
                                                </div>
                                                <div class="entity-calendar__card-address">
                                                    <mc-icon name="pin"></mc-icon>
                                                    <div class="entity-calendar__card-location">
                                                        <?php i::esc_attr_e('<entidade espaço> - Estrada da Pirelli, Conjunto Beija flor. Ananindeua - PA') ?>
                                                    </div>
                                                </div>
                                                <div class="entity-calendar__card-ticket">
                                                    <div class="entity-calendar__card-ticket-classification">
                                                        <?php i::esc_attr_e('CLASSIFICAÇÃO'); ?>

                                                        <span class="livre">
                                                            <?php i::esc_attr_e('Livre'); ?>
                                                        </span>
                                                    </div>
                                                    <div class="entity-calendar__card-ticket-value">
                                                        <?php i::esc_attr_e('ENTRADA'); ?>

                                                        <span class="livre">
                                                            <?php i::esc_attr_e('10,00'); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="all-events">
                                                <?php i::esc_attr_e('Todos os eventos'); ?>
                                            </button>
                                        </div>
                                    </div>

                                    <entity-seals :entity="entity" :editable="entity.currentUserPermissions?.createSealRelation" classes="col-12">
                                        <template #title>
                                            <mc-icon name="section-seals"></mc-icon>
                                            <?php i::esc_attr_e('Selos'); ?>
                                        </template>
                                    </entity-seals>
                                    <?php $this->applyTemplateHook('single2-entity-info-entity-seals', 'before') ?>
                                    <entity-owner :entity="entity" title="<?php i::esc_attr_e('Responsável'); ?>"classes="col-12" editable></entity-owner>
                                    <entity-admins title="<?php i::esc_attr_e('Outros administradores') ?>" :entity="entity" classes="col-12" editable></entity-admins>
                                    <entity-related-agents title="<?php i::esc_attr_e('Agentes Relacionados'); ?>" :entity="entity" classes="col-12" editable></entity-related-agents>
                                </div>
                            </template>
                        </mc-card>
                    </div>
                </aside>
            </mc-container>
        </mc-tab>
        <?php $this->applyTemplateHook('tabs', 'end') ?>
    </mc-tabs>

    <entity-actions :entity="entity" editable></entity-actions>
</div>
<confirm-before-exit :entity="entity"></confirm-before-exit>