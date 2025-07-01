<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-confirm-button
    mc-icon
    mc-alert
    rcv-exit-form-modal
    rcv-save-later-modal
');

$route = $app->request->route;

$entity = $this->controller->requestedEntity;
$term_url = $app->createUrl('site', 'termoAdesao');
?>
<div class="registration-actions">
    <div class="registration-actions__primary">
        <div v-if="hasErrors" class="registration-actions__errors">
            <span class="registration-actions__errors-title"> <?= i::__('Ops! Encontramos erros no preenchimento do cadastro') ?> </span>
            <span class="registration-actions__errors-subtitle">
                <?= i::__('Corrija os campos listados antes de enviar o formulário') ?>
            </span>

            <div class="registration-actions__errors-list scrollbar" :class="{'registration-actions__errors-list--hide' : hideErrors}">
                <template v-for="(errors, stepIndex) in sortedValidationErrors" :key="stepIndex">
                    <div class="registration-actions__errors-step" v-if="Object.keys(errors).length > 0">
                        <div class="registration-actions__errors-step-name">{{stepName(stepIndex)}}</div>
                        <div v-for="(error, key) in errors" class="registration-actions__error" tabindex="0" @click="goToField(stepIndex, key)" @keydown.enter="goToField(stepIndex, key)">
                            <strong>{{fieldName(key)}}</strong> <p v-for="text in error">{{text}}</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="registration-actions__validation">
            <template v-if="!isValidated">
                <div class="registration-actions__alert">
                    <div class="registration-actions__alert-header">
                        <mc-icon name="exclamation"></mc-icon>
                        <span class="bold"><?= i::__('Atenção aos campos obrigatórios') ?></span>
                    </div>
                    <div class="registration-actions__alert-content">
                        <span><?= $this->text('registration_actions_alert_content', i::__('Só é possível enviar o cadastro após o preenchimento de todos os campos obrigatórios'))?></span>
                    </div>
                </div>
            </template>
            <mc-loading v-if="isLastStep" :entity="registration"></mc-loading>
            <mc-confirm-button 
                v-if="isLastStep && !registration.__processing && !hasEditableFields " 
                title="<?= $entity->opportunity->id == ($app->config['rcv.pnabOpportunityId'] ?? '') ? i::esc_attr__('Quer enviar a importação?') : ($entity->status == 0 ? i::esc_attr__('Quer enviar sua inscrição?') : i::esc_attr__('Quer enviar sua atualização?')) ?>"
                yes="<?= i::esc_attr__('Enviar') ?>" 
                no="<?= i::esc_attr__('Cancelar') ?>" 
                @confirm="send($event)"
                dont-close-on-confirm
                :loading="registration.__processing"
            >
                <template #button="modal">
                    <button v-if="!hasEditableFields" @click="modal.open()" class="button button--large button--xbg button--primary button--icon registration-actions__send">
                        <?= i::__("Enviar formulário") ?>
                        <mc-icon name="send"></mc-icon>
                    </button>
                </template>
                <template #message="message">
                    <?php if($entity->opportunity->id == ($app->config['rcv.opportunityId'] ?? '')): ?>
                        <?php if($route != "GET registration.registrationEdit"):?>
                            <?= $this->text('registration_registrationEdit', i::__('Ao enviar sua inscrição você afirma que concorda com os termos da'))?> <a href="<?=$term_url?>" >PNCV</a>. 
                        <?php endif ?>
                    <?php endif; ?>
                </template>
            </mc-confirm-button>

            <mc-confirm-button 
                v-if="!registration.__processing && hasEditableFields" 
                title="<?= ($route != "GET registration.registrationEdit") ? i::esc_attr__('Quer enviar sua inscrição?') : i::esc_attr__('Quer finalizar sua atualização?') ?>"
                yes="<?= i::esc_attr__('Finalizar agora') ?>" 
                no="<?= i::esc_attr__('Cancelar') ?>" 
                @confirm="send($event)"
                dont-close-on-confirm
                :loading="registration.__processing"
            >
                <template #button="modal">
                    <button  @click="modal.open()" class="button button--large button--xbg button--primary button--icon registration-actions__send">
                        <?= i::__("Salvar e finalizar") ?>
                        <mc-icon name="send"></mc-icon>
                    </button>
                </template>
                <template #message="message">
                    <?php if($entity->opportunity->id == ($app->config['rcv.opportunityId'] ?? '')): ?>
                        <?php if($route != "GET registration.registrationEdit"):?>
                            <?= $this->text('registration_registrationEdit', i::__('Ao enviar sua inscrição você afirma que concorda com os termos da'))?> <a href="<?=$term_url?>" >PNCV</a>. 
                        <?php endif ?>
                    <?php endif; ?>
                </template>
            </mc-confirm-button>
        </div>
    </div>

    <div v-if="stepIndex < steps.length - 1 || stepIndex > 0" class="registration-actions__secondary">
        <button @click="nextStep()" class="button button--bg button--large button--primary  button--icon" v-if="stepIndex < steps.length - 1">
            <?= i::__("Próxima etapa") ?>
            <mc-icon name="arrow-right"></mc-icon>
        </button>

        <button @click="previousStep()" class="button button--md button--large button--primary-outline button--icon" v-if="stepIndex > 0">
            <mc-icon name="arrow-left"></mc-icon>
            <?= i::__("Etapa anterior") ?>
        </button>
    </div>
    
    <mc-loading v-if="!isLastStep" :entity="registration"></mc-loading>

    <div v-show="!registration.__processing" class="registration-actions__save-buttons">
        <button @click="save()" class="button button--sm button--large button--primary">
            <?= i::__("Salvar") ?>
        </button>

        
        <mc-modal classes="rcv-exit-form-modal" button-label="label do botão" title="<?= i::__('Atenção!') ?>">
            <template #default="modal">
                <div class="rcv-exit-form-modal modal-options grid-12">
                    <?php if($route == "GET registration.registrationEdit"):?>
                        <p class="col-12"><?= i::__('Sua atualização foi salva, mas não publicada, tem certeza que deseja sair? ') ?>
                        </p>
                    <?php else:?>
                        <p class="col-12"><?= i::__('Seu cadastro foi salvo em rascunho. Para retornar, vá ao <b><u>Painel de Controle</u></b> e acesse 
                            <b><u>Meus Cadastros</u></b>. Na aba de <b>Não enviadas</b>, você encontrará seu cadastro para continuar.') ?>
                        </p>
                    <?php endif?>
                </div>

            </template>

            <template #button="modal">
                <button  @click="saveAndExit(modal)" class="button button--sm button--large button--primary">
                    <?= i::__("Salvar e Sair") ?>
                </button>
            </template>

            <template #actions="modal">
                <button class="button button--md button--primary" @click="exit(modal)">
                    <?= i::__('Sair') ?>
                </button>
            </template>
        </mc-modal>
    </div>
</div>