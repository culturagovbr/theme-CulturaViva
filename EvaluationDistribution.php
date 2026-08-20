<?php

namespace CulturaViva;

use MapasCulturais\App;
use MapasCulturais\Entities\EvaluationMethodConfiguration;
use MapasCulturais\Entities\EvaluationMethodConfigurationAgentRelation;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\RegistrationEvaluation;
use MapasCulturais\Entities\User;
use MapasCulturais\Request;
use Opportunities\Jobs\RedistributeCommitteeRegistrations;
use Slim\Psr7\Factory\ServerRequestFactory;

final class EvaluationDistribution
{
    public static function register(): void
    {
        $app = App::i();
        $opportunity_ids = self::configuredOpportunityIds();

        $app->hook('evaluationMethod.distributionComparator', function (&$comparator, Opportunity $opportunity) use ($app, $opportunity_ids) {
            $first_phase = $opportunity->firstPhase ?: $opportunity;
            $evaluation_config = $opportunity->evaluationMethodConfiguration;
            $ignore_started_evaluations = (array) ($evaluation_config->ignoreStartedEvaluations ?: []);

            if (!self::supportsUniformDistribution(
                $app->view instanceof Theme,
                (int) $first_phase->id,
                $opportunity_ids,
                $ignore_started_evaluations
            )) {
                return;
            }

            $comparator = self::createComparator($ignore_started_evaluations);

            $app->log->debug(sprintf(
                'CulturaViva: distribuicao uniforme habilitada para a fase %d',
                $opportunity->id
            ));
        });

        // libera as iniciadas dos desabilitados antes de cada redistribuição
        $app->hook('job(' . RedistributeCommitteeRegistrations::SLUG . ').execute:before', function () {
            /** @var \MapasCulturais\Entities\Job $this */
            self::releasePhaseEvaluations($this->evaluationMethodConfiguration ?? null);
        });

        // ao desabilitar, libera as iniciadas e redistribui as pendentes
        $app->hook('entity(EvaluationMethodConfigurationAgentRelation).disable:after', function () use ($app, $opportunity_ids) {
            /** @var EvaluationMethodConfigurationAgentRelation $this */
            $evaluation_config = $this->owner;

            if ($this->__skipRedistribution || !self::isCulturaVivaPhase($evaluation_config, $opportunity_ids)) {
                return;
            }

            // com a distribuição desligada, liberar a vaga não levaria a lugar nenhum
            if (($evaluation_config->distributionConfiguration ?? 'deactivate') == 'deactivate') {
                return;
            }

            self::releaseValuerEvaluations($this);

            $app->enqueueOrReplaceJob(RedistributeCommitteeRegistrations::SLUG, [
                'evaluationMethodConfiguration' => $evaluation_config,
            ], 'now');
        });
    }

    public static function releasePhaseEvaluations(?EvaluationMethodConfiguration $evaluation_config): int
    {
        if (!$evaluation_config || !self::isCulturaVivaPhase($evaluation_config, self::configuredOpportunityIds())) {
            return 0;
        }

        $released = 0;

        foreach ($evaluation_config->getAgentRelations() as $relation) {
            $released += self::releaseValuerEvaluations($relation);
        }

        return $released;
    }

    public static function releaseValuerEvaluations(?EvaluationMethodConfigurationAgentRelation $relation): int
    {
        if (!$relation || $relation->status != EvaluationMethodConfigurationAgentRelation::STATUS_DISABLED) {
            return 0;
        }

        $evaluation_config = $relation->owner;

        if (!self::isCulturaVivaPhase($evaluation_config, self::configuredOpportunityIds())) {
            return 0;
        }

        $user = $relation->agent->user ?? null;

        if (!$user) {
            return 0;
        }

        $app = App::i();
        $evaluations = $app->repo('RegistrationEvaluation')->findByOpportunityAndUser(
            $evaluation_config->opportunity,
            $user,
            $relation->group
        );

        $released = 0;
        $http_request = $app->request;

        $app->disableAccessControl();
        // em job não há requisição, e os listeners de remove:after contam com uma
        $app->request = $http_request ?: self::backgroundRequest();
        try {
            foreach ($evaluations as $evaluation) {
                // só rascunho volta para a fila; concluída e enviada permanecem
                if ($evaluation->status != RegistrationEvaluation::STATUS_DRAFT) {
                    continue;
                }

                $evaluation->delete(true);
                $released++;
            }
        } finally {
            $app->request = $http_request;
            $app->enableAccessControl();
        }

        $relation->updateSummary();

        if ($released > 0) {
            $app->log->debug(sprintf(
                'CulturaViva: %d avaliacoes iniciadas liberadas do avaliador %d na fase %d',
                $released,
                $user->id,
                $evaluation_config->opportunity->id
            ));
        }

        return $released;
    }

    private static function backgroundRequest(): Request
    {
        // sem IP: a ação é do sistema, não de um usuário
        $psr7 = (new ServerRequestFactory())->createServerRequest('GET', '/');

        return new Request($psr7, 'job', RedistributeCommitteeRegistrations::SLUG, []);
    }

    public static function configuredOpportunityIds(): array
    {
        $app = App::i();

        return array_map('intval', [
            $app->config['rcv.opportunityId'],
            $app->config['rcv.pnabOpportunityId'],
        ]);
    }

    public static function isCulturaVivaPhase(?EvaluationMethodConfiguration $evaluation_config, array $opportunity_ids): bool
    {
        if (!$evaluation_config || !$evaluation_config->opportunity) {
            return false;
        }

        $opportunity = $evaluation_config->opportunity;
        $first_phase = $opportunity->firstPhase ?: $opportunity;

        return self::isConfiguredPhase((int) $first_phase->id, $opportunity_ids);
    }

    public static function isConfiguredPhase(?int $first_phase_id, array $opportunity_ids): bool
    {
        return $first_phase_id !== null && in_array($first_phase_id, $opportunity_ids, true);
    }

    public static function supportsUniformDistribution(
        bool $is_cultura_viva,
        int $first_phase_id,
        array $opportunity_ids,
        array $ignore_started_evaluations
    ): bool {
        return $is_cultura_viva
            && in_array($first_phase_id, $opportunity_ids, true)
            && (bool) array_filter($ignore_started_evaluations);
    }

    public static function createComparator(array $ignore_started_evaluations): callable
    {
        return function (
            User $valuer1,
            User $valuer2,
            string $committee,
            array $pending_assignments
        ) use ($ignore_started_evaluations): ?int {
            if (empty($ignore_started_evaluations[$committee])) {
                return null;
            }

            return EvaluationDistribution::comparePendingAssignments(
                $valuer1,
                $valuer2,
                $pending_assignments
            );
        };
    }

    public static function comparePendingAssignments(
        User $valuer1,
        User $valuer2,
        array $pending_assignments
    ): int {
        $pending1 = $pending_assignments[$valuer1->id] ?? 0;
        $pending2 = $pending_assignments[$valuer2->id] ?? 0;

        if ($pending1 !== $pending2) {
            return $pending1 <=> $pending2;
        }

        return $valuer1->id <=> $valuer2->id;
    }
}
