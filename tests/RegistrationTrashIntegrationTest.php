<?php

namespace CulturaViva\Tests;

use CulturaViva\RegistrationTrash;
use MapasCulturais\API;
use MapasCulturais\ApiQuery;
use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Entities\RegistrationEvaluation;
use MapasCulturais\Exceptions\PermissionDenied;
use PHPUnit\Framework\TestCase;

// Confere, contra o banco, a lixeira (-10): fluxos do core, travas e ferramenta do painel.
// Roda só com CULTURAVIVA_INTEGRATION=1 e HTTP_HOST do subsite; tudo acontece numa transação desfeita no fim.
class RegistrationTrashIntegrationTest extends TestCase
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

        // a exceção da trava interrompe o _setStatusTo antes de religar o controle de acesso
        while (!self::$app->isAccessControlEnabled()) {
            self::$app->enableAccessControl();
        }
    }

    private function conn()
    {
        return self::$app->em->getConnection();
    }

    private function mainOpportunityId(): int
    {
        return (int) self::$app->config['rcv.opportunityId'];
    }

    private function firstUserId(): int
    {
        return (int) $this->conn()->fetchOne("SELECT id FROM usr ORDER BY id LIMIT 1");
    }

    // inscrição ativa da primeira fase com organização, colocada como pendente
    private function sentRegistration(): array
    {
        $row = $this->conn()->fetchAssociative("
            SELECT r.id, r.number, ar.agent_id AS org_id
            FROM registration r
            JOIN agent_relation ar ON ar.object_id = r.id AND ar.object_type = 'MapasCulturais\\Entities\\Registration' AND ar.type = 'coletivo'
            WHERE r.opportunity_id = ? AND r.status > 0
            ORDER BY r.id LIMIT 1
        ", [$this->mainOpportunityId()]);

        if (!$row) {
            $this->markTestSkipped('Sem inscrição ativa com organização na oportunidade.');
        }

        $this->conn()->executeStatement("UPDATE registration SET status = 1 WHERE id = ?", [$row['id']]);

        return $row;
    }

    private function trashedRegistrationId(): int
    {
        $registration = $this->sentRegistration();
        $this->conn()->executeStatement(
            "UPDATE registration SET status = -10, valuers = jsonb_build_object(?::text, 'Comissão teste') WHERE id = ?",
            [(string) $this->firstUserId(), $registration['id']]
        );
        self::$app->em->clear();

        return (int) $registration['id'];
    }

    private function state(int $registration_id): array
    {
        return [
            'registration' => $this->conn()->fetchAssociative(
                "SELECT status, valuers::text AS valuers, valuers_exceptions_list::text AS exceptions FROM registration WHERE id = ?",
                [$registration_id]
            ),
            'evaluations' => $this->conn()->fetchAllAssociative(
                "SELECT id, user_id, status, result, committee FROM registration_evaluation WHERE registration_id = ? ORDER BY id",
                [$registration_id]
            ),
        ];
    }

    function testCoreNaoMostraALixeiraNaFilaDoAvaliador(): void
    {
        $id = $this->trashedRegistrationId();
        $count = $this->conn()->fetchOne("SELECT count(*) FROM evaluations WHERE registration_id = ?", [$id]);

        $this->assertSame(0, (int) $count);
    }

    function testCoreNaoMostraALixeiraNaApiSemFiltroDeStatus(): void
    {
        $id = $this->trashedRegistrationId();
        $ids = (new ApiQuery(Registration::class, ['id' => API::IN([$id])]))->findIds();

        $this->assertNotContains($id, array_map('intval', $ids));
    }

    function testRemoveAvaliadoresDaLixeiraAposRedistribuir(): void
    {
        $id = $this->trashedRegistrationId();

        $this->assertGreaterThanOrEqual(1, RegistrationTrash::clearTrashedValuers($this->mainOpportunityId()));
        $this->assertSame('{}', $this->conn()->fetchOne("SELECT valuers::text FROM registration WHERE id = ?", [$id]));
    }

    function testBloqueiaAvaliacaoDeInscricaoNaLixeira(): void
    {
        $app = self::$app;
        // proxy não inicializado, como no envio em lote
        $registration = $app->em->getReference(Registration::class, $this->trashedRegistrationId());

        $evaluation = new RegistrationEvaluation;
        $evaluation->registration = $registration;
        $evaluation->user = $app->repo('User')->find($this->firstUserId());
        $evaluation->committee = 'Comissão teste';

        $app->disableAccessControl();
        try {
            $evaluation->save(true);
            $this->fail('A avaliação de inscrição na lixeira foi salva.');
        } catch (PermissionDenied $e) {
            $this->assertStringContainsString('removida', $e->getMessage());
        } finally {
            $app->enableAccessControl();
        }
    }

    function testBloqueiaSaidaDaLixeiraPelaEntidade(): void
    {
        $app = self::$app;
        $id = $this->trashedRegistrationId();
        $registration = $app->repo('Registration')->find($id);

        $app->disableAccessControl();
        try {
            $registration->setStatusToApproved();
            $this->fail('A inscrição saiu da lixeira pela entidade.');
        } catch (PermissionDenied $e) {
            $this->assertStringContainsString('lixeira', $e->getMessage());
        } finally {
            $app->enableAccessControl();
        }

        $this->assertSame(-10, (int) $this->conn()->fetchOne("SELECT status FROM registration WHERE id = ?", [$id]));
    }

    function testResumoDaOportunidadeNaoContaALixeira(): void
    {
        $this->trashedRegistrationId();
        $summary = self::$app->repo('Opportunity')->find($this->mainOpportunityId())->getSummary(true);
        $expected = $this->conn()->fetchOne("SELECT count(*) FROM registration WHERE opportunity_id = ? AND status <> -10", [$this->mainOpportunityId()]);

        $this->assertSame((int) $expected, (int) $summary['registrations']);
        $this->assertArrayNotHasKey('', $summary);
    }

    function testAcessoExigeSaasSuperAdminNaLista(): void
    {
        $app = self::$app;
        $saas_id = (int) $this->conn()->fetchOne("SELECT usr_id FROM role WHERE name = ? ORDER BY usr_id LIMIT 1", [RegistrationTrash::ROLE]);
        $common_id = (int) $this->conn()->fetchOne("SELECT id FROM usr WHERE id NOT IN (SELECT usr_id FROM role) ORDER BY id LIMIT 1");
        $original = $app->config['rcv.lixeira.usuarios'] ?? '';

        try {
            $app->config['rcv.lixeira.usuarios'] = "{$saas_id},{$common_id}";
            $this->assertTrue(RegistrationTrash::canManage($app->repo('User')->find($saas_id)));
            $this->assertFalse(RegistrationTrash::canManage($app->repo('User')->find($common_id)), 'sem o papel');

            $app->config['rcv.lixeira.usuarios'] = '';
            $this->assertFalse(RegistrationTrash::canManage($app->repo('User')->find($saas_id)), 'fora da lista');
        } finally {
            $app->config['rcv.lixeira.usuarios'] = $original;
        }
    }

    function testEnviaERestauraSemPerderNada(): void
    {
        $app = self::$app;
        $registration = $this->sentRegistration();
        $id = (int) $registration['id'];
        $users = $this->conn()->fetchFirstColumn("SELECT id FROM usr WHERE id <> (SELECT u.id FROM usr u JOIN agent a ON a.user_id = u.id JOIN registration r ON r.agent_id = a.id WHERE r.id = ?) ORDER BY id LIMIT 2", [$id]);

        $this->conn()->executeStatement("DELETE FROM registration_evaluation WHERE registration_id = ?", [$id]);
        $this->conn()->executeStatement(
            "UPDATE registration SET valuers = jsonb_build_object(?::text, 'Comissão teste', ?::text, 'Comissão teste'), valuers_exceptions_list = jsonb_build_object('include', jsonb_build_array(?::int), 'exclude', '[]'::jsonb) WHERE id = ?",
            [(string) $users[0], (string) $users[1], (int) $users[0], $id]
        );
        foreach ([[$users[0], 0], [$users[1], 2]] as [$user_id, $status]) {
            $this->conn()->executeStatement("
                INSERT INTO registration_evaluation (id, registration_id, user_id, result, evaluation_data, status, create_timestamp, committee, is_tiebreaker)
                VALUES (nextval('registration_evaluation_id_seq'), ?, ?, 'valid', '{}', ?, now(), 'Comissão teste', false)
            ", [$id, $user_id, $status]);
        }

        $before = $this->state($id);
        $user = $app->repo('User')->find($this->firstUserId());

        $trashed = RegistrationTrash::trash([$registration['number']], 'motivo do teste de integração', $user);
        $this->assertSame('enviada', $trashed[0]['resultado']);

        $during = $this->state($id);
        $this->assertSame(-10, (int) $during['registration']['status']);
        $this->assertSame('{}', $during['registration']['valuers']);
        $this->assertSame([2], array_map('intval', array_column($during['evaluations'], 'status')), 'só a enviada fica');
        $this->assertNotFalse($this->conn()->fetchOne("SELECT value FROM registration_meta WHERE object_id = ? AND key = ?", [$id, RegistrationTrash::META]));

        $restored = RegistrationTrash::restore([$registration['number']], $user);
        $this->assertSame('restaurada', $restored[0]['resultado']);

        $this->assertEquals($before, $this->state($id));
    }

    function testNaoRestauraComBackupJaUsado(): void
    {
        $app = self::$app;
        $registration = $this->sentRegistration();
        $user = $app->repo('User')->find($this->firstUserId());

        RegistrationTrash::trash([$registration['number']], 'motivo do teste de integração', $user);
        RegistrationTrash::restore([$registration['number']], $user);

        // volta para a lixeira fora da ferramenta
        $this->conn()->executeStatement("UPDATE registration SET status = -10 WHERE id = ?", [$registration['id']]);

        $result = RegistrationTrash::restore([$registration['number']], $user);
        $this->assertSame(['ignorada', ['sem_backup']], [$result[0]['resultado'], $result[0]['motivos']]);
        $this->assertSame(-10, (int) $this->conn()->fetchOne("SELECT status FROM registration WHERE id = ?", [$registration['id']]));

        $listed = array_values(array_filter(RegistrationTrash::listTrashed(), fn ($item) => $item['numero'] === $registration['number']))[0];
        $this->assertFalse($listed['restauravel']);
        $this->assertNull($listed['motivo']);
    }

    function testBloqueiaUnicaCertificacaoDaOrganizacao(): void
    {
        $app = self::$app;
        $registration = $this->sentRegistration();
        $org_id = (int) $registration['org_id'];
        $category = $this->conn()->fetchOne("SELECT category FROM registration WHERE id = ?", [$registration['id']]);
        $type = RegistrationTrash::typeOfCategory($category, $app->config['rcv.categoriesMap']);
        $seal_id = (int) $app->config['rcv.verificationSeals'][$type];

        // única certificação: as demais inscrições da organização deixam de ser ativas
        $this->conn()->executeStatement("
            UPDATE registration SET status = 3
            WHERE id <> :id AND opportunity_id = :main AND status > 0
              AND id IN (SELECT object_id FROM agent_relation WHERE agent_id = :org AND type = 'coletivo' AND object_type = 'MapasCulturais\\Entities\\Registration')
        ", ['id' => $registration['id'], 'main' => $this->mainOpportunityId(), 'org' => $org_id]);
        $this->conn()->executeStatement("UPDATE registration SET status = 10 WHERE id = ?", [$registration['id']]);
        $this->conn()->executeStatement("
            INSERT INTO seal_relation (id, seal_id, object_id, object_type, agent_id, owner_id, status, create_timestamp)
            VALUES (nextval('seal_relation_id_seq'), ?, ?, 'MapasCulturais\\Entities\\Agent', ?, ?, 1, now())
        ", [$seal_id, $org_id, $org_id, $org_id]);

        $item = RegistrationTrash::analyze([$registration['number']])[0];
        $this->assertSame('bloqueada', $item['situacao']);
        $this->assertSame(['unica_certificacao'], $item['motivos']);

        $result = RegistrationTrash::trash([$registration['number']], 'motivo do teste de integração', $app->repo('User')->find($this->firstUserId()));
        $this->assertSame('ignorada', $result[0]['resultado']);
        $this->assertSame(10, (int) $this->conn()->fetchOne("SELECT status FROM registration WHERE id = ?", [$registration['id']]));
    }

    function testMoveEDevolvePonteiroDaOrganizacao(): void
    {
        $app = self::$app;
        $org = $this->conn()->fetchAssociative("
            SELECT ar.agent_id AS org_id, min(r.id) AS first_id, max(r.id) AS other_id
            FROM agent_relation ar JOIN registration r ON r.id = ar.object_id
            WHERE ar.object_type = 'MapasCulturais\\Entities\\Registration' AND ar.type = 'coletivo' AND r.opportunity_id = ? AND r.status > 0
            GROUP BY 1 HAVING count(*) >= 2 LIMIT 1
        ", [$this->mainOpportunityId()]);

        if (!$org) {
            $this->markTestSkipped('Sem organização com duas inscrições ativas.');
        }

        // as duas pendentes: o ponteiro vai para a outra ativa
        $this->conn()->executeStatement("UPDATE registration SET status = 1 WHERE id IN (?, ?)", [$org['first_id'], $org['other_id']]);

        $number = $this->conn()->fetchOne("SELECT number FROM registration WHERE id = ?", [$org['first_id']]);
        $pointer = fn () => $this->conn()->fetchOne("SELECT value FROM agent_meta WHERE object_id = ? AND key = 'rcv_registration'", [$org['org_id']]);

        $this->conn()->executeStatement("DELETE FROM agent_meta WHERE object_id = ? AND key = 'rcv_registration'", [$org['org_id']]);
        $this->conn()->executeStatement(
            "INSERT INTO agent_meta (id, object_id, key, value) VALUES (nextval('agent_meta_id_seq'), ?, 'rcv_registration', ?)",
            [$org['org_id'], RegistrationTrash::registrationReference((int) $org['first_id'])]
        );

        $item = RegistrationTrash::analyze([$number])[0];
        $this->assertContains('ponteiro', $item['motivos']);
        $this->assertSame((int) $org['other_id'], $item['ponteiro']['novo']);

        $user = $app->repo('User')->find($this->firstUserId());
        RegistrationTrash::trash([$number], 'motivo do teste de integração', $user);
        $this->assertSame(RegistrationTrash::registrationReference((int) $org['other_id']), $pointer());

        RegistrationTrash::restore([$number], $user);
        $this->assertSame(RegistrationTrash::registrationReference((int) $org['first_id']), $pointer());
    }
}
