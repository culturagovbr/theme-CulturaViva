<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
use MapasCulturais\i;
?>
<div class="home-developers"> 
    <div class="home-developers__content">
        <span class="dev-icon"><mc-icon name="code"></mc-icon></span>
        <label class="home-developers__content--title"><?= $this->text('title',i::__('Sabia que o <b>Mapas Culturais</b> é um software livre?')) ?></label>
        <div class="home-developers__content--description">
            <?= $this->text('description',i::__('Você pode usar a API para acessar os dados públicos no nosso banco de dados e utilizá-los para desenvolver aplicações externas. Além disso, também é possível contribuir com o <u>software livre Mapas Culturais</u> direto no GitHub.')) ?>
        </div>
        <div class="home-developers__content--link">
            <a class="link" href="https://github.com/mapasculturais"> 
                <?php i::_e("Conheça o repositório") ?>
                <mc-icon name="github"></mc-icon>
            </a>
        </div>
    </div>
</div>