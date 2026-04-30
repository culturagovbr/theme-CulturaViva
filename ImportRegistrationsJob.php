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
        $app  = App::i();

        /** @var \MapasCulturais\Connection */
        $conn = $app->em->getConnection();

        $registration = $job->registration;
        $app->clearHooks('entity(Registration).insert:finish');
        Importer::$registration = $registration;

        $t0 = microtime(true);

        try {
            $app->disableAccessControl();

            // ── FASE A — leitura pré-transação (read-only) ─────────────────
            $app->log->info("[IMPORT:{$registration->id}] Fase A — construindo plano de execução");

            $plan = Importer::buildExecutionPlan($registration);

            $t1 = microtime(true);
            $app->log->info(sprintf(
                "[IMPORT:%d] Fase A concluída em %.2fs (%d linhas)",
                $registration->id, $t1 - $t0, $plan['total_rows']
            ));

            // ── FASE B — transação write-only ──────────────────────────────
            $app->log->info("[IMPORT:{$registration->id}] Fase B — aplicando plano (transação)");

            $conn->beginTransaction();

            $plan = Importer::applyExecutionPlan($plan);

            $conn->commit();
            $app->enableAccessControl();

            $t2 = microtime(true);
            $app->log->info(sprintf(
                "[IMPORT:%d] Fase B concluída em %.2fs (transação)",
                $registration->id, $t2 - $t1
            ));

            // ── FASE C — emails pós-commit ─────────────────────────────────
            $app->log->info("[IMPORT:{$registration->id}] Fase C — enviando emails");

            $email_error = null;
            try {
                Importer::sendEmails($plan);
            } catch (\Throwable $e) {
                $email_error = $e;
                $app->log->error("[IMPORT:{$registration->id}] Erro ao enviar emails: {$e->getMessage()}");
                $app->log->debug($e->getTraceAsString());
                Importer::generateImporterLog($registration, "Importação concluída, mas houve erro ao enviar emails: {$e->getMessage()}");
            }

            $t3 = microtime(true);
            $app->log->info(sprintf(
                "[IMPORT:%d] Concluído em %.2fs total (A:%.2fs B:%.2fs C:%.2fs)",
                $registration->id, $t3 - $t0, $t1 - $t0, $t2 - $t1, $t3 - $t2
            ));

            $final_message = $email_error
                ? 'Importado com sucesso, mas houve falha no envio de notificações'
                : 'Importado com sucesso';

            Importer::generateImporterLog($registration, $final_message);
            Importer::updateImporterStatusFile($registration, [
                'status'    => 10,
                'message'   => $final_message,
                'timestamp' => date('d-m-Y H:i:s'),
            ]);

            return true;

        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $app->enableAccessControl();

            try {
                Importer::sendEmailError($registration, $e);
            } catch (\Throwable $email_error) {
                $app->log->error("[IMPORT:{$registration->id}] Erro ao enviar email de falha: {$email_error->getMessage()}");
                $app->log->debug($email_error->getTraceAsString());
            }

            $app->log->debug("[IMPORT:{$registration->id}] Erro: {$e->getMessage()}");
            $app->log->debug($e->getTraceAsString());

            Importer::generateImporterLog($registration, "Erro ao importar inscrições: {$e->getMessage()}");
            Importer::generateImporterLog($registration, "Stack trace: {$e->getTraceAsString()}");
            Importer::updateImporterStatusFile($registration, [
                'status'    => 2,
                'message'   => $e->getMessage(),
                'timestamp' => date('d-m-Y H:i:s'),
            ]);

            $app->disableAccessControl();
            $registration->setStatusToInvalid();
            $registration->save(true);
            $app->enableAccessControl();

            return false;
        }
    }
}