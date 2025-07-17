<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>

<div class="field" :class="fieldClass">
    <label v-if="!hideLabels"> <?php i::_e('Ações estruturantes') ?></label>
    <mc-multiselect :model="selectedActions" :placeholder="actionsPlaceholder" :items="actions" hide-filter hide-button></mc-multiselect>
    <mc-tag-list v-if="!hideTags" editable :tags="selectedActions" classes="agent__background agent__color"></mc-tag-list>
</div>

<div class="field" :class="fieldClass">
    <label v-if="!hideLabels"> <?php i::_e('Outras ações estruturantes') ?></label>
    <mc-multiselect :model="selectedOtherActions" :placeholder="otherActionsPlaceholder" :items="otherActions" hide-filter hide-button></mc-multiselect>
    <mc-tag-list v-if="!hideTags" editable :tags="selectedOtherActions" classes="agent__background agent__color"></mc-tag-list>
</div>