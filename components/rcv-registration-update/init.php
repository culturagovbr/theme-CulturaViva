<?php 
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
*/

use MapasCulturais\API;
use MapasCulturais\ApiQuery;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\Registration;

if (!$app->user->is('admin')) {

    $query = new ApiQuery(Registration::class, [
        '@permissions' => '@control', 
        '@select' => 'id,number,category,relatedAgents,owner.{name}', 
        '@order' => 'id ASC', 
        'opportunity' => API::EQ($app->config['rcv.opportunityId']), 
        'status' => API::EQ(Registration::STATUS_APPROVED)
    ]);
    $registrations = $query->find();

    $this->jsObject['config']['rcvRegistrationUpdate'] = [
        'registrations' => $registrations,
    ];

} else {

    $this->jsObject['config']['rcvRegistrationUpdate'] = [
        'opportunity' => $app->config['rcv.opportunityId'],
        'status' => Registration::STATUS_APPROVED,
    ];

}