<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

 $entity = $this->controller->requestedEntity;
 $this->jsObject['config']['rcv_registration_actions']['hasEditableFields'] = isset($_SESSION["{$entity}:editableFields"]) && $_SESSION["{$entity}:editableFields"];