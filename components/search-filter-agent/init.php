<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->jsObject['initialPseudoQuery'] = [
    'tipoPonto' => [],
    'En_Estado' => [],
    'En_Municipio' => [],
    'term:acao_estruturante' => [],
    'term:acao_estruturante_outra' => [],
];

if ($app->view->canUserControlRCV()) {
    $this->jsObject['initialPseudoQuery']['@verified'] = true;
}