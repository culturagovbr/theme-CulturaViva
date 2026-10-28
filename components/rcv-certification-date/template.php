<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-icon
    mc-modal
');
?>

<mc-modal classes="rcv-certification-date__modal" :title="text('title')">
    <template #default="modal">
        <p>{{ text('description') }}</p>
        <p v-if="currentDate" class="rcv-certification-date__current">{{ text('currentDate') }} <strong>{{ currentDate }}</strong></p>

        <div class="field rcv-certification-date__field">
            <label for="rcv-certification-date-date"><?= i::__('Nova data da certificação') ?></label>
            <input id="rcv-certification-date-date" type="date" v-model="date" :max="maxDate" :min="minDate">
        </div>

        <div class="field rcv-certification-date__field">
            <label for="rcv-certification-date-password"><?= i::__('Sua senha') ?></label>
            <input id="rcv-certification-date-password" type="password" v-model="password" autocomplete="current-password" @keydown.enter.prevent="confirm(modal)">
            <small class="rcv-certification-date__hint">{{ text('passwordHint') }}</small>
        </div>
    </template>

    <template #actions="modal">
        <button class="button button--text button--md" @click="modal.close()"><?= i::__('Cancelar') ?></button>
        <button class="button button--primary button--md" :disabled="!canConfirm" @click="confirm(modal)"><?= i::__('Confirmar') ?></button>
    </template>

    <template #button="modal">
        <button type="button" class="button button--primary-outline button--icon button--sm rcv-certification-date__button" @click="open(modal)">
            <mc-icon name="edit"></mc-icon> <?= i::__('Alterar data da certificação') ?>
        </button>
    </template>
</mc-modal>
