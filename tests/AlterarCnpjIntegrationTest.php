<?php

namespace CulturaViva\Tests;

use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use PHPUnit\Framework\TestCase;

// Confere, contra o banco, que o CNPJ pedido na alteração de CNPJ só chega à organização depois da aprovação.
// Roda só com CULTURAVIVA_INTEGRATION=1 e HTTP_HOST do subsite; tudo acontece numa transação desfeita no fim.
class AlterarCnpjIntegrationTest extends TestCase
{
    const CNPJ_SOLICITADO = '11.222.333/0001-81';
    const NOME_SOLICITADO = 'ORGANIZACAO DO TESTE DE ALTERACAO DE CNPJ';

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

    private function conn()
    {
        return self::$app->em->getConnection();
    }

    // inscrição da primeira fase com organização, colocada como pendente, como fica depois do pedido
    private function sentRegistration(): array
    {
        $row = $this->conn()->fetchAssociative("
            SELECT r.id, ar.agent_id AS org_id
            FROM registration r
            JOIN agent_relation ar ON ar.object_id = r.id AND ar.object_type = 'MapasCulturais\\Entities\\Registration' AND ar.type = 'coletivo'
            WHERE r.opportunity_id = ? AND r.status > 0
            ORDER BY r.id LIMIT 1
        ", [(int) self::$app->config['rcv.opportunityId']]);

        if (!$row) {
            $this->markTestSkipped('Sem inscrição ativa com organização na oportunidade.');
        }

        $this->conn()->executeStatement("UPDATE registration SET status = 1 WHERE id = ?", [$row['id']]);
        self::$app->em->clear();

        return $row;
    }

    private function orgMeta(int $org_id, string $key)
    {
        return $this->conn()->fetchOne("SELECT value FROM agent_meta WHERE object_id = ? AND key = ?", [$org_id, $key]);
    }

    private function requestChange(int $registration_id): Registration
    {
        $app = self::$app;
        $registration = $app->repo('Registration')->find($registration_id);

        $app->disableAccessControl();
        $app->view->setRcvCnpjRequest($registration, ['cnpj' => self::CNPJ_SOLICITADO, 'nomeCompleto' => self::NOME_SOLICITADO]);
        $registration->save(true);
        $app->enableAccessControl();

        return $registration;
    }

    private function pendingRequest(int $registration_id)
    {
        self::$app->em->clear();

        return self::$app->repo('Registration')->find($registration_id)->rcv_cnpj_solicitado;
    }

    function testPedidoNaoMexeNaOrganizacaoAntesDaAprovacao(): void
    {
        $row = $this->sentRegistration();
        $cnpj_antes = $this->orgMeta($row['org_id'], 'cnpj');

        $this->requestChange($row['id']);

        $this->assertSame($cnpj_antes, $this->orgMeta($row['org_id'], 'cnpj'));
        $this->assertSame(self::CNPJ_SOLICITADO, $this->pendingRequest($row['id'])['cnpj']);
    }

    function testAprovacaoAplicaOCnpjSolicitado(): void
    {
        $app = self::$app;
        $row = $this->sentRegistration();
        $registration = $this->requestChange($row['id']);

        $app->disableAccessControl();
        $registration->setStatusToApproved();
        $app->enableAccessControl();

        $this->assertSame(self::CNPJ_SOLICITADO, $this->orgMeta($row['org_id'], 'cnpj'));
        $this->assertSame(self::NOME_SOLICITADO, $this->orgMeta($row['org_id'], 'nomeCompleto'));
        $this->assertEmpty($this->pendingRequest($row['id']));
    }

    function testRecusaDescartaOPedidoEMantemOCnpj(): void
    {
        $app = self::$app;
        $row = $this->sentRegistration();
        $cnpj_antes = $this->orgMeta($row['org_id'], 'cnpj');
        $registration = $this->requestChange($row['id']);

        $app->disableAccessControl();
        $registration->setStatusToNotApproved();
        $app->enableAccessControl();

        $this->assertSame($cnpj_antes, $this->orgMeta($row['org_id'], 'cnpj'));
        $this->assertEmpty($this->pendingRequest($row['id']));
    }

    // é o que a API da inscrição faz com os dados do PATCH; o pedido não pode vir por aí
    function testGravacaoDiretaDoPedidoEIgnorada(): void
    {
        $app = self::$app;
        $row = $this->sentRegistration();
        $registration = $app->repo('Registration')->find($row['id']);

        $app->disableAccessControl();
        $registration->rcv_cnpj_solicitado = ['cnpj' => self::CNPJ_SOLICITADO, 'nomeCompleto' => self::NOME_SOLICITADO];
        $registration->save(true);
        $app->enableAccessControl();

        $this->assertEmpty($this->pendingRequest($row['id']));
    }

    function testCnpjPendenteContaComoEmUsoNaChecagemDeDuplicidade(): void
    {
        $app = self::$app;
        $digits = preg_replace('/\D/', '', self::CNPJ_SOLICITADO);
        $row = $this->sentRegistration();
        $em_uso_antes = $app->view->countRcvRegistrationsWithColetivoCnpjFromOtherResponsible($app, $digits, null);

        $this->requestChange($row['id']);

        $this->assertSame($em_uso_antes + 1, $app->view->countRcvRegistrationsWithColetivoCnpjFromOtherResponsible($app, $digits, null));
    }

    // outra organização, na mesma categoria, tenta enviar cadastro com o CNPJ que está pendente no pedido
    function testEnvioComCnpjPendenteEmOutroPedidoEBloqueado(): void
    {
        $app = self::$app;
        $row = $this->sentRegistration();
        $this->requestChange($row['id']);
        $pedido = $app->repo('Registration')->find($row['id']);

        $outra = $this->conn()->fetchAssociative("
            SELECT r.id, ar.agent_id AS org_id
            FROM registration r
            JOIN agent_relation ar ON ar.object_id = r.id AND ar.object_type = 'MapasCulturais\\Entities\\Registration' AND ar.type = 'coletivo'
            WHERE r.opportunity_id = ? AND r.category = ? AND r.id <> ? AND ar.agent_id <> ?
            ORDER BY r.id LIMIT 1
        ", [(int) $app->config['rcv.opportunityId'], $pedido->category, $row['id'], $row['org_id']]);

        if (!$outra) {
            $this->markTestSkipped('Sem outra inscrição com organização na mesma categoria.');
        }

        $app->disableAccessControl();
        $org = $app->repo('Agent')->find($outra['org_id']);
        $org->cnpj = self::CNPJ_SOLICITADO;
        $org->save(true);
        $app->enableAccessControl();
        $this->conn()->executeStatement("UPDATE registration SET range = ? WHERE id = ?", [$app->config['rcv.rangesMap']['cadastro'], $outra['id']]);
        $app->em->clear();

        $registration = $app->repo('Registration')->find($outra['id']);
        // o cnpj da organização é privado; no envio real quem lê é o dono logado
        $errors = [];
        $app->disableAccessControl();
        $app->applyHookBoundTo($registration, 'entity(Registration).sendValidationErrors', [&$errors]);
        $app->enableAccessControl();

        $this->assertArrayHasKey('agent_cnpj', $errors);
    }

    function testAprovacaoSemPedidoNaoMexeNoCnpj(): void
    {
        $app = self::$app;
        $row = $this->sentRegistration();
        $cnpj_antes = $this->orgMeta($row['org_id'], 'cnpj');
        $registration = $app->repo('Registration')->find($row['id']);

        $app->disableAccessControl();
        $registration->setStatusToApproved();
        $app->enableAccessControl();

        $this->assertSame($cnpj_antes, $this->orgMeta($row['org_id'], 'cnpj'));
    }
}
