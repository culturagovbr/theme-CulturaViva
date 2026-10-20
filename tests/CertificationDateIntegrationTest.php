<?php

namespace CulturaViva\Tests;

use CulturaViva\CertificationDate;
use DateTime;
use MapasCulturais\App;
use MapasCulturais\Entities\AgentSealRelation;
use PHPUnit\Framework\TestCase;

// Confere, contra o banco, a gravação da nova data na relação de selo.
// Roda só com CULTURAVIVA_INTEGRATION=1 e HTTP_HOST do subsite; tudo acontece numa transação desfeita no fim.
class CertificationDateIntegrationTest extends TestCase
{
    private static ?App $app = null;

    public static function setUpBeforeClass(): void
    {
        $bootstrap = dirname(__DIR__, 4) . '/html/bootstrap.php';

        if (getenv('CULTURAVIVA_INTEGRATION') !== '1' || !file_exists($bootstrap)) {
            return;
        }

        require_once $bootstrap;
        self::$app = App::i();
    }

    protected function setUp(): void
    {
        if (!self::$app) {
            $this->markTestSkipped('Teste de integração: rodar com CULTURAVIVA_INTEGRATION=1 no container.');
        }

        self::$app->em->getConnection()->beginTransaction();
        self::$app->em->clear();
    }

    protected function tearDown(): void
    {
        if (!self::$app) {
            return;
        }

        $conn = self::$app->em->getConnection();
        while ($conn->isTransactionActive()) {
            $conn->rollBack();
        }
        self::$app->em->clear();

        while (!self::$app->isAccessControlEnabled()) {
            self::$app->enableAccessControl();
        }
    }

    private function verificationRelation(): AgentSealRelation
    {
        $seals = self::$app->config['rcv.verificationSeals'];
        $seal = self::$app->repo('Seal')->find((int) $seals['ponto']);
        $relation = $seal ? self::$app->repo(AgentSealRelation::class)->findOneBy(['seal' => $seal]) : null;

        if (!$relation) {
            $this->markTestSkipped('Nenhuma organização certificada como Ponto no banco.');
        }

        return $relation;
    }

    function testSavesNewDateAndRecalculatesExpiration(): void
    {
        $relation = $this->verificationRelation();
        $previous = $relation->createTimestamp->format('d/m/Y');
        $valid_period = (int) $relation->seal->validPeriod;

        $result = CertificationDate::apply($relation, new DateTime('2015-03-10'));

        $this->assertSame((int) $relation->id, $result['relation']);
        $this->assertSame((int) $relation->owner->id, $result['organization']);
        $this->assertSame($previous, $result['previous']);
        $this->assertSame('10/03/2015', $result['date']);

        self::$app->em->clear();
        $saved = self::$app->repo(AgentSealRelation::class)->find($relation->id);

        $this->assertSame('2015-03-10 00:00:00', $saved->createTimestamp->format('Y-m-d H:i:s'));
        $this->assertSame(
            CertificationDate::expirationFor(new DateTime('2015-03-10'), $valid_period)->format('Y-m-d'),
            $saved->validateDate->format('Y-m-d')
        );
        $this->assertTrue(self::$app->isAccessControlEnabled());
    }

    function testErrorMessages(): void
    {
        $this->assertStringContainsString((string) CertificationDate::MIN_YEAR, CertificationDate::errorMessage('too_old'));
        $this->assertStringContainsString('futuro', CertificationDate::errorMessage('future'));
        $this->assertStringContainsString('dd/mm/aaaa', CertificationDate::errorMessage('invalid'));
        $this->assertStringContainsString('dd/mm/aaaa', CertificationDate::errorMessage('anything'));
    }

    function testRecognizesOnlyRelationsWithVerificationSeal(): void
    {
        $relation = $this->verificationRelation();
        $seals = self::$app->config['rcv.verificationSeals'];

        $this->assertTrue(CertificationDate::isVerificationRelation($relation, $seals));

        $other = self::$app->repo(AgentSealRelation::class)->findOneBy(['seal' => self::$app->repo('Seal')->find((int) self::$app->config['rcv.waitingUpdateSeal'])]);
        if ($other) {
            $this->assertFalse(CertificationDate::isVerificationRelation($other, $seals));
        }
    }
}
