<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->layout = 'entity';

$this->addOpportunityPhasesToJs();
$this->useOpportunityAPI();

$this->import('
    complaint-suggestion
    entity-admins
    entity-actions
    entity-file
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-links
    entity-owner
    entity-related-agents
    entity-seals
    entity-social-media
    entity-terms
    evaluations-list
    mc-breadcrumb
    mc-container
    mc-share-links
    mc-tab
    mc-tabs
    opportunity-subscription-list
    opportunity-phase-evaluation
    opportunity-phases-timeline
    rcv-point-subscription
    rcv-entity-header
    opportunity-owner-type
    v1-embed-tool
');

$label = $this->isRequestedEntityMine() ? i::__('Pontos e Pontões') : i::__('Pontos e Pontões');

$this->breadcrumb = [
  ['label' => i::__('Inicio'), 'url' => $app->createUrl('panel', 'index')],
  ['label' => $label, 'url' => $app->createUrl('panel', 'registrations')],
  ['label' => $entity->name, 'url' => $app->createUrl('opportunity', 'single', [$entity->id])],
];
?>
<div class="main-app single single-opportunity">
  <mc-breadcrumb></mc-breadcrumb>
  <rcv-entity-header :entity="entity">
    <template #metadata>
        <dl v-if="global.showIds[entity.__objectType]" class="metadata__id" v-if="entity.id">
            <dt class="metadata__id--id"><?= i::__('ID') ?></dt>
            <dd><strong>{{entity.id}}</strong></dd>
        </dl> 
        <dl v-if="entity.type">
            <dt><?= i::__('Tipo')?></dt>
            <dd :class="[entity.__objectType+'__color', 'type']"> {{entity.type.name}} </dd>
        </dl>
        <dl v-if="entity.ownerEntity" class="single-opportunity__owner">
            <dt><?= i::__('Vinculado com ') ?><opportunity-owner-type :entity="entity"></opportunity-owner-type></dt>
            <dd><mc-link :entity="entity.ownerEntity"></mc-link></dd>
        </dl>
    </template>
  </rcv-entity-header>

    <mc-container class="opportunity">
        <main class="grid-12">
            <rcv-point-subscription class="col-12" :entity="entity"></rcv-point-subscription>
            <opportunity-subscription-list class="col-12" hide-infos></opportunity-subscription-list>
            <div class="grid-12">
                <div v-if="entity.longDescription" class="col-12">
                    <h3><?= i::__("Apresentação") ?></h3>
                    <p class="description" v-html="entity.longDescription"></p>
                </div>
                
                <entity-file :entity="entity" group-name="rules" classes="col-12" title="<?php i::esc_attr_e('Regulamento'); ?>"></entity-file>
                <entity-files-list :entity="entity" classes="col-12" group="downloads" title="<?php i::esc_attr_e('Arquivos para download');?>"></entity-files-list>
                <entity-links :entity="entity" classes="col-12" title="<?php i::_e('Links'); ?>"></entity-links>
                <entity-gallery-video :entity="entity" classes="col-12"></entity-gallery-video>
                <entity-gallery :entity="entity" classes="col-12"></entity-gallery>
            </div>
        </main>
        <aside>
            <div class="flex-container">
                <entity-social-media :entity="entity" classes="col-12"></entity-social-media>
                <entity-related-agents :entity="entity" classes="col-12" title="<?php i::esc_attr_e('Agentes Relacionados');?>"></entity-related-agents>
                <entity-admins :entity="entity" classes="col-12"></entity-admins>
                <entity-owner classes="col-12" title="<?php i::esc_attr_e('Publicado por');?>" :entity="entity"></entity-owner>
                <mc-share-links  classes="col-12" title="<?php i::esc_attr_e('Compartilhar');?>" text="<?php i::esc_attr_e('Veja este link:');?>"></mc-share-links>
            </div>  
        </aside>
    </mc-container>

    <entity-actions :entity="entity"></entity-actions>
</div>
