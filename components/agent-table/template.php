<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$canUserControl = $app->view->canUserControlRCV();

$this->import('
    entity-table
    mc-alert
    mc-icon
    mc-export-spreadsheet
    mc-states-and-cities
    rcv-structuring-actions
');
?>

<div class="agent-table">
    <entity-table type="agent" identifier="agentTable" :query="mergedQuery" :headers="headers" endpoint="find" required="name,type" :visible="visibleColumns" @clear-filters="clearFilters" @remove-filter="removeFilter($event)" :hide-advanced-filters="true" show-index all-headers :filtersDictComplement="filtersDictComplement">
        <template #actions="{entities, spreadsheetQuery}">
            <div class="agent-table__actions">
                <h4 class="bold"><?= i::__('Ações:') ?></h4>
                <mc-export-spreadsheet :owner="owner" endpoint="entities" :params="{entityType: 'agent', query: spreadsheetQuery}" group="entities-spreadsheets"></mc-export-spreadsheet>
                <mc-alert type="warning">
                    <div>
                        <?= i::__('Só serão exportados dados das organizações que permitiram sua divulgação.') ?>
                    </div>
                </mc-alert>
            </div>
        </template>

        <template #filters="{entities}">
            <div class="agent-table__multiselects grid-12">
                <mc-multiselect class="col-3 sm:col-6" :model="selectedArea" :items="terms" placeholder="<?= i::esc_attr__('Selecione as áreas: ') ?>" @selected="filterByArea(entities)" @removed="filterByArea(entities)" :hide-filter="hideFilters" hide-button></mc-multiselect>

                <mc-multiselect class="col-3 sm:col-6" :model="selectedPoint" :items="typePoint" placeholder="<?= i::esc_attr__('Tipo de Ponto ') ?>" @selected="filterByPoint(entities,$event)" @removed="filterByPoint(entities,$event)" :hide-filter="hideFilters" hide-button></mc-multiselect>

                <mc-states-and-cities field-class="col-3 sm:col-6" hide-labels hide-tags v-model:model-states="selectedStates" v-model:model-cities="selectedCities" @changeCities="filterByCities(entities)" @changeStates="filterByState(entities)"></mc-states-and-cities>

                <slot name="filters" v-bind="{entities}"></slot>

                <rcv-structuring-actions field-class="col-3 sm:col-6" hide-labels hide-tags v-model:model-actions="selectedStructuringActions" v-model:model-other-actions="selectedOtherStructuringActions" @changeActions="filterByStructuringActions(entities)" @changeOtherActions="filterByOtherStructuringActions(entities)"></rcv-structuring-actions>
            </div>

            <div class="agent-table__inputs">
                <?php if ($canUserControl) : ?>
                    <div class="field">
                        <div class="field__group">
                            <label class="field__checkbox">
                                <input v-model="query['@verified']" @change="() => { if (mergedQuery['@verified'] == null) { delete mergedQuery['@verified']; } }" type="checkbox" :true-value="null" false-value="1" />
                                <?php i::_e('Exibir organizações não certificadas') ?>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </template>

        <template #singleUrl="{entity}">
            <a :href="entity.singleUrl">{{entity.id}}</a>
        </template>

        <template v-if="hasSlot('advanced-filters')" #advanced-filters="{entities}">
            <slot name="advanced-filters" v-bind="{entities}"></slot>
        </template>

        <template #paisPontaPontao="{entity}">
            {{entity.En_Pais || entity.paisPontaPontao || entity.pais}}
        </template>

        <template #tipoPonto="{ entity }">
        {{
            (Array.isArray(entity.tipoPonto) ? entity.tipoPonto : [entity.tipoPonto])
            .map(tipo =>
                tipo === 'ponto_entidade' ? 'Ponto de Cultura (entidade com CNPJ)' :
                tipo === 'pontao' ? 'Pontão de Cultura (entidade com CNPJ)' :
                tipo === 'ponto_coletivo' ? 'Ponto de Cultura (coletivo sem CNPJ)' :
                tipo
            )
            .join(', ')
        }}
        </template>

        <template #cepPontaPontao="{entity}">
            {{entity.En_CEP || entity.cepPontaPontao}}
        </template>

        <template #En_Nome_LogradouroPontaPontao="{entity}">
            {{entity.En_Nome_Logradouro || entity.En_Nome_LogradouroPontaPontao}}
        </template>

        <template #En_NumPontaPontao="{entity}">
            {{entity.En_Num || entity.En_NumPontaPontao}}
        </template>

        <template #En_MunicipioPontaPontao="{entity}">
            {{entity.En_Municipio || entity.En_MunicipioPontaPontao}}
        </template>

        <template #En_EstadoPontaPontao="{entity}">
            {{ entity.En_Estado || entity.En_EstadoPontaPontao}}
        </template>

        <template #seals="{entity}">
            {{getMcDate(entity.seals[0]?.createTimestamp)?.date('numeric year')}}
        </template>

        <template #outrosSelos="{entity}">
            {{othersSeals(entity)}}
        </template>

        <template #icon-text="popover">
            <button class="agent-table__button button button--icon button--primary button--sm">
               <?= i::__('Selecionar dados') ?>
            </button>
        </template>
    </entity-table>
</div>