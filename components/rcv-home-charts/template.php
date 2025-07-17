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
<div class="rcv-home-charts">
    <h2 class="rcv-home-charts__title"><?= i::__('Cultura Viva em Números') ?></h2>

    <div class="rcv-home-charts__container">

        <div class="rcv-home-charts__chart">
            <h2 class="rcv-home-charts__description"><?= i::__('Municípios com Pontos e Pontões de Cultura Cadastrados') ?></h2>

            <div class="rcv-home-charts__legend">
                <div v-for="(year, index) in years" :key="index" class="rcv-home-charts__legend-item">
                    <span :class="['rcv-home-charts__legend-color', `rcv-home-charts__legend-color--${year}`]"></span>{{ year }}
                </div>
            </div>

            <div class="rcv-home-charts__grid">

                <div class="rcv-home-charts__y-axis">
                    <span v-for="value in yAxisLabels" :key="value">{{ value }}</span>
                </div>

                <div class="rcv-home-charts__bars">
                    <div v-for="line in horizontalLines" :key="line" class="rcv-home-charts__horizontal-line" :style="{ top: `${line}px` }"></div>

                    <div v-for="region in chartData" :key="region.name" class="rcv-home-charts__bar">
                        <div
                            v-for="(segment, year) in region.data"
                            :key="year"
                            :class="['rcv-home-charts__bar-segment', `rcv-home-charts__bar-segment--${year}`]"
                            :style="{ height: `${segment.height}px` }">
                            <span v-if="segment.value" class="rcv-home-charts__bar-value">{{ segment.value }}</span>
                        </div>
                        <div class="rcv-home-charts__bar-label">{{ region.name }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rcv-home-charts__map-chart">
            <img src="<?php $this->asset( 'img/rcv-home-chart/home-map-chart.png' ) ?>"  alt=""></img>
        </div>

    </div>
</div>