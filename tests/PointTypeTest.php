<?php

namespace CulturaViva\Tests;

use CulturaViva\PointType;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/PointType.php';

class PointTypeTest extends TestCase
{
    const SEALS = ['pontao' => '101', 'ponto' => '6', 'desativado' => '116'];

    private function resolve(array $seal_ids, bool $has_cnpj, $declared_types = null): array
    {
        return PointType::resolve($seal_ids, $has_cnpj, $declared_types, self::SEALS);
    }

    function testSeloDePontaoDefinePontao(): void
    {
        $this->assertSame([PointType::PONTAO], $this->resolve([101], true, ['pontao']));
    }

    function testSeloDePontaoDispensaDeclaracao(): void
    {
        $this->assertSame([PointType::PONTAO], $this->resolve([101], true, []));
    }

    function testDeclaracaoDePontaoSemSeloNaoDefinePontao(): void
    {
        $this->assertSame([PointType::ENTIDADE], $this->resolve([6], true, ['pontao']));
    }

    function testTipoAcumuladoNaoDefineMaisDeUmPonto(): void
    {
        $declared = ['ponto_coletivo', 'ponto_entidade', 'pontao'];

        $this->assertSame([PointType::ENTIDADE], $this->resolve([6], true, $declared));
    }

    function testCnpjDefineEntidadeMesmoDeclarandoColetivo(): void
    {
        $this->assertSame([PointType::ENTIDADE], $this->resolve([6], true, ['ponto_coletivo']));
    }

    function testSemCnpjADeclaracaoDesempata(): void
    {
        $this->assertSame([PointType::ENTIDADE], $this->resolve([6], false, ['ponto_entidade']));
    }

    function testSemCnpjESemDeclaracaoEhColetivo(): void
    {
        $this->assertSame([PointType::COLETIVO], $this->resolve([6], false, []));
    }

    function testOsDoisSelosDefinemOsDoisTipos(): void
    {
        $this->assertSame([PointType::PONTAO, PointType::ENTIDADE], $this->resolve([6, 101], true, ['pontao']));
    }

    function testSelosAdministrativosNaoDefinemTipo(): void
    {
        $this->assertSame([], $this->resolve([105, 117], true, ['pontao']));
        $this->assertSame([PointType::ENTIDADE], $this->resolve([6, 105, 117], true, ['ponto_entidade']));
    }

    function testSemSeloNaoTemTipo(): void
    {
        $this->assertSame([], $this->resolve([], true, ['pontao']));
    }

    function testDeclaracaoComoJsonCru(): void
    {
        $this->assertSame([PointType::ENTIDADE], $this->resolve([6], false, '["ponto_entidade"]'));
        $this->assertSame([PointType::COLETIVO], $this->resolve([6], false, '["ponto_coletivo"]'));
    }

    function testDeclaracaoAusenteOuInvalida(): void
    {
        $this->assertSame([PointType::COLETIVO], $this->resolve([6], false, null));
        $this->assertSame([PointType::COLETIVO], $this->resolve([6], false, 'texto solto'));
    }

    function testChaveDoCategoriesMapUsaHifen(): void
    {
        $this->assertSame('ponto-entidade', PointType::categoryKey(PointType::ENTIDADE));
        $this->assertSame('pontao', PointType::categoryKey(PointType::PONTAO));
    }
}
