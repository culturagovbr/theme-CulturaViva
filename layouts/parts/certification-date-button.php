<?php

/**
 * Botão de alteração da data de certificação, só para quem pode alterá-la.
 *
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\SealRelation $relation
 */

use CulturaViva\CertificationDate;

if (!CertificationDate::canManage($app->user)) {
    return;
}

$this->import('
    rcv-certification-date
');
?>

<rcv-certification-date relation-id="<?= $relation->id ?>" current-date="<?= $relation->createTimestamp->format('d/m/Y') ?>" min-year="<?= CertificationDate::MIN_YEAR ?>" today="<?= date('Y-m-d') ?>"></rcv-certification-date>
