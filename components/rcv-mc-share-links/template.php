<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
?>
<div :class="classes" class="mc-share-links">
    <!-- Botão principal -->
    <button
        class="mc-share-button bold"
        :title="title"
        @click="toggleShareOptions"
    >
        <slot name="title">
            {{ title }}
        </slot>

    </button>

    <!-- Modal de opções de compartilhamento -->
    <div v-if="showOptions" class="mc-share-options">
        <a
            class="fa fa-twitter mc-share-option"
            title="Compartilhar no Twitter"
            @click="share('twitter')">
            <mc-icon name="twitter"></mc-icon>
        </a>
        <a
            class="fa fa-facebook mc-share-option"
            title="Compartilhar no Facebook"
            @click="share('facebook')">
            <mc-icon name="facebook"></mc-icon>
        </a>
        <a
            class="fa fa-whatsapp mc-share-option"
            title="Compartilhar no WhatsApp"
            @click="share('whatsapp')">
            <mc-icon name="whatsapp"></mc-icon>
        </a>
        <a
            class="fa fa-telegram mc-share-option"
            title="Compartilhar no Telegram"
            @click="share('telegram')">
            <mc-icon name="telegram"></mc-icon>
        </a>
    </div>
</div>