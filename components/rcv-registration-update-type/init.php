<?php 
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
*/

use MapasCulturais\Entities\Registration;

$this->jsObject['config']['rcvRegistrationUpdateType'] = [
    'opportunity' => $app->config['rcv.opportunityId'],
    'status' => Registration::STATUS_APPROVED,
];