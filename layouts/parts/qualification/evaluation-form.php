<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

 use MapasCulturais\i;

$this->import('
  qualification-evaluation-form
');
?>

<qualification-evaluation-form :entity="entity" :form-data="formData">
    <template #non-eliminatory>
        <label><?php i::_e('ATENÇÃO: Para ser habilitado, o proponente pode não atender até 4 critérios não eliminatórios.') ?></label>
    </template>
</qualification-evaluation-form>