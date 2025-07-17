<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */


$this->jsObject['config']['rcvEntityHeader'] = [
    'sealCertifierIdPonto' => $app->config['rcv.verificationSeals']['ponto'],
    'sealCertifierIdPontao' => $app->config['rcv.verificationSeals']['pontao']
];
