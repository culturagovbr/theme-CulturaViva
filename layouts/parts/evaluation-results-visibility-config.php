<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use CulturaViva\EvaluationResultsVisibility;

?>

<div class="col-12 grid-12" v-if="phase.evaluationMethodConfiguration && !phase.isAppealPhase">
    <div class="col-12" v-if="phase.evaluationMethodConfiguration.publishEvaluationDetails">
        <entity-field :entity="phase.evaluationMethodConfiguration" prop="<?= EvaluationResultsVisibility::META_ONLY_FINAL ?>" type="checkbox" :autosave="300"></entity-field>
    </div>
    <div class="col-12">
        <entity-field :entity="phase.evaluationMethodConfiguration" prop="<?= EvaluationResultsVisibility::META_ADMIN_VIEW ?>" type="checkbox" :autosave="300"></entity-field>
    </div>
</div>
