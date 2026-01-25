<?php

namespace CulturaViva\JobTypes;

use MapasCulturais\App;
use MapasCulturais\Definitions\JobType;
use MapasCulturais\Entities\Job;
use CulturaViva\Importer;

class ImportRegistrationsJob extends JobType
{
    const SLUG = "importRegistrations";

    protected function _generateId(array $data, string $start_string, string $interval_string, int $iterations) {
        return "importRegistrations:{$data['registration']->id}";
    }

    protected function _execute(Job $job) {
        $app = App::i();

        /** @var \MapasCulturais\Connection */
        $conn = $app->em->getConnection();

        $registration = $job->registration;
        $app->clearHooks('entity(Registration).insert:finish');
        Importer::$registration = $registration;
        
        try {
            $conn->beginTransaction();
            $job_result = Importer::runImportRegistrationsJob($registration);
            Importer::sendEmails($job_result);

            $conn->commit();
        } catch(\Throwable $e) {
            Importer::sendEmailError($registration, $e);
            $app->log->debug("Erro ao importar inscrições: {$e->getMessage()}");
            $app->log->debug($e->getTraceAsString());

            Importer::generateImporterLog($registration, "Erro ao importar inscrições: {$e->getMessage()}");
            Importer::generateImporterLog($registration, "Stack trace: {$e->getTraceAsString()}");

            Importer::updateImporterStatusFile($registration, [
                'status' => 2,
                'message' => $e->getMessage(),
                'timestamp' => date('d-m-Y H:i:s')
            ]);
            $conn->rollBack();

            $app->disableAccessControl();
            $registration->setStatusToInvalid();
            $registration->save(true);
            $app->enableAccessControl();
        }
    }
}