<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;


?>
<div class="rcv-point-description">
    <h2><?php i::_e('Apresentação'); ?></h2>

    <div class="rcv-point-description__content">

        <div v-if="entity.shortDescription" class=" col-12">
            <p class="description">{{entity.shortDescription}}</p>
        </div>

        <div v-if="entity.longDescription" class="col-12">
            <p v-if="longDescription" class="long-description">{{entity.longDescription}}</p>
            <button @click="showLongDescription()" class="rcv-point-description__read-more">{{readMoreText}}</button>
        </div>

    </div>

    <!-- <div class="rcv-point-description__activity">
        <h2 class="rcv-point-description__activity-title"><?php i::_e('Em atividade desde'); ?></h2>
        <dl v-if="entity.createTimestamp && entity.createTimestamp._date">
            <dd>
                {{ formatDate(entity.createTimestamp._date) }}
            </dd>
        </dl>
    </div> -->

</div>