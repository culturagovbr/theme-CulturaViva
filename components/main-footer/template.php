<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    theme-logo
');
$config = $app->config['social-media'];
$term_url = $app->createUrl('site', 'termoAdesao');
?>
<?php $this->applyTemplateHook("main-footer", "before") ?>
<div v-if="globalState.visibleFooter" class="main-footer">
    <?php $this->applyTemplateHook("main-footer", "begin") ?>
    <div class="main-footer__content">
        <?php $this->applyTemplateHook("main-footer-logo", "before") ?>
        <div class="main-footer__support">
            <?php $this->part('footer-support-message') ?>
        </div>
        <div class="main-footer__content--logo">
            <div class="main-footer__content--logo-img">
                <theme-logo href="<?= $app->createUrl('site', 'index') ?>"></theme-logo>
            </div>

            <div class="main-footer__content--logo-share">
                <?php foreach ($config as $conf) : ?>
                    <a target="_blank" href="<?= $conf['link'] ?>">
                        <mc-icon name='<?= $conf['title'] ?>'></mc-icon>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php $this->applyTemplateHook("main-footer-logo", "after") ?>

        <?php $this->applyTemplateHook("main-footer-links", "before") ?>
        <div class="main-footer__content--links">
            <?php $this->applyTemplateHook("main-footer-links", "begin") ?>

            <ul class="main-footer__content--links-group">
                <li>
                    <a><?php i::_e("Acesse"); ?></a>
                </li>

                <li v-if="global.enabledEntities.opportunities">
                    <a href="<?= $app->createUrl('search', 'opportunities') ?>">
                        <mc-icon name="opportunity"></mc-icon> <?php i::_e('Editais SCDC'); ?>
                    </a>
                </li>

                <li v-if="global.enabledEntities.events">
                    <a href="<?= $app->createUrl('search', 'agents') ?>">
                        <mc-icon name="pin"></mc-icon> <?php i::_e('Mapa de Pontos e Pontões'); ?>
                    </a>
                </li>

                <li v-if="global.enabledEntities.spaces">
                    <a href="<?= $app->createUrl('search', 'spaces') ?>">
                        <mc-icon name="space"></mc-icon> <?php i::_e('Espaços'); ?>
                    </a>
                </li>

                <li v-if="global.enabledEntities.agents">
                    <a href="https://www.gov.br/culturaviva/pt-br">
                        <?php // Defeso eleitoral (ASCOM): selo no mesmo cinza das demais logos.
                              // Reversível: basta voltar para 'img/rcv-footer/selo-cultura.png'. ?>
                        <img src="<?php $this->asset('img/rcv-footer/selo-cultura-defeso.png') ?>" alt=""/>
                        <?php i::_e('Portal Cultura Viva'); ?>
                    </a>
                </li>

                <li v-if="global.enabledEntities.projects">
                    <a href="https://mapa.cultura.gov.br/">
                        <?php // Defeso eleitoral (ASCOM): símbolo do Mapa da Cultura substituído pela
                              // letra "M" neutra, o mesmo desenho do favicon de defeso do theme-MapaMinC.
                              // Reversível: basta voltar para 'img/rcv-footer/icone-cultura.png'. ?>
                        <img src="<?php $this->asset('img/rcv-footer/icone-cultura-defeso.svg') ?>" alt=""/>
                        <?php i::_e('Mapa da Cultura'); ?>
                    </a>
                </li>
            </ul>

            <ul class="main-footer__content--links-group">
                <li>
                    <a href="<?= $app->createUrl('panel', 'index') ?>"><?php i::_e('Sobre'); ?></a>
                </li>
                <li>
                    <a href="<?= $app->createUrl('site', 'comissoes') ?>"><?php i::_e('Comissão de Certificação'); ?></a>
                </li>
                <li>
                    <a href="<?= $app->createUrl('site', 'certificados') ?>"><?php i::_e('Critérios para certificação'); ?></a>
                </li>
                <li>
                    <a href="<?= $app->createUrl('site', 'funcoes-do-cadastro') ?>"><?php i::_e('Funções do Cadastro'); ?></a>
                </li>
                <li>
                    <a href="<?= $app->createUrl('site', 'perguntas-frequentes') ?>"><?php i::_e('Perguntas Frequentes'); ?></a>
                </li>
                <!-- <?php if (!($app->user->is('guest'))) : ?>
                    <li>
                        <a href="<?= $app->createUrl('auth', 'logout') ?>"><?php i::_e('Sair') ?></a>
                    </li>
                <?php endif; ?> -->
            </ul>

            <ul class="main-footer__content--links-group">
                <li>
                    <a><?php i::_e('Ajuda e privacidade'); ?></a>
                </li>

                <!-- <li>
                    <a href="<?= $app->createUrl('faq') ?>"><?php i::_e('Dúvidas frequentes'); ?></a>
                </li> -->

                <li>
                    <a href=""><?php i::_e('Contato por e-mail'); ?></a><a href="mailto:suporte.culturaviva@cultura.gov.br" class="mail"><?php i::_e('suporte.culturaviva@cultura.gov.br'); ?></a>
                </li>
                <li>
                    <a href="<?=$term_url?>"><?php i::_e('Termo de adesão à política nacional de cultura viva'); ?></a>
                </li>

                <?php if (count($app->config['module.LGPD']) > 0): ?>
                    <?php foreach ($app->config['module.LGPD'] as $slug => $cfg) : ?>
                        <li>
                            <a href="<?= $app->createUrl('lgpd', 'view', [$slug]) ?>"><?= $cfg['title'] ?></a>
                        </li>
                    <?php endforeach ?>
                <?php endif; ?>
            </ul>
            <?php $this->applyTemplateHook("main-footer-links", "end") ?>
        </div>
        <?php $this->applyTemplateHook("main-footer-links", "after") ?>
    </div>
    <?php $this->applyTemplateHook("main-footer-reg", "before") ?>
    <div class="main-footer__reg">
        <?php $this->applyTemplateHook("main-footer-reg", "begin") ?>
        <div class="main-footer__reg-content">
            <p>
                <?php i::_e("plataforma criada pela comunidade") ?>
                <span class="mapas"> <mc-icon name="map"></mc-icon><?php i::_e("mapas culturais"); ?> </span>
                <?php i::_e("e desenvolvida por "); ?><strong>hacklab<span style="color: red">/</span></strong>
            </p>

            <a class="link" href="https://github.com/mapasculturais">
                <?php i::_e("Conheça o repositório") ?>
                <mc-icon name="github"></mc-icon>
            </a>
        </div>
        <?php $this->applyTemplateHook("main-footer-reg", "end") ?>
    </div>
    <?php $this->applyTemplateHook("main-footer-reg", "after") ?>
    <?php $this->applyTemplateHook("main-footer", "end") ?>
</div>
<?php $this->applyTemplateHook("main-footer", "after") ?>