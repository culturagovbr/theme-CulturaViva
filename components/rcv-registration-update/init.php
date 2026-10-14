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
    $app->disableAccessControl();

    $conn = $app->em->getConnection();
    $agentId = $app->user->profile->id;
    $opportunity = (int) $app->config['rcv.opportunityId'];

    $registrationObjectType = str_replace('\\', '\\\\', Registration::class);

     $ids = $conn->fetchFirstColumn("
        SELECT DISTINCT r.id
        FROM registration r
        INNER JOIN agent owner_agent
            ON owner_agent.id = r.owner
        WHERE r.opportunity_id = :oppId
          AND r.status         = :approved
          AND (
              owner_agent.user_id = (
                  SELECT a2.user_id FROM agent a2 WHERE a2.id = :agentId LIMIT 1
              )
              OR
              EXISTS (
                  SELECT 1
                  FROM agent_relation ar
                  INNER JOIN agent_relation ar2
                      ON ar2.object_id   = ar.agent_id
                      AND ar2.object_type = 'MapasCulturais\\Entities\\Agent'
                      AND ar2.agent_id   = :agentId
                      AND ar2.status     IN (1, -5)
                  WHERE ar.object_id   = r.id
                    AND ar.object_type = '{$registrationObjectType}'
                    AND ar.type        = 'coletivo'
              )
          )
    ", [
        'agentId'  => $agentId,
        'oppId'    => $opportunityId,
        'approved' => Registration::STATUS_APPROVED,
    ]);

    $registrations = [];
 
    if (!empty($ids)) {
        $query = new ApiQuery(Registration::class, [
            '@select' => 'id,number,category,relatedAgents,owner.{name}',
            '@order'  => 'id ASC',
            'id'      => API::IN($ids),
        ]);
        $registrations = $query->find();
    }
 
    $app->enableAccessControl();

    $this->jsObject['config']['rcvRegistrationUpdate'] = [
        'registrations' => $registrations,
    ];

} else {

    $this->jsObject['config']['rcvRegistrationUpdate'] = [
        'opportunity' => $app->config['rcv.opportunityId'],
        'status' => Registration::STATUS_APPROVED,
    ];

}