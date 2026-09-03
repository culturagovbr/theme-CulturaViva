<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('entity-field');

?>
<div v-if="!deactivated" class="rcv-stale-evaluations-config grid-12">
    <div class="field col-12">
        <div class="field__group">
            <label class="field__checkbox">
                <input type="checkbox" :checked="armed" @change="onToggle($event)" />
                <span><?= i::__('Redistribuir avaliações iniciadas paradas há dias') ?></span>
            </label>
        </div>
    </div>

    <entity-field v-if="armed"
        :entity="entity"
        :prop="daysProp"
        :min="min"
        :max="max"
        :autosave="300"
        classes="col-3 sm:col-6"
        @change="onDaysChange">
        <?= i::__('Dias parado como iniciada') ?> <span class="required">*<?php i::_e('obrigatório') ?></span>
    </entity-field>
</div>
