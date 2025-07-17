<?php
namespace CulturaViva;

require __DIR__ . "/ImportRegistrationsJob.php";

use CulturaViva\JobTypes\ImportRegistrationsJob;
use MapasCulturais\API;
use MapasCulturais\ApiQuery;
use MapasCulturais\App;
use MapasCulturais\Entities\Agent;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Entities\RegistrationFile;
use MapasCulturais\Entities\RegistrationFileConfiguration;
use MapasCulturais\Entities\User;
use MapasCulturais\Utils;
use Monolog\Handler\Curl\Util;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Respect\Validation\Validator as v;

/**
 * São 4 cenários mapeados:
 * 
 * Cenário 1 - O sistema consegue encontrar no **cadastro** (a inscrição do Ponto certificado). (O usuário preencheu 
 *             todo o formulário, marcou que estava concorrendo a um edital municipal ou estadual e enviou ou 
 *             não finalizou o cadastro e envio). Neste caso, a inscrição é marcada como Selecionada, o que por sua vez 
 *             dispara o fluxo de certificação. Ou seja, o ponto ganha o selo. E o usuário recebe uma notificação por email, 
 *             informando a finalização do processo de certificação e convidando para atualizar informações. 
 *
 * Cenário 2 - O sistema não encontrou no **cadastro** a inscrição do Ponto. Neste caso, o sistema procura pelo CNPJ para 
 *             verificar que já é um ponto, no caso de Ponto Entidade ou Pontão. No caso de Ponto Coletivo, o sistema 
 *             procura por CPF e nome da organização para encontrar compatibilidades. Encontrando CNPJ, CPF e Nome 
 *             o sistema cria uma inscrição no cadastro, com dados incompletos, e seleciona a inscrição. Na sequência, 
 *             envia um email notificando para a atualização de dados na plataforma.  
 *
 * Cenário 3 - O sistema não encontrou no **cadastro** a inscrição do Ponto, nem CNPJ, nem CPF, nem Nome da organização. 
 *             Neste caso, o sistema cria um usuário, um agente coletivo e uma inscrição selecionada e notifica 
 *             por email para atualização dos dados. 
 *
 * Cenário 4 - O sistema encontra o CNPJ no **cadastro** da Inscrição do Ponto ou na lista de nome de organizações 
 *             já certificadas, mas está com CPF de outra pessoa. O sistema envia uma notificação para o CPF que entrou 
 *             pela importação, informando que o Ponto estão cadastrado em outro CPF, para regularização via atualização 
 *             cadastral. O ponto/pontão só será selecionado com aplicação de selo após a regularização. 
 * 
 */
class Importer {
    static $types = [
        'pontao' => [
            'Pontão',
            'Pontão com CNPJ',
            'Pontão entidade',
            'Pontão entidade com CNPJ',
            'Pontão de Cultura (com CNPJ)',
            'Pontão de Cultura (entidade com CNPJ)',
            'Pontão de Cultura (entidade)',
        ],

        'ponto-entidade' => [
            'Ponto com CNPJ',
            'Ponto entidade',
            'Ponto entidade com CNPJ',
            'Ponto de Cultura (com CNPJ)',
            'Ponto de Cultura (entidade com CNPJ)',
            'Ponto de Cultura (entidade)',
        ],

        'ponto-coletivo' => [
            'Ponto sem CNPJ',
            'Ponto Coletivo',
            'Ponto Coletivo sem CNPJ',
            'Ponto de Cultura (sem CNPJ)',
            'Ponto de Cultura (coletivo)',
            'Ponto de Cultura (coletivo sem CNPJ)',
        ],
    ];

    static $column_mapping = [
        'ponto_data'           => 'Data da Certificação',
        'ponto_tipo'           => 'Tipo de Certificação',
        'ponto_uf'             => 'Estado',
        'ponto_municipio'      => 'Município',
        'organizacao_nome'     => 'Nome da organização',
        'organizacao_cnpj'     => 'CNPJ',
        'organizacao_email'    => 'Email da organização',
        'organizacao_telefone' => 'Telefone da organização',
        'responsavel_nome'     => 'Nome do responsável',
        'responsavel_cpf'      => 'CPF',
        'responsavel_email'    => 'Email do responsável',
        'responsavel_telefone' => 'Telefone do responsável'
    ];

    private static $theme = null;

    public static function init($theme) {
        $app = App::i();

        self::$theme = $theme;

        $app->registerJobType(new ImportRegistrationsJob(ImportRegistrationsJob::SLUG));

        // Ao aprovar a inscrição agenda o job de importação das inscrições a partir da planilha enviada na inscrição
        $app->hook("entity(Registration).status(approved)", function() use ($app) {
            /** @var \MapasCulturais\Entities\Registration $this */

            if ($this->opportunity->id !== (int) $app->config['rcv.pnabOpportunityId']) {
                return;
            }

            if ($file = self::getRegistrationFile($this)) {
                $params = [
                    'registration' => $this
                ];

                $app->enqueueJob("importRegistrations", $params);
                $app->log->info("Job criado para importar a planilha da inscrição {$this->id}");
            }
        });
    }

    /** 
     * Retorna a planilha de importação de pontos da inscrição
     * @param Registration $registration
     * @return Worksheet
     */
    static function getSheet(RegistrationFile $registration_file): Worksheet {
        $spreadsheet = IOFactory::load($registration_file->path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet;
    }

    /** 
     * Retorna o arquivo da planilha de importação de pontos da inscrição
     * @param Registration $registration
     * @return RegistrationFile
     */
    static function getRegistrationFile(Registration $registration): ?RegistrationFile {
        $app = App::i();

        $field_name = 'rfc_'.$app->config['rcv.pnabOpportunityAttachmentId'];

        $file = $app->repo('RegistrationFile')->findOneBy([
            'group' => $field_name,
            'owner' => $registration
        ]);

        return $file;
    }

    /** 
     * Parseia a linha da planilha de importação de pontos
     * @param array $header
     * @param array $row
     * @return object
     */
    static function parseRow(array $header, array $row): object {
        $app = App::i();

        $column_mapping = (object) self::$column_mapping;

        // mapeia os campos da planilha
        $new_column_mapping = (object) [];
        foreach ($header as $column => $value) {

            if (empty($value)) {
                continue;
            }

            $value = $app->slugify($value);

            foreach($column_mapping as $key => $val) {
                if(str_starts_with($value, $app->slugify($val))) {
                    $new_column_mapping->$key = $column;
                }
            }
        }

        $column_mapping = $new_column_mapping;

        // parseia os dados da linha
        $data = (object) [];
        foreach($column_mapping as $key => $column) {
            if ($key == 'ponto_tipo') {
                if (!$row[$column_mapping->ponto_tipo]) {
                    $data->$key = '';
                } else {
                    $data->$key = self::parseCategory($row[$column_mapping->ponto_tipo]);
                }
                continue;
            } else {
                $data->$key = $row[$column] ?? null;
            }
        }

        return $data;
    }

    /**
     * Encontra a categoria do ponto 
     * @param string $type
     * @return string
     */
    static function parseCategory(string $type): string {
        $app = App::i();
        $type = $app->slugify($type);

        foreach (self::$types as $category => $types) {
            foreach ($types as $t) {
                if ($app->slugify($t) == $type) {
                    return $app->config['rcv.categoriesMap'][$category];
                }
            }
        }

        return '';
    }

    /** 
     * Encontra a inscrição do ponto na plataforma
     * @param object $row
     * @return Registration|null
     */
    static function findRegistrationByRow(object $row): ?Registration {
        $app = App::i();
        $opportunity_id = $app->config['rcv.opportunityId'];

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            // para ponto coletivo, busca por responsavel_cpf e itera sobre os resultados buscando pelo nome do ponto
            $query = new ApiQuery(Registration::class, [
                'opportunity' => API::EQ($opportunity_id), 
                'category' => API::EQ($row->ponto_tipo), 
                'status' => API::GTE(0),
                '@keyword' => "$row->responsavel_cpf"
            ]);

            $ids = $query->findIds();

            /** @var Registration[] */
            $registrations = $app->repo('Registration')->findBy(['id' => $ids]);

            foreach($registrations as $registration) {
                $coletivo = $registration->getRelatedAgents('coletivo');
                $name = $coletivo[0]->name ?? null;

                if (!empty($coletivo) && !empty($name) && !empty($row->organizacao_nome)) {
                    if ($app->slugify($name) == $app->slugify($row->organizacao_nome)) {
                        return $registration;
                    }
                }
            }
        } else {
            // para ponto entidade e pontão, busca por cnpj e itera sobre os resultados verificando o cpf do responsável
            $query = new ApiQuery(Registration::class, [
                'opportunity' => API::EQ($opportunity_id), 
                'category' => API::EQ($row->ponto_tipo), 
                'status' => API::GTE(0),
                '@keyword' => "$row->organizacao_cnpj"
            ]);

            $ids = $query->findIds();

            /** @var Registration[] */
            $registrations = $app->repo('Registration')->findBy(['id' => $ids]);

            foreach($registrations as $registration) {
                $coletivo = $registration->getRelatedAgents('coletivo');

                if ($app->slugify($coletivo[0]->name) == $app->slugify($row->organizacao_nome) && Utils::formatCnpjCpf($registration->owner->cpf) == Utils::formatCnpjCpf($row->responsavel_cpf)) {
                    return $registration;
                }
            }
        }

        return null;
    }

    /** 
     * Encontra a organização do ponto na plataforma
     * @param object $row
     * @return Agent|null
     */
    static function findOrganizationByRow(object $row): ?Agent {
        $app = App::i();

        $cpf_query = new ApiQuery(Agent::class, [
            'cpf' => API::EQ($row->responsavel_cpf),
            'type' => API::EQ(1)
        ]);

        $owner_ids = $cpf_query->findIds();

        if($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $query = new ApiQuery(Agent::class, [
                'name' => API::ILIKE($row->organizacao_nome),
                'parent' => API::IN($owner_ids),
                'type' => API::EQ(2)
            ]);
        } else {
            $query = new ApiQuery(Agent::class, [
                'cnpj' => API::EQ($row->organizacao_cnpj),
                'parent' => API::IN($owner_ids),
                'type' => API::EQ(2)
            ]);
        }

        $ids = $query->findIds();

        /** @var Agent */
        $agent = $app->repo('Agent')->findOneBy(['id' => $ids], ['updateTimestamp' => 'DESC']);

        return $agent;
    }

    /**
     * Encontra uma organização a partir de outro agente.
     *
     * @param object $row
     * @return Agent|null
     */
    static function findOrganizationFromOtherAgent(object $row): ?Agent {
        $app = App::i();

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $query = new ApiQuery(Agent::class, [
                'name' => API::ILIKE($row->organizacao_nome),
                'type' => API::EQ(2)
            ]);
        } else {
            $query = new ApiQuery(Agent::class, [
                'cnpj' => API::EQ($row->organizacao_cnpj),
                'type' => API::EQ(2)
            ]);
        }

        $ids = $query->findIds();

        /** @var Agent */
        $organization = $app->repo('Agent')->findOneBy(['id' => $ids], ['updateTimestamp' => 'DESC']);

        if ( !$organization ) {
            return null;
        }

        if (isset($organization->owner) && $organization->owner->type->id != 1) {
            return null;
        }

        if (!v::cpf()->validate($organization->owner->cpf)) {
            return null;
        }

        if (Utils::formatCnpjCpf($organization->owner->cpf) != Utils::formatCnpjCpf($row->responsavel_cpf)) {
            return $organization;
        }

        return null;
    }

    /** 
     * Encontra o responsável do ponto na plataforma
     * @param object $row
     * @return Agent|null
     */
    static function findOrganizationOwnerByRow(object $row): ?Agent {
        $app = App::i();

        $query = new ApiQuery(Agent::class, [
            'cpf' => API::EQ($row->responsavel_cpf),
            'type' => API::EQ(1)
        ]);

        $ids = $query->findIds();

        /** @var Agent */
        $agent = $app->repo('Agent')->findOneBy(['id' => $ids], ['updateTimestamp' => 'DESC']);

        return $agent;
    }

    /** 
     * Cria a inscrição do ponto na plataforma
     * @param object $row
     * @param Agent $organization
     * @return Registration
     */
    static function createRegistration(object $row, Agent $organization): Registration {
        $app = App::i();

        $opportunity_id = App::i()->config['rcv.opportunityId'];
        $opportunity = App::i()->repo('Opportunity')->find($opportunity_id);

        $registration = new Registration();
        $registration->opportunity = $opportunity;
        $registration->owner = $organization->parent;
        $registration->category = $row->ponto_tipo;
        $registration->status = Registration::STATUS_DRAFT;

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $registration->proponentType = 'Coletivo';
        } else {
            $registration->proponentType = 'Pessoa Jurídica';
        }

        $registration->save(true);

        $registration->createAgentRelation($organization, 'coletivo');

        return $registration;
    }

    /** 
     * Cria a organização do ponto na plataforma
     * @param object $row
     * @param Agent $owner
     * @return Agent
     */
    static function createOrganization(object $row, Agent $owner): Agent {
        $app = App::i();

        $organization = new Agent();
        $organization->type = 2;
        $organization->parent = $owner;
        $organization->name = $row->organizacao_nome;
        $organization->cnpj = $row->organizacao_cnpj;
        $organization->telefonePublico = $row->organizacao_telefone;
        $organization->En_Estado = $row->ponto_uf;
        $organization->En_Municipio = $row->ponto_municipio;
        $organization->rcv_tipo = "ponto";
        $organization->save(true);

        return $organization;
    }

    /** 
     * Cria o responsável do ponto na plataforma
     * @param object $row
     * @return Agent
     */
    static function createOrganizationOwner(object $row): Agent {
        // cria o usuário
        $user = new User();
        $user->email = $row->responsavel_email;
        $user->authProvider = "0";
        $user->authUid = $row->responsavel_email;
        $user->save(true);

        $owner = new Agent();
        $owner->user = $user;
        $owner->type = 1; // @todo: verificar salvamento correto do type
        $owner->name = $row->responsavel_nome;
        $owner->cpf = $row->responsavel_cpf;
        $owner->emailPrivado = $row->responsavel_email;
        $owner->telefonePrivado = $row->responsavel_telefone;
        $owner->save(true);

        $user->profile = $owner;
        $user->save(true);

        return $owner;
    }

    /**
     * Processa uma linha da planilha e realiza a criação/atualização da inscrição, organização e proprietário.
     *
     * @param object $row
     * @return array{
     *   registration: Registration,
     *   organization: Organization|null,
     *   owner: Owner|null,
     *   scenario: int|float
     * }
     */
    public static function processRow(object $row) {
        $app = App::i();

        $result = [
            'registration_id' => null,
            'organization' => null,
            'owner'        => null,
            'scenario'     => null
        ];

        $scenario = null;

        if ($registration = self::findRegistrationByRow($row)) {
            $app->log->debug("Cenário 1 - encontrou e aprova a inscrição - {$registration->number}");
            $scenario = 1;
        } else if ($organization = self::findOrganizationByRow($row)) {
            $app->log->debug("Cenário 2 - encontrou a organização mas não a inscrição, cria e aprova a inscrição");
            $registration = self::createRegistration($row, $organization);
            $result['organization'] = $organization;
            $scenario = 2;
        } else if ($owner = self::findOrganizationOwnerByRow($row)) {
            $app->log->debug("Cenário 3.1 - encontrou somente o proprietário da organização, cria a organização e a inscrição");

            $organization = self::createOrganization($row, $owner);
            $registration = self::createRegistration($row, $organization);
            $result['organization'] = $organization;
            $result['owner'] = $owner;
            $scenario = 3.1;
        } else if ($organization = self::findOrganizationFromOtherAgent($row)) {
            $app->log->debug("Cenário 5 - encontrou a organização e compara o CPF do parent com o CPF da planilha");

            $result['organization'] = $organization;
            $registration = $organization->rcv_registration;
            $scenario = 5;
        } else {
            $app->log->debug("Cenário 3.2 - não encontrou nada, cria o proprietário, a organização e a inscrição");

            $owner = self::createOrganizationOwner($row);
            $organization = self::createOrganization($row, $owner);
            $registration = self::createRegistration($row, $organization);
            $result['organization'] = $organization;
            $result['owner'] = $owner;
            $scenario = 3.2;
        }

        if($scenario != 5) {
            if($seal = $app->repo('Seal')->find($app->config['rcv.importerSeal'])) {
                $has_seal = false;
    
                $organization = $scenario == 1 ? $app->repo('Agent')->find($registration->relatedAgents['coletivo'][0]->id) : $organization;
                
                $seal_relations = $organization->getSealRelations();
    
                foreach($seal_relations as $seal_relation) {
                    if($seal_relation->seal->id == $seal->id) {
                        $has_seal = true;
                        break;
                    }
                }
    
                if(!$has_seal) {
                    $organization->createSealRelation($seal, agent: $organization);
                }
            }
    
            $registration->setStatusToApproved(true);
        }

        $result['registration_id'] = $scenario == 5 ? null : $registration->id;
        $result['scenario']        = $scenario;
        $result['category']        = $row->ponto_tipo;

        return $result;
    }

    public static function validateRow(object $row, $line): array {
        $errors = [];

        foreach (self::$column_mapping as $key => $label) {
            $field_value = trim($row->{$key} ?? '');

            $cnpj_required = false;

            if ($key == 'ponto_tipo') {
                if (empty($field_value)) {
                    $errors[] = "Linha: {$line} - Campo '{$label}' obrigatório.";
                    continue;
                }

                $parsed_category = self::parseCategory($field_value);

                if (empty($parsed_category)) {
                    $errors[] = "Linha: {$line} - Campo '{$label}' inválido.";
                    continue;
                }

                $category = '';
                foreach (self::$types as $key => $types) {
                    if (in_array($parsed_category, $types)) {
                        $category = $key;
                        break;
                    }
                }

                if ($category !== 'ponto-coletivo') {
                    // CNPJ
                    $cnpj_required = true;

                    if (empty($row->organizacao_cnpj)) {
                        $errors[] = "Linha: {$line} - Campo 'CNPJ' obrigatório para a categoria {$category}.";
                    } elseif (!v::cnpj()->validate($row->organizacao_cnpj)) {
                        $errors[] = "Linha: {$line} - CNPJ ({$row->organizacao_cnpj}) inválido.";
                    }
                    // elseif ($check_cnpj = Importer::checkCNPJ($row->organizacao_cnpj)) {
                    //     $message_error = "CNPJ ({$row->organizacao_cnpj}) inválido. {$check_cnpj['message']}";
                    //     $errors[] = "Linha: {$line} - {$message_error}";
                    // }
                }

            } elseif ($key == 'responsavel_cpf') {
                 if (empty($row->responsavel_cpf)) {
                    $errors[] = "Linha: {$line} - Campo 'CPF' obrigatório.";
                } elseif (!v::cpf()->validate($row->responsavel_cpf)) {
                    $errors[] = "Linha: {$line} - Campo 'CPF' inválido.";
                }
            } elseif (in_array($key, ['organizacao_cnpj', 'ponto_tipo'])) {
                continue; // Pula os campos validados anteriormente
            } elseif ($key == 'responsavel_email') {
                if (empty($row->responsavel_email)) {
                    $errors[] = "Linha: {$line} - Campo 'Email do responsável' obrigatório.";
                } elseif (!v::email()->validate($field_value)) {
                    $errors[] = "Linha: {$line} - Campo 'Email do responsável' inválido.";
                }
            } elseif ($key == 'organizacao_email') {
                if ($cnpj_required && empty($row->organizacao_email)) {
                    $errors[] = "Linha: {$line} - Campo 'CNPJ' obrigatório.";
                } elseif(!empty(!v::email()->validate($field_value))) {
                    $errors[] = "Linha: {$line} - Campo 'Email da organização' inválido.";
                }
            } else {
                // Valida se os demais campos estão preenchidos
                if (empty($field_value)) {
                    $errors[] = "Linha: {$line} - Campo '{$label}' obrigatório.";
                }
            }
        }

        return array_filter($errors);
    }

    public static function runImportRegistrationsJob(Registration $registration) {
        $app = App::i();
        $app->log->info("Iniciando importação da planilha da inscrição {$registration->id}");

        if (!$registration) {
            $app->log->error("Inscrição {$registration->id} não encontrada.");
            throw new \Exception("Inscrição {$registration->id} não encontrada.");
        }

        $file = Importer::getRegistrationFile($registration);
        if (!$file) {
            $app->log->error("Nenhum arquivo encontrado para a inscrição {$registration->id}.");
            throw new \Exception("Nenhum arquivo encontrado para a inscrição {$registration->id}.");
        }

        $app->disableAccessControl();

        $sheet = Importer::getSheet($file);
        $header = $sheet->rangeToArray("A1:" . $sheet->getHighestColumn() . "1", null, true, true, true)[1];
        $data_range = $sheet->rangeToArray("A2:" . $sheet->getHighestColumn() . $sheet->getHighestRow(), null, true, true, true);

        $result = [
            'registration' => $registration,
            'total_rows'   => 0,
            'success_rows' => 0
        ];

        foreach ($data_range as $index => $row) {
            if (!array_filter($row)) {
                continue;
            }

            $app->log->debug("=========================================================");

            $result['total_rows']++;

            try {
                $parsed_row = Importer::parseRow($header, $row);
                $process_row = Importer::processRow($parsed_row);
                $result['data_rows'][] = $process_row;
                $result['success_rows']++;

                $app->log->info("Linha {$index} importada com sucesso.");
            } catch (\Exception $e) {
                $app->log->error("Erro ao importar a linha {$index}: " . $e->getMessage());
                $app->enableAccessControl();
                throw $e;
            }
        }

        $app->enableAccessControl();
        $app->log->info("Importação concluída para a inscrição {$registration->id}.");

        return $result;
    }

    /**
     * Envia e-mails de notificação para os responsáveis pelas inscrições importadas.
     *
     * @param array $data
     * @return void
     */
    public static function sendEmails(array $data) {
        $app = App::i();

        // Envia e-mail para cada caso/linha da planilha
        foreach ($data['data_rows'] as $row) {

            if ($row['scenario'] == 1) {

                $registration = $app->repo('registration')->findOneBy(['id' => $row['registration_id']]);
                $organization = $app->repo('Agent')->findOneBy(['parent' => $registration->owner->id]);

                $template_data = [
                    'siteName'           => $app->siteName,
                    'userName'           => $registration->owner->name,
                    'registrationNumber' => $registration->number,
                    'redirectUrl'        => $app->createUrl('registration', 'single', [$registration->id]),
                    'organizationName'   => $organization->name,
                    'organizationCNPJ'   => $organization->cnpj,
                    'type'               => $row['category']
                ];

                $message = $app->renderMustacheTemplate('primeiro_caso.html', $template_data);
                $subject = "Sua inscrição {$registration->number} foi certificada por um Edital de Seleção da Cultura Viva.";

            } else if ($row['scenario'] == 2) {

                $registration = $app->repo('registration')->findOneBy(['id' => $row['registration_id']]);

                $template_data = [
                    'siteName'         => $app->siteName,
                    'userName'         => $registration->owner->name,
                    'redirectUrl'      => $app->createUrl('registration', 'single', [$registration->id]),
                    'organizationName' => $row['organization']->name,
                    'organizationCNPJ' => $row['organization']->cnpj,
                    'type'             => $row['category']
                ];

                $message = $app->renderMustacheTemplate('segundo_caso.html', $template_data);
                $subject = "Sua organização {$row['organization']->name} foi certificada por um Edital de Seleção da Cultura Viva.";

            } else if ($row['scenario'] == 3.1) {

                $registration = $app->repo('registration')->findOneBy(['id' => $row['registration_id']]);

                $template_data = [
                    'siteName'         => $app->siteName,
                    'userName'         => $registration->owner->name,
                    'redirectUrl'      => $app->createUrl('registration', 'single', [$registration->id]),
                    'organizationName' => $row['organization']->name,
                    'organizationCNPJ' => $row['organization']->cnpj,
                    'type'             => $row['category']
                ];

                $message = $app->renderMustacheTemplate('terceiro_caso.html', $template_data);
                $subject = "Sua organização {$row['organization']->name} foi certificada por um Edital de Seleção da Cultura Viva.";

            } else if ($row['scenario'] == 3.2) {

                $registration = $app->repo('registration')->findOneBy(['id' => $row['registration_id']]);

                $template_data = [
                    'siteName'         => $app->siteName,
                    'userName'         => $registration->owner->name,
                    'redirectUrl'      => $app->createUrl('registration', 'single', [$registration->id]),
                    'organizationName' => $row['organization']->name,
                    'organizationCNPJ' => $row['organization']->cnpj,
                    'type'             => $row['category']
                ];

                $message = $app->renderMustacheTemplate('quarto_caso.html', $template_data);
                $subject = "Sua organização {$row['organization']->name} foi certificada por um Edital de Seleção da Cultura Viva.";

            } else if ($row['scenario'] == 5) {

                $organization = $row['organization'];

                $template_data = [
                    'siteName'         => $app->siteName,
                    'userName'         => $organization->parent->name,
                    'redirectUrl'      => $app->createUrl('site/atualizacao-cadastral'),
                    'organizationName' => $organization->name,
                    'organizationCNPJ' => $organization->cnpj,
                    'type'             => $row['category']
                ];

                $message = $app->renderMustacheTemplate('quinto_caso.html', $template_data);
                $subject = "Sua organização {$organization->name} foi certificada por um Edital de Seleção da Cultura Viva.";
            } else {
                continue;
            }

            $to = $registration->owner->emailPrivado;

            $app->createAndSendMailMessage([
                'to'      => $to,
                'subject' => $subject,
                'body'    => $message,
            ]);
        }

        $registration = $data['registration'];

        // Envia e-mail para o gestor com resumo da importação
        $template_data = [
            'siteName'           => $app->siteName,
            'userName'           => $registration->owner->name,
            'registrationNumber' => $registration->number,
            'totalRows'          => $data['total_rows'],
            'successRows'        => $data['success_rows']
        ];

        $to      = $registration->owner->emailPrivado;
        $subject = "Importação realizada com sucesso";
        $message = $app->renderMustacheTemplate('import_summary.html', $template_data);

        $app->createAndSendMailMessage([
            'to'      => $to,
            'subject' => $subject,
            'body'    => $message,
        ]);

    }

    /**
     * Envia um e-mail de erro para o responsável pela inscrição.
     */
    public static function sendEmailError(Registration $registration, $message) {
        $app = App::i();

        $template_data = [
            'siteName'           => $app->siteName,
            'userName'           => $registration->owner->name,
            'registrationNumber' => $registration->number,
            'redirectUrl'        => $app->createUrl('registration', 'single', [$registration->id])
        ];

        $to      = $registration->owner->emailPrivado;
        $subject = "A importação da planilha falhou";
        $message = $app->renderMustacheTemplate('import_error.html', $template_data);

        $app->createAndSendMailMessage([
            'to'      => $to,
            'subject' => $subject,
            'body'    => $message
        ]);
    }

    public static function checkCNPJ($cnpj) {
        $valid_cnpj = self::$theme->getCNPJ($cnpj, source:'importer');

        $result = [
            'error'   => false,
            'message' => ''
        ];

        if ($valid_cnpj) {
            if ($valid_cnpj['situacaoCadastral']['codigo'] !== '2') {
                $result['error'] = true;
                $result['message'] = 'Situação cadastral do CNPJ é inválida.';
            }

            $legal_nature = $valid_cnpj['naturezaJuridica'] ?? null;

            if ($legal_nature) {
                $legal_nature_code = $legal_nature['codigo'];
                $allowed_legal_natures = ['1', '3', '2143', '3999', '3069', '3131', '3239', '3301', '3220'];
            }

            // Se a natureza jurídica não for válida
            if (!in_array($legal_nature_code, $allowed_legal_natures)) {
                $result['error'] = true;
                $result['message'] = 'A natureza jurídica do CNPJ é inválida.';
            }
        }

        return $result;
    }
}