<?php

namespace CulturaViva;

use MapasCulturais\App;
use MapasCulturais\i;

final class CommitteeMembership
{
    public static function register(): void
    {
        $app = App::i();
        $opportunity_ids = EvaluationDistribution::configuredOpportunityIds();

        // impede o mesmo agente de entrar duas vezes na mesma comissão
        $app->hook('entity(EvaluationMethodConfigurationAgentRelation).validationErrors', function (&$errors) use ($opportunity_ids) {
            /** @var \MapasCulturais\Entities\EvaluationMethodConfigurationAgentRelation $this */
            if (!$this->isNew()) {
                return;
            }

            if (!EvaluationDistribution::isCulturaVivaPhase($this->owner, $opportunity_ids)) {
                return;
            }

            if (!self::alreadyInCommittee($this->owner, $this->group, $this->agent->id)) {
                return;
            }

            $errors['agent'] = [i::__('Esta pessoa já faz parte desta comissão, habilitada ou desabilitada.')];
        });

        // o componente do core ignora o erro da API, então avisa pelo front
        $app->hook('Theme::enqueueComponentScript', function ($result, string $component, array $dependences = []) {
            if ($component !== 'opportunity-evaluation-committee') {
                return;
            }

            $this->enqueueScript('components', 'culturaviva-committee-duplicate-guard', 'js/committee-duplicate-guard.js', ['opportunity-evaluation-committee']);
        }, 1000);

        // acrescenta o texto sem substituir o texts.php do core
        $app->hook('component(<<*>>.<<*>>.opportunity-evaluation-committee).texts', function (&$texts) {
            $texts['avaliadorJaNaComissao'] = i::__('Esta pessoa já faz parte desta comissão, habilitada ou desabilitada.');
        });
    }

    public static function alreadyInCommittee($evaluation_config, ?string $group, $agent_id): bool
    {
        if (!$evaluation_config || !$group || !$agent_id) {
            return false;
        }

        // getAgentRelations traz status > 0, e desabilitado é 8
        foreach ($evaluation_config->getAgentRelations() as $relation) {
            if ($relation->group === $group && $relation->agent->id == $agent_id) {
                return true;
            }
        }

        return false;
    }
}
