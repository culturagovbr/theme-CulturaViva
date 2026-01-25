<?php
/**
 * @var \MapasCulturais\Themes\BaseV2\Theme $this
 * @var \MapasCulturais\App $app
 * @var \MapasCulturais\Entities\Registration $entity
 */

use MapasCulturais\i;

$this->layout = 'registrations';

$this->import('
    mc-breadcrumb
    opportunity-header
    registration-steps
    select-entity
    opportunity-header
    rcv-registration-edition
');

$this->useOpportunityAPI();

$opportunity = $entity->opportunity;

$breadcrumb = [
    ['label' => i::__('Oportunidades'), 'url' => $app->createUrl('panel', 'opportunities')],
    ['label' => $opportunity->firstPhase->name, 'url' => $app->createUrl('opportunity', 'single', [$opportunity->firstPhase->id])],
];

if (!$opportunity->isFirstPhase) {
    $breadcrumb[] = ['label' => $opportunity->name, 'url' => $app->createUrl('opportunity', 'single', [$opportunity->id])];
}

$breadcrumb[] = ['label' => i::__('Formulário')];

$this->breadcrumb = $breadcrumb;

/**
 * @todo registration-form
 */
?>

<div class="main-app registration edit">
    <mc-breadcrumb></mc-breadcrumb>
    <opportunity-header :opportunity="entity.opportunity"></opportunity-header>

    <div class="registration__title">
        <h1>
            <?= 
                $this->controller->action == "registrationEdit" ? 
                    i::__('Formulário de atualização cadastral') : 
                    i::__('Formulário de inscrição') 
            ?>
        </h1>
        <h3>
            <?= $opportunity->name ?>
        </h3>
    </div>

    <rcv-registration-edition editable-fields :entity="entity"></rcv-registration-edition>
</div>