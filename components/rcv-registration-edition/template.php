<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-avatar
    mc-container
    registration-actions
    registration-autosave-notification
    registration-form
    rcv-registration-info
    registration-related-agents
    registration-related-space
    registration-related-project
    registration-steps
');

?>

<div class="registration__content">
    <div class="registration__steps">
        <registration-steps :steps="steps" v-model:step-index="stepIndex"></registration-steps>
    </div>

    <mc-container>
        <main class="grid-12">
            <rcv-registration-info :registration="entity" classes="col-12"></rcv-registration-info>

            <section class="section">
                <h2 class="section__title" id="main-info">
                    {{ stepIndex + 1 }}. {{ step.name || text('Informações básicas') }}
                </h2>
                <registration-autosave-notification :registration="entity"></registration-autosave-notification>

                <div class="section__content">
                    <div v-if="entity.opportunity.enableQuotasQuestion" class="card owner">
                        <h3 class="card__title">
                            <?= i::__('Vai concorrer às cotas?') ?>
                        </h3>

                        <div class="card__content">
                            <entity-field :entity="entity" prop="appliedForQuota" :hide-label="true"></entity-field>
                        </div>
                    </div>

                    <registration-related-agents :registration="entity"></registration-related-agents>
                    <registration-related-space :registration="entity"></registration-related-space>
                    <registration-related-project :registration="entity"></registration-related-project>
                </div>
            </section>

            <section class="section" v-if="preview || !entity.opportunity.proponentAgentRelation?.[entity.proponentType] || (entity.agentRelations.coletivo && entity.opportunity.proponentAgentRelation?.[entity.proponentType])">
                <registration-form :registration="entity" :step="step"></registration-form>
            </section>
        </main>

        <aside>
            <registration-actions :registration="entity" :steps="steps" v-model:step-index="stepIndex" :editable-fields="editableFields"></registration-actions>
        </aside>
    </mc-container>
</div>
