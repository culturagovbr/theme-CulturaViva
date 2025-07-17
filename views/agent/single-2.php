<?php

use MapasCulturais\i;

$this->layout = 'entity';

$this->import('
    entity-actions
    mc-breadcrumb
    rcv-agent-tabs
    rcv-entity-header
');

$label = $this->isRequestedEntityMine() ? i::__('Meus agentes') : i::__('Agentes');
$this->breadcrumb = [
    ['label' => i::__('Inicio'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => $label, 'url' => $app->createUrl('search', 'agents')],
    ['label' => $entity->name, 'url' => $app->createUrl('agent', 'single', [$entity->id])],
];
?>

<div class="main-app">
    <mc-breadcrumb></mc-breadcrumb>

    <div class="entity-header__content">
        <rcv-entity-header :show-field="true" :entity="entity"></rcv-entity-header>
    </div>

    <rcv-agent-tabs :entity="entity"></rcv-agent-tabs>

    <entity-actions :entity="entity"></entity-actions>
</div>