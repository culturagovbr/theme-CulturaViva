<?php 
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
*/

use MapasCulturais\ApiQuery;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\Registration;

$this->jsObject['config']['rcvRegistrationUpdateCnpj'] = [
    'opportunity' => $app->config['rcv.opportunityId'],
    'status' => Registration::STATUS_APPROVED,
];