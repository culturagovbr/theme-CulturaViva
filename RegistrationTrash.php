<?php

namespace CulturaViva;

use MapasCulturais\App;
use MapasCulturais\i;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Entities\User;
use MapasCulturais\Exceptions\PermissionDenied;
use MapasCulturais\UserInterface;
use Opportunities\Jobs\RedistributeCommitteeRegistrations;
use Opportunities\Jobs\UpdateSummaryCaches;

// Lixeira de inscrições (status -10): ferramenta do painel e proteções
final class RegistrationTrash
{
    public const STATUS = -10;

    // metadado da inscrição com o backup de cada envio para a lixeira
    public const META = 'rcv_lixeira';

    public const MAX_NUMBERS = 200;
    public const MIN_REASON_LENGTH = 10;

    // listagem da lixeira
    public const LIST_LIMIT = 50;
    public const LIST_MAX_LIMIT = 100;
    public const LIST_FILTERS = ['todas', 'restauraveis', 'sem_backup'];

    // cabe uma lista colada de até MAX_NUMBERS números
    public const MAX_SEARCH_LENGTH = 4000;

    public const ROLE = 'saasSuperAdmin';

    // nomes usados no Cadastro Nacional (mesmos do hook statusesNames do tema)
    private const STATUS_NAMES = [10 => 'Habilitado', 3 => 'Inabilitado'];

    // situações da análise
    public const BLOCKED = 'bloqueada';
    public const WARNING = 'aviso';
    public const ALLOWED = 'liberada';

    private const REGISTRATION_CLASS = 'MapasCulturais\\Entities\\Registration';
    private const AGENT_CLASS = 'MapasCulturais\\Entities\\Agent';

    public static function register(): void
    {
        self::registerSummaryFilter();
        self::registerGuards();
        self::registerRoutes();
    }

    private static function registerSummaryFilter(): void
    {
        $em = App::i()->em;
        $config = $em->getConfiguration();

        if (!$config->getFilterClassName(RegistrationTrashSummaryFilter::NAME)) {
            $config->addFilter(RegistrationTrashSummaryFilter::NAME, RegistrationTrashSummaryFilter::class);
        }

        $em->getFilters()->enable(RegistrationTrashSummaryFilter::NAME);
    }

    private static function registerGuards(): void
    {
        $app = App::i();

        // não permite criar, salvar ou enviar avaliação de inscrição na lixeira
        $app->hook('entity(RegistrationEvaluation).save:before', function () use ($app) {
            /** @var \MapasCulturais\Entities\RegistrationEvaluation $this */
            $registration = $this->registration;

            // leitura direta: com ?? o proxy não inicializado da inscrição devolve null
            if ($registration && self::isTrashed($registration->status)) {
                throw new PermissionDenied($app->user, $registration, 'evaluate', 'Esta inscrição foi removida e não pode mais ser avaliada.');
            }
        });

        // não permite que a inscrição saia da lixeira pela entidade (ex.: aplicação automática do resultado)
        // save:requests roda antes do save gravar o status no banco
        $app->hook('entity(Registration).save:requests', function () use ($app) {
            /** @var Registration $this */
            $original = $app->em->getUnitOfWork()->getOriginalEntityData($this);

            if (self::blocksStatusChange($original['status'] ?? null, $this->status)) {
                throw new PermissionDenied($app->user, $this, 'changeStatus', "A inscrição {$this->number} está na lixeira e não pode ter o status alterado.");
            }
        });

        // após cada redistribuição, remove avaliadores atribuídos a inscrições na lixeira
        $app->hook('job(' . RedistributeCommitteeRegistrations::SLUG . ').execute:after', function () use ($app) {
            /** @var \MapasCulturais\Entities\Job $this */
            $opportunity = $this->evaluationMethodConfiguration->opportunity ?? null;

            if ($opportunity && ($count = self::clearTrashedValuers($opportunity->id))) {
                $app->log->debug("CulturaViva: removidos avaliadores de {$count} inscrições na lixeira da fase {$opportunity->id}");
            }
        });
    }

    private static function registerRoutes(): void
    {
        $app = App::i();

        $app->hook('GET(panel.rcv-lixeira)', function () use ($app) {
            /** @var \MapasCulturais\Controllers\Panel $this */
            $this->requireAuthentication();

            if (!self::canManage($app->user)) {
                $app->halt(403, i::__('Acesso restrito.'));
            }

            $this->render('rcv-lixeira');
        });

        $app->hook('POST(site.rcv-lixeira-analisar)', function () {
            /** @var \MapasCulturais\Controllers\Site $this */
            self::requireManager($this);

            $parsed = self::parseNumbers((string) ($this->data['numeros'] ?? ''));

            if (count($parsed['numbers']) > self::MAX_NUMBERS) {
                $this->errorJson(sprintf(i::__('Informe no máximo %d números por vez.'), self::MAX_NUMBERS), 400);
            }

            $this->json(['itens' => self::analyze($parsed['numbers']), 'invalidos' => $parsed['invalid']]);
        });

        $app->hook('POST(site.rcv-lixeira-enviar)', function () use ($app) {
            /** @var \MapasCulturais\Controllers\Site $this */
            self::requireManager($this);

            $parsed = self::parseNumbers((string) ($this->data['numeros'] ?? ''));
            $reason = trim((string) ($this->data['motivo'] ?? ''));

            if (!$parsed['numbers']) {
                $this->errorJson(i::__('Informe ao menos um número de inscrição.'), 400);
            }

            if (count($parsed['numbers']) > self::MAX_NUMBERS) {
                $this->errorJson(sprintf(i::__('Informe no máximo %d números por vez.'), self::MAX_NUMBERS), 400);
            }

            if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
                $this->errorJson(sprintf(i::__('Informe o motivo com pelo menos %d caracteres.'), self::MIN_REASON_LENGTH), 400);
            }

            $this->json(['itens' => self::trash($parsed['numbers'], $reason, $app->user)]);
        });

        $app->hook('POST(site.rcv-lixeira-restaurar)', function () use ($app) {
            /** @var \MapasCulturais\Controllers\Site $this */
            self::requireManager($this);

            $parsed = self::parseNumbers((string) ($this->data['numeros'] ?? ''));

            if (!$parsed['numbers']) {
                $this->errorJson(i::__('Informe ao menos um número de inscrição.'), 400);
            }

            if (count($parsed['numbers']) > self::MAX_NUMBERS) {
                $this->errorJson(sprintf(i::__('Informe no máximo %d números por vez.'), self::MAX_NUMBERS), 400);
            }

            $this->json(['itens' => self::restore($parsed['numbers'], $app->user)]);
        });

        $app->hook('POST(site.rcv-lixeira-restaurar-busca)', function () use ($app) {
            /** @var \MapasCulturais\Controllers\Site $this */
            self::requireManager($this);

            $search = self::normalizeListParams($this->data)['busca'];

            if ($search === '') {
                $this->errorJson(i::__('Informe uma busca para restaurar em lote.'), 400);
            }

            $this->json(self::restoreBySearch($search, $app->user));
        });

        $app->hook('POST(site.rcv-lixeira-listar)', function () {
            /** @var \MapasCulturais\Controllers\Site $this */
            self::requireManager($this);

            $this->json(self::listTrashed($this->data));
        });

        $app->hook('panel.nav', function (&$nav_items) use ($app) {
            if (!isset($nav_items['opportunities'])) {
                return;
            }

            $nav_items['opportunities']['items'][] = [
                'route' => 'panel/rcv-lixeira',
                'icon' => 'trash',
                'label' => i::__('Lixeira de inscrições'),
                'condition' => fn () => self::canManage($app->user),
            ];
        }, 20);
    }

    public static function requireManager($controller): void
    {
        $controller->requireAuthentication();

        if (!self::canManage(App::i()->user)) {
            $controller->errorJson(i::__('Acesso restrito.'), 403);
        }
    }

    public static function isTrashed($status): bool
    {
        return $status !== null && (int) $status === self::STATUS;
    }

    // bloqueia apenas a saída da lixeira
    public static function blocksStatusChange($original_status, $new_status): bool
    {
        return self::isTrashed($original_status) && !self::isTrashed($new_status);
    }

    // UserInterface porque o visitante é um GuestUser, não uma entidade User
    public static function canManage(?UserInterface $user): bool
    {
        if (!$user || $user->is('guest') || !$user->is(self::ROLE)) {
            return false;
        }

        return self::isAllowedUserId((int) $user->id, App::i()->config['rcv.lixeira.usuarios'] ?? '');
    }

    public static function isAllowedUserId(int $user_id, $allowed): bool
    {
        $allowed = is_array($allowed) ? $allowed : explode(',', (string) $allowed);
        $allowed = array_filter(array_map('trim', $allowed), fn ($id) => ctype_digit($id));

        return $user_id > 0 && in_array($user_id, array_map('intval', $allowed), true);
    }

    // aceita números separados por ;, vírgula, espaço ou quebra de linha, com ou sem o prefixo on-
    public static function parseNumbers(string $input): array
    {
        $numbers = [];
        $invalid = [];

        foreach (preg_split('/[\s;,]+/', $input, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            $digits = preg_match('/^(?:on-)?(\d+)$/i', $token, $match) ? ltrim($match[1], '0') : '';

            if ($digits !== '') {
                $numbers['on-' . $digits] = true;
            } else {
                $invalid[$token] = true;
            }
        }

        return ['numbers' => array_keys($numbers), 'invalid' => array_keys($invalid)];
    }

    public static function typeOfCategory(?string $category, array $categories_map): string
    {
        return $category !== null && $category === ($categories_map['pontao'] ?? null) ? 'pontao' : 'ponto';
    }

    /**
     * Classifica uma inscrição da análise
     *
     * @param array $item found, status, org_has_seal, other_certified_same_type, other_certified_any, pointer_to_this
     */
    public static function classify(array $item): array
    {
        if (empty($item['found'])) {
            return ['situacao' => self::BLOCKED, 'motivos' => ['nao_encontrada']];
        }

        if (self::isTrashed($item['status'] ?? null)) {
            return ['situacao' => self::BLOCKED, 'motivos' => ['ja_na_lixeira']];
        }

        $reasons = [];

        if ((int) $item['status'] === Registration::STATUS_APPROVED) {
            if (!empty($item['other_certified_same_type'])) {
                $reasons[] = 'certificacao_duplicada';
            } elseif (!empty($item['org_has_seal'])) {
                return ['situacao' => self::BLOCKED, 'motivos' => ['unica_certificacao']];
            } else {
                $reasons[] = 'certificada_sem_selo';
            }
        }

        if (!empty($item['pointer_to_this'])) {
            $reasons[] = 'ponteiro';
        }

        return ['situacao' => $reasons ? self::WARNING : self::ALLOWED, 'motivos' => $reasons];
    }

    /**
     * Escolhe a inscrição que passa a representar a organização
     *
     * @param array $candidates [['id', 'status', 'type', 'timestamp']] inscrições ativas da organização, fora do lote
     */
    public static function choosePointerTarget(array $candidates, string $type): ?int
    {
        $by_rank = [];

        foreach ($candidates as $candidate) {
            $approved = (int) $candidate['status'] === Registration::STATUS_APPROVED;

            if ($approved && $candidate['type'] === $type) {
                $rank = 0;
            } elseif ($approved) {
                $rank = 1;
            } else {
                $rank = 2;
            }

            $by_rank[$rank][] = $candidate;
        }

        if (!$by_rank) {
            return null;
        }

        ksort($by_rank);
        $best = reset($by_rank);
        usort($best, fn ($a, $b) => [$b['timestamp'] ?? '', $b['id']] <=> [$a['timestamp'] ?? '', $a['id']]);

        return (int) $best[0]['id'];
    }

    public static function statusName(int $status): ?string
    {
        return self::STATUS_NAMES[$status] ?? Registration::getStatusNameById($status);
    }

    public static function registrationReference(?int $id): ?string
    {
        return $id ? self::REGISTRATION_CLASS . ':' . $id : null;
    }

    public static function chainOpportunityIds(): array
    {
        $app = App::i();
        $main = (int) $app->config['rcv.opportunityId'];

        return array_map('intval', $app->em->getConnection()->fetchFirstColumn(
            "SELECT id FROM opportunity WHERE id = :main OR parent_id = :main",
            ['main' => $main]
        ));
    }

    /**
     * Analisa os números sem alterar nada
     */
    public static function analyze(array $numbers, bool $lock = false): array
    {
        if (!$numbers) {
            return [];
        }

        $app = App::i();
        $conn = $app->em->getConnection();
        $main = (int) $app->config['rcv.opportunityId'];
        $categories_map = $app->config['rcv.categoriesMap'] ?? [];
        $seals = $app->config['rcv.verificationSeals'] ?? [];
        $chain = self::chainOpportunityIds();

        $phases = $conn->fetchAllAssociative("
            SELECT r.id, r.number, r.opportunity_id, r.status, r.category, r.agent_id,
                   COALESCE(r.sent_timestamp, r.create_timestamp) AS timestamp
            FROM registration r
            WHERE r.number IN (:numbers) AND r.opportunity_id IN (:chain)
            ORDER BY r.opportunity_id
            " . ($lock ? 'FOR UPDATE' : ''),
            ['numbers' => $numbers, 'chain' => $chain],
            ['numbers' => \Doctrine\DBAL\ArrayParameterType::STRING, 'chain' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $by_number = [];
        foreach ($phases as $phase) {
            $by_number[$phase['number']][] = $phase;
        }

        $first_ids = [];
        foreach ($by_number as $number => $rows) {
            foreach ($rows as $row) {
                if ((int) $row['opportunity_id'] === $main) {
                    $first_ids[$number] = (int) $row['id'];
                }
            }
        }

        $all_ids = array_map(fn ($row) => (int) $row['id'], $phases);
        $batch_first_ids = array_values($first_ids);

        $orgs = self::fetchOrganizations($batch_first_ids);
        $owners = self::fetchNames(array_unique(array_map(fn ($row) => (int) $row['agent_id'], $phases)));
        $evaluations = self::fetchEvaluationCounts($all_ids);
        $org_ids = array_unique(array_filter(array_map(fn ($org) => $org['org_id'], $orgs)));
        $org_seals = self::fetchOrganizationSeals($org_ids, $seals);
        $org_registrations = self::fetchOrganizationRegistrations($org_ids, $main, $categories_map);

        $result = [];
        foreach ($numbers as $number) {
            $rows = $by_number[$number] ?? [];
            $first = null;
            foreach ($rows as $row) {
                if ((int) $row['opportunity_id'] === $main) {
                    $first = $row;
                }
            }

            if (!$first) {
                $result[] = ['numero' => $number] + self::classify(['found' => false]);
                continue;
            }

            $first_id = (int) $first['id'];
            $type = self::typeOfCategory($first['category'], $categories_map);
            $org = $orgs[$first_id] ?? null;
            $org_id = $org['org_id'] ?? null;

            $others = array_values(array_filter(
                $org_registrations[$org_id] ?? [],
                fn ($candidate) => !in_array((int) $candidate['id'], $batch_first_ids, true)
            ));

            $other_certified_same_type = (bool) array_filter($others, fn ($c) => (int) $c['status'] === Registration::STATUS_APPROVED && $c['type'] === $type);
            $pointer_to_this = $org && $org['rcv_registration'] === self::registrationReference($first_id);

            $classification = self::classify([
                'found' => true,
                'status' => (int) $first['status'],
                'org_has_seal' => in_array($type, $org_seals[$org_id] ?? [], true),
                'other_certified_same_type' => $other_certified_same_type,
                'pointer_to_this' => $pointer_to_this,
            ]);

            $pointer_target = $pointer_to_this ? self::choosePointerTarget($others, $type) : null;

            // sem outra inscrição ativa, o vínculo é removido
            if ($pointer_to_this && !$pointer_target) {
                $classification['motivos'] = array_map(fn ($reason) => $reason === 'ponteiro' ? 'ponteiro_removido' : $reason, $classification['motivos']);
            }

            $result[] = [
                'numero' => $number,
                'id' => $first_id,
                'status' => (int) $first['status'],
                'status_nome' => self::statusName((int) $first['status']),
                'categoria' => $first['category'],
                'tipo' => $type,
                'responsavel' => $owners[(int) $first['agent_id']] ?? null,
                'organizacao' => $org ? ['id' => $org_id, 'nome' => $org['name'], 'cnpj' => $org['cnpj']] : null,
                'fases' => array_map(fn ($row) => [
                    'id' => (int) $row['id'],
                    'oportunidade' => (int) $row['opportunity_id'],
                    'status' => (int) $row['status'],
                    'avaliacoes' => $evaluations[(int) $row['id']] ?? [],
                ], $rows),
                'ponteiro' => $pointer_to_this ? ['organizacao' => $org_id, 'novo' => $pointer_target] : null,
            ] + $classification;
        }

        return $result;
    }

    /**
     * Envia para a lixeira as inscrições liberadas da análise, nas duas fases
     */
    public static function trash(array $numbers, string $reason, User $user): array
    {
        $app = App::i();
        $conn = $app->em->getConnection();
        $now = date('Y-m-d H:i:s');
        $results = [];
        $trashed_opportunities = [];

        $conn->beginTransaction();
        try {
            foreach (self::analyze($numbers, true) as $item) {
                if ($item['situacao'] === self::BLOCKED) {
                    $results[] = ['numero' => $item['numero'], 'resultado' => 'ignorada', 'motivos' => $item['motivos']];
                    continue;
                }

                foreach ($item['fases'] as $phase) {
                    $record = self::trashPhase($phase['id'], $user, $reason, $now);

                    if ($phase['id'] === $item['id'] && $item['ponteiro']) {
                        $record['ponteiro'] = self::movePointer($item['ponteiro']['organizacao'], $item['id'], $item['ponteiro']['novo']);
                    }

                    self::saveRecord($phase['id'], $record);
                    $trashed_opportunities[$phase['oportunidade']] = true;
                }

                $results[] = ['numero' => $item['numero'], 'resultado' => 'enviada', 'motivos' => $item['motivos'], 'ponteiro' => $item['ponteiro']];
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        self::refreshSummaries(array_keys($trashed_opportunities));

        return $results;
    }

    private static function trashPhase(int $registration_id, User $user, string $reason, string $now): array
    {
        $conn = App::i()->em->getConnection();

        $registration = $conn->fetchAssociative(
            "SELECT status, valuers::text AS valuers, valuers_exceptions_list::text AS exceptions FROM registration WHERE id = ?",
            [$registration_id]
        );

        $evaluations = $conn->fetchAllAssociative(
            "SELECT * FROM registration_evaluation WHERE registration_id = ? AND status IN (0, 1) ORDER BY id",
            [$registration_id]
        );

        $valuers = json_decode($registration['valuers'] ?: '{}', true) ?: [];
        $exceptions = json_decode($registration['exceptions'] ?: '{}', true) ?: [];
        $evaluator_ids = array_unique(array_map('intval', array_merge(
            is_array($valuers) ? array_keys($valuers) : [],
            array_column($evaluations, 'user_id'),
            $exceptions['include'] ?? [],
            $exceptions['exclude'] ?? []
        )));

        $conn->executeStatement("
            UPDATE registration
            SET status = :status, valuers = '{}', valuers_exceptions_list = '{\"include\": [], \"exclude\": []}'
            WHERE id = :id
        ", ['status' => self::STATUS, 'id' => $registration_id]);

        if ($evaluations) {
            $conn->executeStatement(
                "DELETE FROM registration_evaluation WHERE id IN (:ids)",
                ['ids' => array_map('intval', array_column($evaluations, 'id'))],
                ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
            );
        }

        if ($evaluator_ids) {
            $conn->executeStatement(
                "DELETE FROM pcache WHERE object_type = :type AND object_id = :id AND user_id IN (:users)",
                ['type' => self::REGISTRATION_CLASS, 'id' => $registration_id, 'users' => $evaluator_ids],
                ['users' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
            );
        }

        return [
            'status' => (int) $registration['status'],
            'valuers' => $registration['valuers'],
            'valuers_exceptions_list' => $registration['exceptions'],
            'avaliacoes' => $evaluations,
            'usuario' => (int) $user->id,
            'data' => $now,
            'motivo' => $reason,
        ];
    }

    private static function movePointer(int $org_id, int $from_id, ?int $to_id): array
    {
        $conn = App::i()->em->getConnection();
        $to = self::registrationReference($to_id);

        if ($to) {
            $conn->executeStatement(
                "UPDATE agent_meta SET value = :to WHERE object_id = :org AND key = 'rcv_registration'",
                ['to' => $to, 'org' => $org_id]
            );
        } else {
            $conn->executeStatement("DELETE FROM agent_meta WHERE object_id = :org AND key = 'rcv_registration'", ['org' => $org_id]);
        }

        return ['organizacao' => $org_id, 'antes' => self::registrationReference($from_id), 'depois' => $to];
    }

    private static function saveRecord(int $registration_id, array $record): void
    {
        $conn = App::i()->em->getConnection();

        $previous = $conn->fetchOne("SELECT value FROM registration_meta WHERE object_id = ? AND key = ?", [$registration_id, self::META]);
        if ($previous) {
            $record['anterior'] = json_decode($previous, true);
        }

        $conn->executeStatement("DELETE FROM registration_meta WHERE object_id = ? AND key = ?", [$registration_id, self::META]);
        $conn->executeStatement(
            "INSERT INTO registration_meta (id, object_id, key, value) VALUES (nextval('registration_meta_id_seq'), ?, ?, ?)",
            [$registration_id, self::META, json_encode($record, JSON_UNESCAPED_UNICODE)]
        );
    }

    /**
     * Restaura inscrições enviadas pela ferramenta, nas duas fases
     */
    public static function restore(array $numbers, User $user): array
    {
        $app = App::i();
        $conn = $app->em->getConnection();
        $chain = self::chainOpportunityIds();
        $now = date('Y-m-d H:i:s');
        $results = [];
        $restored_ids = [];
        $restored_opportunities = [];

        $conn->beginTransaction();
        try {
            foreach ($numbers as $number) {
                $phases = $conn->fetchAllAssociative("
                    SELECT r.id, r.opportunity_id, r.status, m.value AS record
                    FROM registration r
                    LEFT JOIN registration_meta m ON m.object_id = r.id AND m.key = :meta
                    WHERE r.number = :number AND r.opportunity_id IN (:chain)
                    ORDER BY r.opportunity_id
                    FOR UPDATE OF r
                ", ['meta' => self::META, 'number' => $number, 'chain' => $chain], ['chain' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

                $trashed = array_filter($phases, fn ($phase) => self::isTrashed($phase['status']));

                if (!$phases) {
                    $results[] = ['numero' => $number, 'resultado' => 'ignorada', 'motivos' => ['nao_encontrada']];
                    continue;
                }

                if (!$trashed) {
                    $results[] = ['numero' => $number, 'resultado' => 'ignorada', 'motivos' => ['fora_da_lixeira']];
                    continue;
                }

                if (array_filter($trashed, fn ($phase) => !self::hasUsableRecord($phase['record']))) {
                    $results[] = ['numero' => $number, 'resultado' => 'ignorada', 'motivos' => ['sem_backup']];
                    continue;
                }

                $notes = [];
                foreach ($trashed as $phase) {
                    $record = json_decode($phase['record'], true);
                    $notes = array_merge($notes, self::restorePhase((int) $phase['id'], $record, $user, $now));
                    $restored_ids[] = (int) $phase['id'];
                    $restored_opportunities[(int) $phase['opportunity_id']] = true;
                }

                $results[] = ['numero' => $number, 'resultado' => 'restaurada', 'motivos' => array_values(array_unique($notes))];
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        foreach ($restored_ids as $id) {
            $app->repo('Registration')->find($id)?->enqueueToPCacheRecreation();
        }

        self::refreshSummaries(array_keys($restored_opportunities));

        return $results;
    }

    private static function restorePhase(int $registration_id, array $record, User $user, string $now): array
    {
        $conn = App::i()->em->getConnection();
        $notes = [];

        $conn->executeStatement("
            UPDATE registration
            SET status = :status, valuers = CAST(:valuers AS jsonb), valuers_exceptions_list = CAST(:exceptions AS jsonb)
            WHERE id = :id
        ", [
            'status' => (int) $record['status'],
            'valuers' => $record['valuers'] ?: '{}',
            'exceptions' => $record['valuers_exceptions_list'] ?: '{"include": [], "exclude": []}',
            'id' => $registration_id,
        ]);

        foreach ($record['avaliacoes'] ?? [] as $evaluation) {
            $exists = $conn->fetchOne(
                "SELECT 1 FROM registration_evaluation WHERE id = :id OR (registration_id = :registration AND user_id = :user)",
                ['id' => $evaluation['id'], 'registration' => $registration_id, 'user' => $evaluation['user_id']]
            );

            if ($exists) {
                $notes[] = 'avaliacao_ja_existente';
                continue;
            }

            // coluna boolean não aceita o false do PDO
            $conn->insert('registration_evaluation', array_map(fn ($value) => is_bool($value) ? ($value ? 't' : 'f') : $value, $evaluation));
        }

        if ($pointer = $record['ponteiro'] ?? null) {
            $current = $conn->fetchOne("SELECT value FROM agent_meta WHERE object_id = ? AND key = 'rcv_registration'", [$pointer['organizacao']]) ?: null;

            if ($current === $pointer['depois']) {
                self::setPointer((int) $pointer['organizacao'], $pointer['antes']);
            } else {
                $notes[] = 'ponteiro_alterado_depois';
            }
        }

        $record['restaurada'] = ['usuario' => (int) $user->id, 'data' => $now];
        $conn->executeStatement(
            "UPDATE registration_meta SET value = ? WHERE object_id = ? AND key = ?",
            [json_encode($record, JSON_UNESCAPED_UNICODE), $registration_id, self::META]
        );

        return $notes;
    }

    // backup já usado numa restauração não vale para outro envio feito fora da ferramenta
    public static function hasUsableRecord(?string $record): bool
    {
        $record = $record ? json_decode($record, true) : null;

        return is_array($record) && !isset($record['restaurada']);
    }

    private static function setPointer(int $org_id, ?string $value): void
    {
        $conn = App::i()->em->getConnection();

        $conn->executeStatement("DELETE FROM agent_meta WHERE object_id = ? AND key = 'rcv_registration'", [$org_id]);

        if ($value) {
            $conn->executeStatement(
                "INSERT INTO agent_meta (id, object_id, key, value) VALUES (nextval('agent_meta_id_seq'), ?, 'rcv_registration', ?)",
                [$org_id, $value]
            );
        }
    }

    public static function normalizeListParams(array $data): array
    {
        $filter = (string) ($data['filtro'] ?? 'todas');

        return [
            'busca' => mb_substr(trim((string) ($data['busca'] ?? '')), 0, self::MAX_SEARCH_LENGTH),
            'filtro' => in_array($filter, self::LIST_FILTERS, true) ? $filter : 'todas',
            'pagina' => max(1, (int) ($data['pagina'] ?? 1)),
            'limite' => min(self::LIST_MAX_LIMIT, max(1, (int) ($data['limite'] ?? self::LIST_LIMIT))),
        ];
    }

    public static function likePattern(string $text): string
    {
        return '%' . addcslashes($text, '\\%_') . '%';
    }

    // lista de números colada na busca: vários números, ou um só com o prefixo on-
    public static function searchNumbers(string $search): ?array
    {
        $tokens = preg_split('/[\s;,]+/', $search, -1, PREG_SPLIT_NO_EMPTY);
        $parsed = self::parseNumbers($search);

        if (!$parsed['numbers'] || $parsed['invalid'] || (count($tokens) < 2 && !preg_grep('/^on-/i', $tokens))) {
            return null;
        }

        return array_slice($parsed['numbers'], 0, self::MAX_NUMBERS);
    }

    // busca por lista de números; ou por número, organização e motivo, e pelo CNPJ quando houver dígitos
    public static function searchClause(string $search): array
    {
        if ($search === '') {
            return ['', [], []];
        }

        if ($numbers = self::searchNumbers($search)) {
            return ['AND number IN (:numeros)', ['numeros' => $numbers], ['numeros' => \Doctrine\DBAL\ArrayParameterType::STRING]];
        }

        $sql = 'number ILIKE :busca OR org_name ILIKE :busca OR motivo ILIKE :busca';
        $params = ['busca' => self::likePattern($search)];

        $digits = preg_replace('/\D/', '', $search);
        if ($digits !== '') {
            $sql .= " OR regexp_replace(COALESCE(cnpj, ''), '\D', '', 'g') LIKE :digitos";
            $params['digitos'] = self::likePattern($digits);
        }

        return ["AND ({$sql})", $params, []];
    }

    // inscrições da primeira fase na lixeira, uma linha por inscrição, com a primeira organização vinculada
    private static function trashedBaseQuery(): array
    {
        $sql = "
            WITH base AS (
                SELECT r.id, r.number, r.category, m.value AS record, org.name AS org_name, org.cnpj,
                       (m.value IS NOT NULL AND NOT jsonb_exists(m.value::jsonb, 'restaurada')) AS restauravel,
                       m.value::jsonb ->> 'data' AS enviada_em,
                       m.value::jsonb ->> 'motivo' AS motivo
                FROM registration r
                LEFT JOIN registration_meta m ON m.object_id = r.id AND m.key = :meta
                LEFT JOIN LATERAL (
                    SELECT a.name, (SELECT am.value FROM agent_meta am WHERE am.object_id = a.id AND am.key = 'cnpj' LIMIT 1) AS cnpj
                    FROM agent_relation ar
                    JOIN agent a ON a.id = ar.agent_id
                    WHERE ar.object_id = r.id AND ar.object_type = :type AND ar.type = 'coletivo'
                    ORDER BY ar.id
                    LIMIT 1
                ) org ON true
                WHERE r.opportunity_id = :main AND r.status = :status
            )
        ";

        return [$sql, ['meta' => self::META, 'type' => self::REGISTRATION_CLASS, 'main' => App::i()->config['rcv.opportunityId'], 'status' => self::STATUS]];
    }

    /**
     * Restaura as restauráveis encontradas pela busca, até o limite por lote
     */
    public static function restoreBySearch(string $search, User $user, int $limit = self::MAX_NUMBERS): array
    {
        $conn = App::i()->em->getConnection();
        [$base, $bind] = self::trashedBaseQuery();
        [$search_sql, $search_bind, $search_types] = self::searchClause($search);

        $numbers = $conn->fetchFirstColumn("
            {$base}
            SELECT number FROM base
            WHERE restauravel {$search_sql}
            ORDER BY enviada_em DESC NULLS LAST, id DESC
            LIMIT :limite
        ", $bind + $search_bind + ['limite' => $limit], $search_types + ['limite' => \Doctrine\DBAL\ParameterType::INTEGER]);

        $results = $numbers ? self::restore($numbers, $user) : [];
        $remaining = (int) $conn->fetchOne("{$base} SELECT count(*) FROM base WHERE restauravel {$search_sql}", $bind + $search_bind, $search_types);

        return ['itens' => $results, 'restantes' => $remaining];
    }

    /**
     * Inscrições da primeira fase na lixeira, paginadas, com busca e filtro
     */
    public static function listTrashed(array $data = []): array
    {
        $app = App::i();
        $conn = $app->em->getConnection();
        $params = self::normalizeListParams($data);

        [$base, $bind] = self::trashedBaseQuery();

        [$search_sql, $search_bind, $search_types] = self::searchClause($params['busca']);
        $filter_sql = ['restauraveis' => 'AND restauravel', 'sem_backup' => 'AND NOT restauravel'][$params['filtro']] ?? '';

        $totals = array_map('intval', $conn->fetchAssociative("
            {$base}
            SELECT count(*) AS todas, count(*) FILTER (WHERE restauravel) AS restauraveis, count(*) FILTER (WHERE NOT restauravel) AS sem_backup
            FROM base WHERE true {$search_sql}
        ", $bind + $search_bind, $search_types));

        $rows = $conn->fetchAllAssociative("
            {$base}
            SELECT * FROM base
            WHERE true {$search_sql} {$filter_sql}
            ORDER BY (CASE WHEN restauravel THEN enviada_em END) DESC NULLS LAST, id DESC
            LIMIT :limite OFFSET :deslocamento
        ", $bind + $search_bind + [
            'limite' => $params['limite'],
            'deslocamento' => ($params['pagina'] - 1) * $params['limite'],
        ], $search_types + ['limite' => \Doctrine\DBAL\ParameterType::INTEGER, 'deslocamento' => \Doctrine\DBAL\ParameterType::INTEGER]);

        $records = array_map(fn ($row) => $row['restauravel'] ? json_decode($row['record'], true) : null, $rows);
        $names = self::fetchUserNames(array_filter(array_map(fn ($record) => $record['usuario'] ?? null, $records)));
        $total = $totals[$params['filtro']];

        // com lista colada, informa os números que não estão na lixeira
        $searched = self::searchNumbers($params['busca']);
        $missing = $searched ? array_values(array_diff($searched, $conn->fetchFirstColumn(
            "{$base} SELECT number FROM base WHERE number IN (:numeros)",
            $bind + ['numeros' => $searched],
            ['numeros' => \Doctrine\DBAL\ArrayParameterType::STRING]
        ))) : [];

        return [
            'itens' => array_map(fn ($row, $record) => [
                'numero' => $row['number'],
                'id' => (int) $row['id'],
                'categoria' => $row['category'],
                'organizacao' => $row['org_name'],
                'status_anterior' => $record ? self::statusName((int) $record['status']) : null,
                'enviada_por' => $record ? ($names[$record['usuario']] ?? "#{$record['usuario']}") : null,
                'enviada_em' => $record['data'] ?? null,
                'motivo' => $record['motivo'] ?? null,
                'restauravel' => (bool) $record,
            ], $rows, $records),
            'total' => $total,
            'totais' => $totals,
            'pagina' => $params['pagina'],
            'paginas' => max(1, (int) ceil($total / $params['limite'])),
            'numeros_buscados' => $searched ? count($searched) : 0,
            'fora_da_lixeira' => $missing,
        ];
    }

    public static function clearTrashedValuers(int $opportunity_id): int
    {
        return App::i()->em->getConnection()->executeStatement("
            UPDATE registration
            SET valuers = '{}'
            WHERE opportunity_id = :opportunity_id
              AND status = :status
              AND valuers::text NOT IN ('{}', '[]')
        ", ['opportunity_id' => $opportunity_id, 'status' => self::STATUS]);
    }

    private static function refreshSummaries(array $opportunity_ids): void
    {
        $app = App::i();

        foreach ($opportunity_ids as $opportunity_id) {
            $opportunity = $app->repo('Opportunity')->find($opportunity_id);
            if (!$opportunity) {
                continue;
            }

            $app->mscache->delete($opportunity->summaryCacheKey);
            $app->enqueueOrReplaceJob(UpdateSummaryCaches::SLUG, [
                'opportunity' => $opportunity,
                'evaluationMethodConfiguration' => $opportunity->evaluationMethodConfiguration ?: null,
            ], 'now');
        }
    }

    private static function fetchOrganizations(array $registration_ids): array
    {
        if (!$registration_ids) {
            return [];
        }

        $rows = App::i()->em->getConnection()->fetchAllAssociative("
            SELECT ar.object_id AS registration_id, a.id AS org_id, a.name,
                   (SELECT value FROM agent_meta WHERE object_id = a.id AND key = 'cnpj' LIMIT 1) AS cnpj,
                   (SELECT value FROM agent_meta WHERE object_id = a.id AND key = 'rcv_registration' LIMIT 1) AS rcv_registration
            FROM agent_relation ar
            JOIN agent a ON a.id = ar.agent_id
            WHERE ar.object_type = :type AND ar.type = 'coletivo' AND ar.object_id IN (:ids)
        ", ['type' => self::REGISTRATION_CLASS, 'ids' => $registration_ids], ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['registration_id']] = ['org_id' => (int) $row['org_id']] + $row;
        }

        return $result;
    }

    private static function fetchNames(array $agent_ids): array
    {
        if (!$agent_ids) {
            return [];
        }

        return App::i()->em->getConnection()->fetchAllKeyValue(
            "SELECT id, name FROM agent WHERE id IN (:ids)",
            ['ids' => array_values($agent_ids)],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );
    }

    private static function fetchUserNames(array $user_ids): array
    {
        if (!$user_ids) {
            return [];
        }

        return App::i()->em->getConnection()->fetchAllKeyValue(
            "SELECT u.id, COALESCE(a.name, u.email) FROM usr u LEFT JOIN agent a ON a.id = u.profile_id WHERE u.id IN (:ids)",
            ['ids' => array_values(array_unique($user_ids))],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );
    }

    private static function fetchEvaluationCounts(array $registration_ids): array
    {
        if (!$registration_ids) {
            return [];
        }

        $rows = App::i()->em->getConnection()->fetchAllAssociative(
            "SELECT registration_id, status, count(*) AS total FROM registration_evaluation WHERE registration_id IN (:ids) GROUP BY 1, 2",
            ['ids' => $registration_ids],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['registration_id']][(int) $row['status']] = (int) $row['total'];
        }

        return $result;
    }

    private static function fetchOrganizationSeals(array $org_ids, array $seals): array
    {
        $seal_types = array_filter(['ponto' => $seals['ponto'] ?? null, 'pontao' => $seals['pontao'] ?? null]);

        if (!$org_ids || !$seal_types) {
            return [];
        }

        $rows = App::i()->em->getConnection()->fetchAllAssociative("
            SELECT object_id, seal_id FROM seal_relation
            WHERE object_type = :type AND object_id IN (:orgs) AND seal_id IN (:seals) AND status > 0
        ", [
            'type' => self::AGENT_CLASS,
            'orgs' => array_values($org_ids),
            'seals' => array_map('intval', array_values($seal_types)),
        ], [
            'orgs' => \Doctrine\DBAL\ArrayParameterType::INTEGER,
            'seals' => \Doctrine\DBAL\ArrayParameterType::INTEGER,
        ]);

        $types_by_seal = array_flip(array_map('intval', $seal_types));
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['object_id']][] = $types_by_seal[(int) $row['seal_id']];
        }

        return $result;
    }

    private static function fetchOrganizationRegistrations(array $org_ids, int $main, array $categories_map): array
    {
        if (!$org_ids) {
            return [];
        }

        $rows = App::i()->em->getConnection()->fetchAllAssociative("
            SELECT ar.agent_id AS org_id, r.id, r.status, r.category, COALESCE(r.sent_timestamp, r.create_timestamp) AS timestamp
            FROM agent_relation ar
            JOIN registration r ON r.id = ar.object_id
            WHERE ar.object_type = :type AND ar.type = 'coletivo' AND ar.agent_id IN (:orgs)
              AND r.opportunity_id = :main AND r.status > 0
        ", ['type' => self::REGISTRATION_CLASS, 'orgs' => array_values($org_ids), 'main' => $main], ['orgs' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['org_id']][] = [
                'id' => (int) $row['id'],
                'status' => (int) $row['status'],
                'type' => self::typeOfCategory($row['category'], $categories_map),
                'timestamp' => $row['timestamp'],
            ];
        }

        return $result;
    }
}
