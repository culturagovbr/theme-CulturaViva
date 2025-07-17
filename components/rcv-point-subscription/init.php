<?php
/* Get agents from logged user */
$queryAgents = new MapasCulturais\ApiQuery(MapasCulturais\Entities\Agent::class, [
    '@select' => 'id,name,files.avatar', 
    '@permissions' => '@control', 
    '@limit' => '2',
    'type' => 'EQ(2)',
]);

$query_registration = new MapasCulturais\ApiQuery(MapasCulturais\Entities\Registration::class, [
    '@select' => 'id,agentRelations,category,editUrl,status,singleUrl', 
    '@permissions' => '@control', 
    'opportunity' => "EQ({$app->config['rcv.opportunityId']})",
    'user' => 'EQ(@me)',
    'status' => 'GTE(0)'
]);

$has_registrations = [];
if($result = $query_registration->getFindResult()) {
    foreach($result as $key => $values) {
        if($relations = (array) $values['agentRelations']) {
            $has_registrations[] = [
                'registrationId' => $values['id'],
                'editUrl' => $values['editUrl'],
                'singleUrl' => $values['singleUrl'],
                'status' => $values['status'],
                'registrationNumber' => $values['number'],
                'category' => $values['category'],
                'agentId' => $relations['coletivo'][0]['agent']['id']
            ]; 
        }
    }
}

$locked_fields = [];
foreach($app->config['rcv.apiReturnedFieldsUsed'] as $field => $params) {
    if($params['disable']) {
        $locked_fields[] = $field;
    }
}

$this->jsObject['config']['rcvPointSubscription']['agents'] = $queryAgents->getFindResult();
$this->jsObject['config']['rcvPointSubscription'] =[
    'rcvSeals' => $app->config['rcv.seals'],
    'categoriesMap' => $app->config['rcv.categoriesMap'],
    'has_registrations' => $has_registrations,
    'apiReturnedFieldsUsed' => $app->config['rcv.apiReturnedFieldsUsed'],
    'apiReturnedLockedFields' => $locked_fields
];
