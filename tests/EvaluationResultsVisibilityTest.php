<?php

namespace CulturaViva\Tests;

use CulturaViva\EvaluationResultsVisibility;
use MapasCulturais\Entities\Registration;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/EvaluationResultsVisibility.php';

class EvaluationResultsVisibilityTest extends TestCase
{
    function testKeepsCoreDecisionWhenNoOptionIsEnabled(): void
    {
        $this->assertTrue(EvaluationResultsVisibility::resolve(true, false, false, false, Registration::STATUS_SENT));
        $this->assertFalse(EvaluationResultsVisibility::resolve(false, false, false, false, Registration::STATUS_NOTAPPROVED));
    }

    function testHidesPublishedDetailsWhileRegistrationHasNoFinalResult(): void
    {
        $this->assertFalse(EvaluationResultsVisibility::resolve(true, true, false, false, Registration::STATUS_SENT));
    }

    function testShowsPublishedDetailsAfterFinalResult(): void
    {
        $this->assertTrue(EvaluationResultsVisibility::resolve(true, true, false, false, Registration::STATUS_NOTAPPROVED));
        $this->assertTrue(EvaluationResultsVisibility::resolve(true, true, false, false, Registration::STATUS_APPROVED));
    }

    function testOnlyFinalOptionDoesNotPublishWhenCoreDoesNotPublish(): void
    {
        $this->assertFalse(EvaluationResultsVisibility::resolve(false, true, false, false, Registration::STATUS_APPROVED));
    }

    function testAdminSeesDetailsWhenOptionIsEnabledEvenWithoutPublication(): void
    {
        $this->assertTrue(EvaluationResultsVisibility::resolve(false, false, true, true, Registration::STATUS_SENT));
        $this->assertTrue(EvaluationResultsVisibility::resolve(true, true, true, true, Registration::STATUS_SENT));
    }

    function testAdminOptionDoesNotApplyToNonAdmins(): void
    {
        $this->assertFalse(EvaluationResultsVisibility::resolve(false, false, true, false, Registration::STATUS_APPROVED));
    }

    function testAdminWithoutOptionFollowsTheSameRuleAsProponent(): void
    {
        $this->assertFalse(EvaluationResultsVisibility::resolve(false, false, false, true, Registration::STATUS_APPROVED));
        $this->assertFalse(EvaluationResultsVisibility::resolve(true, true, false, true, Registration::STATUS_SENT));
    }

    function testFinalResultStartsAfterSentStatus(): void
    {
        $this->assertFalse(EvaluationResultsVisibility::hasFinalResult(Registration::STATUS_DRAFT));
        $this->assertFalse(EvaluationResultsVisibility::hasFinalResult(Registration::STATUS_SENT));
        $this->assertTrue(EvaluationResultsVisibility::hasFinalResult(Registration::STATUS_NOTAPPROVED));
        $this->assertTrue(EvaluationResultsVisibility::hasFinalResult(Registration::STATUS_APPROVED));
    }
}
