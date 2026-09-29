<?php

namespace CulturaViva\Tests;

use CulturaViva\RegistrationTrash;
use CulturaViva\RegistrationTrashSummaryFilter;
use MapasCulturais\GuestUser;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/RegistrationTrash.php';
require_once dirname(__DIR__) . '/RegistrationTrashSummaryFilter.php';

class RegistrationTrashTest extends TestCase
{
    const CATEGORIES = ['pontao' => 'Pontão de Cultura (entidade com CNPJ)', 'ponto-entidade' => 'Ponto de Cultura (entidade com CNPJ)'];

    function testReconheceSomenteOStatusDaLixeira(): void
    {
        $this->assertTrue(RegistrationTrash::isTrashed(-10));
        $this->assertTrue(RegistrationTrash::isTrashed('-10'));

        foreach ([null, 0, 1, 2, 3, 8, 10, -9, -2] as $status) {
            $this->assertFalse(RegistrationTrash::isTrashed($status), "status {$status}");
        }
    }

    function testBloqueiaSaidaDaLixeira(): void
    {
        foreach ([0, 1, 2, 3, 8, 10] as $new_status) {
            $this->assertTrue(RegistrationTrash::blocksStatusChange(-10, $new_status), "-10 para {$new_status}");
        }
    }

    function testPermiteManterNaLixeira(): void
    {
        $this->assertFalse(RegistrationTrash::blocksStatusChange(-10, -10));
    }

    function testNaoInterfereForaDaLixeira(): void
    {
        $this->assertFalse(RegistrationTrash::blocksStatusChange(1, 10));
        $this->assertFalse(RegistrationTrash::blocksStatusChange(1, -10));
        $this->assertFalse(RegistrationTrash::blocksStatusChange(null, 1));
    }

    function testVisitanteENuloNaoAcessam(): void
    {
        $this->assertFalse(RegistrationTrash::canManage(GuestUser::i()));
        $this->assertFalse(RegistrationTrash::canManage(null));
    }

    function testLeNumerosComSeparadoresMisturados(): void
    {
        $parsed = RegistrationTrash::parseNumbers("on-123; on-456,789\n  on-1011\ton-1213  ON-1415");

        $this->assertSame(['on-123', 'on-456', 'on-789', 'on-1011', 'on-1213', 'on-1415'], $parsed['numbers']);
        $this->assertSame([], $parsed['invalid']);
    }

    function testIgnoraRepetidosEListaInvalidos(): void
    {
        $parsed = RegistrationTrash::parseNumbers('on-123; 123; on-0123; abc; on-; 12a');

        $this->assertSame(['on-123'], $parsed['numbers']);
        $this->assertSame(['abc', 'on-', '12a'], $parsed['invalid']);
    }

    function testEntradaVazia(): void
    {
        $this->assertSame(['numbers' => [], 'invalid' => []], RegistrationTrash::parseNumbers(" ;\n, "));
    }

    function testAceitaSomenteUsuariosDaLista(): void
    {
        $this->assertTrue(RegistrationTrash::isAllowedUserId(7, '3, 7,12'));
        $this->assertTrue(RegistrationTrash::isAllowedUserId(7, ['7']));
        $this->assertFalse(RegistrationTrash::isAllowedUserId(8, '3,7,12'));
        $this->assertFalse(RegistrationTrash::isAllowedUserId(7, ''));
        $this->assertFalse(RegistrationTrash::isAllowedUserId(0, '0'));
        $this->assertFalse(RegistrationTrash::isAllowedUserId(7, '7abc'));
    }

    function testIdentificaTipoPelaCategoria(): void
    {
        $this->assertSame('pontao', RegistrationTrash::typeOfCategory(self::CATEGORIES['pontao'], self::CATEGORIES));
        $this->assertSame('ponto', RegistrationTrash::typeOfCategory(self::CATEGORIES['ponto-entidade'], self::CATEGORIES));
        $this->assertSame('ponto', RegistrationTrash::typeOfCategory(null, self::CATEGORIES));
    }

    function testBloqueiaNaoEncontradaEJaNaLixeira(): void
    {
        $this->assertSame(['situacao' => 'bloqueada', 'motivos' => ['nao_encontrada']], RegistrationTrash::classify(['found' => false]));
        $this->assertSame(['situacao' => 'bloqueada', 'motivos' => ['ja_na_lixeira']], RegistrationTrash::classify(['found' => true, 'status' => -10]));
    }

    function testBloqueiaUnicaCertificacao(): void
    {
        $result = RegistrationTrash::classify(['found' => true, 'status' => 10, 'org_has_seal' => true, 'other_certified_same_type' => false]);

        $this->assertSame(['situacao' => 'bloqueada', 'motivos' => ['unica_certificacao']], $result);
    }

    function testAvisaCertificacaoDuplicada(): void
    {
        $result = RegistrationTrash::classify(['found' => true, 'status' => 10, 'org_has_seal' => true, 'other_certified_same_type' => true]);

        $this->assertSame(['situacao' => 'aviso', 'motivos' => ['certificacao_duplicada']], $result);
    }

    function testAvisaCertificadaSemSelo(): void
    {
        $result = RegistrationTrash::classify(['found' => true, 'status' => 10, 'org_has_seal' => false, 'other_certified_same_type' => false]);

        $this->assertSame(['situacao' => 'aviso', 'motivos' => ['certificada_sem_selo']], $result);
    }

    function testAvisaPonteiroDaOrganizacao(): void
    {
        $result = RegistrationTrash::classify(['found' => true, 'status' => 1, 'pointer_to_this' => true]);

        $this->assertSame(['situacao' => 'aviso', 'motivos' => ['ponteiro']], $result);
    }

    function testLiberaQualquerOutroStatus(): void
    {
        foreach ([0, 1, 2, 3, 8] as $status) {
            $this->assertSame(['situacao' => 'liberada', 'motivos' => []], RegistrationTrash::classify(['found' => true, 'status' => $status]), "status {$status}");
        }
    }

    function testPonteiroPrefereCertificadaDoMesmoTipo(): void
    {
        $candidates = [
            ['id' => 1, 'status' => 1, 'type' => 'ponto', 'timestamp' => '2026-09-01'],
            ['id' => 2, 'status' => 10, 'type' => 'pontao', 'timestamp' => '2026-09-02'],
            ['id' => 3, 'status' => 10, 'type' => 'ponto', 'timestamp' => '2025-01-01'],
        ];

        $this->assertSame(3, RegistrationTrash::choosePointerTarget($candidates, 'ponto'));
    }

    function testPonteiroUsaCertificadaDeOutroTipoAntesDaAtiva(): void
    {
        $candidates = [
            ['id' => 1, 'status' => 1, 'type' => 'ponto', 'timestamp' => '2026-09-01'],
            ['id' => 2, 'status' => 10, 'type' => 'pontao', 'timestamp' => '2025-01-01'],
        ];

        $this->assertSame(2, RegistrationTrash::choosePointerTarget($candidates, 'ponto'));
    }

    function testPonteiroUsaAtivaMaisRecente(): void
    {
        $candidates = [
            ['id' => 1, 'status' => 1, 'type' => 'ponto', 'timestamp' => '2026-01-01'],
            ['id' => 2, 'status' => 3, 'type' => 'ponto', 'timestamp' => '2026-09-01'],
        ];

        $this->assertSame(2, RegistrationTrash::choosePointerTarget($candidates, 'ponto'));
    }

    function testPonteiroVazioSemCandidatas(): void
    {
        $this->assertNull(RegistrationTrash::choosePointerTarget([], 'ponto'));
    }

    function testBackupJaUsadoNaoValeParaRestaurar(): void
    {
        $this->assertTrue(RegistrationTrash::hasUsableRecord('{"status": 1, "motivo": "x"}'));
        $this->assertFalse(RegistrationTrash::hasUsableRecord('{"status": 1, "restaurada": {"usuario": 3}}'));
        $this->assertFalse(RegistrationTrash::hasUsableRecord(null));
        $this->assertFalse(RegistrationTrash::hasUsableRecord('nao e json'));
    }

    function testFiltroDoResumoSoAgeDentroDoGetSummary(): void
    {
        $this->assertTrue(RegistrationTrashSummaryFilter::calledFromSummary([
            ['function' => 'addFilterConstraint', 'class' => RegistrationTrashSummaryFilter::class],
            ['function' => 'getSummary', 'class' => 'MapasCulturais\\Entities\\Opportunity'],
        ]));

        $this->assertFalse(RegistrationTrashSummaryFilter::calledFromSummary([
            ['function' => 'find', 'class' => 'Doctrine\\ORM\\EntityRepository'],
            ['function' => 'getSummary', 'class' => 'MapasCulturais\\Entities\\EvaluationMethodConfiguration'],
        ]));
    }
}
