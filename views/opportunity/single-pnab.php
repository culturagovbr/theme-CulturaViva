<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->layout = 'entity';

$this->addOpportunityPhasesToJs();
$this->useOpportunityAPI();

$this->import('
    entity-owner    
    mc-breadcrumb
    mc-container
    mc-share-links
    opportunity-subscription-list
    rcv-pnab-subscription
');

$this->breadcrumb = [
    ['label' => i::__('Inicio'), 'url' => $app->createUrl('panel', 'index')],
    ['label' => i::__('Oportunidades'), 'url' => $app->createUrl('panel', 'opportunities')],
    ['label' => $entity->name]
];
?>

<mc-breadcrumb></mc-breadcrumb>
<mc-container class="static-page static-page--update single-pnab">
    <main class="static-page__content single-pnab__content">
        <div class="single-pnab__header">
            <div class="single-pnab__header-content grid-12">
                <div class="single-pnab__img">
                    <img src="<?php $this->asset('img/single-pnab/logo-cultura.png') ?>" />
                </div>

                <div class="single-pnab__title col-6">
                    <h2 class="bold"><?= i::__('Espaço do gestor') ?></h2>
                </div>
            </div>
        </div>

        <div class="single-pnab__content static-page__content">
            <h3 class="bold"><?= i::__('Envio de dados das organizações certificadas por editais') ?></h3>

            <p><?= i::__('Encaminhe as informações sobre as organizações certificadas como Ponto ou Pontão de Cultura
             em seu estado ou município por meio de editais Cultura Viva já realizados. O material enviado passará por 
             análise da SCDC.') ?></p>

            <div class="static-page__section single-pnab__submit">
                <rcv-pnab-subscription :entity="entity"></rcv-pnab-subscription>
            </div>

            <div class="division-section"></div>

            <div v-if="global.auth.isLoggedIn" class="single-pnab__content single-pnab__content-solicitation">
                <opportunity-subscription-list hide-subtitle hide-infos></opportunity-subscription-list>
            </div>
        </div>

    </main>

    <aside>
        <div class="flex-container aside-col">
            <entity-owner classes="col-12" title="<?php i::esc_attr_e('Publicado por'); ?>" :entity="entity"></entity-owner>
            <mc-share-links classes="col-12" title="<?php i::esc_attr_e('Compartilhar'); ?>" text="<?php i::esc_attr_e('Veja este link:'); ?>"></mc-share-links>
        </div>
    </aside>
</mc-container>