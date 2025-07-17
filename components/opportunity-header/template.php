<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */
use MapasCulturais\i;

$this->import('
    mc-avatar
');
?>
<?php $this->applyTemplateHook('opportunity-header', 'before'); ?>
<header class="opportunity-header">
    <?php $this->applyTemplateHook('opportunity-header', 'begin'); ?>
    <div class="opportunity-header__content">
        <div class="left">
            <div class="image">
               <mc-avatar :entity="firstPhase" size="medium"></mc-avatar>
            </div>

            <div class="title">
                <slot name="title-name">
                    <span class="title__title">
                        <a :href="firstPhase.getUrl('single')">{{firstPhase.name}}</a>
                    </span>
                </slot>
            </div>
        </div>
        <div class="right">
            <slot name="button"></slot>
        </div>
        
    </div>
    <div class="">
        <slot name="footer"></slot>
    </div>
    <?php $this->applyTemplateHook('opportunity-header', 'end'); ?>
</header>
<?php $this->applyTemplateHook('opportunity-header', 'after'); ?>