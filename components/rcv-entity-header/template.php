<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-avatar
    mc-title
    rcv-mc-share-links
');
?>
<?php $this->applyTemplateHook('entity-header', 'before'); ?>
<header v-if="!editable" class="entity-header" :class="{ 'entity-header--no-image': !entity.files.header }">
    <?php $this->applyTemplateHook('entity-header', 'begin'); ?>
    <div class="entity-header__single--cover" :style="{ '--url': url(entity.files.header?.url) }"></div>
    
    <div class="entity-header__single--content">
        <div class="leftSide">
            <div class="avatar">
                <mc-avatar :entity="entity" size="big"></mc-avatar>
            </div>

            <?php $this->applyTemplateHook('single2-entity-info-mc-share-links', 'before') ?>
            <div v-if="showField" class="entity-header__share-links">
                <rcv-mc-share-links title="<?php i::esc_attr_e('Compartilhar'); ?>" text="<?php i::esc_attr_e('Veja este link:'); ?>">
                    <template #title>
                        <mc-icon name="section-share"></mc-icon>
                        <?php i::esc_attr_e('Compartilhar'); ?>
                    </template>
                </rcv-mc-share-links>
            </div>
            <?php $this->applyTemplateHook('single2-entity-info-mc-share-links', 'before') ?>
        </div>

        <div class="rightSide">
            <div class="rightSide__data">
                <div class="data">
                    <mc-title tag="h1" size="big" class="entity-header__title"> {{entity.name}} </mc-title>
                    <div v-if="showField" class="metadata">
                        <slot name="metadata">
                            <dl v-if="entity.id && global.showIds[entity.__objectType]" class="metadata__id">
                                <dt class="metadata__id--id"><?= i::__('ID') ?></dt>
                                <dd><strong>{{entity.id}}</strong></dd>
                            </dl>
                            <dl v-if="entity.type?.id == 1">
                                <dt><?= i::__('Tipo') ?></dt>
                                <dd>{{entity.type.name}} </dd>
                            </dl>
                            <dl v-if="entity.type?.id == 1 && entity.updateTimestamp && entity.updateTimestamp._date">
                                <dt><?= i::__('Atualizado em') ?></dt>
                                <dd>
                                    {{ formatDate(entity.updateTimestamp._date) }}
                                </dd>
                            </dl>
    
                        </slot>
                    </div>
                </div>

                <div class="info">
                    <div v-if="entity.site" class="site">
                        <a :href="entity.site" target="_blank"><mc-icon :class="entity.__objectType+'__color'" name="link"></mc-icon>{{entity.site}}</a>
                    </div>
                    <div v-if="showField" class="lastSide">
                        <mc-modal v-if="entity.seals.length > 1" title="Certificados">
                            <template #default="modal">
                                    <div class="entity-header__certificates">
                                        <a v-for="seal in entity.seals" :href="seal.singleUrl" class="link">
                                            <div class="entity-header__certificate">
                                                <div class="entity-header__certificate-image">
                                                    <div v-if="seal.files?.avatar" class="image">
                                                        <mc-avatar :entity="seal" size="small" square></mc-avatar>
                                                    </div>
                                                    <div v-if="!(seal.files?.avatar)">
                                                        <mc-icon name="certificate"></mc-icon>
                                                    </div>
                                                </div>
                                                <span class="entity-header__certificate-label">{{seal.name}}</span>
                                            </div>
                                        </a>
                                    </div>
                            </template>
                            <template #button="modal">
                                <a class="button button--primary" @click="modal.open()"><?= i::__('Certificados') ?></a>
                            </template>
                        </mc-modal>

                        <template v-if="entity.seals.length == 1" v-for="seal in entity.seals">
                            <a :href="seal.singleUrl" class="button button--primary"><?= $this->text('certificateButton', i::__('Certificado')); ?> </a>
                        </template>
                    </div>
                </div>
                <div v-if="showField" class="entity-header__taxonomies">
                    <?php $this->applyTemplateHook('single2-entity-info-taxonomie-area', 'before') ?>
                    <entity-terms :entity="entity" hide-required classes="col-12" taxonomy="area" title="<?php i::esc_attr_e('Áreas de atuação'); ?>"></entity-terms>
                    <?php $this->applyTemplateHook('single2-entity-info-taxonomie-area', 'after') ?>
                </div>
            </div>

        </div>
    </div>

    <?php $this->applyTemplateHook('entity-header', 'end'); ?>

</header>

<header v-if="editable" class="entity-header">
    <?php $this->applyTemplateHook('entity-header', 'begin'); ?>
    <div class="entity-header__edit">
        <div class="entity-header__edit--content">
            <div class="title">
                <div :class="['icon', entity.__objectType+'__background']">
                    <mc-icon :entity="entity"></mc-icon>
                </div>
                <h2 v-if="this.entity.__objectType!='opportunity'">{{titleEdit}}</h2>
                <h2 v-if="this.entity.__objectType=='opportunity'">{{entity.name}}</h2>

            </div>
        </div>
    </div>
    <?php $this->applyTemplateHook('entity-header', 'end'); ?>
</header>
<?php $this->applyTemplateHook('entity-header', 'after'); ?>