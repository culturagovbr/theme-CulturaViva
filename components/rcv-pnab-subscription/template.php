<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
?>

<div class="grid-12 rcv-pnab-subscription">
    <div class="col-12 rcv-pnab-subscription__wrapper">
        <button v-if="!global.auth.isLoggedIn" @click="redirectLogin()" class="button button--primary button--xbg" ><?= i::__('Acessar ou criar conta') ?></button>
        <button v-if="!processing && global.auth.isLoggedIn" @click="subscribe()" class="button button--primary button--xbg" ><?= i::__('Enviar dados') ?></button>
        <mc-loading :condition="processing" class="col-12"> <?= i::__('Direcionando para o formulário') ?></mc-loading>
    </div>
</div>