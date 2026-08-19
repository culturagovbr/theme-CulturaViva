<?php

namespace CulturaViva\Tests;

use CulturaViva\EvaluationDistribution;
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

    function testReleaseRefusesWithoutAnEvaluationConfiguration(): void
    {
        $this->assertSame(0, EvaluationDistribution::releasePhaseEvaluations(null));
        $this->assertSame(0, EvaluationDistribution::releaseValuerEvaluations(null));
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
