<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>
<div :class="[{'disabled' : Object.keys(countries).length == 0}]" class="field">
    <label><?= i::__('Países') ?></label>

    <mc-multiselect 
        :model="selectedCountries" 
        title="<?php i::_e('Busque ou selecione os países') ?>" 
        :items="countries" 
        placeholder="<?= i::esc_attr__('Busque ou selecione os países') ?>"
        :disabled="Object.keys(countries).length.length == 0" 
        hide-filter
        hide-button>
    </mc-multiselect>

    <mc-tag-list editable :tags="selectedCountries" :labels="countries" classes="agent__background agent__color"></mc-tag-list>
</div>