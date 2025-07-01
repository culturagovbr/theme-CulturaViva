<?php 
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    rcv-home-cards
    rcv-home-charts
    rcv-home-developers 
    rcv-home-entities 
    rcv-home-header 
');
?>
<rcv-home-header></rcv-home-header>
<rcv-home-entities></rcv-home-entities>
<!-- <rcv-home-charts :chart-data="[
    { name: 'Norte', data: { 2029: { height: 44, value: 0 }, 2030: { height: 44, value: 0 } } },
    { name: 'Nordeste', data: { 2029: { height: 100, value: 735 }, 2030: { height: 100, value: 735 } } },
    { name: 'Centro-oeste', data: { 2029: { height: 125, value: 125 }, 2030: { height: 125, value: 125 } } },
    { name: 'Sudeste', data: { 2029: { height: 137, value: 137 }, 2030: { height: 137, value: 137 } } },
    { name: 'Sul', data: { 2029: { height: 86, value: 86 }, 2030: { height: 86, value: 86 } } }
]"></rcv-home-charts> -->
<rcv-home-cards></rcv-home-cards>
<rcv-home-developers></rcv-home-developers>