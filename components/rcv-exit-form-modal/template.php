<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>

<mc-modal classes="rcv-exit-form-modal" button-label="label do botão" title="<?= i::__('Falta tão pouco! Quer mesmo sair?') ?>">
    <template #default="modal">
        <div class="rcv-exit-form-modal modal-options grid-12">
            <p class="col-12"><?= i::__('As informações preenchidas não serão salvas. Se você sair agora vai precisar fazer a inscrição do começo de novo quando voltar') ?>
            </p>
        </div>

    </template>

    <template #button="modal">
        <button @click="modal.open()" class="button button--sm button--large button--primary">
            <?= i::__("Sair") ?>
        </button>
    </template>


    <template #actions="modal">
        <button class="button-cancel button button--md button--primary" @click="modal.close()">
            <?= i::__('Cancelar') ?>
        </button>

        <button class="button button--md button--primary" @click="exit(modal)">
            <?= i::__('Sair') ?>
        </button>
    </template>
</mc-modal>