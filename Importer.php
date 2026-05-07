<?php
namespace CulturaViva;

require __DIR__ . "/ImportRegistrationsJob.php";

use CulturaViva\JobTypes\ImportRegistrationsJob;
use DateTime;
use MapasCulturais\API;
use MapasCulturais\ApiQuery;
use MapasCulturais\App;
use MapasCulturais\i;
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

    public static Registration $registration;

    private const BR_UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

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

        if(!$app->getRegisteredJobType(ImportRegistrationsJob::SLUG)) {
            $app->registerJobType(new ImportRegistrationsJob(ImportRegistrationsJob::SLUG));
        }

        // Na aprovação da inscrição PNAB, agenda a importação da planilha anexada
        $app->hook("entity(Registration).status(approved)", function() use ($app) {
            /** @var \MapasCulturais\Entities\Registration $this */

            if ($this->opportunity->id !== (int) $app->config['rcv.pnabOpportunityId']) {
                return;
            }

            if ($file = self::getRegistrationFile($this)) {
                $params = [
                    // Não enviar a entidade inteira para o Job (evita payload grande e erros ao persistir).
                    'registration_id' => $this->id
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
        $path = $registration_file->getPath();
        $spreadsheet = IOFactory::load($path);
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
     * Valida extensão .xlsx e acesso ao arquivo antes de {@see IOFactory::load} (Cultura Viva / PNAB).
     *
     * @return string|null mensagem traduzida de erro, ou null se ok
     */
    static function validatePnabSpreadsheetFileForReading(RegistrationFile $file): ?string {
        $name = (string) $file->name;
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = strtolower((string) pathinfo((string) $file->getPath(), PATHINFO_EXTENSION));
        }
        if ($ext !== 'xlsx') {
            return i::__('Envie um arquivo no formato Excel (.xlsx).');
        }
        $path = $file->getPath();
        if (!$path || !is_file($path) || !is_readable($path)) {
            return i::__('Não foi possível acessar o arquivo enviado. Tente anexar novamente.');
        }
        return null;
    }

    /**
     * Normaliza texto de célula de cabeçalho PNAB para comparação com o modelo oficial.
     */
    static function normalizePnabHeaderCell(string $value): string {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $value;
    }

    /**
     * Cabeçalhos esperados na linha 1 (colunas A–M), conforme modelo oficial da planilha PNAB.
     * A coluna C é validada separadamente (prefixo "Tipo de Certificação" após slugify).
     *
     * @return array<string, string> letra da coluna => texto esperado (A, B, D–M)
     */
    static function getPnabSpreadsheetCanonicalHeaderCells(): array {
        return [
            'A' => '#',
            'B' => 'Data da Certificação (resultado final da etapa de habilitação)',
            'D' => 'Estado',
            'E' => 'Município',
            'F' => 'Nome da Organização',
            'G' => 'CNPJ (se houver)',
            'H' => 'Email da organização',
            'I' => 'Telefone da organização',
            'J' => 'Nome do responsável',
            'K' => 'CPF do responsável',
            'L' => 'Email do responsável',
            'M' => 'Telefone do responsável',
        ];
    }

    /**
     * Valida a primeira linha da planilha PNAB (A1:M1): ordem e textos do modelo oficial.
     * Coluna C: exige prefixo slugificado "Tipo de Certificação" (texto longo do modelo aceito).
     *
     * @param array<int|string, mixed> $row1 linha 1 com chaves de coluna A…M (como em {@see Worksheet::rangeToArray})
     * @return list<string> mensagens de erro traduzidas; vazio se ok
     */
    static function validatePnabSpreadsheetHeaderRow(array $row1): array {
        $app = App::i();
        $errors = [];
        $canonical = self::getPnabSpreadsheetCanonicalHeaderCells();

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $col) {
            $raw = isset($row1[$col]) ? (string) $row1[$col] : '';
            $normalized = self::normalizePnabHeaderCell($raw);

            if ($col === 'C') {
                if ($normalized === '') {
                    $errors[] = sprintf(
                        i::__('Cabeçalho inválido na coluna %s. Utilize o modelo oficial da planilha sem alterar a primeira linha.'),
                        $col
                    );
                    continue;
                }
                $slugCell = $app->slugify($normalized);
                $slugTipo = $app->slugify('Tipo de Certificação');
                if (!str_starts_with($slugCell, $slugTipo)) {
                    $errors[] = sprintf(
                        i::__('Cabeçalho inválido na coluna %s. Utilize o modelo oficial da planilha sem alterar a primeira linha.'),
                        $col
                    );
                }
                continue;
            }

            $expected = self::normalizePnabHeaderCell($canonical[$col]);
            if ($normalized !== $expected) {
                $errors[] = sprintf(
                    i::__('Cabeçalho inválido na coluna %s. Utilize o modelo oficial da planilha sem alterar a primeira linha.'),
                    $col
                );
            }
        }

        return $errors;
    }

    /**
     * Lê A1:M1 e valida cabeçalhos PNAB.
     *
     * @return list<string>
     */
    static function validatePnabSpreadsheetHeaderFromSheet(Worksheet $sheet): array {
        $rows = $sheet->rangeToArray('A1:M1', null, true, true, true);
        if (!isset($rows[1]) || !is_array($rows[1])) {
            return [i::__('Não foi possível ler a primeira linha da planilha. Utilize o modelo oficial (.xlsx).')];
        }
        return self::validatePnabSpreadsheetHeaderRow($rows[1]);
    }

    /**
     * Mantém só linhas em que pelo menos uma célula tem conteúdo (após o cabeçalho).
     *
     * @param array<int|string, mixed> $data_range retorno de {@see Worksheet::rangeToArray} nas linhas A2:M*
     * @return array<int|string, array>
     */
    static function filterPnabRowsWithAnyCell(array $data_range): array {
        return array_filter($data_range, static function ($row) {
            return is_array($row) && (bool) array_filter($row);
        });
    }

    /**
     * Valida o valor da coluna A ("#") em uma linha de dados PNAB: deve ser o inteiro sequencial esperado
     * (normalmente 1..N após {@see filterPnabRowsWithAnyCell} + {@see array_values}).
     *
     * @param mixed $cell_a valor lido da coluna A (inteiro, float “redondo” ou string só com dígitos)
     * @param int   $expected_one_based valor esperado (ex.: 1, 2, 3…)
     * @param int   $line número de linha exibido na mensagem (alinhado ao usado em {@see validateRow})
     * @return string|null mensagem de erro, ou null se ok
     */
    static function validatePnabSpreadsheetDataRowIndexCell($cell_a, int $expected_one_based, int $line): ?string {
        $raw = is_string($cell_a) ? trim($cell_a) : $cell_a;

        $ok = false;
        if (is_int($raw)) {
            $ok = ($raw === $expected_one_based);
        } elseif (is_float($raw)) {
            $ok = ((int) $raw === $expected_one_based) && ((float) $expected_one_based === (float) $raw);
        } elseif (is_string($raw) && $raw !== '' && ctype_digit($raw)) {
            $ok = ((int) $raw === $expected_one_based);
        }

        if ($ok) {
            return null;
        }

        return "Linha: {$line} - Campo '#' inválido. Esperado: {$expected_one_based}.";
    }

    /**
     * Validação completa da planilha PNAB no envio da inscrição (hook {@see Theme} / sendValidationErrors).
     * Não grava dados; só lê o arquivo anexado e devolve mensagens no mesmo formato de {@see $errorsResult}.
     *
     * Pré-condição: a inscrição já é da oportunidade PNAB (o hook filtra antes de chamar).
     *
     * @return array<string, list<string>> chaves `file_{attachmentId}` ou `error` => lista de mensagens; vazio = ok
     */
    public static function validatePnabSpreadsheetForSend(Registration $registration): array {
        $app = App::i();

        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '1024M');

        if (!isset($app->config['rcv.pnabOpportunityAttachmentId'])) {
            return ['error' => ['Erro inesperado, procure o suporte.']];
        }

        $field_file_id      = 'file_' . $app->config['rcv.pnabOpportunityAttachmentId'];
        $pnab_attachment_id = 'rfc_' . $app->config['rcv.pnabOpportunityAttachmentId'];

        if (!isset($registration->files[$pnab_attachment_id])) {
            return [$field_file_id => ['A planilha é obrigatória.']];
        }

        $pnabFile = $registration->files[$pnab_attachment_id];

        $readError = self::validatePnabSpreadsheetFileForReading($pnabFile);
        if ($readError !== null) {
            return [$field_file_id => [$readError]];
        }

        try {
            $sheet = self::getSheet($pnabFile);
        } catch (\Throwable $e) {
            $app->log->debug('PNAB: falha ao ler planilha: ' . $e->getMessage());

            return [$field_file_id => [i::__('O arquivo não é uma planilha Excel válida ou está corrompido. Utilize o modelo .xlsx e tente novamente.')]];
        }

        try {
            $headerErrors = self::validatePnabSpreadsheetHeaderFromSheet($sheet);
            if ($headerErrors !== []) {
                return [$field_file_id => $headerErrors];
            }

            $highestColumn = strtoupper((string) $sheet->getHighestColumn());
            if ($highestColumn !== 'M') {
                return [$field_file_id => [i::__('A planilha deve manter exatamente as colunas do modelo oficial (A até M), sem adicionar, remover ou mover colunas.')]];
            }

            $highestRow = (int) $sheet->getHighestRow();
            if ($highestRow < 2) {
                return [$field_file_id => [i::__('A planilha deve conter pelo menos uma linha de dados.')]];
            }

            $header     = $sheet->rangeToArray('A1:M1', null, true, true, true)[1];
            $data_range = $sheet->rangeToArray("A2:M{$highestRow}", null, true, true, true);
            $data_rows  = array_values(self::filterPnabRowsWithAnyCell($data_range));

            if ($data_rows === []) {
                return [$field_file_id => [i::__('A planilha deve conter pelo menos uma linha de dados.')]];
            }

            $validate_rows = [];

            foreach ($data_rows as $index => $row) {
                if (!array_filter($row)) {
                    continue;
                }

                $expected_num = $index + 1;

                $row_index_error = self::validatePnabSpreadsheetDataRowIndexCell($row['A'] ?? null, $expected_num, $expected_num);
                if ($row_index_error !== null) {
                    $validate_rows[] = $row_index_error;
                }

                $parsed_row = self::parseRow($header, $row);
                $parsed_row = self::normalizeRow($parsed_row);

                if ($row_errors = self::validateRow($parsed_row, $expected_num)) {
                    $validate_rows = array_merge($validate_rows, $row_errors);
                }
            }

            if ($validate_rows !== []) {
                return [$field_file_id => $validate_rows];
            }
        } catch (\Throwable $e) {
            return [$field_file_id => ['A planilha não foi processada pois não está em conformidade com o modelo disponibilizado. Verifique as regras e tente novamente.']];
        }

        return [];
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

        // Resolve coluna → campo a partir do cabeçalho
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

        // Extrai valores da linha conforme o mapeamento
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
     * Normaliza os campos de uma linha já parseada (formata CPF/CNPJ, remove quebras de linha).
     * Não acessa o banco de dados.
     *
     * @param object $row
     * @return object
     */
    public static function normalizeRow(object $row): object {
        $row->responsavel_cpf  = Utils::formatCnpjCpf(trim($row->responsavel_cpf));
        $row->organizacao_cnpj = $row->organizacao_cnpj
            ? Utils::formatCnpjCpf(trim($row->organizacao_cnpj))
            : null;
        if ($row->organizacao_cnpj === '') {
            $row->organizacao_cnpj = null;
        }
        $row->organizacao_nome = trim($row->organizacao_nome);
        $row->organizacao_telefone = self::normalizeBrPhone($row->organizacao_telefone ?? null);
        $row->responsavel_telefone = self::normalizeBrPhone($row->responsavel_telefone ?? null);

        foreach ($row as $key => $value) {
            if (is_string($value) && preg_match('/\r\n|\r|\n/', $value)) {
                $row->$key = preg_replace('/\r\n|\r|\n/', ' ', $value);
            }
        }

        return $row;
    }

    /**
     * Normaliza telefone BR (DDD + 8/9 dígitos) para o formato canônico:
     * - (DD) NNNN-NNNN  (10 dígitos)
     * - (DD) NNNNN-NNNN (11 dígitos)
     *
     * Se não for possível normalizar (tamanho diferente de 10/11 após remover não-dígitos),
     * devolve o valor original (trimado) para a validação acusar erro.
     */
    private static function normalizeBrPhone($value): ?string {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) === 10) {
            $ddd = substr($digits, 0, 2);
            $p1  = substr($digits, 2, 4);
            $p2  = substr($digits, 6, 4);
            return "({$ddd}) {$p1}-{$p2}";
        }

        if (strlen($digits) === 11) {
            $ddd = substr($digits, 0, 2);
            $p1  = substr($digits, 2, 5);
            $p2  = substr($digits, 7, 4);
            return "({$ddd}) {$p1}-{$p2}";
        }

        return $raw;
    }

    /**
     * Encontra a inscrição do ponto na plataforma
     * @param object $row
     * @return Registration|null
     * @deprecated Usar findRegistrationByRowReadOnly() na Fase A
     */
    static function findRegistrationByRow(object $row): ?Registration {
        $app = App::i();
        $opportunity_id = $app->config['rcv.opportunityId'];

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            // Ponto coletivo: busca por CPF e filtra pelo nome do coletivo
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
                $coletivo_status = $coletivo[0]->status;

                if (!empty($coletivo) && $coletivo_status >= 0 && !empty($name) && !empty($row->organizacao_nome)) {
                    if ($app->slugify($name) == $app->slugify($row->organizacao_nome)) {
                        return $registration;
                    }
                }
            }
        } else {
            // Ponto entidade/pontão: busca por CNPJ e confere o CPF do responsável
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
                $coletivo_status = $coletivo ? $coletivo[0]->status : null; 

                if($registration->owner->status < 0) {
                    $user = $registration->owner->user;

                    if($user && $user->status <= 0) {
                        continue;
                    }

                    $app->disableAccessControl();
                    $agent = new Agent();
                    $agent->user = $user;
                    $agent->name = $row->responsavel_nome;
                    $agent->type = 1;
                    $agent->status = Agent::STATUS_ENABLED;
                    $agent->save();

                    $registration->owner = $agent;
                    $registration->save();

                    $user->profile = $agent;
                    $user->save();
                    $app->enableAccessControl();
                }

                if ($coletivo && $coletivo_status >= 0 && $app->slugify($coletivo[0]->name) == $app->slugify($row->organizacao_nome) && Utils::formatCnpjCpf($registration->owner->cpf) == Utils::formatCnpjCpf($row->responsavel_cpf)) {
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
     * @deprecated Usar findOrganizationByRowReadOnly() na Fase A
     */
    static function findOrganizationByRow(object $row): ?Agent {
        $app = App::i();

        $cpf_query = new ApiQuery(Agent::class, [
            'cpf' => API::OR(API::EQ($row->responsavel_cpf), API::EQ(preg_replace('/[-\.]/', '', $row->responsavel_cpf))),
            'type' => API::EQ(1)
        ]);

        $owner_ids = $cpf_query->findIds();
        $valid_owner_ids = [];

        foreach($owner_ids as $owner_id) {
            $owner = $app->repo('Agent')->find($owner_id);

            if($owner && $owner->status < 0) {
                $app->disableAccessControl();
                $owner->cpf = null;
                $owner->save();
                $app->enableAccessControl();
                continue;
            }

            $valid_owner_ids[] = $owner_id;
        }

        if($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $query = new ApiQuery(Agent::class, [
                'name' => API::ILIKE($row->organizacao_nome),
                'parent' => API::IN($valid_owner_ids),
                'type' => API::EQ(2),
                'status' => API::GTE(0)
            ]);
            $ids = $query->findIds();
        } else {
            $query1 = new ApiQuery(Agent::class, [
                'cnpj' => API::OR(API::EQ($row->organizacao_cnpj), API::EQ(preg_replace('/[-\.\/]/', '', $row->organizacao_cnpj))),
                'parent' => API::IN($valid_owner_ids),
                'type' => API::EQ(2),
                'status' => API::GTE(0)
            ]);
            $query2 = new ApiQuery(Agent::class, [
                'name' => API::ILIKE($row->organizacao_nome),
                'parent' => API::IN($valid_owner_ids),
                'type' => API::EQ(2),
                'status' => API::GTE(0)
            ]);
            $ids = array_merge($query1->findIds(), $query2->findIds());
        }


        /** @var Agent */
        $agent = $app->repo('Agent')->findOneBy(['id' => $ids], ['updateTimestamp' => 'DESC']);

        // Se não for coletivo e estiver sem CNPJ cadastrado, completa a partir da planilha
        if($agent && !$agent->cnpj && $row->ponto_tipo != $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $agent->cnpj = $row->organizacao_cnpj;
            $agent->save();
        }

        return $agent;
    }

    /**
     * Encontra uma organização a partir de outro agente.
     *
     * @param object $row
     * @return Agent|null
     * @deprecated Usar findOrganizationFromOtherAgentReadOnly() na Fase A
     */
    static function findOrganizationFromOtherAgent(object $row): ?Agent {
        $app = App::i();

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $registration_query = new ApiQuery(Registration::class, [
                '@keyword' => $row->organizacao_nome,
                '@select'  => 'relatedAgents',
                'opportunity' => API::EQ($app->config['rcv.opportunityId'])
            ]);

            $registrations = $registration_query->find();
            $ids = array_map(fn($r) => $r['relatedAgents']['coletivo'][0]['id'], $registrations);
        } else {
            $query = new ApiQuery(Agent::class, [
                'cnpj' => API::OR(API::EQ($row->organizacao_cnpj), API::EQ(preg_replace('/[-\.\/]/', '', $row->organizacao_cnpj))),
                'type' => API::EQ(2)
            ]);
            $ids = $query->findIds();
        }

        
        $organizations = $app->repo('Agent')->findBy(['id' => $ids]);
        foreach($organizations as $org) {
            if (Utils::formatCnpjCpf($org->owner->cpf) == Utils::formatCnpjCpf($row->responsavel_cpf)) {
                return null;
            }
        }

        /** @var Agent */
        $organization = $app->repo('Agent')->findOneBy(['id' => $ids], ['updateTimestamp' => 'DESC']);

        if ( !$organization ) {
            return null;
        }

        if($organization->status < 0) {
            return null;
        }

        $owner = $organization->owner;

        if ($owner && $owner->type->id != 1) {
            return null;
        }

        if(!$organization->owner->cpf && $organization->owner->name != $row->responsavel_nome) {
            return $organization;
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
     * Versão read-only de findOrganizationFromOtherAgent.
     * Idêntica ao original — já não fazia writes.
     *
     * @param object $row
     * @return Agent|null
     */
    public static function findOrganizationFromOtherAgentReadOnly(object $row): ?Agent {
        return self::findOrganizationFromOtherAgent($row);
    }

    /** 
     * Encontra o responsável do ponto na plataforma
     * @param object $row
     * @return Agent|null
     * @deprecated Usar findOrganizationOwnerByRowReadOnly() na Fase A
     */
    static function findOrganizationOwnerByRow(object $row): ?Agent {
        $app = App::i();

        $query = new ApiQuery(Agent::class, [
            'cpf' => API::OR(API::EQ($row->responsavel_cpf), API::EQ(preg_replace('/[-\.]/', '', $row->responsavel_cpf))),
            'type' => API::EQ(1)
        ]);

        $ids = $query->findIds();

        $valid_owner_ids = [];

        foreach($ids as $id) {
            $owner = $app->repo('Agent')->find($id);

            if($owner && $owner->status < 0) {
                $app->disableAccessControl();
                $owner->cpf = null;
                $owner->save();
                $app->enableAccessControl();
                continue;
            }

            $valid_owner_ids[] = $id;
        }

        /** @var Agent */
        $agent = $app->repo('Agent')->findOneBy(['id' => $valid_owner_ids], ['updateTimestamp' => 'DESC']);

        return $agent;
    }

    // -------------------------------------------------------------------------
    // Fase A: buscas read-only e montagem do plano de execução
    // -------------------------------------------------------------------------

    private static function entityId($entity): ?int {
        return $entity ? (int) $entity->id : null;
    }

    private static function entityIds(array $entities): array {
        return array_values(array_filter(array_map(
            fn($entity) => self::entityId($entity),
            $entities
        )));
    }

    private static function findEntityById(string $repository, ?int $id) {
        if (!$id) {
            return null;
        }

        return App::i()->repo($repository)->find($id);
    }

    /**
     * Busca uma inscrição existente compatível com a linha, sem executar writes.
     *
     * @param object $row linha normalizada
     * @return array{registration: Registration, organization: Agent, deferred: array}|null
     */
    public static function findRegistrationByRowReadOnly(object $row): ?array {
        $app = App::i();
        $opportunity_id = $app->config['rcv.opportunityId'];

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $query = new ApiQuery(Registration::class, [
                'opportunity' => API::EQ($opportunity_id),
                'category'    => API::EQ($row->ponto_tipo),
                'status'      => API::GTE(0),
                '@keyword'    => "$row->responsavel_cpf"
            ]);

            $ids = $query->findIds();
            $registrations = $app->repo('Registration')->findBy(['id' => $ids]);

            foreach ($registrations as $registration) {
                $coletivo        = $registration->getRelatedAgents('coletivo');
                $name            = $coletivo[0]->name ?? null;
                $coletivo_status = $coletivo[0]->status ?? null;

                if (!empty($coletivo) && $coletivo_status >= 0 && !empty($name) && !empty($row->organizacao_nome)) {
                    if ($app->slugify($name) == $app->slugify($row->organizacao_nome)) {
                        return [
                            'registration' => $registration,
                            'organization' => $coletivo[0],
                            'deferred'     => ['new_owner_for_user' => null],
                        ];
                    }
                }
            }
        } else {
            $query = new ApiQuery(Registration::class, [
                'opportunity' => API::EQ($opportunity_id),
                'category'    => API::EQ($row->ponto_tipo),
                'status'      => API::GTE(0),
                '@keyword'    => "$row->organizacao_cnpj"
            ]);

            $ids = $query->findIds();
            $registrations = $app->repo('Registration')->findBy(['id' => $ids]);

            foreach ($registrations as $registration) {
                $coletivo        = $registration->getRelatedAgents('coletivo');
                $coletivo_status = $coletivo ? $coletivo[0]->status : null;

                if ($registration->owner->status < 0) {
                    $user = $registration->owner->user;

                    if ($user && $user->status <= 0) {
                        continue;
                    }

                    // No fluxo antigo, o owner era trocado por um Agent sem CPF antes do match.
                    // Para manter o mesmo resultado no modo read-only, esta inscrição não entra
                    // como candidata.
                    continue;
                }

                if ($coletivo && $coletivo_status >= 0
                    && $app->slugify($coletivo[0]->name) == $app->slugify($row->organizacao_nome)
                    && Utils::formatCnpjCpf($registration->owner->cpf) == Utils::formatCnpjCpf($row->responsavel_cpf)
                ) {
                    return [
                        'registration' => $registration,
                        'organization' => $coletivo[0],
                        'deferred'     => ['new_owner_for_user' => null],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Busca uma organização compatível com a linha, sem executar writes.
     * Ajustes necessários (ex.: completar CNPJ) são devolvidos em 'deferred'.
     *
     * @param object $row linha normalizada
     * @return array{organization: Agent, owner: Agent|null, deferred: array}|null
     */
    public static function findOrganizationByRowReadOnly(object $row, array $cleared_agent_ids = []): ?array {
        $app = App::i();

        $cpf_query = new ApiQuery(Agent::class, [
            'cpf'  => API::OR(API::EQ($row->responsavel_cpf), API::EQ(preg_replace('/[-\.]/', '', $row->responsavel_cpf))),
            'type' => API::EQ(1)
        ]);

        $owner_ids       = $cpf_query->findIds();
        $valid_owner_ids = [];
        $clear_cpf_agents = [];

        foreach ($owner_ids as $owner_id) {
            if (isset($cleared_agent_ids[$owner_id])) {
                continue;
            }

            $owner = $app->repo('Agent')->find($owner_id);

            if ($owner && $owner->status < 0) {
                $clear_cpf_agents[] = $owner;
                continue;
            }

            $valid_owner_ids[] = $owner_id;
        }

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $query = new ApiQuery(Agent::class, [
                'name'   => API::ILIKE($row->organizacao_nome),
                'parent' => API::IN($valid_owner_ids),
                'type'   => API::EQ(2),
                'status' => API::GTE(0)
            ]);
            $ids = $query->findIds();
        } else {
            $query1 = new ApiQuery(Agent::class, [
                'cnpj'   => API::OR(API::EQ($row->organizacao_cnpj), API::EQ(preg_replace('/[-\.\/]/', '', $row->organizacao_cnpj))),
                'parent' => API::IN($valid_owner_ids),
                'type'   => API::EQ(2),
                'status' => API::GTE(0)
            ]);
            $query2 = new ApiQuery(Agent::class, [
                'name'   => API::ILIKE($row->organizacao_nome),
                'parent' => API::IN($valid_owner_ids),
                'type'   => API::EQ(2),
                'status' => API::GTE(0)
            ]);
            $ids = array_merge($query1->findIds(), $query2->findIds());
        }

        /** @var Agent|null */
        $agent = $app->repo('Agent')->findOneBy(['id' => $ids], ['updateTimestamp' => 'DESC']);

        if (!$agent) {
            return null;
        }

        // CNPJ ausente: registra para completar na Fase B
        $set_org_cnpj = null;
        if (!$agent->cnpj && $row->ponto_tipo != $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $set_org_cnpj = $row->organizacao_cnpj;
        }

        $owner = $agent->parent;

        return [
            'organization' => $agent,
            'owner'        => $owner,
            'deferred'     => [
                'clear_cpf_agents' => $clear_cpf_agents,
                'set_org_cnpj'     => $set_org_cnpj,
            ],
        ];
    }

    /**
     * Busca o responsável (owner) compatível com a linha, sem executar writes.
     * Ajustes necessários são devolvidos em 'deferred'.
     *
     * @param object $row linha normalizada
     * @return array{owner: Agent, deferred: array}|null
     */
    public static function findOrganizationOwnerByRowReadOnly(object $row, array $cleared_agent_ids = []): ?array {
        $app = App::i();

        $query = new ApiQuery(Agent::class, [
            'cpf'  => API::OR(API::EQ($row->responsavel_cpf), API::EQ(preg_replace('/[-\.]/', '', $row->responsavel_cpf))),
            'type' => API::EQ(1)
        ]);

        $ids             = $query->findIds();
        $valid_owner_ids = [];
        $clear_cpf_agents = [];

        foreach ($ids as $id) {
            if (isset($cleared_agent_ids[$id])) {
                continue;
            }

            $owner = $app->repo('Agent')->find($id);

            if ($owner && $owner->status < 0) {
                $clear_cpf_agents[] = $owner;
                continue;
            }

            $valid_owner_ids[] = $id;
        }

        /** @var Agent|null */
        $agent = $app->repo('Agent')->findOneBy(['id' => $valid_owner_ids], ['updateTimestamp' => 'DESC']);

        if (!$agent) {
            return null;
        }

        return [
            'owner'    => $agent,
            'deferred' => ['clear_cpf_agents' => $clear_cpf_agents],
        ];
    }

    /**
     * Resolve a decisão da linha (cenário, entidades encontradas e writes diferidos),
     * sem executar alterações no banco.
     *
     * @param object $row  linha já parseada e normalizada
     * @param int    $line número da linha na planilha (para logging)
     * @return array RowDecision
     */
    public static function resolveRowScenario(object $row, int $line, array $cleared_agent_ids = []): array {
        $decision = [
            'line'            => $line,
            'row'             => $row,
            'scenario'        => null,
            'registration_id' => null,
            'organization_id' => null,
            'owner_id'        => null,
            'deferred'        => [
                'clear_cpf_agent_ids' => [],
                'set_org_cnpj'        => null,
            ],
            'email' => [
                'to'                  => $row->responsavel_email,
                'user_name'           => $row->responsavel_nome,
                'organization_name'   => $row->organizacao_nome,
                'organization_cnpj'   => $row->organizacao_cnpj,
                'registration_id'     => null,
                'registration_number' => null,
                'category'            => $row->ponto_tipo,
            ],
        ];

        // Cenário 5: CPF conflitante (organização vinculada a outro responsável)
        if ($org = self::findOrganizationFromOtherAgentReadOnly($row)) {
            $decision['scenario']        = 5;
            $decision['organization_id'] = self::entityId($org);
            $decision['email']['organization_name'] = $org->name;
            $decision['email']['organization_cnpj'] = $org->cnpj;
            return $decision;
        }

        // Cenário 1: inscrição já existe no cadastro
        if ($result = self::findRegistrationByRowReadOnly($row)) {
            $decision['scenario']        = 1;
            $decision['registration_id'] = self::entityId($result['registration']);
            $decision['organization_id'] = self::entityId($result['organization']);
            $decision['email']['registration_id']     = $result['registration']->id;
            $decision['email']['registration_number'] = $result['registration']->number;
            $decision['email']['user_name']           = $result['registration']->owner->name;
            $decision['email']['organization_name']   = $result['organization']->name;
            $decision['email']['organization_cnpj']   = $result['organization']->cnpj;
            return $decision;
        }

        // Cenário 2: organização existe, mas a inscrição ainda não
        if ($result = self::findOrganizationByRowReadOnly($row, $cleared_agent_ids)) {
            $decision['scenario']        = 2;
            $decision['organization_id'] = self::entityId($result['organization']);
            $decision['owner_id']        = self::entityId($result['owner']);
            $decision['email']['user_name']           = $result['organization']->parent->name;
            $decision['email']['organization_name']   = $result['organization']->name;
            $decision['email']['organization_cnpj']   = $result['organization']->cnpj;

            $decision['deferred']['clear_cpf_agent_ids'] = self::entityIds($result['deferred']['clear_cpf_agents']);
            $decision['deferred']['set_org_cnpj']        = $result['deferred']['set_org_cnpj'];
            return $decision;
        }

        // Cenário 3.1: owner existe, mas não há organização nem inscrição
        if ($result = self::findOrganizationOwnerByRowReadOnly($row, $cleared_agent_ids)) {
            $decision['scenario'] = 3.1;
            $decision['owner_id'] = self::entityId($result['owner']);
            $decision['email']['user_name'] = $result['owner']->name;
            $decision['deferred']['clear_cpf_agent_ids'] = self::entityIds($result['deferred']['clear_cpf_agents']);
            return $decision;
        }

        // Cenário 4: nada encontrado, criar tudo
        $decision['scenario'] = 4;
        return $decision;
    }

    /**
     * Lê a planilha e monta o plano de execução (ExecutionPlan).
     * Esta etapa não abre transação e não executa writes.
     *
     * @param Registration $pnab_registration inscrição PNAB que contém a planilha
     * @return array ExecutionPlan
     * @throws \Exception se o arquivo não for encontrado
     */
    public static function buildExecutionPlan(Registration $pnab_registration): array {
        $app = App::i();
        $app->log->info("Fase A: construindo plano de execução para inscrição {$pnab_registration->id}");

        $file = self::getRegistrationFile($pnab_registration);
        if (!$file) {
            throw new \Exception("Nenhum arquivo encontrado para a inscrição {$pnab_registration->id}.");
        }

        $sheet      = self::getSheet($file);
        $header     = $sheet->rangeToArray("A1:" . $sheet->getHighestColumn() . "1", null, true, true, true)[1];
        $data_range = $sheet->rangeToArray("A2:" . $sheet->getHighestColumn() . $sheet->getHighestRow(), null, true, true, true);
        $data_range = self::filterPnabRowsWithAnyCell($data_range);
        $data_range = array_values($data_range);

        $plan = [
            'pnab_registration'    => $pnab_registration,
            'pnab_registration_id' => $pnab_registration->id,
            'total_rows'           => count($data_range),
            'rows'                 => [],
        ];

        $cleared_agent_ids = [];

        foreach ($data_range as $index => $raw_row) {
            $line     = $index + 1;
            $row      = self::parseRow($header, $raw_row);
            $row      = self::normalizeRow($row);
            $decision = self::resolveRowScenario($row, $line, $cleared_agent_ids);

            foreach ($decision['deferred']['clear_cpf_agent_ids'] as $agent_id) {
                $cleared_agent_ids[$agent_id] = true;
            }

            $plan['rows'][] = $decision;

            $app->log->debug("Fase A: linha {$line} → cenário {$decision['scenario']}");
        }

        $app->log->info("Fase A: {$plan['total_rows']} linhas resolvidas.");
        return $plan;
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
        $registration->range = $app->config['rcv.rangesMap']['cadastro-via-edital'];
        $registration->status = Registration::STATUS_DRAFT;

        if ($row->ponto_tipo == $app->config['rcv.categoriesMap']['ponto-coletivo']) {
            $registration->proponentType = 'Coletivo';
        } else {
            $registration->proponentType = 'Pessoa Jurídica';
        }

        $registration->save();

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
       
        $type_selected = self::getTypeDict($row->ponto_tipo);
        $organization = new Agent();
        $organization->type = 2;
        $organization->parent = $owner;
        $organization->name = $row->organizacao_nome;
        $organization->cnpj = $row->organizacao_cnpj;
        $organization->telefonePublico = $row->organizacao_telefone;
        $organization->En_Estado = $row->ponto_uf;
        $organization->En_Municipio = $row->ponto_municipio;
        $organization->rcv_tipo = "ponto";
        $organization->tipoPonto = [$type_selected];
        $organization->save();

        return $organization;
    }

    /** 
     * Cria o responsável do ponto na plataforma
     * @param object $row
     * @return Agent
     */
    static function createOrganizationOwner(object $row): Agent {
        // Usuário vinculado ao responsável (authUid por e-mail)
        $user = new User();
        $user->email = $row->responsavel_email;
        $user->authProvider = "0";
        $user->authUid = $row->responsavel_email;
        $user->save();

        $owner = new Agent();
        $owner->user = $user;
        $owner->type = 1; // @todo: confirmar persistência do campo type
        $owner->name = $row->responsavel_nome;
        $owner->cpf = $row->responsavel_cpf;
        $owner->emailPrivado = $row->responsavel_email;
        $owner->telefonePrivado = $row->responsavel_telefone;
        $owner->save();

        $user->profile = $owner;
        $user->save();

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
     * @deprecated Substituído por resolveRowScenario() (Fase A) + applyScenario() (Fase B)
     */
    public static function processRow(object $row, Registration $pnab_registration) {
        $app = App::i();

        $result = [
            'registration_id' => null,
            'organization' => null,
            'owner'        => null,
            'scenario'     => null
        ];

        $scenario = null;
    
        $row->responsavel_cpf = Utils::formatCnpjCpf(trim($row->responsavel_cpf));
        $row->organizacao_cnpj = $row->organizacao_cnpj ? Utils::formatCnpjCpf(trim($row->organizacao_cnpj)) : null;
        $row->organizacao_nome = trim($row->organizacao_nome);

        foreach ($row as $key => $value) {
            if (is_string($value) && preg_match('/\r\n|\r|\n/', $value)) {
                $row->$key = preg_replace('/\r\n|\r|\n/', ' ', $value);
            }
        }

        if ($organization = self::findOrganizationFromOtherAgent($row)) {
            $app->log->debug("Cenário 5 - encontrou a organização e compara o CPF do parent com o CPF da planilha");

            $result['organization'] = $organization;
            $registration = $organization->rcv_registration;
            $scenario = 5;
        } else if ($registration = self::findRegistrationByRow($row)) {
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
            $organization->rcv_registration = $registration;
            $organization->save();
            $result['organization'] = $organization;
            $result['owner'] = $owner;
            $scenario = 3.1;
        } else {
            $app->log->debug("Cenário 4 - não encontrou nada, cria o proprietário, a organização e a inscrição");

            $owner = self::createOrganizationOwner($row);
            $organization = self::createOrganization($row, $owner);
            $registration = self::createRegistration($row, $organization);
            $organization->rcv_registration = $registration;
            $organization->save();
            $result['organization'] = $organization;
            $result['owner'] = $owner;
            $scenario = 4;
        }

        if($scenario != 5) {
            if($seal = $app->repo('Seal')->find($app->config['rcv.importerSeal'])) {
                $has_seal = false;
                $has_waiting_update_seal = false;
    
                $organization = $scenario == 1 ? $app->repo('Agent')->find($registration->relatedAgents['coletivo'][0]->id) : $organization;
                
                if(!in_array($scenario, [3.1, 4])) {
                    $type_selected = self::getTypeDict($row->ponto_tipo);

                    $organization->tipoPonto = Importer::ensureTypeInArray($type_selected, $organization->tipoPonto);
                    $organization->save();
                }
                
                $waiting_update_seal = $app->repo('Seal')->find($app->config['rcv.waitingUpdateSeal']);

                $seal_relations = $organization->getSealRelations();
    
                foreach($seal_relations as $seal_relation) {
                    if($seal_relation->seal->id == $waiting_update_seal->id) {
                        $has_waiting_update_seal = true;
                    }

                    if($seal_relation->seal->id == $seal->id) {
                        $has_seal = true;
                    }
                }
    
                // Selo de certificação (via edital)
                if(!$has_seal) {
                    $organization->createSealRelation($seal, agent: $organization);
                }

                // Selo de aguardando atualização (quando a inscrição foi criada/atualizada pela importação)
                if($scenario != 1 && !$has_waiting_update_seal) {
                    $organization->createSealRelation($waiting_update_seal, agent: $organization);
                }
            }
    
            if(!$registration->sentTimestamp) {
                $registration->sentTimestamp = new \DateTime;
            }

            $registration->setStatusToApproved(false);

            // Para inscrições criadas pela importação, usa a data da planilha na relação de verificação
            if($scenario != 1) {
                $relations = $organization->getSealRelations();
                $seals_ids = [
                    $app->config['rcv.verificationSeals']['ponto'],
                    $app->config['rcv.verificationSeals']['pontao']
                ];
    
                foreach($relations as $relation) {
                    if(in_array($relation->seal->id, $seals_ids)) {
                        $relation->createTimestamp = DateTime::createFromFormat(Utils::detectDateFormat($row->ponto_data), $row->ponto_data);
                        $relation->save();
                    }
                }
            }

            // Guarda contexto da importação na inscrição
            $registration->rcv_importer_row = [
                'data' => (array) $row,
                'scenario' => $scenario
            ];
            $registration->rcv_pnab_registration = $pnab_registration;
            $registration->save();
        }       

        $result['registration_id'] = $scenario == 5 ? null : $registration->id;
        $result['scenario']        = $scenario;
        $result['category']        = $row->ponto_tipo;
        $result['email']           = $row->responsavel_email;
        $result['userName']        = $row->responsavel_nome;

        return $result;
    }

    public static function validateRow(object $row, $line): array {
        $errors = [];

        foreach (self::$column_mapping as $key => $label) {
            $field_value = trim($row->{$key} ?? '');

            $cnpj_required = false;

            if ($key == 'ponto_tipo') {
                $parsed_category = self::parseCategory($field_value);

                if (empty($parsed_category)) {
                    $errors[] = "Linha: {$line} - Campo '{$label}' inválido.";
                    continue;
                }

                if (empty($field_value)) {
                    $errors[] = "Linha: {$line} - Campo '{$label}' obrigatório.";
                    continue;
                }

                // A chave da categoria vem do categoriesMap (fonte de verdade para rótulos/códigos)
                $app = App::i();
                $category = array_search($parsed_category, (array) $app->config['rcv.categoriesMap'], true);
                if ($category === false) {
                    $category = '';
                }

                // Coletivo é definido pelo valor canônico do mapa
                $coletivo_canon     = (string) ($app->config['rcv.categoriesMap']['ponto-coletivo'] ?? '');
                $is_ponto_coletivo = ((string) $parsed_category === $coletivo_canon);

                $raw_cnpj = trim((string) ($row->organizacao_cnpj ?? ''));
                if ($is_ponto_coletivo) {
                    if ($raw_cnpj !== '') {
                        $errors[] = "Linha: {$line} - Campo 'CNPJ' deve ficar vazio para a categoria {$category}.";
                    }
                } else {
                    $cnpj_required = true;
                    $formatted_cnpj = $raw_cnpj !== '' ? Utils::formatCnpjCpf($raw_cnpj) : '';

                    if ($formatted_cnpj === '') {
                        $errors[] = "Linha: {$line} - Campo 'CNPJ' obrigatório para a categoria {$category}.";
                    } elseif (!v::cnpj()->validate($formatted_cnpj)) {
                        $errors[] = "Linha: {$line} - CNPJ ({$raw_cnpj}) inválido.";
                    }
                }

            } elseif ($key == 'responsavel_cpf') {
                 if (empty($row->responsavel_cpf)) {
                    $errors[] = "Linha: {$line} - Campo 'CPF' obrigatório.";
                } elseif (!v::cpf()->validate($row->responsavel_cpf)) {
                    $errors[] = "Linha: {$line} - Campo 'CPF' inválido.";
                }
            } elseif (in_array($key, ['organizacao_cnpj', 'ponto_tipo'])) {
                continue; // Já validado acima
            } elseif ($key == 'responsavel_email') {
                if (empty($row->responsavel_email)) {
                    $errors[] = "Linha: {$line} - Campo 'Email do responsável' obrigatório.";
                } elseif (preg_match('/[A-Z]/', $field_value)) {
                    $errors[] = "Linha: {$line} - Campo 'Email do responsável' inválido. Não use letras maiúsculas.";
                } elseif (!v::email()->validate($field_value)) {
                    $errors[] = "Linha: {$line} - Campo 'Email do responsável' inválido.";
                }
            } elseif ($key == 'organizacao_email') {
                if (empty($row->organizacao_email)) {
                    $errors[] = "Linha: {$line} - Campo 'Email da organização' obrigatório.";
                } elseif (preg_match('/[A-Z]/', $field_value)) {
                    $errors[] = "Linha: {$line} - Campo 'Email da organização' inválido. Não use letras maiúsculas.";
                } elseif (!v::email()->validate($field_value)) {
                    $errors[] = "Linha: {$line} - Campo 'Email da organização' inválido.";
                }
            } elseif($key == 'ponto_data') {
                if (empty($row->ponto_data)) {
                    $errors[] = "Linha: {$line} - Campo 'Data da Certificação' obrigatório.";
                } elseif(!Utils::detectDateFormat($row->ponto_data)) {
                    $errors[] = "Linha: {$line} - Campo 'Data da Certificação' inválido.";
                }
            } elseif($key == 'organizacao_telefone') {
                if(empty($row->organizacao_telefone)) {
                    $errors[] = "Linha: {$line} - Campo 'Telefone da organização' obrigatório.";
                } elseif(!v::brPhone()->validate($row->organizacao_telefone)) {
                    $errors[] = "Linha: {$line} - Campo 'Telefone da organização' inválido.";
                }
            } elseif($key == 'responsavel_telefone') {
                if(empty($row->responsavel_telefone)) {
                    $errors[] = "Linha: {$line} - Campo 'Telefone do responsável' obrigatório.";
                } elseif(!v::brPhone()->validate($row->responsavel_telefone)) {
                    $errors[] = "Linha: {$line} - Campo 'Telefone do responsável' inválido.";
                }
            } elseif($key == 'organizacao_nome') {
                if(empty($row->organizacao_nome)) {
                    $errors[] = "Linha: {$line} - Campo 'Nome da organização' obrigatório.";
                }
            } elseif($key == 'responsavel_nome') {
                if(empty($row->responsavel_nome)) {
                    $errors[] = "Linha: {$line} - Campo 'Nome do responsável' obrigatório.";
                }
            } elseif($key == 'ponto_uf') {
                $uf = $field_value;

                if ($uf === '') {
                    $errors[] = "Linha: {$line} - Campo 'Estado' obrigatório.";
                } elseif (strlen($uf) !== 2) {
                    $errors[] = "Linha: {$line} - Campo 'Estado' inválido. Deve conter a sigla do estado (2 letras).";
                } elseif ($uf !== strtoupper($uf)) {
                    $errors[] = "Linha: {$line} - Campo 'Estado' inválido. Use a sigla em caixa alta (ex.: SP).";
                } elseif (!in_array($uf, self::BR_UFS, true)) {
                    $errors[] = "Linha: {$line} - Campo 'Estado' inválido. Informe uma UF brasileira válida (ex.: SP, RJ, DF).";
                }
            } elseif($key == 'ponto_municipio') {
                if(empty($row->ponto_municipio)) {
                    $errors[] = "Linha: {$line} - Campo 'Município' obrigatório.";
                }
            } else {
                if (empty($field_value)) {
                    $errors[] = "Linha: {$line} - Campo '{$label}' obrigatório.";
                }
            }
        }

        return array_filter($errors);
    }

    /**
     * @deprecated Substituído por buildExecutionPlan() + applyExecutionPlan() + sendEmails()
     *             com orquestração em ImportRegistrationsJob::_execute()
     */
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
        $data_range = array_values(array_filter(array_map('array_filter', $data_range)));

        $result = [
            'registration' => $registration,
            'total_rows'   => 0,
            'success_rows' => 0
        ];

        $total_file_rows = count($data_range);

        foreach ($data_range as $index => $row) {
            $app->em->clear();
            $parsed_row = null;
            $process_row = null;
            
            $line = $index + 1;

            $app->log->debug("=========================================================");

            $result['total_rows']++;

            try {
                $parsed_row = Importer::parseRow($header, $row);
                $process_row = Importer::processRow($parsed_row, $registration);
                $result['data_rows'][] = $process_row;
                $result['success_rows']++;

                $log = "{$registration->id} - Linha {$line} importada com sucesso.";
                $app->log->info($log);

                $log_file = "[{$line}/{$total_file_rows}] Cenário {$process_row['scenario']} importado com sucesso";
                Importer::generateImporterLog($registration, $log_file);

                $percentage = number_format(($line / $total_file_rows) * 100, 1) . '%';
                Importer::updateImporterStatusFile($registration, [
                    'status' => 1,
                    'message' => $percentage,
                    'timestamp' => date('d-m-Y H:i:s')
                ]);
            } catch (\Throwable $e) {
                $error_class = get_class($e);
                $error_trace = json_encode($e->getTrace());

                $log = "Erro ao importar a linha {$line}: ({$error_class}) " . $e->getMessage();
                $log_error_trace = "Rastreamento de erro: {$error_trace}";
                $app->log->error($log);
                $app->log->error($log_error_trace);

                $scenario = $process_row['scenario'] ?? 'desconhecido';

                $log_file = "[{$line}/{$total_file_rows}] Cenário {$scenario} ERRO";
                Importer::generateImporterLog($registration, $log_file);
                Importer::generateImporterLog($registration, $e->getMessage());
                Importer::generateImporterLog($registration, $e->getTraceAsString());

                Importer::updateImporterStatusFile($registration, [
                    'status' => 2,
                    'message' => $e->getMessage(),
                    'timestamp' => date('d-m-Y H:i:s')
                ]);
                
                $app->enableAccessControl();
                throw $e;
            }
            $app->em->flush();
        }

        $app->enableAccessControl();
        $app->log->info("Importação concluída para a inscrição {$registration->id}.");

        Importer::generateImporterLog($registration, 'Importação concluída com sucesso');

        Importer::updateImporterStatusFile($registration, [
            'status' => 10,
            'message' => 'Importado com sucesso',
            'timestamp' => date('d-m-Y H:i:s')
        ]);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Fase B: aplicação do plano dentro da transação
    // -------------------------------------------------------------------------

    /**
     * Executa os writes que foram adiados na Fase A (campo 'deferred' do RowDecision).
     * Deve rodar antes da aplicação do cenário.
     *
     * @param array $decision RowDecision (por referência)
     * @return void
     */
    private static function applyDeferredWrites(array &$decision): void {
        $app = App::i();

        // Limpa CPF de agentes desativados identificados na Fase A
        foreach ($decision['deferred']['clear_cpf_agent_ids'] as $agent_id) {
            $agent = self::findEntityById('Agent', $agent_id);
            if (!$agent) {
                throw new \Exception("Agente {$agent_id} não encontrado para limpeza de CPF durante a importação.");
            }

            $app->disableAccessControl();
            $agent->cpf = null;
            $agent->save();
            $app->enableAccessControl();
        }

        // Completa CNPJ na organização quando estava ausente (cenário 2)
        if ($decision['deferred']['set_org_cnpj'] && !$decision['organization_id']) {
            throw new \Exception('Organização ausente para atualização de CNPJ durante a importação.');
        }

        if ($decision['deferred']['set_org_cnpj']) {
            $organization = self::findEntityById('Agent', $decision['organization_id']);
            if (!$organization) {
                throw new \Exception("Organização {$decision['organization_id']} não encontrada para atualização de CNPJ durante a importação.");
            }

            $app->disableAccessControl();
            $organization->cnpj = $decision['deferred']['set_org_cnpj'];
            $organization->save();
            $app->enableAccessControl();
        }
    }

    /**
     * Aplica a decisão da linha: cria entidades quando necessário, aplica selos e aprova inscrição.
     * Esta função parte do RowDecision; não executa novas buscas.
     *
     * @param array         $decision      RowDecision (por referência, para preencher dados de email quando necessário)
     * @param \MapasCulturais\Entities\Seal $importer_seal
     * @param \MapasCulturais\Entities\Seal $waiting_seal
     * @param Registration  $pnab_reg      inscrição PNAB original
     * @return void
     */
    private static function applyScenario(
        array &$decision,
        $importer_seal,
        $waiting_seal,
        Registration $pnab_reg
    ): void {
        $app      = App::i();
        $row      = $decision['row'];
        $scenario = $decision['scenario'];
        $registration = self::findEntityById('Registration', $decision['registration_id'] ?? null);
        $organization = self::findEntityById('Agent', $decision['organization_id'] ?? null);
        $owner = self::findEntityById('Agent', $decision['owner_id'] ?? null);

        // Cenário 5 não altera dados; apenas notificação
        if ($scenario == 5) {
            return;
        }

        if ($scenario == 1 && (!$registration || !$organization)) {
            throw new \Exception('Inscrição ou organização do cenário 1 não encontrada durante a importação.');
        }

        if ($scenario == 2 && !$organization) {
            throw new \Exception('Organização do cenário 2 não encontrada durante a importação.');
        }

        if ($scenario == 3.1 && !$owner) {
            throw new \Exception('Responsável do cenário 3.1 não encontrado durante a importação.');
        }

        // Cenário 2: organização existe, criar inscrição
        if ($scenario == 2) {
            $registration = self::createRegistration($row, $organization);
            $decision['registration_id'] = $registration->id;
            $decision['email']['registration_id']     = $registration->id;
            $decision['email']['registration_number'] = $registration->number;
        }

        // Cenário 3.1: owner existe, criar organização e inscrição
        if ($scenario == 3.1) {
            $organization = self::createOrganization($row, $owner);
            $registration = self::createRegistration($row, $organization);
            $organization->rcv_registration = $registration;
            $organization->save();
            $decision['organization_id'] = $organization->id;
            $decision['registration_id'] = $registration->id;
            $decision['email']['registration_id']     = $registration->id;
            $decision['email']['registration_number'] = $registration->number;
        }

        // Cenário 4: criar tudo
        if ($scenario == 4) {
            $owner        = self::createOrganizationOwner($row);
            $organization = self::createOrganization($row, $owner);
            $registration = self::createRegistration($row, $organization);
            $organization->rcv_registration = $registration;
            $organization->save();
            $decision['owner_id']        = $owner->id;
            $decision['organization_id'] = $organization->id;
            $decision['registration_id'] = $registration->id;
            $decision['email']['user_name']           = $owner->name;
            $decision['email']['registration_id']     = $registration->id;
            $decision['email']['registration_number'] = $registration->number;
        }

        // Selos e aprovação: cenários 1, 2, 3.1, 4

        // No cenário 1, recarrega a organização a partir da relação da inscrição
        if ($scenario == 1) {
            $organization = $app->repo('Agent')->find($registration->relatedAgents['coletivo'][0]->id);
        }

        // tipoPonto: atualiza somente quando a organização já existia antes desta importação
        if (!in_array($scenario, [3.1, 4])) {
            $type_selected            = self::getTypeDict($row->ponto_tipo);
            $organization->tipoPonto  = self::ensureTypeInArray($type_selected, $organization->tipoPonto);
            $organization->save();
        }

        // Aplica selos somente quando existirem na base (mantém o guard do fluxo antigo)
        if ($importer_seal) {
            $has_seal = false;
            foreach ($organization->getSealRelations() as $sr) {
                if ($sr->seal->id == $importer_seal->id) { $has_seal = true; break; }
            }
            if (!$has_seal) {
                $organization->createSealRelation($importer_seal, agent: $organization);
            }
        }
        if ($waiting_seal) {
            $has_waiting_seal = false;
            foreach ($organization->getSealRelations() as $sr) {
                if ($sr->seal->id == $waiting_seal->id) { $has_waiting_seal = true; break; }
            }
            if ($scenario != 1 && !$has_waiting_seal) {
                $organization->createSealRelation($waiting_seal, agent: $organization);
            }
        }

        // sentTimestamp e status da inscrição
        if (!$registration->sentTimestamp) {
            $registration->sentTimestamp = new \DateTime;
        }
        $registration->setStatusToApproved(false);

        // Atualiza o timestamp das relações de verificação (cenários != 1)
        if ($scenario != 1) {
            $verification_ids = [
                $app->config['rcv.verificationSeals']['ponto'],
                $app->config['rcv.verificationSeals']['pontao'],
            ];
            foreach ($organization->getSealRelations() as $sr) {
                if (in_array($sr->seal->id, $verification_ids)) {
                    $sr->createTimestamp = \DateTime::createFromFormat(
                        Utils::detectDateFormat($row->ponto_data),
                        $row->ponto_data
                    );
                    $sr->save();
                }
            }
        }

        // Metadados da inscrição
        $registration->rcv_importer_row = [
            'data'     => (array) $row,
            'scenario' => $scenario,
        ];
        $registration->rcv_pnab_registration = $pnab_reg;
        $registration->save();
    }

    /**
     * Aplica o plano de execução dentro da transação (já aberta pelo job).
     * Não executa find*(); trabalha apenas com as decisões geradas na Fase A.
     *
     * @param array $plan ExecutionPlan produzido por buildExecutionPlan()
     * @return array o mesmo plan, enriquecido com dados gerados na aplicação (ex.: ids/números)
     */
    public static function applyExecutionPlan(array $plan): array {
        $app   = App::i();
        $total = $plan['total_rows'];

        $pnab_reg = self::findEntityById('Registration', $plan['pnab_registration_id'] ?? self::entityId($plan['pnab_registration']));
        if (!$pnab_reg) {
            throw new \Exception('Inscrição PNAB não encontrada durante a aplicação da importação.');
        }

        $importer_seal = $app->repo('Seal')->find($app->config['rcv.importerSeal']);
        $waiting_seal  = $app->repo('Seal')->find($app->config['rcv.waitingUpdateSeal']);

        foreach ($plan['rows'] as $i => &$decision) {
            $line = $decision['line'];

            try {
                // Ajustes diferidos da Fase A
                self::applyDeferredWrites($decision);

                // Aplicação do cenário
                self::applyScenario($decision, $importer_seal, $waiting_seal, $pnab_reg);

                // Persistência por linha
                $app->em->flush();

                // Progresso
                $percentage = number_format((($i + 1) / $total) * 100, 1) . '%';
                self::generateImporterLog($pnab_reg,
                    "[{$line}/{$total}] Cenário {$decision['scenario']} importado com sucesso");
                self::updateImporterStatusFile($pnab_reg, [
                    'status'    => 1,
                    'message'   => $percentage,
                    'timestamp' => date('d-m-Y H:i:s'),
                ]);

                // Compacta o decision para reduzir memória (Fase C só precisa de scenario + email + line).
                // Evita manter em RAM: row, entidades Doctrine e estruturas de deferred.
                $decision = [
                    'line'     => $line,
                    'scenario' => $decision['scenario'] ?? null,
                    'email'    => $decision['email'] ?? [],
                ];

                $app->em->clear();
                $pnab_reg = self::findEntityById('Registration', $plan['pnab_registration_id']);
                if (!$pnab_reg) {
                    throw new \Exception('Inscrição PNAB não encontrada durante a importação.');
                }
                $importer_seal = $app->repo('Seal')->find($app->config['rcv.importerSeal']);
                $waiting_seal  = $app->repo('Seal')->find($app->config['rcv.waitingUpdateSeal']);

            } catch (\Throwable $e) {
                self::generateImporterLog($pnab_reg,
                    "[{$line}/{$total}] Cenário {$decision['scenario']} ERRO: " . $e->getMessage());
                throw $e;
            }
        }

        $plan['pnab_registration'] = self::findEntityById('Registration', $plan['pnab_registration_id']);

        return $plan;
    }

    // -------------------------------------------------------------------------
    // Fase C: envio de emails pós-commit
    // -------------------------------------------------------------------------

    /**
     * Envia e-mails de notificação a partir do plano já aplicado.
     *
     * @param array $plan ExecutionPlan produzido por buildExecutionPlan() e aplicado por applyExecutionPlan()
     * @return void
     * @deprecated Assinatura antiga (array $data) substituída por (array $plan)
     */
    public static function sendEmails(array $plan): void {
        $app = App::i();

        foreach ($plan['rows'] as $decision) {
            $e        = $decision['email'];
            $scenario = $decision['scenario'];

            switch ($scenario) {
                case 1:
                    $template = 'primeiro_caso.html';
                    $template_data = [
                        'siteName'           => $app->siteName,
                        'userName'           => $e['user_name'],
                        'registrationNumber' => $e['registration_number'],
                        'redirectUrl'        => $app->createUrl('registration', 'single', [$e['registration_id']]),
                        'organizationName'   => $e['organization_name'],
                        'organizationCNPJ'   => $e['organization_cnpj'],
                        'type'               => $e['category'],
                    ];
                    $subject = "[Cultura Viva] Sua organização {$e['organization_name']} foi certificada por um Edital de Seleção da Cultura Viva.";
                    break;

                case 2:
                    $template = 'segundo_caso.html';
                    $template_data = [
                        'siteName'         => $app->siteName,
                        'userName'         => $e['user_name'],
                        'redirectUrl'      => $app->createUrl('site/atualizacao-cadastral'),
                        'organizationName' => $e['organization_name'],
                        'organizationCNPJ' => $e['organization_cnpj'],
                        'type'             => $e['category'],
                    ];
                    $subject = "[Cultura Viva] Sua organização {$e['organization_name']} foi certificada por um Edital de Seleção da Cultura Viva.";
                    break;

                case 3.1:
                    $template = 'terceiro_caso.html';
                    $template_data = [
                        'siteName'         => $app->siteName,
                        'userName'         => $e['user_name'],
                        'redirectUrl'      => $app->createUrl('site/atualizacao-cadastral'),
                        'organizationName' => $e['organization_name'],
                        'organizationCNPJ' => $e['organization_cnpj'],
                        'type'             => $e['category'],
                    ];
                    $subject = "[Cultura Viva] Sua organização {$e['organization_name']} foi certificada por um Edital de Seleção da Cultura Viva.";
                    break;

                case 4:
                    $template = 'quarto_caso.html';
                    $template_data = [
                        'siteName'         => $app->siteName,
                        'userName'         => $e['user_name'],
                        'redirectUrl'      => $app->createUrl('site/atualizacao-cadastral'),
                        'organizationName' => $e['organization_name'],
                        'organizationCNPJ' => $e['organization_cnpj'],
                        'type'             => $e['category'],
                    ];
                    $subject = "[Cultura Viva] Sua organização {$e['organization_name']} foi certificada por um Edital de Seleção da Cultura Viva.";
                    break;

                case 5:
                    $template = 'quinto_caso.html';
                    $template_data = [
                        'siteName'         => $app->siteName,
                        'userName'         => $e['user_name'],
                        'redirectUrl'      => $app->createUrl('site/atualizacao-cadastral'),
                        'organizationName' => $e['organization_name'],
                        'organizationCNPJ' => $e['organization_cnpj'],
                        'type'             => $e['category'],
                    ];
                    $subject = "[Cultura Viva] Sua organização {$e['organization_name']} foi certificada por um Edital de Seleção da Cultura Viva.";
                    break;

                default:
                    continue 2;
            }

            $app->createAndSendMailMessage([
                'to'      => $e['to'],
                'subject' => $subject,
                'body'    => $app->renderMustacheTemplate($template, $template_data),
            ]);
        }

        // Resumo para o gestor (dono da inscrição PNAB)
        $pnab    = $plan['pnab_registration'];
        $success = count(array_filter($plan['rows'], fn($d) => $d['scenario'] !== null));

        $app->createAndSendMailMessage([
            'to'      => $pnab->owner->emailPrivado,
            'subject' => '[Cultura Viva] Importação realizada com sucesso',
            'body'    => $app->renderMustacheTemplate('import_summary.html', [
                'siteName'           => $app->siteName,
                'userName'           => $pnab->owner->name,
                'registrationNumber' => $pnab->number,
                'totalRows'          => $plan['total_rows'],
                'successRows'        => $success,
            ]),
        ]);
    }

    /**
     * Envia um e-mail de erro para o responsável pela inscrição.
     */
    public static function sendEmailError(Registration $registration, $error) {
        $app = App::i();

        $error_message = $error instanceof \Throwable
            ? $error->getMessage()
            : (string) $error;

        $template_data = [
            'siteName'           => $app->siteName,
            'userName'           => $registration->owner->name,
            'registrationNumber' => $registration->number,
            'redirectUrl'        => $app->createUrl('registration', 'single', [$registration->id]),
            'errorMessage'       => $error_message,
        ];

        $to      = $registration->owner->emailPrivado;
        $subject = "[Cultura Viva] A importação da planilha falhou";
        $body    = $app->renderMustacheTemplate('import_error.html', $template_data);

        $app->createAndSendMailMessage([
            'to'      => $to,
            'subject' => $subject,
            'body'    => $body
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

            // Natureza jurídica fora da lista permitida
            if (!in_array($legal_nature_code, $allowed_legal_natures)) {
                $result['error'] = true;
                $result['message'] = 'A natureza jurídica do CNPJ é inválida.';
            }
        }

        return $result;
    }

    public static function generateImporterLog(Registration $registration, $log_message) {
        $dir_path = PUBLIC_PATH . 'files/importer/';
        $log_path = $dir_path . $registration->id . '.log';

        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0755, true);
        }

        $log = date('Y-m-d H:i:s') . " " . $log_message . "\n";

        file_put_contents($log_path, $log, FILE_APPEND);
    }

    public static function updateImporterStatusFile(Registration $registration, array $status) {
        $dir_path = PUBLIC_PATH . 'files/importer/';
        $status_file_path = $dir_path . $registration->id . '_status.json';

        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0755, true);
        }

        file_put_contents($status_file_path, json_encode($status, JSON_PRETTY_PRINT));
    }

    public static function getTypeDict(string $type): string {
        $app = App::i();
        $categories_map = $app->config['rcv.categoriesMap'];

        $type_dict = [
            'ponto-coletivo' => 'ponto_coletivo',
            'ponto-entidade' => 'ponto_entidade',
            'pontao' => 'pontao'
        ];

        $type_selected = array_search($type, $categories_map);
        $type_selected = $type_dict[$type_selected];
        return $type_selected;
    }

    public static function ensureTypeInArray(string $type_selected, ?array $tipo_ponto = null): array {
        // Evita duplicar o tipo
        if($tipo_ponto && is_array($tipo_ponto) && !in_array($type_selected, $tipo_ponto)) {
            $tipo_ponto[] = $type_selected;
        } else if($tipo_ponto && !is_array($tipo_ponto)) {
            $tipo_ponto = [$tipo_ponto];

            if(!in_array($type_selected, $tipo_ponto)) {
                $tipo_ponto[] = $type_selected;
            }
            
        } else {
            $tipo_ponto = [$type_selected];
        }

        return $tipo_ponto;
    }
}
