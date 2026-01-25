<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-tab
    entity-map
    entity-data
');
?>

<mc-tab v-if="showTab" label="<?= i::_e('Localização') ?>" slug="localizacao">
    <mc-container>
        <main>
            <div class="single-data grid-12">
                <h3 class="single-data__title col-12"><?= i::__("Localização") ?></h3>

                <template v-for="(values, group) in fields" :key="group">

                    <template v-if="hasSubgroups(values) && showGroup(group)" v-for="(_values, _group) in values" >
                        <div class="single-data__field col-12 grid-12 sub">

                            <template v-for="(label, prop) in _values" :key="prop" >
                                <div v-if="isValid(prop)" class="single-data__field sm:col-12" :class="[{'col-6': Object.keys(_values).length === 2}, {'col-4': Object.keys(_values).length === 4}]">
                                    <entity-data :entity="entity" classes="col-12" :prop="prop" :label="label"></entity-data>
                                </div>
                            </template>
    
                        </div>
                    </template>

                    <div v-if="!hasSubgroups(values) && showGroup(group)" class="single-data__field col-12 grid-12 gru">
                        <template v-for="(label, prop) in values" :key="prop" >
                            <div v-if="isValid(prop)" class="single-data__field col-12">
                                <entity-data :entity="entity" classes="col-12" :prop="prop" :label="label"></entity-data>
                            </div>
                        </template>
                        
                    </div>

                </template>

                <!-- Linha divisória -->
                <div class="col-12">
                    <hr class="single-data__line">
                </div>

                <!-- Mapa -->
                <div v-if="entity.publicLocation" class="col-12">
                    <entity-map :entity="entity" :editable="false"></entity-map>
                </div>

                <!-- Linha divisória -->
                <div class="col-12">
                    <hr class="single-data__line">
                </div>

            </div>
        </main>
        <aside>
            <div class="grid-12">
                <div class="col-12 entity-calendar"  v-if="false">
                    <div class="entity-calendar__header">
                        <mc-icon name="event"></mc-icon>
                        <h4 class="entity-calendar__title">
                            <?php i::esc_attr_e('Agenda'); ?>
                        </h4>
                    </div>

                    <div class="col-12 entity-calendar__cards">
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
            </div>
        </aside>
    </mc-container>
</mc-tab>
