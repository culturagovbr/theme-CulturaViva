<?php

namespace CulturaViva\Tests;

use CulturaViva\EvaluationDistribution;
use MapasCulturais\Entities\EvaluationMethodConfiguration;
use MapasCulturais\Entities\RegistrationEvaluation;
use MapasCulturais\Entities\User;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/EvaluationDistribution.php';

// dublê de User: dispensa o bootstrap do App
class TestValuer extends User
{
    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

// dublê de EvaluationMethodConfiguration: as propriedades públicas declaradas
// atendem os acessos das validações sem passar pelo __get (que exige o App)
class TestEvaluationConfig extends EvaluationMethodConfiguration
{
    public $redistribuirAvaliacoesIniciadasParadas = false;
    public $diasAvaliacaoIniciadaParada = null;
    public $distributionConfiguration = 'hourly';

    public function __construct()
    {
    }
}

class EvaluationDistributionTest extends TestCase
{
    function testOrdersByPendingAssignmentsBeforeUserId(): void
    {
        $valuer1 = new TestValuer(10);
        $valuer2 = new TestValuer(20);
        $pending_assignments = [10 => 2, 20 => 3];

        $result = EvaluationDistribution::comparePendingAssignments(
            $valuer1,
            $valuer2,
            $pending_assignments
        );

        $this->assertLessThan(0, $result);
    }

    function testUsesUserIdAsStableTiebreaker(): void
    {
        $valuer1 = new TestValuer(10);
        $valuer2 = new TestValuer(20);

        $by_user_id = EvaluationDistribution::comparePendingAssignments(
            $valuer1,
            $valuer2,
            [10 => 2, 20 => 2]
        );

        $this->assertLessThan(0, $by_user_id);
    }

    function testOnlySupportsConfiguredCulturaVivaOpportunitiesWithAnEnabledCommittee(): void
    {
        $opportunity_ids = [5386, 5388];
        $flags = ['committee 1' => true];

        $this->assertTrue(EvaluationDistribution::supportsUniformDistribution(true, 5386, $opportunity_ids, $flags));
        $this->assertFalse(EvaluationDistribution::supportsUniformDistribution(false, 5386, $opportunity_ids, $flags));
        $this->assertFalse(EvaluationDistribution::supportsUniformDistribution(true, 9999, $opportunity_ids, $flags));
        $this->assertFalse(EvaluationDistribution::supportsUniformDistribution(true, 5386, $opportunity_ids, ['committee 1' => false]));
    }

    function testReleaseOnlyRunsForConfiguredCulturaVivaPhases(): void
    {
        $opportunity_ids = [5386, 5388];

        $this->assertTrue(EvaluationDistribution::isConfiguredPhase(5386, $opportunity_ids));
        $this->assertTrue(EvaluationDistribution::isConfiguredPhase(5388, $opportunity_ids));
        $this->assertFalse(EvaluationDistribution::isConfiguredPhase(9999, $opportunity_ids));
        $this->assertFalse(EvaluationDistribution::isConfiguredPhase(null, $opportunity_ids));
    }

    function testReleaseIgnoresPhaseWithoutEvaluationConfiguration(): void
    {
        $this->assertFalse(EvaluationDistribution::isCulturaVivaPhase(null, [5386, 5388]));
    }

    function testReleaseRefusesWithoutARelation(): void
    {
        $this->assertSame(0, EvaluationDistribution::releasePhaseEvaluations(null));
        $this->assertSame(0, EvaluationDistribution::releaseValuerEvaluations(null));
    }

    function testStaleReleaseRefusesWithoutAConfigurationOrRelation(): void
    {
        $this->assertSame(0, EvaluationDistribution::releaseStalePhaseEvaluations(null));
        $this->assertSame(0, EvaluationDistribution::releaseStaleValuerEvaluations(null, 5));
    }

    function testNormalizeStaleDaysKeepsValueInRange(): void
    {
        $this->assertSame(0, EvaluationDistribution::normalizeStaleDays(0));
        $this->assertSame(0, EvaluationDistribution::normalizeStaleDays(-3));
        $this->assertSame(0, EvaluationDistribution::normalizeStaleDays('abc'));
        $this->assertSame(1, EvaluationDistribution::normalizeStaleDays(1));
        $this->assertSame(45, EvaluationDistribution::normalizeStaleDays('45'));
        $this->assertSame(180, EvaluationDistribution::normalizeStaleDays(180));
        $this->assertSame(180, EvaluationDistribution::normalizeStaleDays(999));
    }

    function testIsStaleStartedEvaluationOnlyMatchesOldDrafts(): void
    {
        $now = new \DateTimeImmutable('2026-08-27 12:00:00');
        $old = new \DateTimeImmutable('2026-08-20 11:59:00');
        $recent = new \DateTimeImmutable('2026-08-24 12:00:00');

        // rascunho parado há mais de 5 dias volta para a fila
        $this->assertTrue(EvaluationDistribution::isStaleStartedEvaluation(RegistrationEvaluation::STATUS_DRAFT, $old, 5, $now));

        // rascunho recente permanece
        $this->assertFalse(EvaluationDistribution::isStaleStartedEvaluation(RegistrationEvaluation::STATUS_DRAFT, $recent, 5, $now));

        // concluída e enviada nunca voltam, mesmo antigas
        $this->assertFalse(EvaluationDistribution::isStaleStartedEvaluation(RegistrationEvaluation::STATUS_EVALUATED, $old, 5, $now));
        $this->assertFalse(EvaluationDistribution::isStaleStartedEvaluation(RegistrationEvaluation::STATUS_SENT, $old, 5, $now));

        // sem data de atividade ou sem dias válidos, não mexe
        $this->assertFalse(EvaluationDistribution::isStaleStartedEvaluation(RegistrationEvaluation::STATUS_DRAFT, null, 5, $now));
        $this->assertFalse(EvaluationDistribution::isStaleStartedEvaluation(RegistrationEvaluation::STATUS_DRAFT, $old, 0, $now));
    }

    private function config(bool $enabled, $days, string $distribution = 'hourly'): TestEvaluationConfig
    {
        $config = new TestEvaluationConfig();
        $config->{EvaluationDistribution::META_STALE_ENABLED} = $enabled;
        $config->{EvaluationDistribution::META_STALE_DAYS} = $days;
        $config->distributionConfiguration = $distribution;

        return $config;
    }

    function testStaleDaysRequiredSoQuandoMarcadaSemValorValido(): void
    {
        // marcada com valor na faixa: não exige mais nada
        $this->assertFalse(EvaluationDistribution::staleDaysRequired($this->config(true, 45)));

        // marcada e vazia (ou zero): exige
        $this->assertTrue(EvaluationDistribution::staleDaysRequired($this->config(true, null)));
        $this->assertTrue(EvaluationDistribution::staleDaysRequired($this->config(true, 0)));

        // fora da faixa (500) é responsabilidade do isStaleDaysInRange, não daqui
        $this->assertFalse(EvaluationDistribution::staleDaysRequired($this->config(true, 500)));

        // desmarcada: nunca exige
        $this->assertFalse(EvaluationDistribution::staleDaysRequired($this->config(false, null)));

        // distribuição desativada: não exige (fica dormente)
        $this->assertFalse(EvaluationDistribution::staleDaysRequired($this->config(true, null, 'deactivate')));
    }

    function testIsStaleDaysInRangeSoCobraFaixaQuandoMarcada(): void
    {
        $enabled = $this->config(true, null);

        $this->assertTrue(EvaluationDistribution::isStaleDaysInRange($enabled, 1));
        $this->assertTrue(EvaluationDistribution::isStaleDaysInRange($enabled, 45));
        $this->assertTrue(EvaluationDistribution::isStaleDaysInRange($enabled, 180));

        $this->assertFalse(EvaluationDistribution::isStaleDaysInRange($enabled, 0));
        $this->assertFalse(EvaluationDistribution::isStaleDaysInRange($enabled, 181));
        $this->assertFalse(EvaluationDistribution::isStaleDaysInRange($enabled, 500));

        // desmarcada: qualquer valor passa
        $this->assertTrue(EvaluationDistribution::isStaleDaysInRange($this->config(false, null), 500));
    }

    function testTheRoundSplitComesBeforeTheHeldAssignments(): void
    {
        $valuer1 = new TestValuer(10);
        $valuer2 = new TestValuer(20);

        // o 10 tem muito mais acumulado, mas recebeu menos nesta rodada e vem primeiro
        $result = EvaluationDistribution::comparePendingAssignments(
            $valuer1,
            $valuer2,
            [10 => 1, 20 => 3],
            [10 => 500, 20 => 0]
        );

        $this->assertLessThan(0, $result);
    }

    function testHeldAssignmentsRotateTheTurnOnTies(): void
    {
        $valuer1 = new TestValuer(10);
        $valuer2 = new TestValuer(20);

        // empatados na rodada: começa quem tem menos, e não o de menor id
        $result = EvaluationDistribution::comparePendingAssignments(
            $valuer1,
            $valuer2,
            [10 => 2, 20 => 2],
            [10 => 9, 20 => 4]
        );

        $this->assertGreaterThan(0, $result);
    }

    function testOnlyTheTiebreakerRotatesTheTurn(): void
    {
        $comparator = EvaluationDistribution::createComparator(
            [EvaluationDistribution::TIEBREAKER_GROUP => true, 'committee 1' => true],
            [10 => 9, 20 => 4]
        );
        $valuer1 = new TestValuer(10);
        $valuer2 = new TestValuer(20);

        $this->assertGreaterThan(0, $comparator($valuer1, $valuer2, EvaluationDistribution::TIEBREAKER_GROUP, []));

        // a comissão comum segue com o id como desempate, como antes
        $this->assertLessThan(0, $comparator($valuer1, $valuer2, 'committee 1', []));
    }

    function testComparatorDelegatesDisabledCommitteesToCore(): void
    {
        $comparator = EvaluationDistribution::createComparator([
            'committee 1' => true,
            'committee 2' => false,
        ]);
        $valuer1 = new TestValuer(10);
        $valuer2 = new TestValuer(20);
        $pending_assignments = [10 => 1, 20 => 2];

        $this->assertLessThan(0, $comparator($valuer1, $valuer2, 'committee 1', $pending_assignments));
        $this->assertNull($comparator($valuer1, $valuer2, 'committee 2', $pending_assignments));
    }
}
