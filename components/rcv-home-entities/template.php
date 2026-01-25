<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-link
');

$url = $app->createUrl('opportunity', 'single', [$app->config['rcv.pnabOpportunityId']]);
?>
<div class="home-entities">
    
    <div class="home-entities__content">
        <div class="home-entities__content--header">
            <label class="title">
                <?= $this->text('title', i::__('Aqui você encontra informações da Rede Cultura Viva!')) ?>
            </label>
        </div>
        
        <div class="home-entities__content--cards">
            <div class="card">
                <div class="card__left">
                    <div class="card__left--content">
                        <div class="card__left--content-icon opportunity__background">
                            <mc-icon name="opportunity"></mc-icon>
                            <img src="<?php $this->asset('img/rcv-home-entities/ellipsered.png') ?>" alt=""></img>
                        </div>                        
                        <div class="card__left--content-title">
                            <label class="title">
                                <?= i::__('Política Nacional Cultura Viva') ?>
                            </label>
                        </div>
                    </div>
                    <div class="card__left--img">
                        <img src="<?php $this->asset('img/rcv-home-entities/banner-home1.png') ?>" />
                    </div>
                </div>
                <div class="card__right">
                    <p><?= $this->text('opportunities', i::__('Fique por dentro da Cultura Viva, o Cadastro Nacional e tudo o que precisa saber para entrar na rede!')) ?></p>
                    <mc-link route="site/pncv" class="button button--icon button--sm opportunity__color">
                        <?= i::__('Acesse')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div class="card">
                <div class="card__left">
                    <div class="card__left--content">
                        <div class="card__left--content-icon event__background">
                            <mc-icon name="event"></mc-icon>
                            <img src="<?php $this->asset( 'img/rcv-home-entities/ellipseyellow.png' ) ?>" alt=""></img>
                        </div>                        
                        <div class="card__left--content-title">
                            <label class="title">
                                <?= i::__('Certificados e Selos') ?>
                            </label>
                        </div>
                    </div>
                    <div class="card__left--img">
                        <img src="<?php $this->asset('img/rcv-home-entities/banner-home2.png') ?>" />
                    </div>
                </div>
                <div class="card__right">
                    <p><?= $this->text('events', i::__('Emita seu certificado de Ponto ou Pontão de Cultura e conheça os demais selos da Cultura Viva.')) ?></p>
                    <mc-link route="site/certificados" class="button button--icon button--sm event__color">
                        <?= i::__('Acesse')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div class="card">
                <div class="card__left">
                    <div class="card__left--content">
                        <div class="card__left--content-icon space__background">
                            <mc-icon name="space"></mc-icon>
                            <img src="<?php $this->asset( 'img/rcv-home-entities/ellipseblue.png' ) ?>"  alt=""></img>
                        </div>                        
                        <div class="card__left--content-title">
                            <label class="title">
                                <?= i::__('Comissões') ?>
                            </label>
                        </div>
                    </div>
                    <div class="card__left--img">
                        <img src="<?php $this->asset('img/rcv-home-entities/banner-home3.png') ?>" />
                    </div>
                </div>
                <div class="card__right">
                    <p><?= $this->text('spaces', i::__('Informe-se sobre a Comissão Nacional de Pontos e Pontões de Cultura, a Comissão de Certificação e a Comissão de Gestão do Cadastro.')) ?></p>
                    <mc-link route="site/comissoes" class="button button--icon button--sm space__color">
                        <?= i::__('Acesse')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <div class="card">
                <div class="card__left">
                    <div class="card__left--content">
                        <div class="card__left--content-icon agent__background">
                            <mc-icon name="agent-2"></mc-icon>
                            <img src="<?php $this->asset( 'img/rcv-home-entities/ellipsepurple.png' ) ?>"  alt=""></img>
                        </div>                        
                        <div class="card__left--content-title">
                            <label class="title">
                                <?= i::__('Redes de Pontos e Pontões') ?>
                            </label>
                        </div>
                    </div>
                    <div class="card__left--img">
                        <img src="<?php $this->asset('img/rcv-home-entities/banner-home4.png') ?>" />
                    </div>
                </div>
                <div class="card__right">
                    <p><?= $this->text('agents', i::__('Conheça os 42 Pontões selecionados no Edital Nº. 9/2023 para atuar na rede Cultura Viva nos territórios e por temáticas.')) ?></p>
                    <mc-link route="site/redes-pontos-pontoes" class="button button--icon button--sm agent__color">
                        <?= i::__('Acesse')?>
                        <mc-icon name="access"></mc-icon>
                    </mc-link>
                </div>
            </div>

            <?php if ( !$app->config['rcv.disablePnabOpportunity'] ) : ?>
                <div class="card">
                    <div class="card__left">
                        <div class="card__left--content">
                            <div class="card__left--content-icon project__background">
                                <mc-icon name="project"></mc-icon>
                                <img src="<?php $this->asset( 'img/rcv-home-entities/ellipse-black.png' ) ?>"  alt=""></img>
                            </div>                        
                            <div class="card__left--content-title">
                                <label class="title">
                                    <?= i::__('Espaço do gestor') ?>
                                </label>
                            </div>
                        </div>
                        <div class="card__left--img">
                            <img src="<?php $this->asset( 'img/rcv-home-entities/banner-home5.png' ) ?>" />
                        </div>
                    </div>
                    <div class="card__right">
                        <p><?= $this->text('projects', i::__('Envie informações sobre os Pontos ou Pontões de Cultura certificados em seu estado ou município por meio de editais já realizados.')) ?></p>
                        <a href="<?= $url ?>" class="button button--icon button--sm project__color">
                            <?= i::__('Acesse') ?>
                            <mc-icon name="access"></mc-icon>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$app->config['rcv.disableDataDashboard']) : ?>
            <div class="card">
                <div class="card__left">
                    <div class="card__left--content">
                        <div class="card__left--content-icon space__background">
                            <mc-icon name="indicator"></mc-icon>
                        </div>                        
                        <div class="card__left--content-title">
                            <label class="title">
                                <?= i::__('Indicadores') ?>
                            </label>
                        </div>
                    </div>
                    <div class="card__left--img">
                        <img src="<?php $this->asset('img/rcv-home-entities/banner-home6.png') ?>" />
                    </div>
                </div>
                <div class="card__right">
                    <p><?= $this->text('spaces', i::__('Confira informações sobre a distribuição geográfica dos Pontos e Pontões de Cultura certificados, além suas áreas de atuação.')) ?></p>
                    <a href="<?= $url = $app->createUrl('metabase', 'dashboard'); ?>/public" class="button button--icon button--sm project__color">
                        <?= i::__('Acesse') ?>
                        <mc-icon name="access"></mc-icon>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
