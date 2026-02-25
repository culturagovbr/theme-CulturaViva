<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-confirm-button
    mc-loading
');
?>
<div v-if="!empty && show" class="entity-actions">
    <?php $this->applyTemplateHook('entity-actions', 'before') ?>
    <div class="entity-actions__content">
        <?php $this->applyTemplateHook('entity-actions', 'begin'); ?>
        <mc-loading :entity="entity"></mc-loading>
        <template v-if="!entity.__processing">
            <?php $this->applyTemplateHook('entity-actions', 'begin') ?>

            <div class="entity-actions__content--groupBtn rowBtn" ref="buttons1">
                <?php $this->applyTemplateHook('entity-actions--primary', 'begin') ?>
                
                <?php $this->applyTemplateHook('entity-actions--primary', 'end') ?>
            </div>
            <?php $this->applyTemplateHook('entity-actions--leftGroupBtn', 'after'); ?>

            <div v-if="editable" class="entity-actions__content--groupBtn" ref="buttons2">
                <?php $this->applyTemplateHook('entity-actions--secondary', 'begin') ?>
                <mc-confirm-button v-if="entity.status == 0" @confirm="exit()">
                    <template #button="modal">
                        <button @click="modal.open()" class="button button--md publish publish-exit">
                            <?php i::_e("Sair") ?>
                        </button>
                    </template>
                    <template #message="message">
                        <?php i::_e('Deseja sair?') ?>
                    </template>
                </mc-confirm-button>
                <button v-if="entity.currentUserPermissions?.modify" @click="save()" class="button button--md publish publish-exit">
                    <?php i::_e("Salvar") ?>
                </button>
                <mc-confirm-button v-if="(entity.status == 0 || entity.status == -2) && entity.currentUserPermissions?.publish && entity.type.name !== 'Coletivo'" @confirm="entity.publish()">
                    <template #button="modal">
                        <button @click="modal.open()" class="button button--md publish publish-exit">
                            <?php i::_e("Salvar e publicar") ?>
                        </button>
                    </template>
                    <template #message="message">
                        <?php i::_e('Você está certo que deseja publicar esse perfil?') ?>
                    </template>
                </mc-confirm-button>
                <button v-if="entity.status == 1 && entity.currentUserPermissions?.modify" @click="exit()" class="button button--md publish publish-exit">
                    <?php i::_e("Sair") ?>
                </button>

                <?php $this->applyTemplateHook('entity-actions--secondary', 'end') ?>
            </div>

            <div v-if="!editable" class="entity-actions__content--groupBtn" ref="buttons2">
                <?php $this->applyTemplateHook('entity-actions--secondary', 'begin') ?>
                <a v-if="entity.currentUserPermissions?.modify && entity.__objectType!='opportunity' && entity.type.name === 'Coletivo'" :href="entity.editUrl" class="button button button--md publish">
                    <?php i::_e('Editar perfil') ?>
                </a>
                <mc-link v-if="entity.currentUserPermissions?.modify && entity.__objectType!='opportunity' && entity.type.name === 'Coletivo'" class="button button button--md update" route="site/atualizacao-cadastral" right-icon>
                    <?php i::_e('Atualização de cadastro') ?>
                </mc-link>
                <a v-if="entity.currentUserPermissions?.modify && entity.__objectType=='opportunity' && entity.type.name !== 'Coletivo'" :href="entity.editUrl" class="button button button--md publish">
                    <?php i::_e('Gerenciar') ?> {{entityType}}
                </a>
                <a v-if="entity.currentUserPermissions?.modify && entity.__objectType!='opportunity' && entity.type.name !== 'Coletivo'" :href="entity.editUrl" class="button button button--md publish">
                    <?php i::_e('Editar') ?> {{entityType}}
                </a>
                <mc-link v-if="entity.currentUserPermissions?.modify && entity.__objectType!='opportunity' && entity.type.name !== 'Coletivo'" class="button button button--md update" route="site/atualizacao-cadastral" right-icon>
                    <?php i::_e('Atualizar dados') ?>
                </mc-link>
                <?php $this->applyTemplateHook('entity-actions--secondary', 'end') ?>
            </div>
            <?php $this->applyTemplateHook('entity-actions', 'end') ?>
        </template>
        <?php $this->applyTemplateHook('entity-actions', 'end'); ?>
    </div>
    <?php $this->applyTemplateHook('entity-actions', 'after') ?>
</div>