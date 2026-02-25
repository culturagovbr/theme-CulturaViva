<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$opportunity_id = $app->config['rcv.opportunityId'];

$this->import('
    mc-link
');
?>

<mc-link :params="{id:<?= $opportunity_id ?>}" class="button button--primary button--icon" icon="external" route="opportunity/registrations" right-icon>
    <h4 class="semibold"><?= i::__("Lista de inscrições") ?></h4>
</mc-link>


<mc-link :params="{id:<?= $opportunity_id ?>}" route="opportunity/allEvaluations" class="opportunity-phase-list-evaluation_buttonbox button button--primary button--icon" icon="external" right-icon>
    <h4 class="semibold"><?= i::__("Lista de avaliações") ?></h4>
</mc-link>
