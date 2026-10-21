<?php

namespace CulturaViva;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

// Tira a lixeira (status -10) só da contagem do resumo da oportunidade, que não conhece esse status
class RegistrationTrashSummaryFilter extends SQLFilter
{
    public const NAME = 'rcv_lixeira_resumo';

    private const REGISTRATION_CLASS = 'MapasCulturais\\Entities\\Registration';
    private const OPPORTUNITY_CLASS = 'MapasCulturais\\Entities\\Opportunity';

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->getName() !== self::REGISTRATION_CLASS || !self::calledFromSummary(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20))) {
            return '';
        }

        return "{$targetTableAlias}.status <> " . RegistrationTrash::STATUS;
    }

    public static function calledFromSummary(array $trace): bool
    {
        foreach ($trace as $frame) {
            if (($frame['function'] ?? '') === 'getSummary' && is_a($frame['class'] ?? '', self::OPPORTUNITY_CLASS, true)) {
                return true;
            }
        }

        return false;
    }
}
