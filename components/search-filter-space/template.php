<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-multiselect 
    mc-tag-list 
    mc-states-and-cities
    search-filter 
');

?>
<search-filter :position="position" :pseudo-query="pseudoQuery">
    <label class="form__label">
        <?= i::_e('Filtros de espaço') ?>
    </label>
    <form class="form scrollbar" @submit="$event.preventDefault()">
        <?php $this->applyTemplateHook('search-filter-agent', 'begin') ?>

        <div class="field">
            <label> <?php i::_e('Área de atuação') ?></label>
            <mc-multiselect :model="pseudoQuery['term:area']" placeholder="<?php i::_e('Selecione as áreas de atuação') ?>" :items="terms" hide-filter hide-button></mc-multiselect>
            <mc-tag-list editable :tags="pseudoQuery['term:area']" classes="agent__background agent__color"></mc-tag-list>
        </div>

        <mc-states-and-cities v-model:model-states="pseudoQuery['En_Estado']" v-model:model-cities="pseudoQuery['En_Municipio']"></mc-states-and-cities>

        <?php $this->applyTemplateHook('search-filter-agent', 'end') ?>
    </form>
    <a class="clear-filter" @click="clearFilters()"><?php i::_e('Limpar todos os filtros') ?></a>
</search-filter>