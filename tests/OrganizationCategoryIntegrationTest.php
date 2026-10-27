<?php

namespace CulturaViva\Tests;

use MapasCulturais\App;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Exceptions\Halt;
use PHPUnit\Framework\TestCase;

// Confere, contra o banco, que o vínculo de uma organização a um cadastro novo do tipo oposto é recusado no servidor.
// Roda só com CULTURAVIVA_INTEGRATION=1 e HTTP_HOST do subsite; tudo acontece numa transação desfeita no fim.
class OrganizationCategoryIntegrationTest extends TestCase
{
    const HOOK = 'POST(registration.createAgentRelation):before';

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
        self::$app->disableAccessControl();
        // no CLI não há requisição; o controller e o MapasBlame precisam de uma
        self::$app->request = new \MapasCulturais\Request(\Slim\Psr7\Factory\ServerRequestFactory::createFromGlobals(), 'registration', 'createAgentRelation', []);
        self::$app->response = (new \Slim\Psr7\Factory\ResponseFactory)->createResponse();
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

    private function profile(): Agent
    {
        $profile_id = self::$app->em->getConnection()->fetchOne("SELECT profile_id FROM usr WHERE profile_id IS NOT NULL ORDER BY id LIMIT 1");

        return self::$app->repo('Agent')->find($profile_id);
    }

    private function organization(?string $cnpj = null): Agent
    {
        $profile = $this->profile();
        $org = new Agent($profile->user);
        $org->type = 2;
        $org->name = 'Organização do teste de categoria';
        $org->parent = $profile;
        if ($cnpj) {
            $org->cnpj = $cnpj;
        }
        $org->save(true);

        return $org;
    }

    private function registration(string $key, ?Agent $org = null, int $status = Registration::STATUS_DRAFT): Registration
    {
        $app = self::$app;
        $registration = new Registration;
        $registration->opportunity = $app->repo('Opportunity')->find($app->config['rcv.opportunityId']);
        $registration->owner = $this->profile();
        $registration->category = $app->config['rcv.categoriesMap'][$key];
        $registration->range = 'Cadastro';
        $registration->save(true);

        if ($org) {
            $registration->createAgentRelation($org, 'coletivo', false, true, true);
        }

        if ($status !== Registration::STATUS_DRAFT) {
            $app->em->getConnection()->executeStatement("UPDATE registration SET status = ? WHERE id = ?", [$status, $registration->id]);
            $app->em->refresh($registration);
        }

        return $registration;
    }

    // dispara o hook como o core faz ao receber o pedido de vínculo; devolve o status HTTP ou null se passou
    private function link(Registration $registration, Agent $org): ?int
    {
        $app = self::$app;
        $controller = $app->controller('registration');
        $controller->setRequestData(['id' => $registration->id]);
        $controller->postData = ['group' => 'coletivo', 'agentId' => $org->id];

        try {
            $app->applyHookBoundTo($controller, self::HOOK);
        } catch (Halt $halt) {
            return $app->response->getStatusCode();
        }

        return null;
    }

    function testRecusaCadastroComCnpjNaOrganizacaoDoColetivo(): void
    {
        $org = $this->organization();
        $this->registration('ponto-coletivo', $org);

        $this->assertSame(403, $this->link($this->registration('pontao'), $org));
        $this->assertSame(403, $this->link($this->registration('ponto-entidade'), $org));
    }

    function testRecusaColetivoNaOrganizacaoComCnpj(): void
    {
        $org = $this->organization('11.222.333/0001-81');

        $this->assertSame(403, $this->link($this->registration('ponto-coletivo'), $org));
    }

    function testRecusaColetivoNaOrganizacaoComEntidadeHabilitada(): void
    {
        $org = $this->organization();
        $this->registration('ponto-entidade', $org, Registration::STATUS_APPROVED);

        $this->assertSame(403, $this->link($this->registration('ponto-coletivo'), $org));
    }

    function testLiberaCasosPrevistos(): void
    {
        $this->assertNull($this->link($this->registration('ponto-coletivo'), $this->organization()), 'coletivo em organização nova');

        $entidade = $this->organization('11.222.333/0001-81');
        $this->registration('ponto-entidade', $entidade, Registration::STATUS_APPROVED);
        $this->assertNull($this->link($this->registration('pontao'), $entidade), 'Pontão em organização que já é entidade');

        $inabilitado = $this->organization();
        $this->registration('ponto-coletivo', $inabilitado, Registration::STATUS_NOTAPPROVED);
        $this->assertNull($this->link($this->registration('pontao'), $inabilitado), 'Pontão em organização com coletivo inabilitado');
    }

    function testNaoAlcancaInscricaoQueJaFoiEnviada(): void
    {
        $org = $this->organization();
        $this->registration('ponto-coletivo', $org);

        $this->assertNull($this->link($this->registration('pontao', null, Registration::STATUS_SENT), $org));
    }
}
