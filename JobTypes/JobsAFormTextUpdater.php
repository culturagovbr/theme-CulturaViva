<?php

namespace CulturaViva\JobTypes;

use DateTime;
use MapasCulturais\API;
use MapasCulturais\App;
use MapasCulturais\ApiQuery;
use MapasCulturais\Entities\Job;
use MapasCulturais\Entities\Metadata;
use MapasCulturais\Definitions\JobType;
use MapasCulturais\Entities\Registration;
use Opportunities\Jobs\RedistributeCommitteeRegistrations;

class JobsAFormTextUpdater extends JobType
{


    const SLUG = "JobsAFormTextUpdater";

    protected function _generateId(array $data, string $start_string, string $interval_string, int $iterations)
    {
        return "JobsAFormTextUpdater";
    }

    protected function _execute(Job $job)
    {
        $app = App::i();
        $config = include THEMES_PATH . 'CulturaViva/conf-base.php';
       
        $conn = $app->em->getConnection();
        $opp = $config['rcv.opportunityId'];
        $question_field = $config['rcv.fieldQuestion'];
        $question_response = $config['rcv.questionResponse'];

        $query = "
            SELECT
                *
            FROM
                registration_meta rm
            JOIN registration r on rm.object_id = r.id
            WHERE
                rm.key = '{$question_field}' AND 
                rm.object_id in (SELECT id FROM registration where opportunity_id = {$opp}) AND
                rm.value = '{$question_response}' AND
                r.sent_timestamp < NOW() - INTERVAL '6 months'
        ";
        
        $registrations = $conn->fetchAll($query);
        $found = false;
        
        foreach ($registrations as $registration) {
            $registration = $app->repo('Registration')->find($registration['object_id']);
            $registration->registerFieldsMetadata();
            
            $registration->$question_field = $config['rcv.questionNoResponse'] ;
            $registration->range = $config['rcv.rangesMap']['cadastro'];
            $registration->save(true);
            $found = true;

            $app->em->clear();
        }

        if($found && $registration->opportunity->evaluationMethodConfiguration){
            $distribute_execution_time = date($app->config['registrations.distribution.dateString']) . ' ' . $app->config['registrations.distribution.incrementString'];
            $app->enqueueJob(RedistributeCommitteeRegistrations::SLUG, ['evaluationMethodConfiguration' => $registration->opportunity->evaluationMethodConfiguration], $distribute_execution_time);
        }
    }
}
