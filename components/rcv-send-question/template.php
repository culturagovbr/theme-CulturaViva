<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>
<div class="rcv-send-question">
    <small>Dúvidas? Entre em contato.</small>

    <mc-modal classes="rcv-send-question__modal" button-label="Enviar mensagem" :title="stepTitle" ref="updateModal" @close="closeModal()">
        <template #default="modal">
    
            <div class="rcv-send-question__modal-content">
                <div class="field">
                    <label><?= i::__('Nome') ?></label>
                    <input type="text" v-model="name">
                </div>

                <div class="field">
                    <label><?= i::__('E-mail') ?></label>
                    <input type="email" v-model="email">
                </div>
    
                <div class="grid-12">
                    <div class="field col-12">
                        <label>Descreva o motivo do seu contato</label>
                        <textarea v-model="question"></textarea>
                    </div>
                </div>
            </div>
    
        </template>
    
        <template #button="modal">
            <button class="button button--blue rcv-send-question__button" @click="modal.open()">
                <?= i::__("Enviar mensagem") ?>
            </button>
        </template>
    
        <template #actions="modal">
            <button class="button button--md button--primary" :class="[{'disabled' : !isFormValid}]" @click="sendMessage(modal)">
                <?= i::__('Enviar') ?>
            </button>
        </template>
    </mc-modal>
</div>