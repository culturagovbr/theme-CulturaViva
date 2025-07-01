<?php
/**
 * @var MapasCulturais\Entities\SealRelation $relation
 */
use MapasCulturais\i;

$this->layout = 'seal-relation';

$pontao = $relation->owner;

$creationDate = $relation->createTimestamp->format('d/m/Y');
$updateDate = $pontao->updateTimestamp->format('d/m/Y');

$address = "";
$pais = $pontao->En_Pais ?: $app->config['app.defaultCountry'];
$pais = $pais == 'BR' ? 'Brasil' : $pais;

if ($pontao->En_Pais && $pontao->En_Municipio && $pontao->En_Estado) {
    $address = "
        <p>{$pais}</p>
        <p>{$pontao->En_Municipio}</p>
        <p>{$pontao->En_Estado}</p>
    ";
} else {
    $address = "<p>".i::__('Não se aplica')."</p>";
}

$app->disableAccessControl();
$cnpj = trim($pontao->cnpj ?: '');
$app->enableAccessControl();

if (is_string($cnpj) && empty($cnpj)) {
    $cnpj = I::__("Não se aplica");
}

$this->import(' 
    mc-icon
');

?>
<div class="main-app">
    <div id="print">
        <div class="rcv-certificate-actions">
            <div class="rcv-certificate-back">
                <a href="<?= $app->createUrl('agente', '', [$pontao->id])?>" class="button button--icon button--sm button--primary-outline" >
                    <mc-icon name="arrow-left"> </mc-icon> <?php i::_e('Voltar')?>
                </a>
            </div>
            <button class="button button--primary button--icon button--sm rcv-certificate-print" onclick="window.print();">
                <mc-icon name="print"></mc-icon> <?php i::_e("Salvar");?>
            </button>
        </div>
        <div class="rcv-certificate rcv-certificate--blue">
            <div class="rcv-certificate__inner">
                <div class="rcv-certificate__background">
                    <img src="<?php $this->asset('img/certificate/folhagem.svg'); ?>" alt="" />
                </div>
                <div class="rcv-certificate__header">
                    <div class="rcv-certificate__header-left">
                        <img src="<?php $this->asset('img/certificate/selo-pontao.png'); ?>" alt="Pontão de Cultura" />
                    </div>
                    <div class="rcv-certificate__header-right">
                        <h1 class='rcv-certificate__cadastro'><?= i::__('Cadastro nacional de pontos e pontões de cultura') ?></h1>
                        <?php if (!$pontao->name):?>
                            <h2> <?= $pontao->nomeCompleto ?></h2>
                        <?php else: ?>
                            <h2><?= $pontao->name ?></h2>
                            <p><?= $pontao->nomeCompleto ?></p>
                        <?php endif; ?>
                        <small>ID: <?= $pontao->id ?></small>
                    </div>
                </div>

                <div class="rcv-certificate__content">
                    <p class="rcv-certificate__text">
                        <?= i::__(' O Ministério da Cultura, por meio da Secretaria de Cidadania e Diversidade Cultural, certifica esta
                                    organização como <b>Pontão de Cultura</b>, de acordo os critérios e normativas da <b>Política Nacional Cultura Viva</b>
                                    (Lei n° 13.018/2014). O reconhecimento valoriza as expressões, a formação e o fazer cultural desenvolvidos
                                    na comunidade, a articulação na rede Cultura Viva e as contribuições para o acesso, a proteção e a
                                    promoção dos direitos culturais no Brasil.');?>
                    </p>
                    <div class="rcv-certificate__form">
                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <?= $address ?>
                            </span>
                            <small class="rcv-certificate__form-legend"><?= i::__('Localização') ?></small>
                        </div>
        
                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <p><?= $creationDate ?></p>
                            </span>
                            <small class="rcv-certificate__form-legend"><?= i::__('Data da certificação') ?></small>
                        </div>

                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <p><?= $updateDate ?></p>
                            </span>
                            <small class="rcv-certificate__form-legend"><?= i::__('Data da atualização') ?></small>
                        </div>

                        <div class="rcv-certificate__form-data">
                            <span class="rcv-certificate__form-info">
                                <p><?= $cnpj ?></p>
                            </span>
                            <small class="rcv-certificate__form-legend"><?= i::__('CNPJ') ?></small>
                        </div>
                    </div>
                </div>
                <div class="rcv-certificate__footer">
                    <div class="rcv-certificate__footer-left">
                        <div class="rcv-certificate__profile-link">
                            <a href="<?= $app->createUrl('agent', 'single', [$pontao->id]) ?>" class="rcv-certificate__profile-click hide">
                                <mc-icon name="cursor-click" />
                            </a>

                            <div class="rcv-certificate__profile-qrcode">
                                <vue-qrcode 
                                    value="<?= $app->createUrl('agent', 'single', [$pontao->id]) ?>" 
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