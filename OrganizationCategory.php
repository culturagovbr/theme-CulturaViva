<?php

namespace CulturaViva;

use MapasCulturais\Entities\Registration;

// Uma organização é coletivo sem CNPJ ou entidade com CNPJ, nunca as duas; o Pontão é sempre entidade.
// O formulário de novo cadastro grava o CNPJ na organização escolhida, então sem esta regra um cadastro com CNPJ
// iniciado na organização de um coletivo deixava o CNPJ nela e no certificado do coletivo.
// A mesma regra está no disabledEntity de components/rcv-point-subscription/script.js.
final class OrganizationCategory
{
    // a organização já é coletivo e não pode entrar num cadastro com CNPJ
    const IS_COLETIVO = 'coletivo';

    // a organização tem CNPJ ou cadastro de entidade e não pode entrar num cadastro de coletivo
    const IS_ENTIDADE = 'entidade';

    /**
     * @param string $subscription_key chave do rcv.categoriesMap do cadastro novo
     * @param array $registrations inscrições da organização na oportunidade, cada uma com category e status
     */
    public static function conflict(string $subscription_key, bool $org_has_cnpj, array $registrations, array $categories_map): ?string
    {
        $coletivo = $categories_map['ponto-coletivo'];

        $active_categories = [];
        foreach ($registrations as $registration) {
            if (self::isActive((int) $registration['status'])) {
                $active_categories[] = $registration['category'];
            }
        }

        if ($subscription_key === 'ponto-coletivo') {
            $has_cnpj_registration = (bool) array_diff($active_categories, [$coletivo]);

            return $org_has_cnpj || $has_cnpj_registration ? self::IS_ENTIDADE : null;
        }

        return in_array($coletivo, $active_categories, true) ? self::IS_COLETIVO : null;
    }

    // rascunho, enviada e habilitada contam; inabilitada e lixeira não
    public static function isActive(int $status): bool
    {
        return $status >= Registration::STATUS_DRAFT && $status !== Registration::STATUS_NOTAPPROVED;
    }
}
