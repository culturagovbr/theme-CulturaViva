<?php

namespace CulturaViva\Tests;

use CulturaViva\OrganizationCategory;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/OrganizationCategory.php';

class OrganizationCategoryTest extends TestCase
{
    const CATEGORIES = [
        'pontao' => 'Pontão de Cultura (entidade com CNPJ)',
        'ponto-entidade' => 'Ponto de Cultura (entidade com CNPJ)',
        'ponto-coletivo' => 'Ponto de Cultura (coletivo sem CNPJ)',
    ];

    private function conflict(string $subscription_key, bool $org_has_cnpj, array $registrations = []): ?string
    {
        return OrganizationCategory::conflict($subscription_key, $org_has_cnpj, $registrations, self::CATEGORIES);
    }

    private function registration(string $key, int $status): array
    {
        return ['category' => self::CATEGORIES[$key], 'status' => $status];
    }

    function testCadastroComCnpjRecusaOrganizacaoDoColetivo(): void
    {
        foreach ([0, 1, 10] as $status) {
            $registrations = [$this->registration('ponto-coletivo', $status)];

            $this->assertSame(OrganizationCategory::IS_COLETIVO, $this->conflict('pontao', false, $registrations), "pontao, coletivo {$status}");
            $this->assertSame(OrganizationCategory::IS_COLETIVO, $this->conflict('ponto-entidade', false, $registrations), "entidade, coletivo {$status}");
        }
    }

    function testCadastroDeColetivoRecusaOrganizacaoComCnpj(): void
    {
        $this->assertSame(OrganizationCategory::IS_ENTIDADE, $this->conflict('ponto-coletivo', true));
    }

    function testCadastroDeColetivoRecusaOrganizacaoComInscricaoDeEntidadeOuPontao(): void
    {
        foreach (['ponto-entidade', 'pontao'] as $key) {
            $registrations = [$this->registration($key, 10)];

            $this->assertSame(OrganizationCategory::IS_ENTIDADE, $this->conflict('ponto-coletivo', false, $registrations), $key);
        }
    }

    function testInscricaoInabilitadaOuNaLixeiraNaoConta(): void
    {
        foreach ([3, -10] as $status) {
            $this->assertNull($this->conflict('pontao', true, [$this->registration('ponto-coletivo', $status)]), "coletivo {$status}");
            $this->assertNull($this->conflict('ponto-coletivo', false, [$this->registration('ponto-entidade', $status)]), "entidade {$status}");
        }
    }

    function testCasosPrevistosContinuamLiberados(): void
    {
        $this->assertNull($this->conflict('ponto-coletivo', false), 'coletivo em organização nova');
        $this->assertNull($this->conflict('ponto-entidade', true), 'entidade em organização com CNPJ');
        $this->assertNull($this->conflict('pontao', true, [$this->registration('ponto-entidade', 10)]), 'Pontão em organização que já é entidade');
        $this->assertNull($this->conflict('pontao', false), 'Pontão em organização nova');
    }

    function testStatusChegaComoTexto(): void
    {
        $this->assertSame(OrganizationCategory::IS_COLETIVO, $this->conflict('pontao', false, [['category' => self::CATEGORIES['ponto-coletivo'], 'status' => '1']]));
    }
}
