<?php 
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
*/

use MapasCulturais\ApiQuery;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\Registration;

if (!$app->user->is('admin')) {
    $query = new ApiQuery(Agent::class, ['@permissions' => '@control', '@order' => 'id ASC', '@verified' => 1]);
    $agent_ids = $query->findIds();

    $registrations = [];
    foreach($agent_ids as $agent_id) {
        $agent = $app->repo('Agent')->find($agent_id);
        
        if($agent->type->id == 2) {
            $registrations[] = $app->repo('Registration')->findBy(['owner' => $agent->owner, 'opportunity' => $app->config['rcv.opportunityId'], 'status' => Registration::STATUS_APPROVED]);
        }
    }

    $registrations = array_filter($registrations, function($item) {
        return !empty($item) || (is_array($item) && !empty($item));
    });

    $registration_info = [];
    foreach ($registrations as $registrationArray) {
        if (!empty($registrationArray)) {
            foreach ($registrationArray as $registration) {
                $registration_info[$registration->id] = [
                    'nome' => $registration->relatedAgents['coletivo'][0]->name ?? '',
                    'nomeCompleto' => $registration->relatedAgents['coletivo'][0]->nomeCompleto,
                    'cnpj' => $registration->relatedAgents['coletivo'][0]->cnpj,
                    'id' => $registration->id,
                ];
            }
        }
    }

    $this->jsObject['config']['rcvRegistrationUpdate'] = [
        'registrations' => $registration_info,
    ];

} else {

    $this->jsObject['config']['rcvRegistrationUpdate'] = [
        'opportunity' => $app->config['rcv.opportunityId'],
        'status' => Registration::STATUS_APPROVED,
    ];

}