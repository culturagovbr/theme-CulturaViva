<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

/**
  * @todo: Criar componente para filtro por países na tela de busca dos pontos e pontões (mapa)
  */

$conn = $app->em->getConnection();

$paises = $conn->fetchAll("SELECT DISTINCT(VALUE) FROM agent_meta WHERE (key = 'pais' OR key = 'paisPontaPontao') ORDER BY value ASC");

$this->jsObject['config']['countries'] = [
  'paises' => $paises,
];