<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
use MapasCulturais\i;

$this->import('
    mc-confirm-button
    mc-icon
    mc-popover
');

?>
<div class="rcv-importer-logs">
    <mc-popover v-if="(entity.status == 10 || entity.status == 2) || (importStatus?.status > 0)" openside="down-right">
        <template #button="popover">
            <slot name="button">
                <a v-if="importStatus?.status && importStatus?.status !== 0" :class="`button button-text rcv-importer-logs__message_${importStatus.status}`" @click="popover.toggle()">
                    [{{importStatus?.message}}]
                </a>

                <span v-else>&nbsp;</span>
            </slot>
        </template>
        <template #default="{close}" class="grid-12">
            <div class="col-12 rcv-importer-logs__content">
                <p><b>Status:</b> {{getStatusLabel(importStatus?.status)}}</p>
                <p><b>Atualização:</b> {{importStatus?.timestamp}}</p>
                <p><a :href="getUrlLogFile(entity.id)" target="_blank">Visualizar log</a></p>
            </div>
        </template>
    </mc-popover>

    <span v-else>&nbsp;</span>
</div>