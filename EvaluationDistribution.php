<?php

namespace CulturaViva;

use MapasCulturais\App;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\User;

final class EvaluationDistribution
{
    public static function register(): void
    {
        $app = App::i();
        $opportunity_ids = array_map('intval', [
            $app->config['rcv.opportunityId'],
            $app->config['rcv.pnabOpportunityId'],
        ]);

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
