<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$canUserControl = $app->view->canUserControlRCV();

$this->import('
    mc-multiselect 
    mc-tag-list 
    mc-states-and-cities
    search-filter 
    rcv-countries
    rcv-structuring-actions
');

?>
<search-filter :position="position" :pseudo-query="pseudoQuery">
    <label class="form__label">
        <?= i::_e('Filtrar Pontos e Pontões') ?>
    </label>
    <div class="foundResults" v-if="count">
        {{ count }} <?= i::__('Pontos e Pontões encontrados com endereço') ?>
    </div>
    <form class="form scrollbar" @submit="$event.preventDefault()">
        <?php $this->applyTemplateHook('search-filter-agent', 'begin') ?>

        <div class="field">
            <label> <?php i::_e('Tipo de ponto') ?></label>
            <mc-multiselect :model="pseudoQuery['tipoPonto']" placeholder="<?php i::_e('Selecione os tipos de ponto') ?>" :items="{ponto_coletivo: 'Ponto Coletivo', ponto_entidade: 'Ponto Entidade', pontao: 'Pontão'}" hide-filter hide-button></mc-multiselect>
            <mc-tag-list editable :tags="pseudoQuery['tipoPonto']" :labels="{ponto_coletivo: 'Ponto Coletivo', ponto_entidade: 'Ponto Entidade', pontao: 'Pontão'}"  classes="agent__background agent__color"></mc-tag-list>
        </div>

        <div class="field">
            <label> <?php i::_e('Área de atuação') ?></label>
            <mc-multiselect :model="pseudoQuery['term:area']" placeholder="<?php i::_e('Selecione as áreas de atuação') ?>" :items="terms" hide-filter hide-button></mc-multiselect>
            <mc-tag-list editable :tags="pseudoQuery['term:area']" :labels="terms" classes="agent__background agent__color"></mc-tag-list>
        </div>

        <rcv-structuring-actions v-model:model-actions="pseudoQuery['term:acao_estruturante']" v-model:model-other-actions="pseudoQuery['term:acao_estruturante_outra']"></rcv-structuring-actions>

        <mc-states-and-cities v-model:model-states="pseudoQuery['En_Estado']" v-model:model-cities="pseudoQuery['En_Municipio']"></mc-states-and-cities>

        <rcv-countries 
            v-model:modelCountries1="pseudoQuery['paisPontaPontao']"
            v-model:modelCountries2="pseudoQuery['pais']">
        </rcv-countries>

        <?php if ($canUserControl) :?>
            <div class="field">
                <label> <?php i::_e('Status da organização') ?> </label>
                
                <div class="field__group">
                    <label class="field__checkbox">
                        <input v-model="pseudoQuery['@verified']" type="checkbox" :true-value="null" false-value="true" />
                        <?php i::_e('Exibir organizações não certificadas') ?>
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <?php $this->applyTemplateHook('search-filter-agent', 'end') ?>
    </form>
    <a class="clear-filter" @click="clearFilters()"><?php i::_e('Limpar todos os filtros') ?></a>
</search-filter>