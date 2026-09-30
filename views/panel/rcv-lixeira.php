<?php

use MapasCulturais\i;

$this->import('
    mc-icon
    rcv-registration-trash
');
?>

<div class="panel-page">
    <header class="panel-page__header">
        <div class="panel-page__header-title">
            <div class="title">
                <div class="title__icon primary__background">
                    <mc-icon name="trash"></mc-icon>
                </div>
                <h1 class="title__title"><?= i::_e('Lixeira de inscrições') ?></h1>
            </div>
        </div>
        <p class="panel-page__header-subtitle">
            <?= i::_e('Envia inscrições do Cadastro Nacional para a lixeira pelo número e restaura as que foram enviadas por esta ferramenta.') ?>
        </p>
    </header>

    <rcv-registration-trash></rcv-registration-trash>
</div>
