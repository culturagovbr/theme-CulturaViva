<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    request-agent-avatar
')
?>

<div :class="['registration-info', classes]">
    <section class="registration-info__toggle">
        <header @click="toggleInfo()" :class="{ 'registration-info__toggle-header--active': showInfo }" class="registration-info__toggle-header">
            <h3 class="registration-info__title bold"> <?= $this->text('registration_title', i::__('Informações do cadastro'))?> </h3>
            <mc-icon :name="showInfo ? 'arrowPoint-up' : 'arrowPoint-down'"></mc-icon>
        </header>
        <div v-if="showInfo" class="registration-info__toggle-content">    
            <div class="section__content">
                <div class="registration-info__content">
                    <div class="registration-info__data">
                        <h5 class="registration-info__data__title semibold"> <?= i::__('Cadastro') ?> </h5>
                        <h4 v-if="registration.number" class="registration-info__data__info bold"> {{registration.number}} </h4>
                        <h4 v-if="!registration.number" class="registration-info__data__info bold"> mc-000000000 </h4>
                    </div>
                    <div class="registration-info__data">
                        <h5 class="registration-info__data__title semibold"> <?= i::__('Data') ?> </h5>
                        <h4 class="registration-info__data__info bold">{{registration?.createTimestamp?.date('2-digit year')}}</h4>
                    </div>
                    <div v-if="registration.category" class="registration-info__data">
                        <h5 class="registration-info__data__title semibold"> <?= i::__('Categoria') ?> </h5>
                        <h4 class="registration-info__data__info bold">{{registration.category}}</h4>
                    </div>
                </div>

                <div class="card owner">
                    <div class="card__content">
                        <div class="owner">
                            <mc-avatar v-if="!registration.opportunity.requestAgentAvatar" :entity="registration.owner" size="small"></mc-avatar>
                            <request-agent-avatar v-if="registration.opportunity.requestAgentAvatar" :entity="registration"></request-agent-avatar>
                            <div class="owner__content">
                                <div class="owner__content--title">
                                    <h3 class="card__title">
                                        <?= i::__('Responsável pela organização') ?>
                                    </h3>
                                    <div class="owner__name">
                                        {{registration.owner.name}}
                                    </div>
                                </div>
                                <div v-if="registration.opportunity.requestAgentAvatar" class="card__mandatory">
                                    <div class="obrigatory"> <?= i::__('*obrigatório') ?> </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card collective" v-if="registration.agentRelations.coletivo?.length > 0">
                    <div class="card__content" v-for="agentCollective in registration.agentRelations.coletivo">
                        <div class="collective">
                            <mc-avatar :entity="agentCollective?.agent" size="small"></mc-avatar>
                            <div class="collective__content">
                                <div class="collective__content--title">
                                    <h3 class="card__title">
                                        <?= i::__('Organização') ?>
                                    </h3>
                                    <div class="collective__name">
                                        {{agentCollective.agent.nomeCompleto}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>