<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-card
    mc-icon    
');
?>
<mc-card v-if="opportunity && canSee('agentsSummary')" :class="classes">
    <template #title>
        <h3><?= i::__("Nome do Ponto ou Pontão") ?></h3>
    </template>
    <template #content>
        <div class="mc-linked-entity">
            <div class="mc-linked-entity__img">
                <mc-icon name="project"></mc-icon>
            </div>
            <h5 v-if="entity.agentsData?.coletivo?.name">{{entity.agentsData?.coletivo.name}}</h5>
            <h5 v-else><?= i::__("Nome do Ponto ou Pontão não informado") ?></h5>
        </div>
    </template>
</mc-card>
    