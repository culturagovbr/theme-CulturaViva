<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 * @var MapasCulturais\Entities\Opportunity $entity
 */

use MapasCulturais\i;

$this->breadcrumb = [
    ['label' => i::__('Início'), 'url' => $app->createUrl('site', 'index')],
    ['label' => i::__('Projetos')],
];

$this->import('
    mc-breadcrumb
')
?>

<div class="static-page">
    <mc-breadcrumb></mc-breadcrumb>
    
    <div class="static-page__title">
        <h2 class="bold"> <?= i::__('Projetos') ?> </h2>
    </div>
    
    <div class="static-page__content">
        <h3 class="bold"> <?= i::__('Lorem Ipsum') ?> </h3>
        
        <p>
            <?= i::__('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris vitae ipsum vehicula, ullamcorper turpis eu, tincidunt dolor. Donec cursus lorem nec nisi vestibulum vulputate. 
            Nulla eget ornare odio. Interdum et malesuada fames ac ante ipsum primis in faucibus. Aenean ut erat finibus, auctor nisl nec, pellentesque elit. Nam nisl tellus, lacinia egestas 
            ligula sit amet, sodales pulvinar ligula. Integer hendrerit facilisis dui in aliquet.')?>
        </p>

        <p>
            <?= i::__('Nulla convallis risus eros, et imperdiet est eleifend eget. Morbi est diam, accumsan ut libero eget, pulvinar posuere purus. Pellentesque in ipsum et nibh lobortis venenatis
             nec ut tellus. Cras consectetur sed diam eu imperdiet. Vivamus lacus mi, ullamcorper venenatis elementum non, vestibulum eu mauris. Ut in justo tincidunt, imperdiet purus vitae, 
             rutrum metus. Suspendisse dapibus sem eget nisl semper, quis rhoncus quam dictum. Praesent cursus nisl id nibh efficitur hendrerit. Nunc sed sodales velit.') ?>
        </p>

        <p>
            <?= i::__('Quisque quis odio vitae ex malesuada accumsan. Nullam interdum eget nunc sit amet tempus. Mauris pellentesque pulvinar quam elementum tempus. Sed viverra nulla ligula, 
            ac vehicula magna vestibulum et. Donec suscipit lacus eget tincidunt bibendum. Fusce luctus fringilla diam laoreet blandit. Vestibulum efficitur commodo tortor, id vulputate urna 
            efficitur at. Suspendisse rutrum tortor ac velit cursus auctor. Phasellus tincidunt mi nec mi efficitur pharetra. Sed sagittis justo a aliquam consectetur. Duis vitae magna eu eros 
            tincidunt hendrerit non vitae eros. Morbi volutpat volutpat enim, id auctor metus lobortis ac. Morbi pulvinar lectus convallis hendrerit dapibus.') ?>
        </p>
    </div>
</div>


