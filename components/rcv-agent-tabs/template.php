<?php

use MapasCulturais\i;

$this->import('
    agent-data-2
    complaint-suggestion
    entity-admins
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-links
    entity-list
    entity-location
    entity-owner
    entity-related-agents
    entity-seals
    entity-social-media
    entity-terms
    mc-container
    mc-modal
    mc-tab
    mc-tabs
    opportunity-list
    rcv-mc-share-links
    rcv-point-description
    select-entity
    entity-field
');
?>

<mc-tabs class="tabs" sync-hash>
    <?php $this->applyTemplateHook('tabs','begin') ?>
    <mc-tab icon="exclamation" label="<?= i::_e('Informações') ?>" slug="info">
        <div class="tabs__info">
            <mc-container>
                <main>
                    <!-- <opportunity-list></opportunity-list> -->
                    <div class="grid-12">

                        <rcv-point-description class="col-12" :entity="entity"></rcv-point-description>

                        <agent-data-2  class="col-12" :entity="entity"></agent-data-2>
                        <complaint-suggestion :entity="entity" classes="col-12" show-only-send-message-modal></complaint-suggestion>

                        <entity-gallery :entity="entity" classes="col-12"></entity-gallery>
                        <entity-gallery-video :entity="entity" classes="col-12"></entity-gallery-video>


                        <entity-location v-if="entity.rcv_org_brasil === 'Sim'" :entity="entity" classes="col-12"></entity-location>
                        <div class="col-12" v-if="entity.rcv_org_brasil === 'Não'">
                            <p class="entity-location__address" v-if="entity.En_publicLocationPontaPontao === 'Sim'">
                                <span>
                                    {{entity.En_Nome_LogradouroPontaPontao}}, {{entity.En_NumPontaPontao}} - {{entity.En_MunicipioPontaPontao}}/{{entity.En_EstadoPontaPontao}}  - {{entity.cepPontaPontao}} - {{entity.paisPontaPontao}}
                                </span>
                            </p>
                        </div>


                        <entity-files-list v-if="entity.files.downloads!= null" :entity="entity" classes="col-12" group="downloads" title="<?php i::esc_attr_e('Arquivos para download'); ?>"></entity-files-list>
                        <entity-links :entity="entity" classes="col-12" title="<?php i::_e('Links'); ?>"></entity-links>
                        <div v-if="entity.spaces?.length > 0 || entity.children?.length > 0 || entity.events?.length > 0 || entity.projects?.length > 0" class="col-12">
                            <h4 class="property-list"> <?php i::_e('Propriedades do Agente:'); ?> </h4>
                            <entity-list v-if="entity.spaces?.length>0" title="<?php i::esc_attr_e('Espaços'); ?>" type="space" :ids="entity.spaces"></entity-list>
                            <entity-list v-if="entity.events?.length>0" title="<?php i::esc_attr_e('Eventos'); ?>" type="event" :ids="entity.events"></entity-list>
                            <entity-list v-if="entity.children?.length>0" title="<?php i::esc_attr_e('Agentes'); ?>" type="agent" :ids="entity.children"></entity-list>
                            <entity-list v-if="entity.projects?.length>0" title="<?php i::esc_attr_e('Projetos'); ?>" type="project" :ids="entity.projects"></entity-list>
                        </div>

                        <entity-field class="col-12" disabled :entity="entity" prop="rcv_links_coletivo"></entity-field>

                        <div class="col-12 complaint-suggestion">
                            <h4 class="complaint-suggestion__title"><?php i::esc_attr_e('Algum problema?'); ?></h4>
                            <complaint-suggestion :entity="entity" classes="col-12" show-only-complaint-modal></complaint-suggestion>
                        </div>

                    </div>
                </main>
                <aside>
                    <div class="grid-12">

                        <div class="col-12 entity-calendar">

                            <div class="entity-calendar__header" v-if="false">
                                <mc-icon name="event"></mc-icon>
                                <h4 class="entity-calendar__title">
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

                                <div class="entity-calendar__card" v-if="false">

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
                                        <mc-icon name="pin" class="pin"></mc-icon>
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

                        <?php $this->applyTemplateHook('single2-entity-info-entity-seals', 'before') ?>
                        <entity-seals :entity="entity" :editable="entity.currentUserPermissions?.createSealRelation" classes="col-12">
                            <template #title>
                                <mc-icon name="section-seals"></mc-icon>
                                <?php i::esc_attr_e('Selos'); ?>
                            </template>
                        </entity-seals>
                        <?php $this->applyTemplateHook('single2-entity-info-entity-seals', 'before') ?>

                        <?php $this->applyTemplateHook('single2-entity-info-social-media', 'before') ?>
                        <entity-social-media :entity="entity" classes="col-12"></entity-social-media>
                        <?php $this->applyTemplateHook('single2-entity-info-social-media', 'after') ?>

                        <?php $this->applyTemplateHook('single2-entity-info-entity-owner', 'before') ?>
                        <entity-owner classes="col-12" title="<?php i::esc_attr_e('Responsável'); ?>" :entity="entity"></entity-owner>
                        <?php $this->applyTemplateHook('single2-entity-info-entity-owner', 'before') ?>

                        <?php $this->applyTemplateHook('single2-entity-info-entity-admins', 'before') ?>
                        <entity-admins :entity="entity" classes="col-12" title="<?php i::esc_attr_e('Outros administradores') ?>"></entity-admins>
                        <?php $this->applyTemplateHook('single2-entity-info-entity-admins', 'after') ?>

                        <?php $this->applyTemplateHook('single2-entity-info-entity-related-agents', 'before') ?>
                        <entity-related-agents :entity="entity" classes="col-12" title="<?php i::esc_attr_e('Agentes Relacionados'); ?>"></entity-related-agents>
                        <?php $this->applyTemplateHook('single2-entity-info-entity-related-agents', 'before') ?>

                        <!-- <?php $this->applyTemplateHook('single2-entity-info-entity-terms-tag', 'before') ?>
                        <entity-terms :entity="entity" hide-required classes="col-12" taxonomy="tag" title="<?php i::esc_attr_e('Tags') ?>"></entity-terms>
                        <?php $this->applyTemplateHook('single2-entity-info-entity-terms-tag', 'after') ?> -->

                

                        
                    </div>
                </aside>
            </mc-container>
        </div>
    </mc-tab>
    <?php $this->applyTemplateHook('tabs','end') ?>
</mc-tabs>
