<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>

<mc-modal classes="rcv-save-later-modal" button-label="label do botão" title="<?= i::__('Deseja continuar sua inscrição depois?') ?>">
    <template #default="modal">
        <div class="rcv-save-later-modal modal-options grid-12">
            <p class="col-12"><?= i::__('Seu cadastro foi salvo em rascunho. Para retornar, vá ao <b><u>Painel de Controle</u></b> e acesse 
                <b><u>Meus Cadastros</u></b>. Na aba de <b>Não enviadas</b>, você encontrará seu cadastro para continuar.') ?>
            </p>
        </div>

    </template>

    <template #button="modal">
        <button @click="modal.open()" class="button button--sm button--large button--primary">
            <?= i::__("Salvar para depois") ?>
        </button>
    </template>


    <template #actions="modal">
        <button class="button-cancel button button--md button--primary" @click="modal.close()">
            <?= i::__('Cancelar') ?>
        </button>

        <button class="button button--md button--primary" @click="save(modal)">
            <?= i::__('Continuar depois') ?>
        </button>
    </template>
</mc-modal>