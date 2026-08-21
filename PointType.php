<?php

namespace CulturaViva;

// O metadado tipoPonto acumula todos os tipos que a organização já declarou, então quem define o
// tipo é o selo. Entidade e coletivo compartilham o selo de ponto e se distinguem pelo CNPJ.
final class PointType
{
    const PONTAO = 'pontao';
    const ENTIDADE = 'ponto_entidade';
    const COLETIVO = 'ponto_coletivo';

    const SLUGS = [self::PONTAO, self::ENTIDADE, self::COLETIVO];

    public static function resolve(array $seal_ids, bool $has_cnpj, mixed $declared_types, array $verification_seals): array
    {
        $seal_ids = array_map('intval', $seal_ids);
        $types = [];

        if (in_array((int) ($verification_seals['pontao'] ?? 0), $seal_ids, true)) {
            $types[] = self::PONTAO;
        }

        if (in_array((int) ($verification_seals['ponto'] ?? 0), $seal_ids, true)) {
            $types[] = self::isEntity($has_cnpj, $declared_types) ? self::ENTIDADE : self::COLETIVO;
        }

        return $types;
    }

    // ter CNPJ prova que é entidade; não ter não prova o contrário, então a declaração desempata
    public static function isEntity(bool $has_cnpj, mixed $declared_types): bool
    {
        if ($has_cnpj) {
            return true;
        }

        return in_array(self::ENTIDADE, self::normalizeDeclaredTypes($declared_types), true);
    }

    // o metadado chega como array quando registrado no subsite e como json cru fora dele
    public static function normalizeDeclaredTypes(mixed $declared_types): array
    {
        if (is_string($declared_types)) {
            $declared_types = json_decode($declared_types, true);
        }

        return is_array($declared_types) ? $declared_types : [];
    }

    // o rcv.categoriesMap usa hífen no lugar do underline
    public static function categoryKey(string $slug): string
    {
        return str_replace('_', '-', $slug);
    }
}
