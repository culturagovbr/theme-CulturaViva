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
    // liga a redistribuição das avaliações iniciadas paradas há dias
    public const META_STALE_ENABLED = 'redistribuirAvaliacoesIniciadasParadas';

    // dias parada como rascunho a partir dos quais a avaliação volta para a fila
    public const META_STALE_DAYS = 'diasAvaliacaoIniciadaParada';

    // faixa aceita no campo de dias
    public const STALE_DAYS_MIN = 1;
    public const STALE_DAYS_MAX = 180;

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

        // antes de cada redistribuição, libera as iniciadas dos desabilitados
        // e as iniciadas paradas há dias dos avaliadores habilitados
        $app->hook('job(' . RedistributeCommitteeRegistrations::SLUG . ').execute:before', function () {
            /** @var \MapasCulturais\Entities\Job $this */
            $evaluation_config = $this->evaluationMethodConfiguration ?? null;

            self::releasePhaseEvaluations($evaluation_config);
            self::releaseStalePhaseEvaluations($evaluation_config);
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

        // consistência entre o checkbox, o campo de dias e o agendamento da distribuição
        $app->hook('entity(EvaluationMethodConfiguration).save:before', function () {
            /** @var EvaluationMethodConfiguration $this */
            $enabled_key = EvaluationDistribution::META_STALE_ENABLED;
            $days_key = EvaluationDistribution::META_STALE_DAYS;

            // proteção: ao desativar a distribuição, desliga a opção
            if ($this->$enabled_key && ($this->distributionConfiguration ?? 'deactivate') == 'deactivate') {
                $this->$enabled_key = false;
            }

            // marcada sem uma quantidade de dias válida: desliga a opção
            // (rede de segurança para quando o erro de validação não chega à tela)
            $days = $this->$enabled_key ? EvaluationDistribution::normalizeStaleDays($this->$days_key) : 0;

            if (!$days) {
                if ($this->$enabled_key) {
                    $this->$enabled_key = false;
                }

                if ($this->$days_key !== null) {
                    $this->$days_key = null;
                }

                return;
            }

            // marcada: mantém a quantidade de dias dentro da faixa aceita
            if ($days !== $this->$days_key) {
                $this->$days_key = $days;
            }
        });

    }

    // a opção de redistribuir por tempo está valendo (marcada e com distribuição ativa)
    public static function staleConfigActive(EvaluationMethodConfiguration $evaluation_config): bool
    {
        return (bool) $evaluation_config->{self::META_STALE_ENABLED}
            && ($evaluation_config->distributionConfiguration ?? 'deactivate') != 'deactivate';
    }

    // o campo de dias é obrigatório e ainda não tem um valor válido preenchido
    // (usado pelo should_validate do metadado, no caso de valor vazio)
    public static function staleDaysRequired(EvaluationMethodConfiguration $evaluation_config): bool
    {
        return self::staleConfigActive($evaluation_config)
            && self::normalizeStaleDays($evaluation_config->{self::META_STALE_DAYS}) < self::STALE_DAYS_MIN;
    }

    // o valor informado está dentro da faixa aceita (usado pelo 'validations' do
    // metadado, quando há valor preenchido). só cobra a faixa se a opção vale.
    public static function isStaleDaysInRange(EvaluationMethodConfiguration $evaluation_config, $value): bool
    {
        if (!self::staleConfigActive($evaluation_config)) {
            return true;
        }

        $days = (int) $value;

        return $days >= self::STALE_DAYS_MIN && $days <= self::STALE_DAYS_MAX;
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

        // o mesmo usuário pode ter outra relação, habilitada, na mesma comissão
        if (self::hasEnabledRelation($evaluation_config, $relation, $user)) {
            return 0;
        }

        // só rascunho volta para a fila; concluída e enviada permanecem
        $released = self::deleteValuerEvaluations(
            $relation,
            fn ($evaluation) => $evaluation->status == RegistrationEvaluation::STATUS_DRAFT
        );

        if ($released > 0) {
            App::i()->log->debug(sprintf(
                'CulturaViva: %d avaliacoes iniciadas liberadas do avaliador %d na fase %d',
                $released,
                $user->id,
                $evaluation_config->opportunity->id
            ));
        }

        return $released;
    }

    public static function releaseStalePhaseEvaluations(?EvaluationMethodConfiguration $evaluation_config): int
    {
        if (!$evaluation_config || !self::isCulturaVivaPhase($evaluation_config, self::configuredOpportunityIds())) {
            return 0;
        }

        // com a distribuição desligada, liberar a vaga não levaria a lugar nenhum
        if (($evaluation_config->distributionConfiguration ?? 'deactivate') == 'deactivate') {
            return 0;
        }

        if (!$evaluation_config->{self::META_STALE_ENABLED}) {
            return 0;
        }

        $days = self::normalizeStaleDays($evaluation_config->{self::META_STALE_DAYS});

        if ($days < self::STALE_DAYS_MIN) {
            return 0;
        }

        $released = 0;

        foreach ($evaluation_config->getAgentRelations() as $relation) {
            $released += self::releaseStaleValuerEvaluations($relation, $days);
        }

        return $released;
    }

    public static function releaseStaleValuerEvaluations(?EvaluationMethodConfigurationAgentRelation $relation, int $days): int
    {
        if (!$relation || $relation->status != EvaluationMethodConfigurationAgentRelation::STATUS_ENABLED) {
            return 0;
        }

        if ($days < self::STALE_DAYS_MIN) {
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

        $now = new \DateTimeImmutable();

        // concluída e enviada permanecem; só o rascunho parado além do limite volta para a fila
        $released = self::deleteValuerEvaluations($relation, fn ($evaluation) => self::isStaleStartedEvaluation(
            (int) $evaluation->status,
            $evaluation->updateTimestamp ?: $evaluation->createTimestamp,
            $days,
            $now
        ));

        if ($released > 0) {
            App::i()->log->debug(sprintf(
                'CulturaViva: %d avaliacoes iniciadas paradas ha %d+ dias liberadas do avaliador %d na fase %d',
                $released,
                $days,
                $user->id,
                $evaluation_config->opportunity->id
            ));
        }

        return $released;
    }

    /**
     * Remove as avaliações do avaliador que o predicado marcar e atualiza o resumo.
     *
     * Cuida do contexto de execução: em job não há requisição, e os listeners
     * de remove:after contam com uma.
     *
     * @param callable(RegistrationEvaluation): bool $should_release
     */
    private static function deleteValuerEvaluations(EvaluationMethodConfigurationAgentRelation $relation, callable $should_release): int
    {
        $app = App::i();
        $evaluation_config = $relation->owner;

        $evaluations = $app->repo('RegistrationEvaluation')->findByOpportunityAndUser(
            $evaluation_config->opportunity,
            $relation->agent->user,
            $relation->group
        );

        $released = 0;
        $http_request = $app->request;

        $app->disableAccessControl();
        $app->request = $http_request ?: self::backgroundRequest();
        try {
            foreach ($evaluations as $evaluation) {
                if (!$should_release($evaluation)) {
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

        return $released;
    }

    // rascunho cuja última atividade passou do limite de dias configurado
    public static function isStaleStartedEvaluation(
        int $status,
        ?\DateTimeInterface $last_activity,
        int $days,
        ?\DateTimeInterface $now = null
    ): bool {
        if ($status != RegistrationEvaluation::STATUS_DRAFT) {
            return false;
        }

        if ($days < self::STALE_DAYS_MIN || !$last_activity) {
            return false;
        }

        $now = $now ? \DateTimeImmutable::createFromInterface($now) : new \DateTimeImmutable();
        $limit = $now->modify("-{$days} days");

        return $last_activity <= $limit;
    }

    // mantém o valor dentro da faixa aceita; fora dela, devolve 0 (desligado)
    public static function normalizeStaleDays($value): int
    {
        $days = (int) $value;

        if ($days < self::STALE_DAYS_MIN) {
            return 0;
        }

        return min($days, self::STALE_DAYS_MAX);
    }

    private static function hasEnabledRelation(
        EvaluationMethodConfiguration $evaluation_config,
        EvaluationMethodConfigurationAgentRelation $relation,
        User $user
    ): bool {
        foreach ($evaluation_config->getAgentRelations() as $other) {
            if ($other->id == $relation->id || $other->group !== $relation->group) {
                continue;
            }

            if ($other->status == EvaluationMethodConfigurationAgentRelation::STATUS_ENABLED
                && ($other->agent->user->id ?? null) == $user->id) {
                return true;
            }
        }

        return false;
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
