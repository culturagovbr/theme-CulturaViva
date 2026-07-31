<?php

namespace CulturaViva;

use DateTime;
use MapasCulturais\i;
use MapasCulturais\API;
use MapasCulturais\App;
use CulturaViva\Importer;
use MapasCulturais\ApiQuery;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\Space;
use MapasCulturais\Entities\AgentMeta;
use MapasCulturais\Exceptions\NotFound;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Entities\AgentRelation;
use MapasCulturais\Entities\RegistrationEvaluation;
use MapasCulturais\Entities\RegistrationSpaceRelation;
use MapasCulturais\Entities\EvaluationMethodConfigurationAgentRelation;

class Theme extends \MapasCulturais\Themes\BaseV2\Theme
{

    protected Opportunity $opportunity;

    static function getThemeFolder()
    {
        return __DIR__;
    }

    function __construct($config = [])
    {
        $app = App::i();
        $app->config['Metabase']['enabled'] = env('METABASE_CULTURAVIVA_ENABLED', true);

        parent::__construct($config);
    }

    function _init()
    {
        parent::_init();

        $app = App::i();
        EvaluationDistribution::register();

        $self = $this;

        $seals = $app->config['rcv.seals'] ?: '0';
        
        $opportunity_id = $app->config['rcv.opportunityId'];

        $this->opportunity = $app->repo('Opportunity')->find($opportunity_id);

        $theme = $this;

        // Restringe exibição de alguns campos via CSS para usuários não administradores
        if(!$app->user->is('admin')){
            $this->bodyClasses[] = 'not-admin';
        }

        // Faz com que o filtro na tabela de agentes so mostre os selos do CulturaViva
        $app->hook('component(agent-table).querySeals', function(&$queryParams) use ($app) {
            $seals = $app->config['rcv.seals'];
            $queryParams['id'] = "IN({$seals})";
        });

        $app->hook('Theme::enqueueComponentScript', function($result, string $component, array $dependences = []) {
            if ($component !== 'qualification-evaluation-form') {
                return;
            }

            $this->enqueueScript('components', 'culturaviva-qualification-evaluation-recommendations', 'js/qualification-evaluation-recommendations.js', ['qualification-evaluation-form']);
        }, 1000);

        // Ajusta titulo da página do formulário
        $app->hook('mapasculturais.getTitle', function(&$title) use ($app) {
            $entity = $this->controller->requestedEntity;
            if($entity && get_class($entity) == Registration::class) {
                $title = $entity->opportunity->name . " - " . $app->siteName;
            }
        });

        // Bloqeuia campos @ do agente coletivo que são retornados da API da receita
        $app->hook("<<GET|POST|PATCH|PUT>>(registration.<<*>>):before", function () use ($app) {
            $entity = $this->requestedEntity;

            /** @var array */
            $locked_fields = $entity->rcv_locked_fields ?: [];

            $app->hook('mapas.printJsObject:before', function() use ($app, $entity, $locked_fields) {
                $registered_metadadta = $entity->getRegisteredMetadata();
                foreach($registered_metadadta as $field => $def) {
                    $rfc = $def->config['registrationFieldConfiguration'] ?? null;
                    if($rfc && $rfc->fieldType === 'agent-collective-field' && $rfc->config) {
                        $_field = $rfc->config['entityField'];
                        if(in_array($_field, $locked_fields)) {
                            $this->jsObject['EntitiesDescription']['registration'][$field]['readonly'] = true;
                        }
                    }
                   
                }
            });
        },10000);


        // Oculta o campo tipo de proponente do CARD de inscrição
        $app->hook("component(registration-card):before", function () use ($app) {
            $app->config['app.registrationCardFields']['proponentType'] = false;
        });

        // Oculta o campo Tipo de Agente do card Meus Agentes no painel
        $app->hook("component(registration-card):before", function () use ($app) {
            $app->config['app.panelEtityCardFields']['type'] = false;
        });

        $app->hook('view.render(site/index):before', function() use($app) {
            $titleTags = ['application-name', 'twitter:title', 'og:title'];

            foreach ($this->documentMeta as &$meta) {
                if ( (isset($meta['name']) && in_array($meta['name'], $titleTags)) || (isset($meta['property']) && in_array($meta['property'], $titleTags)) ) {
                    $meta['content'] = "Rede Cultura Viva";
                }
            }
        });

        $app->hook('view.render(opportunity/single-cadastro):before', function() use($app) {
            $titleTags = ['application-name', 'twitter:title', 'og:title'];

            foreach ($this->documentMeta as &$meta) {
                if ( (isset($meta['name']) && in_array($meta['name'], $titleTags)) || (isset($meta['property']) && in_array($meta['property'], $titleTags)) ) {
                    $meta['content'] = "Cadastro Nacional de Pontos e Pontões de Cultura";
                }
            }
        });

        $app->hook('view.render(panel/registrations):before', function() use($app) {
            
            $app->hook('mapasculturais.getTitle', function (&$title) use ($app) {
                $title = "Cadastros";
            });

            $titleTags = ['application-name', 'twitter:title', 'og:title'];
            foreach ($this->documentMeta as &$meta) {
                if ( (isset($meta['name']) && in_array($meta['name'], $titleTags)) || (isset($meta['property']) && in_array($meta['property'], $titleTags)) ) {
                    $meta['content'] = "Cadastros";
                }
            }
        });

        $app->hook('view.render(search/agent):before', function() use($app) {
            $titleTags = ['application-name', 'twitter:title', 'og:title'];

            foreach ($this->documentMeta as &$meta) {
                if ( (isset($meta['name']) && in_array($meta['name'], $titleTags)) || (isset($meta['property']) && in_array($meta['property'], $titleTags)) ) {
                    $meta['content'] = "Mapa dos Pontos e Pontões de Cultura";
                }
            }

            $app->hook('mapasculturais.getTitle', function() use ($app) {
                foreach ($app->hooks->hookStack as &$stack) {
                    if ($stack->name === 'mapasculturais.getTitle' && $stack->args[0] == "Agentes") {
                        $stack->args[0] = "Mapa dos Pontos e Pontões de Cultura";
                    }
                }
            });
        });

        $app->hook('view.render(search/space):before', function() use($app) {
            $titleTags = ['application-name', 'twitter:title', 'og:title'];

            foreach ($this->documentMeta as &$meta) {
                if ( (isset($meta['name']) && in_array($meta['name'], $titleTags)) || (isset($meta['property']) && in_array($meta['property'], $titleTags)) ) {
                    $meta['content'] = "Espaços dos Pontos e Pontões de Cultura";
                }
            }
        });

        $app->hook('template(agent.single.tabs):end', function() use($app) {
            if($app->config['rcv.disableTabs']) {
                return;
            }

            /** @var \MapasCulturais\Theme $this */
            $this->part('history-tab');
            $this->part('location-tab');
            $this->part('public-tab');
            $this->part('expertise-area-tab');
            $this->part('cultural-economy-tab');
            $this->part('rfb-tab');
        });

        $app->hook("controller(seal).render(sealrelation)", function(&$template, $seal) use ($app) {
            $seals = $app->config['rcv.verificationSeals'];
            
            if ($seal['relation']->seal->id == $seals['ponto']) {
                $template = "certificado-ponto";
            }

            if ($seal['relation']->seal->id == $seals['pontao']) {
                $template = "certificado-pontao";
            }
        });

        //Filtro de inscrições na API
        $app->hook('ApiQuery(Registration).params', function (&$api_params) use($app) {
            /** @var ApiQuery $this */
            
            if($app->config['rcv.disableApiFilters'] || $this->parentQuery) {
                return;
            }
                 
            $opps_id = [$app->config['rcv.opportunityId'], $app->config['rcv.pnabOpportunityId']];
            if(!isset($api_params['opportunity'])) {
                 $api_params['opportunity'] = API::IN($opps_id);
             } else {
                preg_match('/[\d,]+/', $api_params['opportunity'], $matches);
                $opportunity_params = explode(",", $matches[0]);
                                
                 if(array_diff($opportunity_params, $opps_id)) {
                     $api_params['opportunity'] = API::AND(API::IN($opps_id));    
                 }
             }

        });

        // insere filtro na API para que devolva sempre os selos que estão na lista de selos liberados do CulturaViva
        $app->hook('ApiQuery(Seal).params', function (&$api_params) use($seals, $theme, $app) {
            /** @var ApiQuery $this */

            if($app->config['rcv.disableApiFilters'] || $this->parentQuery || (php_sapi_name() != "cli" && $app->request && $app->request->route == 'GET seal.single')) {
                return;
            }

            $seals_id = $app->config['rcv.seals'];
            $api_params['id'] = API::IN(explode(",", $seals_id));

        });


        $app->hook('ApiQuery(Agent).params', function (&$api_params) use($seals, $theme, $app) {
            /** @var ApiQuery $this */
            
            if($app->config['rcv.disableApiFilters'] || $this->parentQuery) {
                return;
            }
            if (!isset($api_params['type']) && !isset($api_params['id']) && !isset($api_params['parent']) && !isset($api_params['owner']) && !isset($api_params['user'])) {
                $api_params['type'] = API::EQ(2);
            }
        });


        $app->hook('ApiQuery(Agent).joins', function (&$joins) use($seals, $theme, $app) {
            /** @var ApiQuery $this */

            if($app->config['rcv.disableApiFilters'] || $this->parentQuery) {
                return;
            }

            $alias = "rcv_tipo_" . spl_object_id($this);

            $joins .= "
                LEFT JOIN e.__metadata {$alias} 
                    WITH {$alias}.key = 'rcv_tipo'";
            
            if(!$theme->canUserControlRCV()) {
                $joins .= "
                    LEFT JOIN e.__sealRelations rcv_sealRelations
                    LEFT JOIN rcv_sealRelations.seal rcv_seal WITH rcv_seal.id IN ($seals)";
            }

            if($app->auth->isUserAuthenticated() && !$app->user->is('admin')) {
                $joins .= " LEFT JOIN e.__permissionsCache rcv_pcache_agent WITH rcv_pcache_agent.action = '@control'";
            }
        });

        $app->hook('ApiQuery(Agent).where', function (&$where) use($theme, $app) {
            /** @var ApiQuery $this */

            if($app->config['rcv.disableApiFilters'] || $this->parentQuery) {
                return;
            }

            $alias = "rcv_tipo_" . spl_object_id($this);

            $_where = "((e._type = 2 AND {$alias}.value = 'ponto') OR e._type = 1)";

            if(!$theme->canUserControlRCV()) {
                $_where .= " AND ((e._type = 2 AND rcv_seal.id IS NOT NULL) OR e._type = 1)";
            }

            if($app->auth->isUserAuthenticated()) {
                if($app->user->is('admin')) {
                    $_where = "e.user = {$app->user->id} OR ($_where)";
                } else {
                    $_where = "rcv_pcache_agent.user = {$app->user->id} OR ($_where)";
                }
            }

            $where .= " AND ({$_where})";
        });

        $app->hook('ApiQuery(Space).params', function (&$api_params) use($seals, $theme, $app) {
            /** @var ApiQuery $this */
            if($app->config['rcv.disableApiFilters']) {
                return;
            }

            // retorna somente espaços de pontos e pontões certificados
            $this->addFilterByApiQuery(new ApiQuery(Agent::class, ['@select' => 'id']), property: 'owner');
        });

        $app->hook('ApiQuery(Space).joins', function (&$joins) use($seals, $theme, $app) {
            if($app->config['rcv.disableApiFilters']) {
                return;
            }
            
            $agent_meta_class = AgentMeta::class;

            $alias = "rcv_space_owner" . spl_object_id($this);

            $joins .= "
                JOIN e.owner {$alias}
                JOIN $agent_meta_class {$alias}_meta
                    WITH {$alias}_meta.key = 'rcv_sede_spaceId'
                JOIN {$alias}_meta.owner {$alias}_meta_agent 
                    WITH {$alias}_meta_agent.user = {$alias}.user";

            
            if($app->auth->isUserAuthenticated() && !$app->user->is('admin')) {
                $joins .= " 
                    LEFT JOIN e.__permissionsCache rcv_pcache_space 
                        WITH rcv_pcache_space.action = '@control'";
            }
        });

        $app->hook('ApiQuery(Space).where', function (&$where) use($theme, $app) {
            /** @var ApiQuery $this */

            if($app->config['rcv.disableApiFilters']) {
                return;
            }

            $alias = "rcv_space_owner" . spl_object_id($this);

            $_where = "CAST({$alias}_meta.value AS INTEGER) = e.id";

            if($app->auth->isUserAuthenticated()) {
                if($app->user->is('admin')) {
                    $_where = "{$alias}.user = {$app->user->id} OR ($_where)";
                } else {
                    $_where = "rcv_pcache_space.user = {$app->user->id} OR ($_where)";
                }
            }

            $where .= " AND ({$_where})";
        });

        $app->hook('view.requestedEntity(Registration).result', function (&$entity, $class, $id) use($app) {
            if($entity['opportunity']->id == $app->config['rcv.opportunityId']) {
                if($agent_relation = $app->repo('RegistrationAgentRelation')->findOneBy(['owner' => $id, 'group' => 'coletivo'])){
                    $entity['relatedAgents'] = (array) $entity['relatedAgents'];
                    $entity['agentRelations'] = (array) $entity['agentRelations'];

                    $entity['relatedAgents']['coletivo'] = [$agent_relation->agent];
                    $entity['agentRelations']['coletivo'] = [$agent_relation];
                }
            }
        });

        $app->hook('entity(Registration).agentRelationsAllowedStatus', function (&$status) {
            $status[] = Agent::STATUS_DRAFT;
        });

        $app->hook('entity(Registration).getAgentRelations:before', function (&$has_control, &$include_pending_relations, &$agent_statuses) use($app, $theme) {
            if($this->opportunity->id == $app->config['rcv.opportunityId']) {
                $agent_statuses[] = Agent::STATUS_DRAFT;
            }
        });

        $app->hook('controller(opportunity).render(single)', function (&$template) use($app, $theme) {
            /** @var \MapasCulturais\Controllers\Opportunity $this */

            $opportunity = $this->requestedEntity;

            if($app->config['rcv.opportunityId'] == $opportunity->id) {
                $template = 'single-cadastro';
                $theme->bodyClasses[] = 'cadastro';
            }

            if($app->config['rcv.pnabOpportunityId'] == $opportunity->id) {
                $template = 'single-pnab';
            }
        });


        $app->hook('GET(site.funcoes-do-cadastro)', function() use($app) {
            $this->render('funcoes-do-cadastro', []);
        });

        $app->hook('GET(site.perguntas-frequentes)', function() use($app) {
            $this->render('faq', []);
        });

        $app->hook('GET(site.pncv)', function() use($app) {
            $this->render('pncv', []);
        });

        // Certificados e Selos
        $app->hook('GET(site.certificados)', function() use($app) {
            $this->render('certificados', []);
        });

        // Comissões
        $app->hook('GET(site.comissoes)', function() use($app) {
            $this->render('comissoes', []);
        });

        // Redes de Pontos e Pontões
        $app->hook('GET(site.redes-pontos-pontoes)', function() use($app) {
            $this->render('redes-pontos-pontoes', []);
        });


        // Atualização Cadastral
        $app->hook('GET(site.atualizacao-cadastral)', function() use($app) {
            $this->render('atualizacao-cadastral', []);
        });

        $app->hook('POST(site.valida-cnpj)', function() use($app, $theme) {
            $this->requireAuthentication();
            
            $fake_api = $app->config['rcv.toggleCNPJFakeApi'];

            $cpf = preg_replace('/[^\d]/', '', $app->user->profile->cpf);

            $cnpj = $fake_api ? $this->data['cnpj'] : preg_replace('/[^\d]/', '', $this->data['cnpj']);
            $valid_cnpj = $fake_api ? $theme->getCNPJFake($cnpj) : $theme->getCNPJ($cnpj, $cpf);
            
            if($valid_cnpj) {
                $app->cache->save($this->data['cnpj'], $valid_cnpj, DAY_IN_SECONDS);

                // Procurando um CNPJ nas organizações de outros usuários
                
                /** @var \MapasCulturais\Entities\AgentMeta[] */
                $array_meta = $app->repo('AgentMeta')->findBy(['key' => 'cnpj', 'value' => $this->data['cnpj']]);
                
                
               
                // Caso receba algum erro na api da receita
                if($valid_cnpj['situacaoCadastral']['codigo'] !== '2') {
                    $invalid_cnpj = $valid_cnpj['situacaoCadastral']['motivo'];
                    $this->errorJson($invalid_cnpj);
                }

                $legal_nature = $valid_cnpj['naturezaJuridica'] ?? null;

                if($legal_nature) {
                    $legal_nature_code = $legal_nature['codigo'];
                    $allowed_legal_natures = ['1', '3', '2143', '3999', '3069', '3131', '3239', '3301', '3220'];
                }
            
                // Se a natureza jurídica não for válida
                if(!in_array($legal_nature_code, $allowed_legal_natures)) {
                    $this->errorJson('natureza-juridica-invalida');
                }

                // Se tudo der certo retorna o objeto
                $this->json($valid_cnpj);
            }

            // Caso o CNPJ for inválido
            $this->errorJson(false);
        });

        // Pré-validação CNPJ: inscrições RCV (coletivo + meta). Alterar CNPJ: excludeAgentId + categoria pelo coletivo.
        // Novo cadastro: opcional subscriptionType ponto-entidade|pontao (bloqueia só essa modalidade se já existir).
        // Sem subscriptionType: só bloqueia “lotado” (já tem Ponto e Pontão) ou 2+ Pontões — não bloqueia só por vários Pontos (ex.: permite seguir para Pontão no curl/UI legada).
        $app->hook('POST(site.check-cnpj-org-conflict)', function () use ($app, $theme) {
            $this->requireAuthentication();

            $cnpjRaw = $this->data['cnpj'] ?? '';
            $cnpjDigits = preg_replace('/\D/', '', (string) $cnpjRaw);
            if (strlen($cnpjDigits) !== 14) {
                $this->json(['conflict' => false]);

                return;
            }

            $excludeAgentId = isset($this->data['excludeAgentId']) && $this->data['excludeAgentId'] !== ''
                ? (int) $this->data['excludeAgentId']
                : null;

            $categoriesMap = $app->config['rcv.categoriesMap'] ?? [];
            $pontoLabel = $categoriesMap['ponto-entidade'] ?? '';
            $pontaoLabel = $categoriesMap['pontao'] ?? '';

            if ($excludeAgentId !== null) {
                $subscriptionKey = $theme->getRcvSubscriptionTypeKeyByColetivoAgentId($app, $excludeAgentId);
                $categoryLabel = null;
                if ($subscriptionKey === 'ponto-entidade') {
                    $categoryLabel = $pontoLabel;
                } elseif ($subscriptionKey === 'pontao') {
                    $categoryLabel = $pontaoLabel;
                }

                if ($categoryLabel === null || $categoryLabel === '') {
                    $this->json(['conflict' => false]);

                    return;
                }

                $conflict = $theme->countRcvRegistrationsWithColetivoCnpjAndCategory(
                    $app,
                    $cnpjDigits,
                    $categoryLabel,
                    $excludeAgentId
                ) > 0;
            } else {
                $nPonto = $theme->countRcvRegistrationsWithColetivoCnpjAndCategory(
                    $app,
                    $cnpjDigits,
                    $pontoLabel,
                    null
                );
                $nPontao = $theme->countRcvRegistrationsWithColetivoCnpjAndCategory(
                    $app,
                    $cnpjDigits,
                    $pontaoLabel,
                    null
                );

                $subscriptionKey = $this->data['subscriptionType'] ?? null;
                if ($subscriptionKey === 'ponto-entidade') {
                    $conflict = $nPonto >= 1;
                } elseif ($subscriptionKey === 'pontao') {
                    $conflict = $nPontao >= 1;
                } else {
                    $conflict = $nPontao >= 2 || ($nPonto >= 1 && $nPontao >= 1);
                }
            }

            $this->json(['conflict' => $conflict]);
        });

        // Termo de adesão
        $app->hook('GET(site.termoAdesao)', function() use($app) {
            $this->render('termo-adesao', []);
        });
        
        // Endpoint para cadastrar organização
        $app->hook('POST(agent.register-organization)', function () {
            
            $this->requireAuthentication();

            $agent = new Agent;
            $agent->type = 2;
            $agent->name = "";
            $agent->status = Agent::STATUS_DRAFT;

            foreach ($this->data as $field => $value) {
                $agent->$field =  $value;
            }

            $agent->save(true);

            $this->json($agent);
        });

        // Carrega novos ícones 'iconfy' na estrutura default
        $app->hook('component(mc-icon).iconset', function(&$iconset){
            $iconset['indicator'] = 'mdi:graph-bar';
            $iconset['graph-bar'] = "foundation:graph-bar";
            $iconset['section-share'] = "ic:baseline-ios-share";
            $iconset['section-seals'] = "ic:round-turned-in";
            $iconset['arrow-left-return'] = "la:long-arrow-alt-left";
            $iconset['arrow-right-return'] = "la:long-arrow-alt-right";
            $iconset['event'] = "bxs:calendar-event";
            $iconset['cursor-click'] = "mynaui:click-solid";
            $iconset['certificate'] = "mdi:seal-outline";
        });
        
        $app->hook('GET(site.projeto)', function() use($app) {
            $this->render('projetos', []);
        });

        $app->hook('POST(site.alterar-cnpj)', function() use($app, $theme) {
            $this->requireAuthentication();
            
            $registration_info = $this->data['registration'];
            $registration = $app->repo('Registration')->find($registration_info['id'] ?? $registration_info['_id']);

            if($registration) {
                $cnpj = $this->data['cnpj'];
                $full_name = $this->data['apiInfo']['nomeEmpresarial'];

                $agent = $registration->relatedAgents['coletivo'][0];
                $conn = $app->em->getConnection();
                $cnpj_field = $conn->fetchAll("
                    SELECT 
                        *
                    FROM 
                        registration_field_configuration
                    WHERE 
                        field_type = 'agent-collective-field'
                        AND 
                            opportunity_id = {$app->config['rcv.opportunityId']}
                        AND 
                            jsonb_extract_path_text(config::jsonb, 'entityField') = 'cnpj'
                ");
                $cnpj_field_name = 'field_'.$cnpj_field[0]['id'];

                $full_name_field = $conn->fetchAll("
                    SELECT 
                        *
                    FROM 
                        registration_field_configuration
                    WHERE 
                        field_type = 'agent-collective-field'
                        AND 
                            opportunity_id = {$app->config['rcv.opportunityId']}
                        AND 
                            jsonb_extract_path_text(config::jsonb, 'entityField') = 'nomeCompleto'
                        AND EXISTS (
                            SELECT 
                                1
                            FROM 
                                jsonb_array_elements_text(categories::jsonb) AS category
                            WHERE 
                                category = '{$registration->category}'
                        );
                ");
                $full_name_field_name = 'field_'.$full_name_field[0]['id'];
                
                $app->disableAccessControl();
                $conn->exec("UPDATE registration_meta SET value = '{$cnpj}' WHERE object_id = {$registration->id} AND key = '{$cnpj_field_name}'");
                $registration->_newModifiedRevision(sprintf(i::__('campo "%s" modificado'), $cnpj_field_name));

                $conn->exec("UPDATE registration_meta SET value = '{$full_name}' WHERE object_id = {$registration->id} AND key = '{$full_name_field_name}'");
                $registration->_newModifiedRevision(sprintf(i::__('campo "%s" modificado'), $full_name_field_name));

                $registration->status = Registration::STATUS_SENT;
                $registration->range = $app->config['rcv.rangesMap']['alterar-cnpj'];
                $registration->save(true);

                $agent->cnpj = $cnpj;
                $agent->nomeCompleto = $full_name;
                $agent->save(true);
                $user_email = $registration->owner->emailPrivado ?: $registration->owner->emailPublico ?: $registration->owner->email ?: '';
                
                $data = [
                    "owner_name" => $registration->owner->name,
                    "agent_name" => $agent->name,
                    "agent_cnpj" => $agent->cnpj,
                    "recipient" => $registration->owner->email,
                    "email_destinatario" => $user_email
                    
                ];
                
                $theme->sendTransactionalEmail('change_of_cnpj', $data);

                $app->enableAccessControl();

                $this->json(true);
            }

            $this->errorJson(false);
        });

        $app->hook('entity(Registration).canUser(sendEditableFields)', function($user, &$canUser) use($app) {
            $enable_editable_fields = false;

            if(isset($this->agentsData)) {
                $agent_id = $this->agentsData['coletivo']['id'];
                $agent = $app->repo('Agent')->find($agent_id);
                $agent_relations = $agent->agentRelations;

                foreach($agent_relations as $relation) {
                    if($relation->agent->id == $user->profile->id && $relation->status == AgentRelation::STATUS_ENABLED) {
                        $enable_editable_fields = true;
                        break;
                    }
                }
            }

            if(($_SESSION["{$this}:editableFields"] ?? false) || $enable_editable_fields) {
                $canUser = true;
            }
        });

        $app->hook("entity(Registration).send:after", function () use ($app) {
            /** @var Registration $this */
            if (isset($_SESSION["{$this}:editableFields"]) && $_SESSION["{$this}:editableFields"]) {
                $this->save(true);
                unset($_SESSION["{$this}:editableFields"]);
                $this->clearPermissionCache();
            }

            // Adiciona valor da inscrição ao rcv_registration da organização caso já não tenha criado
            $opportunity = $this->opportunity;
            if($opportunity->id == $app->config['rcv.opportunityId']) {
                $opportunity->registerRegistrationMetadata();
                
                // Define a categoria `Aguardando cadastro via edital` para inscrições que responderam SIM no campo da pergunta 
                // A organização está concorrendo em algum edital da Cultura Viva atualmente?
                $field_name = $app->config['rcv.fieldQuestion'];
                if($this->$field_name === $app->config['rcv.questionResponse']) {
                    $app->disableAccessControl();
                    $this->range = $app->config['rcv.rangesMap']['cadastro-via-edital'];
                    $this->save(true);
                    $app->enableAccessControl();
                }

                $organization = $this->relatedAgents['coletivo'][0];
                $agent = $app->repo('Agent')->find($organization->id);

                if(!$agent->rcv_registration) {
                    $app->disableAccessControl();
                    $agent->rcv_registration = $this;
                    $agent->save(true);
                    $app->enableAccessControl();
                }

                $app->disableAccessControl();
                $agent->rcv_last_update_timestamp = date('Y-m-d H:i:s');
                $agent->save(true);
                $app->enableAccessControl();
            }
        });

        $app->hook('POST(site.alterar-organizacao)', function() use($app, $theme) {
            $this->requireAuthentication();
            
            $registration_info = $this->data['registration'];
            $registration = $app->repo('Registration')->find($registration_info['id'] ?? $registration_info['_id']);

            $options = [
                1 => ['category' => $app->config['rcv.categoriesMap']['ponto-entidade'], 'proponentType' => 'Pessoa Jurídica'],
                2 => ['category' => $app->config['rcv.categoriesMap']['pontao'], 'proponentType' => 'Pessoa Jurídica'],
                3 => ['category' => $app->config['rcv.categoriesMap']['ponto-coletivo'], 'proponentType' => 'Coletivo'],
            ];
 
            if($registration) {
                $agent = $registration->relatedAgents['coletivo'][0];
                $proponent_option = $this->data['option'];
                $new_cnpj = null;

                switch($proponent_option) {
                    case 1:
                        $selected_option = $options[1];
                        $new_cnpj = $this->data['cnpj'];
                        $full_name = $this->data['apiInfo']['nomeEmpresarial'];
                        break;
                    case 2:
                    case 3:
                        $selected_option = $options[2];
                        $new_cnpj = $this->data['cnpj'];
                        $full_name = $this->data['apiInfo']['nomeEmpresarial'];
                        break;
                    case 4:
                    case 5:
                        $selected_option = $options[3];
                        break;
                    default:
                        $this->errorJson('Opção inválida');
                        break;
                }
                
                $app->disableAccessControl();

                if ($new_cnpj && $full_name) {
                    $conn = $app->em->getConnection();
                    $cnpj_field = $conn->fetchAll("
                        SELECT 
                            *
                        FROM 
                            registration_field_configuration
                        WHERE 
                            field_type = 'agent-collective-field'
                            AND 
                                opportunity_id = {$app->config['rcv.opportunityId']}
                            AND 
                                jsonb_extract_path_text(config::jsonb, 'entityField') = 'cnpj'
                    ");
                    $cnpj_field_name = 'field_'.$cnpj_field[0]['id'];

                    $full_name_field = $conn->fetchAll("
                        SELECT 
                            *
                        FROM 
                            registration_field_configuration
                        WHERE 
                            field_type = 'agent-collective-field'
                            AND 
                                opportunity_id = {$app->config['rcv.opportunityId']}
                            AND 
                                jsonb_extract_path_text(config::jsonb, 'entityField') = 'nomeCompleto'
                            AND EXISTS (
                                SELECT 
                                    1
                                FROM 
                                    jsonb_array_elements_text(categories::jsonb) AS category
                                WHERE 
                                    category = '{$registration->category}'
                            );
                    ");
                    $full_name_field_name = 'field_'.$full_name_field[0]['id'];
                    
                    $conn->exec("UPDATE registration_meta SET value = '{$new_cnpj}' WHERE object_id = {$registration->id} AND key = '{$cnpj_field_name}'");
                    $registration->_newModifiedRevision(sprintf(i::__('campo "%s" modificado'), $cnpj_field_name));

                    $conn->exec("UPDATE registration_meta SET value = '{$full_name}' WHERE object_id = {$registration->id} AND key = '{$full_name_field_name}'");
                    $registration->_newModifiedRevision(sprintf(i::__('campo "%s" modificado'), $full_name_field_name));

                    $agent->cnpj = $new_cnpj;
                    $agent->nomeCompleto = $full_name;
                    $agent->save(true);
                }

                $registration_field_configuration = $registration->opportunity->registrationFieldConfigurations;
                $registration_file_configuration = $registration->opportunity->registrationFileConfigurations;
                
                $all_fields = array_merge($registration_field_configuration, $registration_file_configuration);

                $editableFields = [];
                foreach($all_fields as $field) {
                    $field_name = $field->fieldName ?: $field->fileGroupName;
                    
                    if(in_array($registration->category, $field->categories)) {
                        $clearFields[] = $field_name;
                    }
                    
                    if(in_array($selected_option['category'], $field->categories) || count($field->categories) == 0) {
                        $editableFields[] = $field_name;
                    }
                }

                $registration->editableFields = $editableFields;

                $conn = $app->em->getConnection();
                foreach($clearFields as $field) {
                    if(str_starts_with($field, 'field_')) {
                        $conn->exec("DELETE FROM registration_meta WHERE object_id = {$registration->id} AND key = '{$field}'");
                    } else {
                        $files = $conn->fetchAll("
                            SELECT 
                                *
                            FROM 
                                file
                            WHERE 
                                grp = '{$field}'
                                AND 
                                object_id = {$registration->id}
                        ");

                        $file = $files ? $app->repo('File')->find($files[0]['id']) : [];

                        if($file) {
                            $file_path = $file->path;
                            if(file_exists($file_path)) {
                                unlink($file_path);
                            }
    
                            $file->delete(true);
                        }
                    }
                }

                $registration->status = Registration::STATUS_SENT;
                $registration->range = $app->config['rcv.rangesMap']['alterar-organizacao'];
                $registration->proponentType = $selected_option['proponentType'];
                $registration->category = $selected_option['category'];
                $registration->rcv_locked_fields = ['name', 'cnpj', 'nomeCompleto'];

                $registration->save(true);
                $_SESSION["{$registration}:editableFields"] = true;
                $registration->clearPermissionCache();
                
                $url = $app->createUrl('registration', 'registrationEdit', [$registration->id]);

                $user_email = $registration->owner->emailPrivado ?: $registration->owner->emailPublico ?: $registration->owner->email ?: '';

                $data = [
                    "owner_name" => $registration->owner->name,
                    "agent_name" => $agent->name,
                    "agent_cnpj" => $agent->cnpj,
                    "recipient" => $registration->owner->email,
                    "email_destinatario" => $user_email
                    
                ];
               
                $theme->sendTransactionalEmail('alteration_type', $data);

                $app->enableAccessControl();

                $url = $app->createUrl('registration', 'registrationEdit', [$registration->id]);
                $this->json($url);
            }

            $this->errorJson(false);
        });

        $app->hook('panel.nav',function(&$nav_items) use($app) {
            $nav_items = [
                'panel-menu' => [
                    'label' => i::__('Menu do Cultura Viva'),
                    'column' => 'left',
                    'items' => [
                        ['route' => 'panel/index', 'icon' => 'dashboard', 'label' => i::__('Painel de Controle')],
                    ]
                ],
            
                'opportunities' => [
                    'label' => i::__(''),
                    'column' => 'left',
                    'items' => [
                        [
                            'route' => 'opportunity/registrations', 'params' => [ $app->config['rcv.opportunityId'] ], 'icon' => 'opportunity', 'label' => i::__('Cadastros'),
                            'condition' => function () {
                                return $this->opportunity->canUser('@control');
                            },
                        ],
                        [
                            'route' => 'opportunity/allEvaluations', 'params' => [ $app->config['rcv.opportunityId'] ], 'icon' => 'opportunity', 'label' => i::__('Avaliações'),
                            'condition' => function () {
                                return $this->opportunity->canUser('@control');
                            },
                        ],
                        [
                            'route' => 'opportunity/edit', 'params' => [ $app->config['rcv.opportunityId'] ], 'icon' => 'opportunity', 'label' => i::__('Comissões'),
                            'condition' => function () {
                                return $this->opportunity->canUser('@control');
                            },
                        ],
                        [
                            'route' => 'opportunity/registrations', 'params' => [ $app->config['rcv.pnabOpportunityId'] ], 'icon' => 'opportunity', 'label' => i::__('Importações'),
                            'condition' => function () {
                                return $this->opportunity->canUser('@control');
                            },
                        ],
                        [
                            'route' => 'panel/evaluations', 'icon' => 'opportunity', 'label' => i::__('Minhas avaliações'),
                            'condition' => function () use ($app) {
                                $rcv_opportunity_id = $app->config['rcv.opportunityId'];
                                $rcv_pnab_opportunity_id = $app->config['rcv.pnabOpportunityId'];
                                $opportunity = $app->repo('Opportunity')->find($rcv_opportunity_id);
                                $pnab_opportunity = $app->repo('Opportunity')->find($rcv_pnab_opportunity_id);

                                $relations = [];
                                if($opportunity->evaluationMethodConfiguration) {
                                    $_relation = $opportunity->evaluationMethodConfiguration->getUserRelation();
                                    $relations[] = $_relation;
                                }

                                if($pnab_opportunity->evaluationMethodConfiguration) {
                                    $_relation = $pnab_opportunity->evaluationMethodConfiguration->getUserRelation();
                                    $relations[] = $_relation;
                                }
                             
                                foreach($relations as $relation) {
                                    if($relation && $relation->status == EvaluationMethodConfigurationAgentRelation::STATUS_ACTIVE) {
                                        return true;
                                    }
                                }
                                return false;
                            },
                        ],
                        [
                            'route' => 'panel/registrations', 'icon' => 'opportunity', 'label' => i::__('Meus cadastros'),
                            'condition' => function () use ($app) {
                                return (($app->user->is('agent') || $app->user->getIsEvaluator()) && !$app->user->is('admin'));
                            },
                        ],
                    ],
                ],
            
                'main' => [
                    'label' => '',
                    'column' => 'left',
                    'items' => [
                        [
                            'route' => 'panel/agents', 'icon' => 'agent', 'label' => i::__('Meus Agentes'),
                            'condition' => function () use ($app) {
                                return $app->isEnabled('agents');
                            },
                        ],
                        [
                            'route' => 'panel/spaces', 'icon' => 'space', 'label' => i::__('Meus Espaços'),
                            'condition' => function () use ($app) {
                                return $app->isEnabled('spaces');
                            },
                        ],
                        [
                            'route' => 'panel/events', 'icon' => 'event', 'label' => i::__('Meus Eventos'),
                            'condition' => function () use ($app) {
                                return $app->isEnabled('events');
                            },
                        ],
                        [
                            'route' => 'metabase/dashboard', 'params' => [ 'admin' ], 'icon' => 'indicator', 'label' => i::__('Indicadores'),
                            'condition' => function () use ($app) {
                                 $opportunity = $app->repo('Opportunity')->find('5386');

                                if ($app->user && $app->user->is('admin') || $opportunity->canUser('@control')) {
                                    return true;
                                }

                                return false;
                            },
                        ],
                    ],
                    'condition' => function () use ($app) {
                        return $app->isEnabled('agents') || $app->isEnabled('spaces') || $app->isEnabled('events') || $app->isEnabled('projects');
                    },
                ],
            
                'more' => [
                    'label' => i::__('Outras opções'),
                    'column' => 'right',
                    'items' => [
                        ['route' => 'panel/my-account', 'icon' => 'account', 'label' => i::__('Conta e Privacidade')],
                    ]
                ],

                'admin' => [
                    'label' => i::__('Administração'),
                    'column' => 'right',
                    'condition' => function () use ($app) {
                        return $app->user->is('admin');
                    },
                    'items' => [
                        [
                            'route' => 'panel/seals',
                            'icon' => 'seal',
                            'label' => i::__('Gestão de Selos'),
                            'condition' => function () use ($app) {
                                return $app->user->is('admin');
                            }
                        ],
                        [
                            'route' => 'panel/user-management',
                            'icon' => 'user-config',
                            'label' => i::__('Gestão de usuários'),
                            'condition' => function () use ($app) {
                                return $app->user->is('admin');
                            }
                        ],
                        [
                            'route' => 'panel/system-roles',
                            'icon' => 'role',
                            'label' => i::__('Funções de usuários'),
                            'condition' => function () use ($app) {
                                return $app->user->is('saasAdmin');
                            }
                        ]
                    ]
                ],
            ];

            if ($app->user->profile) {
                $nav_items['user'] = [
                    'label' => i::__('Usuário Logado'),
                    'column' => 'user',
                    'items' => [
                        ['route' => 'agent/single', 'params' => [$app->user->profile->id], 'icon' => 'agent', 'label' => i::__('Meu Perfil')],
                        ['route' => 'auth/logout', 'icon' => 'logout', 'label' => i::__('Sair')],
                    ]
                ];
            }
        });

        $app->hook('Theme::addOpportunityBreadcramb', function($a, $label) {
            $this->breadcrumb = array_filter($this->breadcrumb ?? [], fn($crumb) => $crumb['label'] !== i::__('Período de inscrição'));
        });

        $app->hook('GET(agent.single):before', function(){
            /** @var \MapasCulturais\Controllers\Agent $this*/

            if($entity = $this->requestedEntity){
                $query = new ApiQuery(Agent::class, ['id' => API::EQ($entity->id)]);
                if(!$query->findOne()){
                    throw new NotFound();
                }
            }
        });

        $app->hook('POST(site.atualizar-cadastro)', function() use($app, $theme) {
            $this->requireAuthentication();

            $registration_info = $this->data['registration'];
            $registration = $app->repo('Registration')->find($registration_info['id'] ?? $registration_info['_id']);

            if($registration) {
                $conn = $app->em->getConnection();
                $cnpj_field = $conn->fetchAll("
                    SELECT 
                        *
                    FROM 
                        registration_field_configuration
                    WHERE 
                        field_type = 'agent-collective-field'
                        AND 
                            opportunity_id = {$app->config['rcv.opportunityId']}
                        AND 
                            jsonb_extract_path_text(config::jsonb, 'entityField') = 'cnpj'
                ");
                $cnpj_field_name = 'field_'.$cnpj_field[0]['id'];

                $full_name_field = $conn->fetchAll("
                    SELECT 
                        *
                    FROM 
                        registration_field_configuration
                    WHERE 
                        field_type = 'agent-collective-field'
                        AND 
                            opportunity_id = {$app->config['rcv.opportunityId']}
                        AND 
                            jsonb_extract_path_text(config::jsonb, 'entityField') = 'nomeCompleto'
                        AND EXISTS (
                            SELECT 
                                1
                            FROM 
                                jsonb_array_elements_text(categories::jsonb) AS category
                            WHERE 
                                category = '{$registration->category}'
                        );
                ");
                $full_name_field_name = 'field_'.$full_name_field[0]['id'];

                $full_name_owner_field = $conn->fetchAll("
                    SELECT 
                        *
                    FROM 
                        registration_field_configuration
                    WHERE 
                        field_type = 'agent-owner-field'
                        AND 
                            opportunity_id = {$app->config['rcv.opportunityId']}
                        AND 
                            jsonb_extract_path_text(config::jsonb, 'entityField') = 'nomeCompleto';
                ");
                $full_name_owner_field_name = 'field_'.$full_name_owner_field[0]['id'];

                $cpf_field = $conn->fetchAll("
                    SELECT 
                        *
                    FROM 
                        registration_field_configuration
                    WHERE 
                        field_type = 'agent-owner-field'
                        AND 
                            opportunity_id = {$app->config['rcv.opportunityId']}
                        AND 
                            jsonb_extract_path_text(config::jsonb, 'entityField') = 'cpf'
                ");
                $cpf_field_name = 'field_'.$cpf_field[0]['id'];

                $registration_field_configuration = $registration->opportunity->registrationFieldConfigurations;
                $registration_file_configuration = $registration->opportunity->registrationFileConfigurations;

                $all_fields = array_merge($registration_field_configuration, $registration_file_configuration);

                // Validação do bloqueio dos campos
                $locked_fields = [];
                $blocked_fields = [$full_name_owner_field_name];

                if ($registration->relatedAgents['coletivo'][0]->name) {
                    $locked_fields[] = 'name';
                    $blocked_fields[] = $cnpj_field_name;
                }

                if ($registration->relatedAgents['coletivo'][0]->nomeCompleto) {
                    $locked_fields[] = 'nomeCompleto';
                    $blocked_fields[] = $full_name_field_name;
                }

                if ($registration->relatedAgents['coletivo'][0]->cnpj) {
                    $locked_fields[] = 'cnpj';
                    $blocked_fields[] = $cpf_field_name;
                }

                $editableFields = [];
                foreach($all_fields as $field) {
                    $fieldName = $field->fieldName ?: $field->fileGroupName;

                    $registration->opportunity->registerRegistrationMetadata();
                    if(in_array($fieldName, $blocked_fields) && !$theme->opportunity->canUser("@control") && $registration->$fieldName) {
                        continue;
                    }
                    
                    $editableFields[] = $fieldName;
                }

                $app->disableAccessControl();
                $registration->editableFields = $editableFields;
                $registration->rcv_locked_fields = $locked_fields;
                $registration->save(true);
                $_SESSION["{$registration}:editableFields"] = true;
                $registration->clearPermissionCache();
                $app->enableAccessControl();

                $url = $app->createUrl('registration', 'registrationEdit', [$registration->id]);
                $this->json($url);
            }

            $this->errorJson(false);
        });

        $app->hook('entity(Registration).status(approved)', function() use($app, $theme) {
            /** @var Registration $this */

            // Verifica se a inscrição é da oportunidade correta
            $opportunity_id = $app->config['rcv.opportunityId'];
            if($this->opportunity->id != $opportunity_id) {
                return;
            }

            /** @var Agent */
            $coletivo = $this->relatedAgents['coletivo'][0] ?? null;

            // Verifica se existe um agente coletivo
            if (!$coletivo) {
                return;
            }

            foreach ($coletivo->getSealRelations() as $relation) {
                if ($relation->seal->id == $app->config['rcv.verificationSeals']['desativado']) {
                    $coletivo->removeSealRelation($relation->seal);
                }
            }

            // publica o agente coletivo
            $app->disableAccessControl();
            $coletivo->publish(true);
            $app->enableAccessControl();

            // Verifica se o coletivo tem localização pública, se tiver cria um espaço para ele
            if($coletivo->publicLocation) {
                $name = $coletivo->name;
                $full_name = $coletivo->nomeCompleto;

                if($app->user->is('admin')) {
                    $spaces_query = new ApiQuery(Space::class, ['name' => API::OR(API::ILIKE($name), API::ILIKE($full_name))]);
                    $spaces_query->addFilterByApiQuery(new ApiQuery(Agent::class, ['user' => API::EQ('@me')]), property: 'owner');
                    
                } else {
                    $spaces_query = new ApiQuery(Space::class, ['name' => API::OR(API::ILIKE($name), API::ILIKE($full_name)), '@permissions' => '@control']);
                }

                $space_ids = $spaces_query->findIds();

                $app->disableAccessControl();
                
                if($space_ids) {
                    $space = $app->repo('Space')->find($space_ids[0]);
                } else {
                    $space = new Space;
                    $space->name = $name ?: $full_name;
                }

                $space->owner = $this->relatedAgents['coletivo'][0];
                $space->location = $coletivo->location;
                $space->type = 125;
                $space->En_CEP = $coletivo->En_CEP;
                $space->En_Nome_Logradouro = $coletivo->En_Nome_Logradouro;
                $space->En_Num = $coletivo->En_Num;
                $space->En_Bairro = $coletivo->En_Bairro;
                $space->En_Municipio = $coletivo->En_Municipio;
                $space->En_Estado = $coletivo->En_Estado;
                $space->En_Complemento = $coletivo->En_Complemento ?: '';
                $space->save(true);

                $theme->createSpaceRelation($space, $this);
                $app->enableAccessControl();
            }
            
            if($this->range != $app->config['rcv.rangesMap']['cadastro-via-edital']) {
                $theme->sendMailRegistrationStatus($this);
            }
        });

        $app->hook('entity(Registration).status(notapproved)', function() use($app, $theme) {
            /** @var Registration $this */

            // Envia notificação de importação não aprovada
            $opportunity_pnab_id = $app->config['rcv.pnabOpportunityId'];
            if($this->opportunity->id == $opportunity_pnab_id) {
                $theme->sendMailRegistrationPnabDenied($this);
            }

            // Verifica se a inscrição é da oportunidade correta
            $opportunity_id = $app->config['rcv.opportunityId'];
            if($this->opportunity->id != $opportunity_id) {
                return;
            }

            // Envia notificação de inscrição não aprovada
            if($this->range != $app->config['rcv.rangesMap']['cadastro-via-edital']) {
                $theme->sendMailRegistrationStatus($this);
            }
        });

        // Altera template de email ao iniciar uma inscrição
        $app->hook("sendMailNotification.registrationStart:beforeEnqueue", function(&$registration, &$template, &$enable, &$params) use ($app) {
            if($registration->opportunity->id == $app->config['rcv.opportunityId']) {
                $template = 'rcv_start_registration';
            }

            if($registration->opportunity->id == $app->config['rcv.pnabOpportunityId']) {
                $template = 'rcv_start_import_registration';
            }
        });

        $app->hook("sendMailNotification.registrationStart", function(&$registration, &$template, &$params) use ($app) {
            if($template == 'rcv_start_registration') {
                $relations = $registration->relatedAgents;
                $coletivo = $relations['coletivo'][0] ?? (object)[];
                $params['nameOrg'] = $coletivo->nomeCompleto ?? null;
                $params['cnpjOrg'] = $coletivo->cnpj ?? null;
                $params['categoryOrg'] = $registration->category;
            }
        });
        
        // Altera template de email ao envio uma inscrição
        $app->hook("sendMailNotification.registrationSend", function(&$registration, &$template, &$enable, &$params) use ($app)  {
            if($registration->opportunity->id == $app->config['rcv.opportunityId']) {
                $relations = $registration->relatedAgents;
                $coletivo = $relations['coletivo'][0] ?? (object)[];
                $params['nameOrg'] = $coletivo->nomeCompleto ?: null;
                $params['cnpjOrg'] = $coletivo->cnpj ?: null;
                $params['categoryOrg'] = $registration->category;

                if((isset($_SESSION["{$registration}:editableFields"]) && $_SESSION["{$registration}:editableFields"])) {
                    $template = 'rcv_send_registration_update' ;
                    $params['orgId'] = $coletivo->id;
                } else {
                    if($registration->opportunity->id == $app->config['rcv.opportunityId']) {
                        $template = 'rcv_send_registration';
                    }
                }

            }

            if($registration->opportunity->id == $app->config['rcv.pnabOpportunityId']) {
                $template = 'rcv_send_import_registration';
            }
        });

        // Define propriedades para auxiliar no momento de ceder a propriedade
        $app->hook('workflow(RequestChangeOwnership).approve:before', function() use($app, $theme) {
                $this->old_owner_origin_id = $this->origin->owner->id;
                $this->old_owner_destination_id = $this->destination->owner->id;
        });

        // Altera a mensagem do request para na solicitação de mudança de propriedade
        $app->hook('request(workflow.message).create:before', function($organization, $origin, $entityType, &$message_to_requester, &$send_message, $requester) use($theme, $app) {
            if($this instanceof \MapasCulturais\Entities\RequestChangeOwnership) {
                $org_name = $organization->name;
                $org_url = $organization->singleUrl;

                $destinattion_name = $this->destination->name;
                $destinattion_url = $this->destination->singleUrl;

                $origin_url = $origin->singleUrl;

                if($organization->ownerUser->id == $this->requesterUser->id) {
                    // ceder propriedade
                    $origin_name = $organization->ownerUser->profile->name;
                    $origin_email = ($organization->ownerUser->profile->emailPrivado ??
                        $organization->ownerUser->profile->emailPublico ?? 
                        $organization->ownerUser->email);
                    $origin_phone = $organization->ownerUser->profile->telefonePublico ?? '';
                } else {
                    // reinvindicar propriedade
                    $origin_name = $origin->ownerUser->profile->name;
                    $origin_email = ($origin->ownerUser->profile->emailPrivado ??
                        $origin->ownerUser->profile->emailPublico ?? 
                        $origin->ownerUser->email);
                    $origin_phone = $origin->ownerUser->profile->telefonePublico ?? '';
                }

                $origin_link = "<a rel='noopener noreferrer' href=\"{$origin_url}\">{$origin_name}</a>";
                $org_link = "<a rel='noopener noreferrer' href=\"{$org_url}\">{$org_name}</a>";
                $destinattion_link = "<a rel='noopener noreferrer' href=\"{$destinattion_url}\">{$destinattion_name}</a>";
                $org_owner_url = "<a rel='noopener noreferrer' href=\"{$organization->owner->singleUrl}\">{$organization->owner->name}</a>";
                $subject = '[Cultura Viva] Requisição de mudança de propriedade';

                if($this->metadata['type'] === "request") {
                    $send_message = sprintf(
                        i::__("%s esta solicitando representar a organização %s.<br>E-mail de contato: %s<br>Telefone de contato: %s"),
                        $origin_link,
                        $org_link,
                        $origin_email,
                        $origin_phone
                    );

                    $message_to_requester = sprintf(i::__("Sua requisição para representar a organização %s foi enviada. Aguarde a aprovação de %s."), $org_link, $org_owner_url);
                    $message_mail = $message_to_requester;
                    $scdc_email = $organization->ownerUser->profile->emailPublico;

                    if($scdc_email && $scdc_email == 'culturaviva@cultura.gov.br') {
                        $subject = '[Cultura Viva] Comprovação de nova representação da organização';

                        $message_mail = "
                            Para realizarmos a alteração de propriedade da organização precisamos que você envie para o email {$app->config['rcv.email']} a ata da última gestão, o estatuto da organização e um documento seu com foto.<br>
                            Além disso, precisamos que anexe uma foto sua segurando o documento com foto, para comprovarmos que você é o agente responsável por esse chamado de suporte.<br><br>
    
                            Aguardamos seu e-mail de retorno com esses anexos.<br><br>
    
                            Atenciosamente,<br>
                            Equipe de suporte<br>
                            Secretaria de Cidadania e Diversidade Cultural (SCDC)<br>
                            Ministério da Cultura";
                    }
                } else {
                    $send_message = sprintf(i::__("%s esta solicitando que você represente a organização %s"), $origin_link, $org_link);
                    $message_to_requester = sprintf(i::__("Sua solicitação para que %s represente a organização %s foi enviada. Aguarde a aprovação de %s."), $destinattion_link, $org_link, $destinattion_link);
                    $message_mail = $message_to_requester;
                }

                $theme->sendMailRequester($origin_email, $message_mail, $subject);
            }
        });

        // Altera mensagem do e-mail para adicionar os botões de rejeitar e aceitar
        $app->hook('request(workflow.message.<<destination|origin>>).sendMail', function(&$message, $notification) use($app) {
            if(get_class($this) === 'MapasCulturais\Entities\RequestChangeOwnership') {
                $approve_url = $app->createUrl('notification', 'approve', ['id' => $notification->id]);
                $reject_url = $app->createUrl('notification', 'reject', ['id' => $notification->id]);
    
                $button_css = "
                    align-items: center;
                    appearance: none;
                    border-radius: 8px;
                    cursor: pointer;
                    display: inline-flex;
                    font-size: 1rem;
                    font-weight: 600;
                    justify-content: center;
                    line-height: 1.125rem;
                    padding: 0.5625rem 1.1875rem;
                    position: relative;
                    text-decoration: none;
                ";
    
                $message = '
                    <div>
                        <p>' . $message . '</p>
                        <a href='. $reject_url . ' style="'.$button_css.'background-color: #fff;border:0.125rem solid #9B156B;color:#9B156B;">Rejeitar</a>
                        <a href='. $approve_url . ' style="'.$button_css.'background-color: #9B156B;border:0.125rem solid #9B156B;color:#fff;">Aceitar</a>
                    </div>
                ';
            }
        });

        // Altera o redirecionamento do botão de rejeitar no e-mail para single do ponto
        $app->hook('notification.reject.referer', function(&$referer) use($app) {
            /** @var \MapasCulturais\Controllers\Notification $this */
            $notification = $this->requestedEntity;
            $single_id = $notification->request->origin->id;

            $referer = $app->createUrl('agent', 'single', [$single_id]);
        });

        // Altera o redirecionamento do botão de aceitar no e-mail para single do ponto
        $app->hook('notification.approve.referer', function(&$referer) use($app) {
            /** @var \MapasCulturais\Controllers\Notification $this */
            $notification = $this->requestedEntity;
            $single_id = $notification->request->origin->id;

            $referer = $app->createUrl('agent', 'single', [$single_id]);
        });

        // Altera a mensagem do request para na aprovação da mudança de propriedade
        $app->hook('request(workflow.message).approve:before', function($organization, $origin, $entityType, &$send_message, $requester) {
            if(get_class($this) === 'MapasCulturais\Entities\RequestChangeOwnership') {
                $org_name = $organization->name;
                $org_url = $organization->singleUrl;

                $origin_url = $requester->profile->singleUrl;
                $origin_name = $requester->profile->name;

                $origin_link = "<a rel='noopener noreferrer' href=\"{$origin_url}\">{$origin_name}</a>";
                $org_link = "<a rel='noopener noreferrer' href=\"{$org_url}\">{$org_name}</a>";

                if($this->metadata['type'] === "request") {
                    $send_message = sprintf(i::__("%s aceitou sua solicitação para representar a organização %s"), $origin_link, $org_link);
                } else {
                    $send_message = sprintf(i::__("%s aceitou sua solicitação para que ele represente a organização %s"), $origin_link, $org_link);
                }
            }
        });

         // Altera a mensagem do request para na rejeição da mudança de propriedade
        $app->hook('request(workflow.message).reject:before', function($organization, $origin, $entityType, &$send_message, $requester) {
            if(get_class($this) === 'MapasCulturais\Entities\RequestChangeOwnership') {
                $org_name = $organization->name;
                $org_url = $organization->singleUrl;

                $origin_url = $requester->profile->singleUrl;
                $origin_name = $requester->profile->name;

                $origin_link = "<a rel='noopener noreferrer' href=\"{$origin_url}\">{$origin_name}</a>";
                $org_link = "<a rel='noopener noreferrer' href=\"{$org_url}\">{$org_name}</a>";

                if($this->metadata['type'] === "request") {
                    if($requester->profile->id != $origin->id) {
                        $send_message = sprintf(i::__("%s não aceitou conceder a representação da organização %s"), $origin_link, $org_link);
                    } else {
                        $send_message = sprintf(i::__("%s cancelou a solicitação para representar da organização %s"), $origin_link, $org_link);
                    }
                } else {
                    if($requester->profile->id != $origin->id) {
                        $send_message = sprintf(i::__("%s cancelou a solicitação para que você represente a organização %s"), $origin_link, $org_link);
                    } else {
                        $send_message = sprintf(i::__("%s não aceitou sua solicitação para representar a organização %s"), $origin_link, $org_link);
                    }
                }
            }
        });

        // Altera owner da inscrição ao aceitar alterar o representante da organização
        $app->hook('workflow(RequestChangeOwnership).approve:after', function() use($app, $theme) {
            if($registration = $this->origin->rcv_registration) {
                $app->disableAccessControl();

                // o destination é o novo owner da organização.
                $registration->owner = $this->destination;
                
                // atualiza o agentsData
                if($registration->status > 0) {
                    $registration->agentsData = $registration->_getAgentsData();
                } else {
                    $registration->agentsData = [];
                }

                $registration->save(true);

                $app->enqueueEntityToPCacheRecreation($registration);

                // apaga os campos @
                if($registration_field_config = $registration->opportunity->registrationFieldConfigurations) {
                    $conn = $app->em->getConnection();
                    foreach($registration_field_config as $field) {
                        if($field->fieldType === 'agent-owner-field') {
                            $field_name = "field_{$field->id}";
                            $conn->executeQuery("DELETE FROM registration_meta WHERE key = '{$field_name}' AND object_id = {$registration->id}");
                        }
                    }
                }

                $app->enableAccessControl();
            }
        });

        // Disparo de e-mail, caso solicitado pelo usuário, ao desativar um ponto/pontão
        $app->hook('POST(site.desativar-ponto)', function() use($app, $theme) {
            $this->requireAuthentication();
            
            $organization_id = $this->data['organization'];
            $organization = $app->repo('Agent')->find($organization_id);

            if($organization) {
                $app->disableAccessControl();
                $organization->rcv_registration->range = $app->config['rcv.rangesMap']['desativar-ponto'];
                $organization->rcv_registration->save(true);
                $app->enableAccessControl();

                $message = $this->data['message'];
                $agent_id = $this->data['user'];
                $agent = $app->repo('Agent')->find($agent_id);
                $theme->sendMailNotification($app, $agent, $organization, $message);

                $registration = $organization->rcv_registration;
                $user_email = $registration->owner->emailPrivado ?: $registration->owner->emailPublico ?: $registration->owner->email ?: '';

                $data = [
                    "owner_name" => $registration->owner->name,
                    "agent_name" => $organization->name,  
                    "agent_cnpj" => $organization->cnpj ?? '',
                    "email_destinatario" => $user_email];

                $theme->sendTransactionalEmail('deactivation_tipo', $data);

                $this->json(true);
            }

            $this->errorJson(false);
        });

        // Após envio da avaliação que tenha faixa diferente de cadastro, altera a faixa da inscrição para cadastro e troca o avaliador para o usuário fantasma
        $app->hook('entity(RegistrationEvaluation).send:after', function() use($app, $theme) {
            /** @var RegistrationEvaluation $this */
            $registration = $this->registration;

            // Verifica se a inscrição é da oportunidade correta e se a faixa da inscrição é diferente de cadastro
            if($registration->opportunity->id == $app->config['rcv.opportunityId'] && in_array($registration->range, [$app->config['rcv.rangesMap']['alterar-cnpj'], $app->config['rcv.rangesMap']['alterar-organizacao']])) {
                $phantom_user_id = $app->config['rcv.phantomUserId'];

                $app->disableAccessControl();
                $registration->range = $app->config['rcv.rangesMap']['cadastro'];
                $registration->save(true);

                if($phantom_user = $app->repo('User')->find($phantom_user_id)) {
                    $this->user = $phantom_user;
                    $this->save(true);
                }

                $app->enableAccessControl();
            }   
        });

        $normalize_spreadsheet_select = function (&$query) {
            if (!isset($query['@select'])) {
                return;
            }

            $query['@select'] = str_replace([
                "terms['acao_estruturante'].join(', ')",
                "terms['acao_estruturante_outra'].join(', ')",
                "terms['rcv_principais_segmentos'].join(', ')",
                "terms['rcv_atuacao_demais_segmentos'].join(', ')",
            ], [
                'acao_estruturante',
                'acao_estruturante_outra',
                'rcv_principais_segmentos',
                'rcv_atuacao_demais_segmentos',
            ], $query['@select']);
        };

        $app->hook('SpreadsheetJob(entities-spreadsheets).getHeader:before', function($job, &$query) use($normalize_spreadsheet_select) {
            if($job->entityClassName == Agent::class) {
                $normalize_spreadsheet_select($query);
            }
        });

        $app->hook('SpreadsheetJob(entities-spreadsheets).getHeader:after', function($job, &$result) use($app, $theme) {
            if($job->entityClassName == Agent::class) {
                foreach($result as $key => $value) {
                    $result[$key] = $app->config['rcv.SpreadsheetColumnsLabels'][$key]['label'] ?? $value;
                }
            }
        });

        $app->hook('SpreadsheetJob(entities-spreadsheets).getBatch:before', function($job, &$query) use($normalize_spreadsheet_select) {
            if($job->entityClassName == Agent::class) {
                $normalize_spreadsheet_select($query);
            }
            $query['status'] = API::GT(0);
        });

        $app->hook('SpreadsheetJob(entities-spreadsheets).getBatch:after', function($job, &$result) use($app, $theme) {
            if($job->entityClassName == Agent::class) {
                foreach($result as &$entity) {
                    $agent = $app->repo('Agent')->find($entity['id']);

                    if($agent) {
                        $entity['publicLocation'] = isset($agent->publicLocation) && $agent->publicLocation ? 'Sim' : 'Não';

                        if ($agent->location) {
                            $entity['location'] = (string) $agent->location;
                        }
                        
                        if (isset($entity['tipoPonto'])) {
                            $tipos = [];

                            if(is_array($entity['tipoPonto'])) {
                                foreach ($entity['tipoPonto'] as $tipo) {
                                    $tipo_ponto = str_replace('_', '-', $tipo);
                                    
                                    $tipos[] = $app->config['rcv.categoriesMap'][$tipo_ponto];
                                }
    
                                $entity['tipoPonto'] = implode(', ', $tipos);
                            }
                        }

                        $entity['ownerName'] = $agent->owner->user->profile->name;
                        $entity['cpf'] = $agent->owner->user->profile->cpf;

                        if(array_key_exists('seals', $entity) && $agent->sealRelations && $agent->sealRelations[0]->createTimestamp) {
                            $entity['seals'] = $agent->sealRelations[0]->createTimestamp?->format('d/m/Y');
                        }

                        if (array_key_exists('rcv_sede_realizaAtividades_outros_lista', $entity) && $agent->rcv_sede_realizaAtividades_outros_lista) {
                            $addresses = json_decode($agent->rcv_sede_realizaAtividades_outros_lista, true);
                            
                            if (is_array($addresses)) {
                                $formattedAddresses = array_map(fn($data) => sprintf(
                                    "%s: %s, %s, %s, %s, %s - %s, %s",
                                    $data['nome'] ?? '',
                                    $data['logradouro'] ?? '',
                                    $data['numero'] ?? '',
                                    $data['bairro'] ?? '',
                                    $data['cidade'] ?? '',
                                    $data['complemento'] ?? '',
                                    $data['estado'] ?? '',
                                    $data['cep'] ?? ''
                                ), $addresses);
                        
                                $entity['rcv_sede_realizaAtividades_outros_lista'] = implode("\n", $formattedAddresses);
                            }
                        }
                        
                        if (in_array('rcv_links_coletivo', array_keys($entity)) && $agent->rcv_links_coletivo) {
                            $links = json_decode($agent->rcv_links_coletivo, true);
                            if (is_array($links)) { {
                                    $formatedLinks = array_map(fn($data) => sprintf(
                                        "%s: %s",
                                        $data['title'] ?? '',
                                        $data['value'] ?? '',
                                    ), $links);
                                }
                                $entity['rcv_links_coletivo'] = implode("\n", $formatedLinks);
                            }
                        }

                        if (in_array('outrosSelos', array_keys($entity))) {
                            $_result = [];
                            $verificationSeals = $app->config['rcv.verificationSeals'];
                            if ($sealRelations = $agent->sealRelations) {

                                foreach ($sealRelations as $relation) {
                                    if ($relation->seal->id != $verificationSeals['ponto'] && $relation->seal->id != $verificationSeals['pontao']) {
                                        $_result[] = $relation->seal->name;
                                    }
                                }
                            }
                            if ($_result) {
                                $entity['outrosSelos'] = implode(', ', $_result);
                            }
                        }

                        if(in_array('acao_estruturante', array_keys($entity)) && $agent->terms['acao_estruturante']) {
                           $entity['acao_estruturante'] = implode(', ', $agent->terms['acao_estruturante']);
                        }

                        if(in_array('acao_estruturante_outra', array_keys($entity)) && $agent->terms['acao_estruturante_outra']) {
                            $entity['acao_estruturante_outra'] = implode(', ', $agent->terms['acao_estruturante_outra']);
                        }

                        if(in_array('rcv_principais_segmentos', array_keys($entity)) && $agent->terms['rcv_principais_segmentos']) {
                            $entity['rcv_principais_segmentos'] = implode(', ', $agent->terms['rcv_principais_segmentos']);
                        }

                        if(in_array('rcv_atuacao_demais_segmentos', array_keys($entity)) && $agent->terms['rcv_atuacao_demais_segmentos']) {
                            $entity['rcv_atuacao_demais_segmentos'] = implode(', ', $agent->terms['rcv_atuacao_demais_segmentos']);
                        }

                        if(in_array('instancia_representacao_minc', array_keys($entity)) && $agent->terms['instancia_representacao_minc']) {
                            $entity['instancia_representacao_minc'] = implode(', ', $agent->terms['instancia_representacao_minc']);
                        }

                        if(in_array('ponto_infra_estrutura', array_keys($entity)) && $agent->terms['ponto_infra_estrutura']) {
                            $entity['ponto_infra_estrutura'] = implode(', ', $agent->terms['ponto_infra_estrutura']);
                        }

                        if(in_array('ponto_equipamentos', array_keys($entity)) && $agent->terms['ponto_equipamentos']) {
                            $entity['ponto_equipamentos'] = implode(', ', $agent->terms['ponto_equipamentos']);
                        }

                        if(in_array('ponto_recursos_humanos', array_keys($entity)) && $agent->terms['ponto_recursos_humanos']) {
                            $entity['ponto_recursos_humanos'] = implode(', ', $agent->terms['ponto_recursos_humanos']);
                        }

                        if(in_array('ponto_hospedagem', array_keys($entity)) && $agent->terms['ponto_hospedagem']) {
                            $entity['ponto_hospedagem'] = implode(', ', $agent->terms['ponto_hospedagem']);
                        }

                        if(in_array('ponto_deslocamento', array_keys($entity)) && $agent->terms['ponto_deslocamento']) {
                            $entity['ponto_deslocamento'] = implode(', ', $agent->terms['ponto_deslocamento']);
                        }

                        if(in_array('ponto_comunicacao', array_keys($entity)) && $agent->terms['ponto_comunicacao']) {
                            $entity['ponto_comunicacao'] = implode(', ', $agent->terms['ponto_comunicacao']);
                        }

                        if(in_array('ponto_sustentabilidade', array_keys($entity)) && $agent->terms['ponto_sustentabilidade']) {
                            $entity['ponto_sustentabilidade'] = implode(', ', $agent->terms['ponto_sustentabilidade']);
                        }

                        if(in_array('metodologias_areas', array_keys($entity)) && $agent->terms['metodologias_areas']) {
                            $entity['metodologias_areas'] = implode(', ', $agent->terms['metodologias_areas']);
                        }

                        if(in_array('rede_pertencente', array_keys($entity)) && $agent->terms['rede_pertencente']) {
                            $entity['rede_pertencente'] = implode(', ', $agent->terms['rede_pertencente']);
                        }
                        
                        if(in_array('area', array_keys($entity)) && $agent->terms['area']) {
                            $entity['area'] = implode(', ', $agent->terms['area']);
                        }  
                        
                         if(in_array('area_atuacao', array_keys($entity)) && $agent->terms['area_atuacao']) {
                            $entity['area_atuacao'] = implode(', ', $agent->terms['area_atuacao']);
                        }   

                        if (in_array('paisPontaPontao', array_keys($entity))) {
                            $_public = ($agent->rcv_org_brasil === "Sim") ? $agent->publicLocation : $agent->En_publicLocationPontaPontao;
                            $public = (is_string($_public) && trim(strtolower($_public)) === "sim") || (is_bool($_public) && $_public === true) ? true : false;
                        
                            if ($public || $app->user->is('admin')) {
                                $entity['paisPontaPontao'] = $agent->En_Pais ?: $agent->paisPontaPontao ?: $agent->pais;
                            }
                        }

                        if (in_array('cepPontaPontao', array_keys($entity))) {
                            $_public = ($agent->rcv_org_brasil === "Sim") ? $agent->publicLocation : $agent->En_publicLocationPontaPontao;
                            $public = (is_string($_public) && trim(strtolower($_public)) === "sim") || (is_bool($_public) && $_public === true) ? true : false;

                            if ($public || $app->user->is('admin')) {
                                $entity['cepPontaPontao'] = $agent->En_CEP ?: $agent->cepPontaPontao;
                            }
                        }

                        if (in_array('En_Nome_LogradouroPontaPontao', array_keys($entity))) {
                            $_public = ($agent->rcv_org_brasil === "Sim") ? $agent->publicLocation : $agent->En_publicLocationPontaPontao;
                            $public = (is_string($_public) && trim(strtolower($_public)) === "sim") || (is_bool($_public) && $_public === true) ? true : false;
                        
                            if ($public || $app->user->is('admin')) {
                                $entity['En_Nome_LogradouroPontaPontao'] = $agent->En_Nome_Logradouro ?: $agent->En_Nome_LogradouroPontaPontao;
                            }
                        }

                        if (in_array('En_NumPontaPontao', array_keys($entity))) {
                            $_public = ($agent->rcv_org_brasil === "Sim") ? $agent->publicLocation : $agent->En_publicLocationPontaPontao;
                            $public = (is_string($_public) && trim(strtolower($_public)) === "sim") || (is_bool($_public) && $_public === true) ? true : false;
                        
                            if ($public || $app->user->is('admin')) {
                                $entity['En_NumPontaPontao'] = $agent->En_Num ?: $agent->En_NumPontaPontao;
                            }
                        }

                        if (in_array('En_MunicipioPontaPontao', array_keys($entity))) {
                            $_public = ($agent->rcv_org_brasil === "Sim") ? $agent->publicLocation : $agent->En_publicLocationPontaPontao;
                            $public = (is_string($_public) && trim(strtolower($_public)) === "sim") || (is_bool($_public) && $_public === true) ? true : false;
                        
                            if ($public || $app->user->is('admin')) {
                                $entity['En_MunicipioPontaPontao'] = $agent->En_Municipio ?: $agent->En_MunicipioPontaPontao;
                            }
                        }

                        if (in_array('En_EstadoPontaPontao', array_keys($entity))) {
                            $_public = ($agent->rcv_org_brasil === "Sim") ? $agent->publicLocation : $agent->En_publicLocationPontaPontao;
                            $public = (is_string($_public) && trim(strtolower($_public)) === "sim") || (is_bool($_public) && $_public === true) ? true : false;
                        
                            if ($public || $app->user->is('admin')) {
                                $entity['En_EstadoPontaPontao'] = $agent->En_Estado ?: $agent->En_EstadoPontaPontao;
                            }
                        }
                    }
                }
            }

        });

        // Executa o registro do plugin RCV antes da execução do JOB
        $app->hook('app.executeJob:before', function() {
            $this->plugins['RCV']->register();
        });

        // Define os headers da tabela de agentes coletivos
        $app->hook('component(agent-table-2).additionalHeaders', function (&$additionalHeaders) use ($app) {
            $additionalHeaders = [];
            $skip_fields = [];
            $definitions = \MapasCulturais\Entities\Agent::getPropertiesMetadata();

            $can_see = function ($def) use ($app) {
                if ($app->user->is('admin')) {
                    return true;
                }

                if (isset($def['private']) && $def['private'] === true) {
                    return false;
                } else {
                    return true;
                }
            
            };
            
            $increment = [
                "singleUrl" => ["label" => "Url"],
                "profile" => ["label" => "Responsável"],
                "seals" => ["label" => "Certificação"],
                "ownerName" => ["label" => "Responsável"],
                "acao_estruturante" => ["label" => "Ações estruturantes da PNCV"],
                "acao_estruturante_outra" => ["label" => "Outras ações estruturantes"],
                "acao_estruturante_outra_descreva" => ["label" => "22957 - Se optou por OUTRA, descreva."],
                "rcv_principais_segmentos" => ["label" => "Principais áreas de atuação"],
                "rcv_atuacao_demais_segmentos" => ["label" => "Demais áreas de atuação"],
                "rcv_deficiencia" => ["label" => "Público PCD"],
                "outrosSelos" => ["label" => "Outros Selos"],
                "publicLocation" => ["label" => "Localização Pública"],
                "location" => ["label" => "Localização"],
            ];

            $definitions = $increment + $definitions;
            
            $columns = $app->config['rcv.SpreadsheetColumnsLabels'];

            foreach($columns as $field => $params) {
                if(!in_array($field, array_keys($definitions))) {
                    continue;
                }

                $def = $definitions[$field];
                
                if (!in_array($field, $skip_fields) && in_array($field, array_keys($columns)) && !str_starts_with($field, "_") && $can_see($def)) {

                    if(!$app->user->is('admin') && !$columns[$field]['public']) {
                        continue;
                    }

                    $data = [
                        'text' => isset($columns[$field]) ? $columns[$field]['label'] : $def['label'],
                        'value' => $field,
                        'slug' => $field
                    ];

                    if ($field == "publicLocation") {
                        $data['value'] = "publicLocation";
                    }

                    if ($field == "location") {
                        $data['value'] = "location";
                    }

                    if(str_starts_with($field, 'geo')) {
                        $data['text'] = $def['label'] . " - Divisão geográfica";
                    }

                    if($field == "cpf") {
                        $data['value'] = 'parent?.cpf';
                    }

                    if($field == "seals") {
                        $data['value'] = "seals[0]?.createTimestamp";
                    }

                    if($field == "ownerName") {
                        $data['value'] = "parent?.name";
                    }

                    if($field == "acao_estruturante") {
                        $data['value'] = "terms['acao_estruturante'].join(', ')";
                    }

                    if($field == "acao_estruturante_outra") {
                        $data['value'] = "terms['acao_estruturante_outra'].join(', ')";
                    }

                    if($field == "rcv_principais_segmentos") {
                        $data['value'] = "terms['rcv_principais_segmentos'].join(', ')";
                    }

                    if($field == "rcv_atuacao_demais_segmentos") {
                        $data['value'] = "terms['rcv_atuacao_demais_segmentos'].join(', ')";
                    }

                    if($field == "area") {
                        $data['value'] = "terms['area']?.join(', ')";
                    }

                    if($field == "area_atuacao") {
                        $data['value'] = "terms['area_atuacao']?.join(', ')";
                    }

                    if($field == "endereco") {
                        $data['value'] = "endereco";
                    }

                    $additionalHeaders[] = $data;
                }
            }

            $opportunity = $app->repo('Opportunity')->find($app->config['rcv.opportunityId']);
            $fields = $opportunity->registrationFieldConfigurations;

            $order_map = [];
            foreach ($fields as $field) {
                if ($field->fieldType == 'agent-collective-field' || $field->fieldType == 'agent-owner-field') {
                    $field_config = $field->config['entityField'] ?: null;

                    if ($field_config) {
                        $order_map[$field_config] = [
                            'stepOrder' => $field->step->displayOrder,
                            'fieldOrder' => $field->displayOrder,
                        ];
                    }
                }
            }

            usort($additionalHeaders, function ($a, $b) use ($order_map) {
                $a_slug = $a['slug'] ?? null;
                $b_slug = $b['slug'] ?? null;

                $a_order = $order_map[$a_slug] ?? ['stepOrder' => PHP_INT_MAX, 'fieldOrder' => PHP_INT_MAX];
                $b_order = $order_map[$b_slug] ?? ['stepOrder' => PHP_INT_MAX, 'fieldOrder' => PHP_INT_MAX];

                return $a_order['stepOrder'] <=> $b_order['stepOrder']
                    ?: $a_order['fieldOrder'] <=> $b_order['fieldOrder'];
            });
        });

        $app->hook('component(agent-table).additionalHeaders', function (&$defaultHeaders, &$additionalHeaders, &$default_select) use ($app) {
            $defaultHeaders = [];
            $default_select = "id,seals,ownerName,En_EstadoPontaPontao,terms,parent.{id,name,cpf},singleUrl,En_CEP,En_Nome_Logradouro,En_Num,En_Complemento,En_Bairro,En_Municipio,En_Estado,En_Pais,rcv_org_brasil";
        });
       
        // Define os headers da tabela de inscrições
        $app->hook('component(opportunity-registrations-table).additionalHeaders', function (&$defaultHeaders, &$default_select, &$available_fields) use ($app) {

            $select = explode(',', $default_select);
            if (!in_array('agentsData', $select)) {
                array_push($select, 'agentsData');
                $default_select = implode(',', $select);
            }

            // Nome da organização
            $organization_name_header = [
                'text' => 'Nome da organização',
                'value' => 'agentsData?.coletivo?.name',
                'slug' => 'nomeCompleto'
            ];

            $agent_index = array_search('agent', array_column($defaultHeaders, 'slug'));
            if($agent_index !== false) {
                array_splice($defaultHeaders, $agent_index + 1, 0, [$organization_name_header]);
            }

            // Coluna UpdateTimestamp
            $update_timestamp_header = [
                'text' => 'Última atualização',
                'value' => 'updateTimestamp',
            ];

            $sent_timestamp_index = array_search('sentTimestamp', array_column($defaultHeaders, 'value'));
            if($sent_timestamp_index !== false) {
                array_splice($defaultHeaders, $sent_timestamp_index + 1, 0, [$update_timestamp_header]);
            }

            $available_fields = array_values( 
                array_filter($available_fields, function($item) {
                    return $item['fieldName'] !== 'proponentType';
                })
            );

            // Lista de valores que precisam ser removidos
            $skipFields = ['rcv_locked_fields', 'appliedPointReward', 'appliedForQuota', 'valuersIncludeList', 'valuersExcludeList', 'valuers', 'rcv_tipo', 'proponentType'];
            // Filtrar o array
            $defaultHeaders = array_values(array_filter($defaultHeaders, function ($header) use ($skipFields) {
                $valueCheck = isset($header['value']) && in_array($header['value'], $skipFields);
                $slugCheck = isset($header['slug']) && in_array($header['slug'], $skipFields);
                return !$valueCheck && !$slugCheck;
            }));

            $opportunity = $this->controller->requestedEntity;
            if($opportunity->id == $app->config['rcv.pnabOpportunityId']) {
                $defaultHeaders[] = [
                    'text' => 'Processamento',
                    'value' => 'linkLog',
                    'slug' => 'linkLog'
                ];
            }

            if($opportunity->id == $app->config['rcv.opportunityId']) {
                $fields = $opportunity->registrationFieldConfigurations;
                $order_map = [];

                foreach ($fields as $field) {
                    if ($field->fieldType == 'agent-collective-field' || $field->fieldType == 'agent-owner-field') {
                        if ($field_name = $field->fieldName ?: null) {
                            $order_map[$field_name] = [
                                'stepOrder' => $field->step->displayOrder,
                                'fieldOrder' => $field->displayOrder,
                            ];
                        }
                    }
                }
    
                usort($defaultHeaders, function ($a, $b) use ($order_map) {
                    $a_slug = $a['slug'] ?? null;
                    $b_slug = $b['slug'] ?? null;
    
                    $a_order = $order_map[$a_slug] ?? ['stepOrder' => PHP_INT_MAX, 'fieldOrder' => PHP_INT_MAX];
                    $b_order = $order_map[$b_slug] ?? ['stepOrder' => PHP_INT_MAX, 'fieldOrder' => PHP_INT_MAX];
    
                    return $a_order['stepOrder'] <=> $b_order['stepOrder']
                        ?: $a_order['fieldOrder'] <=> $b_order['fieldOrder'];
                });
            }
        });

        // Atualiza a data da ultima atualização cadastral no agente
        $app->hook("entity(Registration).<<send|save>>:after", function () use ($app) {
            /** @var Registration $this */
            if (isset($_SESSION["{$this}:editableFields"]) && $_SESSION["{$this}:editableFields"]) {
                if($agentRelations = $this->agentRelations) {
                    foreach($agentRelations  as $relation) {
                        if($relation->group == "coletivo") {
                            $relation->agent->rcv_cad_updateTimestamp = $this->updateTimestamp->format('Y-m-d H:i:s');
                        }
                    }
                }
            }
        });

        // Altera nome do arquivo exportado da planilha de Agentes para Cadastro 
        $app->hook("SpreadsheetJob(entities-spreadsheets).getFilename:after", function($job, &$result) {
            if(str_starts_with($result, 'Agentes')) {
                $result = str_replace("Agentes", "Cadastro", $result);
            }
        });

        $app->hook('mustacheTemplate(export_spreadsheet.html).templateData', function(&$template_data) {
            if (str_contains($template_data->messageBody, 'Sua planilha de Agente')) {
                $template_data->messageBody = i::__('Sua planilha do Cadastro Nacional de Pontos e Pontões de Cultura foi gerada e está pronta para ser baixada. Acesse o link abaixo para obter o arquivo:');
            }
        });

        $app->hook('POST(site.enviar-duvida)', function() use($app, $theme) {
            $this->requireAuthentication();
            
            $name = $this->data['name'];
            $email = $this->data['email'];
            $question = $this->data['question'];

            if($name && $email && $question) {
                $theme->sendMailQuestion($app, $name, $email, $question);

                $this->json(true);
            }

            $this->errorJson(false);
        });

        $app->hook('entity(Registration).statusesNames', function(&$statuses) use($app){
            if($app->view->controller){
                $opportunity = $app->view->controller->requestedEntity;
    
                if($opportunity->id == $app->config['rcv.opportunityId']){
                    $status = [
                        10 => "Habilitado", 
                        3 => "Inabilitado",
                    ];
        
                    foreach ($status as $key => $value) {
                        if (isset($statuses[$key])) {
                            $statuses[$key] = $value;
                        }
                    }
        
                    unset($statuses[8], $statuses[2]);

                }
            }
        });

        // Valida a planilha das organizações certificadas antes do envio da inscrição
        $app->hook('entity(Registration).sendValidationErrors', function(&$errorsResult) use ($app) {
            /** @var \MapasCulturais\Entities\Registration $this */
            if ($this->opportunity->id !== (int) $app->config['rcv.pnabOpportunityId']) {
                return;
            }

            foreach (Importer::validatePnabSpreadsheetForSend($this) as $field_key => $messages) {
                $errorsResult[$field_key] = $messages;
            }
        });

        $app->hook('template(registration.view.main-app):before', function() use($app){
            $registration = $this->controller->requestedEntity;
            $field_name =  $app->config['rcv.fieldQuestion'];

            if (empty($field_name)) {
                return;
            }

            if ($registration->opportunity->id == $app->config['rcv.opportunityId'] && $registration->$field_name == $app->config['rcv.questionResponse']) {
                $app->view->enqueueScript('components', 'registrationView', 'js/registration-view.js', []);
            }
        });

        // Remove pergunta "A organização está concorrendo em algum edital da Cultura Viva atualmente" da tela de atualização cadastral
        $app->hook('entity(Opportunity).registrationFieldConfigurations', function(&$result) use ($app, $self) {
            /** @var \MapasCulturais\Entities\Opportunity $this */

            $controller = $app->view->controller;

            $referer = php_sapi_name() != "cli" ? $app->request->getReferer() : null;
            $registration = $app->view->controller->requestedEntity ?? null;
            $validating = false;

            if($registration instanceof Registration) {
                $registration_url = $app->createUrl('registration', 'registrationEdit', [$registration->id]);
                $validating = $registration_url == ($referer[0] ?? false);
            }

            if($validating || ($this->id == $app->config['rcv.opportunityId'] && $controller && $controller->action == 'registrationEdit')) {
                $concorrendo_edital = $app->config['rcv.fieldQuestion'];

                $result = array_filter($result, function($field) use ($concorrendo_edital) {
                    return $field->fieldName != $concorrendo_edital;
                });

                $result = array_values($result);
            }
        });

        // Remove campos de anexo da etapa 7 da tela de atualização cadastral
        $app->hook('entity(Opportunity).registrationFileConfigurations', function(&$result) use ($app, $self) {
            /** @var \MapasCulturais\Entities\Opportunity $this */

            $controller = $app->view->controller;

            $referer = php_sapi_name() != "cli" ? $app->request->getReferer() : null;
            $registration = $app->view->controller->requestedEntity ?? null;
            $validating = false;

            if($registration instanceof Registration) {
                $registration_url = $app->createUrl('registration', 'registrationEdit', [$registration->id]);
                $validating = $registration_url == ($referer[0] ?? false);
            }

            if($validating || ($this->id == $app->config['rcv.opportunityId'] && $controller && $controller->action == 'registrationEdit')) {
                $files = $app->config['rcv.removeFileFields'];
                $result = array_filter($result, function($field) use ($files) {
                    return !in_array($field->fileGroupName, $files);
                });

                $result = array_values($result);
            }
        });

        // Altera nome do arquivo exportado da planilha de Inscrições para lista-gestores 
        $app->hook("SpreadsheetJob(registrations-spreadsheets).getFilename:after", function($job, &$result) use ($app, $self) {
            $opportunity = $job->owner;
            
            if($opportunity->id == $app->config['rcv.opportunityId']) {
                $extension = $job->extension;
                $date = date('Y-m-d H:i:s');
                
                $result = "lista-gestores-{$opportunity->id}--{$date}.{$extension}";
            }
        });

        $app->hook('component(opportunity-phases-timeline).item:after', function () use ($app) {
            $controller = $app->view->controller;
            $certificate = null;
            $registration = null;

            if ($controller->id === 'registration') {
                /** @var Registration */
                $registration = $controller->requestedEntity;
                /** @var Agent|null */
                $coletivo = $registration->relatedAgents['coletivo'][0] ?? null;
                if ($coletivo) {
                    foreach ($coletivo->sealRelations as $seal_relation) {
                        $seal_id = $seal_relation->seal->id;
                        if ($seal_id == $app->config['rcv.verificationSeals']['ponto'] || $seal_id == $app->config['rcv.verificationSeals']['pontao']) {
                            $certificate = $seal_relation;
                            break;
                        }
                    }
                }
            }

            if ($certificate && $registration && $registration->status == Registration::STATUS_APPROVED) {
            ?>
                <div class="rcv-certificate-link">
                    <a class="button button--primary button--icon" href="<?= $certificate->singleUrl ?>">
                        <mc-icon name="print"></mc-icon> Imprimir certificado
                    </a>
                </div>
            <?php
            }
        });

        // Altera textos da tela de edição da inscrição para a oportunidade do pnab
        $app->hook('view.partial(registration/edit):before', function () use ($app) {
            $registration = $this->controller->requestedEntity;
            $opportunity = $registration->opportunity;

            if($opportunity->id == $app->config['rcv.pnabOpportunityId']) {
                $app->config['text:registration-autosave-notification.registration_alert_message'] = 'Os dados da sua importação serão salvos automaticamente a cada {{resultTime}} segundos.';
                $app->config['text:registration.view.rcv-registration-info.registration_title'] = 'Informações';
                $app->config['text:registration.view.registration-actions.registration_actions_alert_content'] = 'Só é possível enviar após o preenchimento de todos os campos obrigatórios';
            }
        });

        $app->hook('SpreadsheetJob(registrations-spreadsheets).getHeader:after', function($job, &$result) use($app, $theme) {
            //Remoção das coluna no envio da planilha
            if (isset($result['rcv_tipo'])) {
                unset($result['rcv_tipo']);
            }

            if (isset($result['rcv_locked_fields'])) {
                unset($result['rcv_locked_fields']);
            }

            if (isset($result['ownerGeoMesoregiao'])) {
                unset($result['ownerGeoMesoregiao']);
            }

            if (isset($result['valuersIncludeList'])) {
                unset($result['valuersIncludeList']);
            }

            if (isset($result['valuersExcludeList'])) {
                unset($result['valuersExcludeList']);
            }

            if (isset($result['valuers'])) {
                unset($result['valuers']);
            }

            if (isset($result['agent'])) {
                unset($result['agent']);
            }

            if (isset($result['attachments'])) {
                unset($result['attachments']);
            }

            if (isset($result['projectName'])) {
                unset($result['projectName']);
            }

            if (isset($result['editable'])) {
                unset($result['editable']);
            }

            if (isset($result['files'])) {
                unset($result['files']);
            }

            if (isset($result['appliedPointReward'])) {
                unset($result['appliedPointReward']);
            }

            if (isset($result['appliedForQuota'])) {
                unset($result['appliedForQuota']);
            }

            if (isset($result['geoMesoregiao'])) {
                unset($result['geoMesoregiao']);
            }

            if (isset($result['editSentTimestamp'])) {
                unset($result['editSentTimestamp']);
            }

            if (isset($result['editableUntil'])) {
                unset($result['editableUntil']);
            }

            unset($result[$app->config['rcv.namePontoColletivo']]);
            unset($result[$app->config['rcv.nomePontaoDeCultura']]);

            $keys = array_keys($result);

            $result['outrosSelos'] = 'Outros Selos';
            $result['idPonto'] = "Id do ponto";
            $result['legalRep'] = "Representante legal";

            //Adiciona o id do agente responsavel
            $result['ownerID'] = 'Id responsável';

            $keys = array_keys($result);
            $pos = array_search('responsavelNome', $keys);
        
            if ($pos !== false) {
                $result = array_slice($result, 0, $pos + 1, true) +
                          ['ownerID' => 'ID do responsável'] +
                          array_slice($result, $pos + 1, null, true);
            }

            //Ajusta a ordem da exibição da coluna da planilha
            $newKey = 'horaAtualizacao';
            $newLabel = 'Hora de Atualização';

            $keys = array_keys($result);
            $pos = array_search('updateTimestamp', $keys);

            if ($pos !== false) {
                $result = array_slice($result, 0, $pos + 1, true) +
                [$newKey => $newLabel] +
                array_slice($result, $pos + 1, null, true);
            }

            $columnKey = null;
            $columnLabel = 'Se sim, insira endereço do(s) espaço(s).';

            if (isset($result[$columnKey])) {
                unset($result[$columnKey]);
            }

            $keys = array_keys($result);
            $pos = array_search($app->config['rcv.postalCode'], $keys);

            if ($pos !== false) {
                $result = array_slice($result, 0, $pos + 1, true) + 
                [$columnKey => $columnLabel] +
                array_slice($result, $pos + 1, null, true);
            }
        });

        $app->hook('SpreadsheetJob(registrations-spreadsheets).getBatch:after', function($job, &$result) use($app, $theme) {

            $verificationSeals = $app->config['rcv.verificationSeals'] ?? null;
            $legalRepKey = $app->config['rcv.legalRepresentative'] ?? null;
            $fieldAddLegalRepresentative = $app->config['rcv.addLegalRepresentative'] ?? null;
            $organizationIsBrazilKey = $app->config['rcv.organizationIsBrazil'] ?? null;
            $countryKey = $app->config['rcv.country'] ?? null;
            $addressSetKey = $app->config['rcv.addressSet'] ?? null;
            $postalCodeKey = $app->config['rcv.postalCode'] ?? null;
            $stateKey = $app->config['rcv.state'] ?? null;
            $publicPlaceKey = $app->config['rcv.publicPlace'] ?? null;
            $addressNumberKey = $app->config['rcv.addressNumber'] ?? null;
            $cityOfAddressKey = $app->config['rcv.cityOfAddress'] ?? null;
            $nameCulturePointKey = $app->config['rcv.nameCulturePoint'] ?? null;
            $namePontoColletivoKey = $app->config['rcv.namePontoColletivo'] ?? null;
            $nomePontaoDeCulturaKey = $app->config['rcv.nomePontaoDeCultura'] ?? null;

            $ownerUrlByRegistrationId = [];
            $collectiveUrlByRegistrationId = [];
            $collectiveIdByRegistrationId = [];
            $otherSealsByCollectiveId = [];
            $otherSealsByRegistrationId = [];

            $registrationIds = array_values(array_unique(array_filter(array_map(function($entity) {
                return $entity['id'] ?? null;
            }, $result))));

            if ($registrationIds) {
                $conn = $app->em->getConnection();

                $registrations = $conn->executeQuery("
                    SELECT id, agent_id, agents_data
                    FROM registration
                    WHERE id IN (:registration_ids)
                ", [
                    'registration_ids' => $registrationIds,
                ], [
                    'registration_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER,
                ])->fetchAllAssociative();

                foreach ($registrations as $registration) {
                    $registrationId = $registration['id'] ?? null;
                    if (!$registrationId) {
                        continue;
                    }

                    if ($ownerId = $registration['agent_id'] ?? null) {
                        $ownerUrlByRegistrationId[$registrationId] = $app->createUrl('agent', 'single', [$ownerId]);
                    }

                    $agentsData = $registration['agents_data'] ?? [];
                    if (is_string($agentsData)) {
                        $agentsData = json_decode($agentsData, true) ?: [];
                    }
                    if (!is_array($agentsData)) {
                        $agentsData = [];
                    }

                    if ($collective = $agentsData['coletivo'] ?? null) {
                        if ($collectiveId = $collective['id'] ?? null) {
                            $collectiveIdByRegistrationId[$registrationId] = $collectiveId;
                            $collectiveUrlByRegistrationId[$registrationId] = $app->createUrl('agent', 'single', [$collectiveId]);
                        }
                    }
                }

                $collectiveIds = array_values(array_unique(array_filter(array_values($collectiveIdByRegistrationId))));

                if ($collectiveIds) {
                    $sealRelations = $conn->executeQuery("
                        SELECT object_id, seal_id
                        FROM seal_relation
                        WHERE object_type = :object_type
                          AND object_id IN (:collective_ids)
                    ", [
                        'object_type' => Agent::class,
                        'collective_ids' => $collectiveIds,
                    ], [
                        'collective_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER,
                    ])->fetchAllAssociative();

                    $sealIds = [];
                    foreach ($sealRelations as $relation) {
                        $sealId = $relation['seal_id'] ?? null;

                        if ($sealId !== null && $sealId != ($verificationSeals['ponto'] ?? null) && $sealId != ($verificationSeals['pontao'] ?? null)) {
                            $sealIds[] = $sealId;
                        }
                    }

                    $sealIds = array_values(array_unique(array_filter($sealIds)));
                    $sealNamesById = [];

                    if ($sealIds) {
                        $seals = $conn->executeQuery("
                            SELECT id, name
                            FROM seal
                            WHERE id IN (:seal_ids)
                        ", [
                            'seal_ids' => $sealIds,
                        ], [
                            'seal_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER,
                        ])->fetchAllAssociative();

                        foreach ($seals as $seal) {
                            if (isset($seal['id'])) {
                                $sealNamesById[$seal['id']] = $seal['name'] ?? '';
                            }
                        }
                    }

                    foreach ($sealRelations as $relation) {
                        $collectiveId = $relation['object_id'] ?? null;
                        $sealId = $relation['seal_id'] ?? null;

                        if ($collectiveId !== null && $sealId !== null && $sealId != ($verificationSeals['ponto'] ?? null) && $sealId != ($verificationSeals['pontao'] ?? null)) {
                            $otherSealsByCollectiveId[$collectiveId][] = $sealNamesById[$sealId] ?? '';
                        }
                    }

                    foreach ($collectiveIdByRegistrationId as $registrationId => $collectiveId) {
                        if (isset($otherSealsByCollectiveId[$collectiveId])) {
                            $otherSealsByRegistrationId[$registrationId] = implode(', ', $otherSealsByCollectiveId[$collectiveId]);
                        }
                    }
                }
            }

            foreach($result as &$entity){

                //Remoção das coluna no envio da planilha
                if (isset($entity['rcv_tipo'])) {
                    unset($entity['rcv_tipo']);
                }

                if (isset($entity['rcv_locked_fields'])) {
                    unset($entity['rcv_locked_fields']);
                }

                if (isset($entity['valuersIncludeList'])) {
                    unset($entity['valuersIncludeList']);
                }

                if (isset($entity['valuersExcludeList'])) {
                    unset($entity['valuersExcludeList']);
                }

                if (isset($entity['valuers'])) {
                    unset($entity['valuers']);
                }

                if (isset($entity['editable'])) {
                    unset($entity['editable']);
                }

                if (isset($entity['agent'])) {
                    unset($entity['agent']);
                }

                if (isset($entity['projectName'])) {
                    unset($entity['projectName']);
                }

                if (isset($entity['attachments'])) {
                    unset($entity['attachments']);
                }

                if (isset($entity['ownerGeoMesoregiao'])) {
                    unset($entity['ownerGeoMesoregiao']);
                }

                if (isset($entity['files'])) {
                    unset($entity['files']);
                }

                if (isset($entity['appliedPointReward'])) {
                    unset($entity['appliedPointReward']);
                }

                if (isset($entity['appliedForQuota'])) {
                    unset($entity['appliedForQuota']);
                }

                if (isset($entity['geoMesoregiao'])) {
                    unset($entity['geoMesoregiao']);
                }

                if (isset($entity['editSentTimestamp'])) {
                    unset($entity['editSentTimestamp']);
                }

                if (isset($entity['editableUntil'])) {
                    unset($entity['editableUntil']);
                }

                if ($legalRepKey && !empty($entity[$legalRepKey])) {
                    if(isset($entity[$legalRepKey]) && $entity[$legalRepKey]){
                        $entity['legalRep'] = $fieldAddLegalRepresentative ? ($entity[$fieldAddLegalRepresentative] ?? null) : null;
                    }
                }

                //Adiciona outros selos na exportação da planilha
                $registrationId = $entity['id'] ?? null;
                if ($registrationId !== null) {
                    if (isset($ownerUrlByRegistrationId[$registrationId])) {
                        $entity['ownerID'] = $ownerUrlByRegistrationId[$registrationId];
                    }

                    if (isset($collectiveUrlByRegistrationId[$registrationId])) {
                        $entity['idPonto'] = $collectiveUrlByRegistrationId[$registrationId];
                    }

                    if (isset($otherSealsByRegistrationId[$registrationId])) {
                        $entity['outrosSelos'] = $otherSealsByRegistrationId[$registrationId];
                    }
                }

                // Adiciona os endereços na planilha
                if ($organizationIsBrazilKey && (($entity[$organizationIsBrazilKey] ?? '') === 'Sim')) {
                    if ($countryKey) {
                        $entity[$countryKey] = 'Brasil';
                    }
                
                    $addressSet = $addressSetKey ? ($entity[$addressSetKey] ?? null) : null;
                
                    if (is_array($addressSet)) {
                        if ($postalCodeKey) {
                            $entity[$postalCodeKey] = $addressSet['En_CEP'] ?? null;
                        }
                        if ($stateKey) {
                            $entity[$stateKey] = $addressSet['En_Estado'] ?? null;
                        }
                        if ($publicPlaceKey) {
                            $entity[$publicPlaceKey] = $addressSet['En_Nome_Logradouro'] ?? null;
                        }
                        if ($addressNumberKey) {
                            $entity[$addressNumberKey] = $addressSet['En_Num'] ?? null;
                        }
                        if ($cityOfAddressKey) {
                            $entity[$cityOfAddressKey] = $addressSet['En_Municipio'] ?? null;
                        }
                    }
                }

                //Adiciona Hora e data convertidas para a planilha
                if (isset($entity['updateTimestamp']['date'])) {
                    $updateDate = $entity['updateTimestamp']['date']; 
                    $dateTime = new DateTime($updateDate);
    
                    $dateOnly = $dateTime->format('d-m-Y');
                    $timeOnly = $dateTime->format('H:i');
                    
                    $entity['updateTimestamp'] = $dateOnly; 
                    $entity['horaAtualizacao'] = $timeOnly;
                }

                $ponto_cultura = $nameCulturePointKey ? ($entity[$nameCulturePointKey] ?? null) : null;
                $ponto_coletivo = $namePontoColletivoKey ? ($entity[$namePontoColletivoKey] ?? null) : null;
                $pontao_de_cultura = $nomePontaoDeCulturaKey ? ($entity[$nomePontaoDeCulturaKey] ?? null) : null;
                $valorFinal = $ponto_cultura ?: $ponto_coletivo ?: $pontao_de_cultura;
        
                if ($nameCulturePointKey) {
                    $entity[$nameCulturePointKey] = $valorFinal;
                }
        
                // Remove os outros campos
                if ($namePontoColletivoKey) {
                    unset($entity[$namePontoColletivoKey]);
                }
                if ($nomePontaoDeCulturaKey) {
                    unset($entity[$nomePontaoDeCulturaKey]);
                }
            }
        });

        // Remove listagem de oportunidades do breadcrumb na tela de edição da inscrição e da single da oportunidade
        $app->hook('mapas.printJsObject:before', function() use($app) {
            $entity = $this->controller->requestedEntity;

            if(($this->controller->id == 'registration' && $this->controller->action == 'view') || ($this->controller->id == 'opportunity' && $this->controller->action == 'single')) {
                $opportunity = $entity->opportunity ?? $entity;

                $opportunities_ids = [
                    $app->config['rcv.opportunityId'],
                    $app->config['rcv.pnabOpportunityId']
                ];

                if(in_array($opportunity->id, $opportunities_ids)) {
                    $this->breadcrumb = array_values(array_filter($this->breadcrumb, function ($item) {
                        return $item['label'] !== "Oportunidades";
                    }));
                }
            }
        });

        $app->hook('ApiQuery(Registration).appendRelatedAgents', function(&$agents_query_select) use($app) {
            $agents_query_select = "id,type,name,shortDescription,files.avatar,terms,singleUrl,nomeCompleto,cnpj";
        });

        // Altera texto do card da lista de inscrições para a oportunidade do pnab
        $app->hook('view.partial(opportunity/single-pnab):before', function () use ($app) {
            $opportunity = $this->controller->requestedEntity;

            if($opportunity->id == $app->config['rcv.pnabOpportunityId']) {
                $app->config['text:opportunity.single.opportunity-subscription-list.subscription-list-title'] = 'Suas solicitações de importação de dados';
            }
        });

        // Altera texto do card da lista de inscrições para a oportunidade de cadastro
        $app->hook('view.partial(opportunity/single-cadastro):before', function () use ($app) {
            $opportunity = $this->controller->requestedEntity;

            if($opportunity->id == $app->config['rcv.opportunityId']) {
                $app->config['text:opportunity.single.opportunity-subscription-list.subscription-list-title'] = 'Você tem cadastros iniciados';
            }
        });
        
        // Remove o selo de "Aguardando atualização" do agente coletivo quando a inscrição for enviada durante a atualização cadastral
        $app->hook("entity(Registration).sendEditableFields:after", function () use ($app) {
            /** @var Registration $this */
            $opportunity = $this->opportunity;
            $organization = $this->relatedAgents['coletivo'][0] ?? null;

            if($opportunity->id == $app->config['rcv.opportunityId'] && $organization) {
                $has_waiting_update_seal = false;
                $seal_relations = $organization->getSealRelations();

                $waiting_update_seal = $app->repo('Seal')->find($app->config['rcv.waitingUpdateSeal']);

                foreach($seal_relations as $seal_relation) {
                    if($seal_relation->seal->id == $waiting_update_seal->id) {
                        $has_waiting_update_seal = true;
                        break;
                    }
                }

                if($has_waiting_update_seal) {
                    $app->disableAccessControl();
                    $organization->removeSealRelation($waiting_update_seal);
                    $app->enableAccessControl();
                }

                $app->disableAccessControl();
                $organization->rcv_last_update_timestamp = date('Y-m-d H:i:s');
                $organization->save(true);
                $app->enableAccessControl();
            }
        });

        $app->hook('auth.sendEmailValidation', function(&$template_name) {
            if ($template_name == 'email-to-validate-account.html') {
                $template_name = 'rcv-email-to-validate-account.html';
            }
        });

        // Altera mensagem de erro 500 na tela de inscrição para a oportunidade do pnab
        $app->hook('component(mc-entity).texts', function(&$texts) use($app) {
            $controller = $this->controller;

            if($controller->action == 'view' && $controller->id == 'registration') {
                $registration = $controller->requestedEntity;

                if($registration->opportunity->id == $app->config['rcv.pnabOpportunityId']) {
                    $texts['erro inesperado'] = 'Erro genérico ao processar a planilha. Por favor, contactar o suporte';
                }
            }
        });


        $app->hook('<<GET|POST>>(opportunity.<<*>>)', function() use ($app) {
             $opportunity = $this->requestedEntity;
             if($opportunity->id == $app->config['rcv.pnabOpportunityId'] && $app->config['rcv.disablePnabOpportunity']) {
                $url = $app->createUrl('site', 'index', []);
                $app->redirect($url);
             }
        });

        $app->hook('GET(site.importer-log-view)', function() use($app) {
            $this->requireAuthentication();
            
            
            $file = PUBLIC_PATH . 'files/importer/' . $this->data['id'] . '.log';
            if(file_exists($file)) {
                $file_content = explode("\n", file_get_contents($file));
                dump(array_filter($file_content));
            }
        });

        $app->hook('POST(site.importer-log-create)', function() use($app) {
            $this->requireAuthentication();
            
            $id = $this->data['id'];
            $dir_path = PUBLIC_PATH . 'files/importer/';
            $file = $dir_path . $id . '_status.json';

            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0777, true);
            }

            if (!file_exists($file)) {
                $data = [
                    'status' => 0,
                    'message' => '',
                    'timestamp' => date('d-m-Y H:i:s')
                ];

                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $this->json(true);
        });

        Importer::init($self);
    }

    function register() {
        $app = App::i();

        parent::register();

        $this->registerRegistrationMetadata('rcv_tipo', [
            'label' => 'Tipo do agente',
            'type' => 'string',
        ]);
        
        $this->registerRegistrationMetadata('rcv_locked_fields', [
            'label' => 'Campos bloqueados',
            'type' => 'array',
            'readonly' => true
        ]);
    }

    function getCNPJFake($cnpj) {
        $app = App::i();

        $cnpjData = $app->config['rcv.CNPJsFakes'];

        return $cnpjData[$cnpj] ?? false;
    }

    public function getCNPJ($cnpj, $cpf = null, $source = 'modal') {
        $token = $this->getBscToken();

        if(!$token['success']) {
            $this->generateLog($cnpj, $token['error'], $source);
            return;
        }

        $data = json_encode(
            array(
                "cacheEvict" => false,
                "cnpj" => "$cnpj",
                "cpfConsulta" => "$cpf",
                "ipOrigem" => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : "127.0.0.1",
                "ipUsuario" => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1",
                "usuario" => "admin.user"
            )
        );

        $url = env('RCV_BSC_VALIDATE_CNPJ', '');

        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token['accessToken']}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        $log_response = [
            'error' => $error,
            'result' => $result
        ];
        
        $this->generateLog($cnpj, $log_response, $source);
        
        $response = json_decode($result, true);
        return $response['responseBody'] ?? null;
    }

    function getBscToken() {
        $request = curl_init();
        $client_id = env('RCV_BSC_CLIENT_ID', '');
        $client_secret = env('RCV_BSC_CLIENT_SECRET', '');
        $url = env('RCV_BSC_AUTH_TOKEN', '');

        curl_setopt_array($request, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
              "Content-type: application/json",
              "clientId: {$client_id}",
              "clientSecret: {$client_secret}",
            ],
        ]);
        
        $result = curl_exec($request);
        $response = json_decode($result, true);
        $error = curl_error($request);
        curl_close($request);

        if($error) {
            return ['success' => false, 'error' => $error];
        }
        
        return ['success' => true, 'accessToken' => $response['accessToken']];
    }

    function canUserControlRCV() {
        return $this->opportunity->canUser('@control');
    }

    function createSpaceRelation(Space $space, Registration $registration): RegistrationSpaceRelation {
        $relation = new RegistrationSpaceRelation;
        $relation->space = $space;
        $relation->owner = $registration;
        $relation->save(true);


        $collective_agent = $registration->relatedAgents['coletivo'][0];
        $collective_agent->rcv_sede_spaceId = $space->id;
        $collective_agent->save(true);

        return $relation;
    }

    function sendMailNotification($app, Agent $agent, Agent $organization, $message) {
        $filename = $app->view->resolveFilename("views/emails", "disable_cultural_hub.html");
        $template = file_get_contents($filename);

        $params = [
            "siteName" => $app->siteName,
            "baseUrl" => $app->getBaseUrl(),
            'messageBody' => $message,
            'userName' => $agent->name,
            'organization' => $organization->name ?: $organization->nomeCompleto,
        ];
        
        $mustache = new \Mustache_Engine();
        $content = $mustache->render($template, $params);

        $app->createAndSendMailMessage([
            'from' => $agent->emailPrivado,
            'to' => $app->config['rcv.email'],
            'subject' => "[{$app->siteName}] Desativar ponto/pontão de cultura",
            'body' => $content,
        ]);
    }

    function sendMailQuestion($app, $name, $email, $message) {
        $filename = $app->view->resolveFilename("views/emails", "send_question.html");
        $template = file_get_contents($filename);

        $params = [
            "siteName" => $app->siteName,
            "baseUrl" => $app->getBaseUrl(),
            'messageBody' => $message,
            'userName' => $name,
            'userEmail' => $email
        ];
        
        $mustache = new \Mustache_Engine();
        $content = $mustache->render($template, $params);

        $app->createAndSendMailMessage([
            'from' => $app->config['mailer.from'],
            'to' => $app->config['rcv.email'],
            'replyTo' => $email,
            'subject' => "[{$app->siteName}] Dúvida sobre atualização cadastral",
            'body' => $content,
        ]);
    }

    public function sendMailRequester($to, $msg, $subject = null) {
        $app = App::i();
        $dataValue = [
            'message'    => $msg
        ];
        $message = $app->renderMailerTemplate('request_relation', $dataValue);
        $subject = $subject == null ? $message['title'] : $subject;
        $mail = [
            'from'    => $app->config['mailer.from'],
            'to'      => $to,
            'subject' => $subject,
            'body'    => $message['body']
        ];
        $app->sendMailMessage($app->createMailMessage($mail));
    }

    public function generateLog($cnpj, $response, $source) {
        $app = App::i();
        
        $user = $app->user;
        $timestamp = date('Y-m-d H:i:s');

        if(json_validate($response['error'] ?? 'invalid')) {
            $response['error'] = json_decode($response['error'], true);
        }

        if(json_validate($response['result'] ?? 'invalid')) {
            $response['result'] = json_decode($response['result'], true);
        }

        $api_response = is_string($response) ? $response : json_encode($response);

        $log = "---------------------------\n";
        $log .= "Usuário: {$user}\n";
        $log .= "CNPJ: {$cnpj}\n";
        $log .= "Origem: {$source}\n";
        $log .= "Horário: {$timestamp}\n";
        $log .= "Resposta da API:\n{$api_response}\n";
        $log .= "---------------------------\n\n";

        $log_path = PRIVATE_FILES_PATH . 'cnpj_validation_log.txt';
        file_put_contents($log_path, $log, FILE_APPEND);
    }

    public function sendMailRegistrationStatus(Registration $registration) {
        $app = App::i();

        $template = $registration->status == Registration::STATUS_APPROVED ? 'registration_approved' : 'registration_denied';

        $params = [
            "siteName" => $app->siteName,
            "baseUrl" => $app->getBaseUrl(),
            "userName" => $registration->owner->name,
            "registrationNumber" => $registration->number,
            "organizationName" => $registration->relatedAgents['coletivo'][0]->name ?? '',
            "organizationCNPJ" => $registration->relatedAgents['coletivo'][0]->cnpj ?? null,
            "registrationType" => $registration->category,
        ];

        if($registration->status == Registration::STATUS_APPROVED) {
            $config_seal_id = $registration->category == $app->config['rcv.categoriesMap']['pontao'] ? $app->config['rcv.verificationSeals']['pontao'] : $app->config['rcv.verificationSeals']['ponto'];
    
            $seal_relation_url = '';
            if($collective = $registration->relatedAgents['coletivo'][0] ?? null) {
                foreach ($collective->sealRelations as $seal_relation) {
                    if ($seal_relation->seal->id == $config_seal_id) {
                        $seal_relation_url = $seal_relation->singleUrl;
                        break;
                    }
                }
            }
    
            $params['sealRelation'] = $seal_relation_url;
        } else {
            $params['registrationLink'] = $registration->singleUrl;
            $params['newRegistrationLink'] = $app->createUrl('opportunity', 'single', [$registration->opportunity->id]);
        }
        
        $user_email = $registration->owner->emailPrivado ?: (
            $registration->owner->emailPublico ?: (
                $registration->owner->email ?: ''
            )
        );

        $content = $app->renderMailerTemplate($template, $params);

        $mail = [
            'from' => $app->config['mailer.from'],
            'to' => $user_email,
            'subject' => $content['title'],
            'body' => $content['body'],
        ];

        $app->sendMailMessage($app->createMailMessage($mail), true);
    }

    function sendTransactionalEmail($template, $data = []){

        $app = App::i();

        $dataValue = [
            'responsavel_nome'    => $data['owner_name'],
            'agent_name'    => $data['agent_name'],
            'agent_cnpj'    => $data['agent_cnpj'],
        ];
        
        $message = $app->renderMailerTemplate($template, $dataValue);

        $mail = [
            'from'    => $app->config['mailer.from'],
            'to'      => $data['email_destinatario'],
            'subject' => $message['title'],
            'body'    => $message['body']
        ];

        
        $app->sendMailMessage($app->createMailMessage($mail));

    }

    /**
     * Chave ponto-entidade|pontao conforme a inscrição RCV ligada ao agente coletivo (ex.: fluxo alterar CNPJ com excludeAgentId).
     */
    public function getRcvSubscriptionTypeKeyByColetivoAgentId(App $app, int $coletivoAgentId): ?string
    {
        if ($coletivoAgentId <= 0) {
            return null;
        }

        $opportunityId = (int) $app->config['rcv.opportunityId'];
        $categoriesMap = $app->config['rcv.categoriesMap'] ?? [];
        $conn = $app->em->getConnection();
        $registrationObjectType = Registration::class;

        $sql = "
            SELECT r.category
            FROM registration r
            INNER JOIN agent_relation ar ON ar.object_id = r.id
                AND ar.object_type = '{$registrationObjectType}'
                AND ar.type = 'coletivo'
            WHERE ar.agent_id = :agentId
              AND r.opportunity_id = :oppId
              AND r.status != :trash
            LIMIT 1
        ";

        $category = $conn->fetchOne($sql, [
            'agentId' => $coletivoAgentId,
            'oppId' => $opportunityId,
            'trash' => Registration::STATUS_TRASH,
        ]);

        if (!is_string($category) || $category === '') {
            return null;
        }

        if ($category === ($categoriesMap['ponto-entidade'] ?? '')) {
            return 'ponto-entidade';
        }
        if ($category === ($categoriesMap['pontao'] ?? '')) {
            return 'pontao';
        }

        return null;
    }

    /**
     * Inscrições na oportunidade Cultura Viva com agente coletivo contendo o CNPJ e a categoria informados (Ponto entidade ou Pontão).
     */
    public function countRcvRegistrationsWithColetivoCnpjAndCategory(
        App $app,
        string $cnpjDigits,
        string $categoryLabel,
        ?int $excludeColetivoAgentId
    ): int {
        $opportunityId = (int) $app->config['rcv.opportunityId'];
        $conn = $app->em->getConnection();
        $registrationObjectType = Registration::class;

        $params = [
            'digits' => $cnpjDigits,
            'category' => $categoryLabel,
            'oppId' => $opportunityId,
            'trash' => Registration::STATUS_TRASH,
            'notApproved' => Registration::STATUS_NOTAPPROVED,
        ];
        $excludeSql = '';
        if ($excludeColetivoAgentId !== null) {
            $excludeSql = ' AND ar.agent_id <> :excludeAgentId';
            $params['excludeAgentId'] = $excludeColetivoAgentId;
        }

        $sql = "
            SELECT COUNT(DISTINCT r.id)
            FROM registration r
            INNER JOIN agent_relation ar ON ar.object_id = r.id
                AND ar.object_type = '{$registrationObjectType}'
                AND ar.type = 'coletivo'
            INNER JOIN agent_meta am ON am.object_id = ar.agent_id
                AND am.key IN ('cnpj', 'documento')
                AND regexp_replace(COALESCE(am.value, ''), '[^0-9]', '', 'g') = :digits
            WHERE r.opportunity_id = :oppId
              AND r.category = :category
              AND r.status != :trash
              AND r.status != :notApproved
            {$excludeSql}
        ";

        $count = $conn->fetchFirstColumn($sql, $params);

        return (int) ($count[0] ?? 0);
    }

    public function sendMailRegistrationPnabDenied(Registration $registration) {
        $app = App::i();

        $template = 'registration_pnab_denied';

        $params = [
            "siteName" => $app->siteName,
            "baseUrl" => $app->getBaseUrl(),
            "userName" => $registration->owner->name,
            "registrationNumber" => $registration->number,
            "registrationLink" => $registration->singleUrl,
            "opportunityPnabLink" => $app->createUrl('opportunity', 'single', [$registration->opportunity->id])
        ];
        
        $user_email = $registration->owner->emailPrivado ?: $registration->owner->emailPublico ?: $registration->owner->email ?: '';

        $content = $app->renderMailerTemplate($template, $params);

        $mail = [
            'from' => $app->config['mailer.from'],
            'to' => $user_email,
            'subject' => $content['title'],
            'body' => $content['body'],
        ];

        $app->sendMailMessage($app->createMailMessage($mail));
    }
}
