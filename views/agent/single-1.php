<?php

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    agent-data-1
    complaint-suggestion
    entity-actions
    entity-admins
    entity-files-list
    entity-gallery
    entity-gallery-video
    entity-header
    entity-links
    entity-list
    entity-location
    entity-owner
    entity-related-agents
    entity-seals
    entity-social-media
    entity-terms
    mc-breadcrumb
    mc-container
    mc-share-links
    mc-tab
    mc-tabs
    opportunity-list
');

$label = $this->isRequestedEntityMine() ? i::__('Meus agentes') : i::__('Agentes');
$this->breadcrumb = [
    ['label' => i::__('Inicio'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => $label, 'url' => $app->createUrl('search', 'agents')],
    ['label' => $entity->name, 'url' => $app->createUrl('agent', 'single', [$entity->id])],
];
?>

<div class="main-app single-1">
    <mc-breadcrumb></mc-breadcrumb>
    <entity-header :entity="entity"></entity-header>

    <mc-container>
        <main class="grid-12">
            <div class="grid-12 col-12">
                <agent-data-1 :entity="entity" :always-show-title="false" show-only-public-data></agent-data-1>
                <complaint-suggestion :entity="entity" classes="col-12" show-only-send-message-modal></complaint-suggestion>
            </div>
            <?php $this->applyTemplateHook('single1-entity-info-taxonomie-area', 'before') ?>
            <entity-terms :entity="entity" hide-required classes="col-12" taxonomy="area" title="<?php i::esc_attr_e('Áreas de atuação'); ?>"></entity-terms>
            <?php $this->applyTemplateHook('single1-entity-info-taxonomie-area', 'after') ?>

            <?php $this->applyTemplateHook('single1-entity-info-taxonomie-funcao', 'before') ?>
            <entity-terms :entity="entity" hide-required taxonomy="funcao" classes="col-12" title="<?php i::_e('Funções'); ?>"></entity-terms>
            <?php $this->applyTemplateHook('single1-entity-info-taxonomie-funcao', 'after') ?>

            <complaint-suggestion :entity="entity" classes="col-12" show-only-complaint-modal></complaint-suggestion>
        </main>
    </mc-container>

    <entity-actions :entity="entity"></entity-actions>
</div>