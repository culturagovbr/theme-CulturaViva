<?php

namespace CulturaViva;

use MapasCulturais\App;
use MapasCulturais\i;
use MapasCulturais\Entities\EvaluationMethodConfiguration;
use MapasCulturais\Entities\Registration;
use Opportunities\Module as OpportunitiesModule;

final class EvaluationResultsVisibility
{
    // segura os pareceres publicados até o resultado final
    public const META_ONLY_FINAL = 'publicarPareceresApenasResultadoFinal';

    // administradores veem os pareceres mesmo sem publicação
    public const META_ADMIN_VIEW = 'administradoresVeemPareceres';

    public static function register(): void
    {
        $app = App::i();

        self::registerMetadata();

        // prioridade 1000: roda depois do core, que só inclui os pareceres publicados
        $app->hook('entity(Registration).jsonSerialize', function (&$data) use ($app) {
            /** @var Registration $this */
            $evaluation_config = $this->opportunity->evaluationMethodConfiguration;

            if (!$evaluation_config) {
                return;
            }

            $published = array_key_exists('evaluationsDetails', $data);

            if (!self::shouldDisplay($this, $published)) {
                unset($data['consolidatedDetails'], $data['evaluationsDetails']);
                return;
            }

            if ($published) {
                return;
            }

            $em = $evaluation_config->evaluationMethod;
            $data['consolidatedDetails'] = $em->getConsolidatedDetails($this);
            $data['evaluationsDetails'] = [];

            foreach ($this->sentEvaluations as $evaluation) {
                $detail = $em->getEvaluationDetails($evaluation);
                OpportunitiesModule::enrichEvaluationDetailWithValuerInfo($detail, $this, $evaluation_config, $evaluation, $app);
                $data['evaluationsDetails'][] = $detail;
            }
        }, 1000);

        // libera o detalhamento para administradores no registration-status
        $app->hook('Theme::enqueueComponentScript', function ($result, string $component, array $dependences = []) {
            if ($component !== 'registration-status') {
                return;
            }

            $this->enqueueScript('components', 'culturaviva-evaluation-results-visibility', 'js/evaluation-results-visibility.js', ['registration-status']);
        }, 1000);

        // adiciona as opções logo abaixo da publicação de resultados
        $app->hook('component(opportunity-phase-publish-date-config):after', function () {
            /** @var Theme $this */
            $this->part('evaluation-results-visibility-config');
        });
    }

    // registra os metadados de configuração da EMC
    public static function registerMetadata(): void
    {
        $app = App::i();
        $theme = $app->view;

        $theme->registerEvauationMethodConfigurationMetadata(self::META_ONLY_FINAL, [
            'label' => i::__('Exibir os pareceres somente após o resultado final (habilitado ou inabilitado)'),
            'type' => 'boolean',
            'default' => true,
        ]);

        $theme->registerEvauationMethodConfigurationMetadata(self::META_ADMIN_VIEW, [
            'label' => i::__('Permitir que administradores vejam os pareceres, mesmo sem publicá-los'),
            'type' => 'boolean',
            'default' => true,
        ]);

        // publicação dos pareceres do core nasce marcada; o módulo já a registrou quando o tema inicia
        if ($publish_details = $app->getRegisteredMetadataByMetakey('publishEvaluationDetails', EvaluationMethodConfiguration::class)) {
            $publish_details->default_value = true;
        }
    }

    // aplica as opções do tema sobre a decisão do core ($published)
    public static function shouldDisplay(Registration $registration, bool $published): bool
    {
        $evaluation_config = $registration->opportunity->evaluationMethodConfiguration;

        if (!$evaluation_config) {
            return $published;
        }

        return self::resolve(
            $published,
            self::isEnabled($evaluation_config, self::META_ONLY_FINAL),
            self::isEnabled($evaluation_config, self::META_ADMIN_VIEW),
            App::i()->user->is('admin'),
            (int) $registration->status
        );
    }

    public static function adminCanView(Registration $registration): bool
    {
        $evaluation_config = $registration->opportunity->evaluationMethodConfiguration;

        return $evaluation_config
            && self::isEnabled($evaluation_config, self::META_ADMIN_VIEW)
            && App::i()->user->is('admin');
    }

    // regra pura, sem depender do App, para os testes
    public static function resolve(bool $published, bool $only_final, bool $admin_view, bool $is_admin, int $status): bool
    {
        if ($admin_view && $is_admin) {
            return true;
        }

        if ($published && $only_final && !self::hasFinalResult($status)) {
            return false;
        }

        return $published;
    }

    // a autoaplicação só troca o status depois que todas as avaliações foram enviadas
    public static function hasFinalResult(int $status): bool
    {
        return $status > Registration::STATUS_SENT;
    }

    private static function isEnabled(EvaluationMethodConfiguration $evaluation_config, string $meta): bool
    {
        return filter_var($evaluation_config->$meta, FILTER_VALIDATE_BOOLEAN);
    }
}
