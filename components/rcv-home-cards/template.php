<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-link
');
?>
<div class="rcv-home-cards">
    <div class="rcv-home-cards__container">

        <a href="https://www.gov.br/culturaviva/pt-br">
            <div class="rcv-home-cards__card">
                <div class="rcv-home-cards__card-content">
                    <div class="rcv-home-cards__card-icon"><img src="<?php $this->asset('img/rcv-home-cards/rcv-logo.png') ?>" alt=""></div>
                    <h3 class="rcv-home-cards__card-title"><?= $this->text('description', i::__('Portal Cultura Viva')) ?></h3>
                    <p class="rcv-home-cards__card-description"><?= $this->text('description', i::__('Fique por dentro das últimas notícias  e informações sobre a Cultura Viva')) ?></p>
                </div>
            </div>
        </a>

        <a href="https://mapa.cultura.gov.br" target="_blank">
            <div class="rcv-home-cards__card">
                <div class="rcv-home-cards__card-content">
                    <div class="rcv-home-cards__card-icon"><img src="<?php $this->asset('img/rcv-home-cards/group.png') ?>" alt=""></div>
                    <h3 class="rcv-home-cards__card-title"><?= $this->text('description', i::__('Mapa da Cultura')) ?></h3>
                    <p class="rcv-home-cards__card-description"><?= $this->text('description', i::__('Encontre editais e oportunidades do Ministério da Cultura')) ?></p>
                </div>
            </div>
        </a>
    </div>
</div>