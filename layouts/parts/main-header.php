<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-header-menu
    mc-header-menu-user
    mc-icon
    mc-messages 
    theme-logo 
');

?>

<?php $this->applyTemplateHook('main-header', 'before') ?>
<header class="main-header" id="main-header">
    <?php $this->applyTemplateHook('main-header', 'begin') ?>

    <!-- Header superior -->
    <div class="main-header__superior">
        <div class="main-header__superior-content">
            <a href="https://www.gov.br/culturaviva/pt-br" class="main-header__superior-return">
                <mc-icon class="main-header__superior-content-arrow-left" name="arrow-left-return"></mc-icon>
                <span class="main-header__superior-content-link"><?php i::_e('Voltar para o') ?>
                    <span class="main-header__superior-content-arrow-left">
                        <?php i::_e('Portal Cultura Viva') ?>
                    </span>
                </span>
            </a>
            <a href="https://mapa.cultura.gov.br/" class="main-header__superior-progress">
                <span class="main-header__superior-content-link"><?php i::_e('Ir para o ') ?>
                    <span class="main-header__superior-content-arrow-right">
                        <?php i::_e('Mapa da Cultura') ?>
                    </span>
                    <mc-icon class="main-header__superior-content-arrow-right" name="arrow-right-return"></mc-icon>
                </span>
            </a>
        </div>
    </div>

    <div class="main-header__content">

        <?php $this->applyTemplateHook('mc-header-menu', 'before') ?>
        <mc-header-menu>

            <!-- Logo -->
            <template #logo>
                <theme-logo href="<?= $app->createUrl('site', 'index') ?>"></theme-logo>
            </template>

            <!-- Menu principal -->
            <template #default>
                <?php $this->applyTemplateHook('mc-header-menu', 'begin') ?>

                <?php $this->applyTemplateHook('mc-header-menu-home', 'before') ?>
                <li>
                    <?php $this->applyTemplateHook('mc-header-menu-home', 'begin') ?>
                    <a href="<?= $app->createUrl('site', 'index') ?>" class="mc-header-menu--item home">
                        <span class="icon"> <mc-icon name="home"></mc-icon> </span>
                        <p class="label"> <?php i::_e('Inicio') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-home', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-home', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-register', 'before') ?>
                <li>
                    <?php $this->applyTemplateHook('mc-header-menu-register', 'begin') ?>
                    <a href="/novo-cadastro" class="mc-header-menu--item register">
                        <span class="icon"><img src="<?php $this->asset('img/rcv-main-header/frame.png') ?>" alt=""></span>
                        <p class="label"> <?php i::_e('Cadastro') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-register', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-register', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'before') ?>
                <li>
                    <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'agents') ?>#map" class="mc-header-menu--item agent">
                        <span class="icon"> <mc-icon name="pin"> </span>
                        <p class="label"> <?php i::_e('Mapa') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-opportunity', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-spaces', 'before') ?>
                <li v-if="global.enabledEntities.spaces">
                    <?php $this->applyTemplateHook('mc-header-menu-spaces', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'spaces') ?>" class="mc-header-menu--item space">
                        <span class="icon"> <mc-icon name="space"> </span>
                        <p class="label"> <?php i::_e('Espaços') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-spaces', 'end') ?>
                </li>
                <?php $this->applyTemplateHook('mc-header-menu-spaces', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-events', 'before') ?>
                <!-- <li v-if="global.enabledEntities.events">
                    <?php $this->applyTemplateHook('mc-header-menu-events', 'begin') ?>
                    <a href="<?= $app->createUrl('search', 'events') ?>" class="mc-header-menu--item event">
                        <span class="icon"> <mc-icon name="event"> </span>
                        <p class="label"> <?php i::_e('Agenda') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-events', 'end') ?>
                </li> -->
                <?php $this->applyTemplateHook('mc-header-menu-events', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu-events', 'before') ?>
                <!-- <li v-if="global.enabledEntities.events">
                    <?php $this->applyTemplateHook('mc-header-menu-events', 'begin') ?>
                    <a href=" " class="mc-header-menu--item event">
                        <span class="icon"><mc-icon name="graph-bar"></mc-icon></span>
                        <p class="label"> <?php i::_e('Indicadores') ?> </p>
                    </a>
                    <?php $this->applyTemplateHook('mc-header-menu-events', 'end') ?>
                </li> -->
                <?php $this->applyTemplateHook('mc-header-menu-events', 'after') ?>

                <?php $this->applyTemplateHook('mc-header-menu', 'end') ?>
            </template>

        </mc-header-menu>
        <?php $this->applyTemplateHook('mc-header-menu', 'after') ?>

        <div class="main-header__buttons">
            <?php $this->applyTemplateHook('mc-header-menu-user', 'before') ?>
            <?php if ($app->user->is('guest')): ?>
                <!-- Botão login -->
                <a href="<?= $app->createUrl('auth') ?>?redirectTo=<?= $_SERVER['REQUEST_URI'] ?>" class="logIn">
                    <?php i::_e('Entrar') ?>
                </a>
            <?php else: ?>
                <!-- Menu do usuário -->
                <mc-header-menu-user></mc-header-menu-user>
            <?php endif; ?>
            <?php $this->applyTemplateHook('mc-header-menu-user', 'after') ?>
        </div>

    </div>

    <?php $this->applyTemplateHook('main-header', 'end') ?>
</header>
<?php $this->applyTemplateHook('main-header', 'after') ?>

<mc-messages></mc-messages>