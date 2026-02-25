<?php
/**
 * @var MapasCulturais\Entities\SealRelation $relation
 */

use MapasCulturais\i;

$this->layout = 'seal-relation';

$ponto = $relation->owner;

$creationDate = $relation->createTimestamp->format('d/m/Y');
$updateDate = null;

if($ponto->rcv_last_update_timestamp) {
    $last_update = new \DateTime($ponto->rcv_last_update_timestamp);
    $updateDate = $last_update->format('d/m/Y');
}

$address = "";
$pais = $ponto->En_Pais ?: $app->config['app.defaultCountry'];
$pais = $pais == 'BR' ? 'Brasil' : $pais;

if ($ponto->En_Pais && $ponto->En_Municipio && $ponto->En_Estado) {
    $address = "
        <p>{$pais}</p>
        <p>{$ponto->En_Municipio}</p>
        <p>{$ponto->En_Estado}</p>
    ";
} else {
    $address = "<p>".i::__('Não se aplica')."</p>";
}
$app->disableAccessControl();
$cnpj = trim($ponto->cnpj);
if (is_string($cnpj) && empty($cnpj)) {
    $cnpj = I::__("Não se aplica");
}
$app->enableAccessControl();

$this->import('
    mc-icon
');

?>

<div class="main-app">
    <div id="print">
        <div class="rcv-certificate-actions">
            <div class="rcv-certificate-back">
                <a href="<?= $app->createUrl('agente', '', [$ponto->id])?>" class="button button--icon button--sm button--primary-outline" >
                    <mc-icon name="arrow-left"> </mc-icon> <?php i::_e('Voltar')?>
                </a>
            </div>
            <button class="button button--primary-outline button--icon button--sm rcv-certificate-print" onclick="window.print();">
                <mc-icon name="print"></mc-icon> <?php i::_e("Salvar");?>
            </button>
        </div>
        <div class="rcv-certificate">
            <div class="rcv-certificate__inner">
                <div class="rcv-certificate__background">
                    <img src="<?php $this->asset('img/certificate/folhagem.svg'); ?>" alt="" />
                </div>
                <div class="rcv-certificate__header">
                    <div class="rcv-certificate__header-left">
                        <img src="<?php $this->asset('img/certificate/selo-ponto.png'); ?>" alt="Ponto de Cultura" />
                    </div>

                    <div class="rcv-certificate__header-right">
                        <h1 class='rcv-certificate__cadastro'><?= i::__('Cadastro nacional de pontos e pontões de cultura') ?></h1>
                        <?php if (!$ponto->name):?>
                            <h2> <?= $ponto->nomeCompleto ?></h2>
                        <?php else: ?>
                            <h2><?= $ponto->name ?></h2>
                            <p><?= $ponto->nomeCompleto ?></p>
                        <?php endif; ?>
                        <small>ID: <?= $ponto->id ?></small>
                    </div>
                </div>
                <div class="rcv-certificate__content">
                    <p class="rcv-certificate__text">
                        <?= i::__(' O Ministério da Cultura, por meio da Secretaria de Cidadania e Diversidade Cultural, certifica esta
                                    organização como <b>Ponto de Cultura</b>, de acordo os critérios e normativas da <b>Política Nacional Cultura Viva</b>
                                    (Lei n° 13.018/2014). O reconhecimento valoriza as expressões, a formação e o fazer cultural desenvolvidos
                                    na comunidade, a articulação na rede Cultura Viva e as contribuições para o acesso, a proteção e a
                                    promoção dos direitos culturais no Brasil.');?>
                    </p>
                    <div class="rcv-certificate__form">
                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <?= $address ?>
                            </span>
                            <small class="rcv-certificate__form-legend">Localização</small>
                        </div>

                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <p><?= $creationDate ?></p>
                            </span>
                            <small class="rcv-certificate__form-legend">Data da certificação</small>
                        </div>

                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <p><?= $updateDate ?></p>
                            </span>
                            <small class="rcv-certificate__form-legend">Data da atualização</small>
                        </div>

                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <p><?= $cnpj ?></p>
                            </span>
                            <small class="rcv-certificate__form-legend">CNPJ</small>
                        </div>
                    </div>
                </div>
                <div class="rcv-certificate__footer">
                    <div class="rcv-certificate__footer-left">
                        <div class="rcv-certificate__profile-link">
                            <a href="<?= $app->createUrl('agent', 'single', [$ponto->id]) ?>" class="rcv-certificate__profile-click hide">
                                <mc-icon name="cursor-click" />
                            </a>

                            <div class="rcv-certificate__profile-qrcode">
                                <vue-qrcode 
                                    value="<?= $app->createUrl('agent', 'single', [$ponto->id]) ?>" 
                                    :options="{
                                        color: {
                                            dark: '#223169',
                                            light: '#FFFFFF',
                                        },
                                        width: 60, 
                                        margin: 0,
                                    }"
                                    tag="svg">
                                </vue-qrcode>
                            </div>

                            <div class="rcv-certificate__profile-text">
                                <?= i::__('<span class="hide">Clique no botão <br> ou</span> escaneie o QR-Code <br> para ver o perfil completo.') ?>
                            </div>
                        </div>
                    </div>

                    <div class="rcv-certificate__footer-right">
                        <img src="<?php $this->asset('img/certificate/logos.png'); ?>" />
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>