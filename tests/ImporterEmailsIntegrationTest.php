<?php

namespace CulturaViva\Tests;

use CulturaViva\Importer;
use MapasCulturais\App;
use MapasCulturais\Entities\Registration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

// Confere, contra o banco, os e-mails enviados ao fim da importação.
// Roda só com CULTURAVIVA_INTEGRATION=1 e HTTP_HOST do subsite; tudo acontece numa transação desfeita no fim.
class ImporterEmailsIntegrationTest extends TestCase
{
    private static ?App $app = null;

    /** @var Email[]|null e-mails capturados no teste em andamento */
    private static ?array $sent = null;

    private $always_to;

    private $previous_user;

    public static function setUpBeforeClass(): void
    {
        $bootstrap = dirname(__DIR__, 4) . '/html/bootstrap.php';

        if (getenv('CULTURAVIVA_INTEGRATION') !== '1' || !file_exists($bootstrap)) {
            return;
        }

        require_once $bootstrap;
        self::$app = App::i();

        // Troca o transporte por um que só guarda as mensagens.
        self::$app->hook('mailer.transport', function (&$transport) {
            if (self::$sent !== null) {
                $transport = new class extends AbstractTransport {
                    protected function doSend(SentMessage $message): void
                    {
                        ImporterEmailsIntegrationTest::capture($message->getOriginalMessage());
                    }

                    public function __toString(): string
                    {
                        return 'captura://';
                    }
                };
            }
        });
    }

    public static function capture($message): void
    {
        self::$sent[] = $message;
    }

    protected function setUp(): void
    {
        if (!self::$app) {
            $this->markTestSkipped('Teste de integração: rodar com CULTURAVIVA_INTEGRATION=1 no container.');
        }

        self::$app->em->getConnection()->beginTransaction();
        self::$app->em->clear();

        $this->previous_user = self::$app->user->is('guest') ? null : self::$app->user;
        $this->always_to = self::$app->config['mailer.alwaysTo'];
        self::$app->config['mailer.alwaysTo'] = false;
        self::$sent = [];
    }

    protected function tearDown(): void
    {
        if (!self::$app) {
            return;
        }

        self::$sent = null;
        self::$app->config['mailer.alwaysTo'] = $this->always_to;
        self::$app->auth->authenticatedUser = $this->previous_user;

        $conn = self::$app->em->getConnection();
        while ($conn->isTransactionActive()) {
            $conn->rollBack();
        }
        self::$app->em->clear();

        while (!self::$app->isAccessControlEnabled()) {
            self::$app->enableAccessControl();
        }
    }

    // Inscrição cujo dono tem usuário com e-mail, com o e-mail privado do dono trocado pelo informado.
    private function registrationWithPrivateEmail(?string $private_email): Registration
    {
        $conn = self::$app->em->getConnection();
        $row = $conn->fetchAssociative("
            SELECT r.id, a.id AS agent_id, a.user_id
            FROM registration r
            JOIN agent a ON a.id = r.agent_id
            JOIN usr u ON u.id = a.user_id
            WHERE u.email IS NOT NULL AND u.email <> ''
            ORDER BY r.id DESC LIMIT 1
        ");

        if (!$row) {
            $this->markTestSkipped('Sem inscrição com dono que tenha usuário com e-mail.');
        }

        $conn->executeStatement(
            "DELETE FROM agent_meta WHERE object_id = ? AND key = 'emailPrivado'",
            [$row['agent_id']]
        );
        if ($private_email !== null) {
            $conn->executeStatement(
                "INSERT INTO agent_meta (id, object_id, key, value) VALUES (nextval('agent_meta_id_seq'), ?, 'emailPrivado', ?)",
                [$row['agent_id'], $private_email]
            );
        }

        $this->loginAsUserWithoutAccessTo((int) $row['user_id']);
        self::$app->em->clear();

        return self::$app->repo('Registration')->find($row['id']);
    }

    // Autentica, como o job, um usuário sem papel e que não é o dono do agente.
    private function loginAsUserWithoutAccessTo(int $owner_user_id): void
    {
        $user_id = self::$app->em->getConnection()->fetchOne("
            SELECT u.id FROM usr u
            WHERE u.id <> ? AND u.status > 0
              AND NOT EXISTS (SELECT 1 FROM role r WHERE r.usr_id = u.id)
            ORDER BY u.id LIMIT 1
        ", [$owner_user_id]);

        if (!$user_id) {
            $this->markTestSkipped('Sem usuário comum para executar o envio.');
        }

        self::$app->auth->authenticatedUser = self::$app->repo('User')->find($user_id);
    }

    private function row(int $line, ?string $to): array
    {
        return [
            'line'     => $line,
            'scenario' => 4,
            'email'    => [
                'to'                => $to,
                'user_name'         => "Responsável {$line}",
                'organization_name' => "Organização {$line}",
                'organization_cnpj' => '00.000.000/0001-91',
                'category'          => 'Ponto',
            ],
        ];
    }

    private function plan(Registration $pnab, array $rows): array
    {
        return [
            'pnab_registration' => $pnab,
            'total_rows'        => count($rows),
            'rows'              => $rows,
        ];
    }

    private function recipients(): array
    {
        return array_map(
            fn(Email $email) => $email->getTo()[0]->getAddress(),
            self::$sent
        );
    }

    function testResumoVaiParaOEmailPrivadoQuandoOUsuarioLogadoNaoPodeVerDadosPrivados(): void
    {
        $pnab = $this->registrationWithPrivateEmail('privado@teste.local');
        $this->assertFalse($pnab->owner->canUser('viewPrivateData'));

        Importer::sendEmails($this->plan($pnab, [$this->row(2, 'linha2@teste.local')]));

        $this->assertSame(['linha2@teste.local', 'privado@teste.local'], $this->recipients());
    }

    function testResumoVaiParaOEmailDoUsuarioQuandoFaltaOPrivado(): void
    {
        $pnab = $this->registrationWithPrivateEmail(null);

        Importer::sendEmails($this->plan($pnab, [$this->row(2, 'linha2@teste.local')]));

        $this->assertSame(['linha2@teste.local', $pnab->owner->user->email], $this->recipients());
    }

    function testFalhaEmUmEmailNaoInterrompeOsDemais(): void
    {
        $pnab = $this->registrationWithPrivateEmail(null);
        $rows = [
            $this->row(2, 'linha2@teste.local'),
            $this->row(3, null),
            $this->row(4, 'linha4@teste.local'),
        ];

        try {
            Importer::sendEmails($this->plan($pnab, $rows));
            $this->fail('Era esperada a exceção com as falhas de envio.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('linha 3:', $e->getMessage());
            $this->assertStringNotContainsString('linha 2:', $e->getMessage());
            $this->assertStringNotContainsString('linha 4:', $e->getMessage());
            $this->assertStringNotContainsString('resumo do gestor', $e->getMessage());
        }

        $this->assertSame(
            ['linha2@teste.local', 'linha4@teste.local', $pnab->owner->user->email],
            $this->recipients()
        );
    }

    function testAvisoDeFalhaVaiParaOEmailDoUsuarioQuandoFaltaOPrivado(): void
    {
        $registration = $this->registrationWithPrivateEmail(null);

        Importer::sendEmailError($registration, new \Exception('erro de teste'));

        $this->assertSame([$registration->owner->user->email], $this->recipients());
    }
}
