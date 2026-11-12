<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    home-search

');

?>
<div class="home-header">
    <div class="home-header__content">

        <div class="home-header__main">
            <label class="home-header__title">
                <?= $this->text('title', i::__('Você está no Cadastro Nacional de Pontos e Pontões de Cultura')) ?>
            </label>
            <p class="home-header__description">
                <?= $this->text('description', i::__('Faça parte da Rede Cultura Viva!')) ?>
            </p>
        </div>
        
        <div class="home-header__background">
            <div class="img">
                <img src="<?php $this->asset('img/rcv-home-header/background.png') ?>" />
            </div>
        </div>
    </div>


    <div class="home-header__cards">
        <a v-bind:href="cards[0].link" class="home-header__card">
            <div class="home-header__card-icon--desktop"><img src="<?php $this->asset('img/rcv-home-header/add.png') ?>" alt=""></div>
            <div class="home-header__card-content">
                <div class="home-header__card-content--mobile">
                    <div class="home-header__card-icon"><img src="<?php $this->asset('img/rcv-home-header/add.png') ?>" alt=""></div>
                    <h3 class="home-header__card-title">{{ cards[0].title }}</h3>
                </div>

                <div class="home-header__card-line"></div>

                <h3 class="home-header__card-content--desktop">{{ cards[0].title }}</h3>
                <p class="home-header__card-content-description">{{ cards[0].description }}</p>
            </div>

            <p class="home-header__card-content-description--mobile">{{ cards[0].description }}</p>
        </a>

        <a v-bind:href="cards[1].link" class="home-header__card">
            <div class="home-header__card-icon--desktop"><img src="<?php $this->asset('img/rcv-home-header/check.png') ?>" alt=""></div>
            <div class="home-header__card-content">
                <div class="home-header__card-content--mobile">
                    <div class="home-header__card-icon"><img src="<?php $this->asset('img/rcv-home-header/check.png') ?>" alt=""></div>
                    <h3 class="home-header__card-title">{{ cards[1].title }}</h3>
                </div>

                <div class="home-header__card-line"></div>

                <h3 class="home-header__card-content--desktop">{{ cards[1].title }}</h3>
                <p class="home-header__card-content-description">{{ cards[1].description }}</p>
            </div>

            <p class="home-header__card-content-description--mobile">{{ cards[1].description }}</p>
        </a>

    </div>

    <a href="/mapa/#map">
        <div class="home-header__map">
            <div class="home-header__map-container">
                <div class="home-header__map-img"><img src="<?php $this->asset('img/rcv-home-header/home-map.png') ?>"></div>
                <div class="home-header__map-content">
                    <h3 class="home-header__map-title"><?= $this->text('description', i::__('Mapa da rede')) ?></h3>
                    <p class="home-header__map-description"><?= $this->text('description', i::__('Confira a localização dos Pontos e Pontões de Cultura')) ?></p>
                </div>
            </div>
        </div>
    </a>


    <!-- <home-search></home-search> -->
</div>