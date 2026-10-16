<?php

namespace CulturaViva;

use DateTime;
use MapasCulturais\App;
use MapasCulturais\i;
use MapasCulturais\Entities\AgentSealRelation;
use MapasCulturais\Entities\User;
use MapasCulturais\UserInterface;
use MultipleLocalAuth\Provider as LocalAuth;

/**
 * Alteração da data de certificação de um Ponto ou Pontão de Cultura.
 *
 * A data impressa no certificado é o createTimestamp da relação entre a organização
 * e o selo de verificação (rcv.verificationSeals). O core não prevê editar essa data,
 * então o tema expõe uma ação própria, restrita a administradores, confirmada por senha
 * e registrada no MapasBlame.
 */
final class CertificationDate
{
    public const ROLE = 'admin';

    // usado quando o plugin MultipleLocalAuth não está carregado
    public const PASSWORD_META = 'localAuthenticationPassword';
    public const ROUTE = 'rcv-alterar-data-certificacao';
    public const BLAME_ACTION = 'rcv-data-certificacao alterar';

    // a Política Nacional Cultura Viva nasce em 2004; antes disso é erro de digitação
    public const MIN_YEAR = 2004;

    public const FORMATS = ['d/m/Y', 'Y-m-d'];

    public static function register(): void
    {
        $app = App::i();

        $app->hook('POST(site.' . self::ROUTE . ')', function () use ($app) {
            /** @var \MapasCulturais\Controllers\Site $this */
            self::requireManager($this);
            self::requirePassword($this);

            $relation = $app->repo(AgentSealRelation::class)->find((int) ($this->data['relation'] ?? 0));

            if (!$relation || !self::isVerificationRelation($relation, $app->config['rcv.verificationSeals'])) {
                $this->errorJson(i::__('Certificado não encontrado.'), 404);
            }

            $date = self::parseDate((string) ($this->data['date'] ?? ''));

            if ($error = self::validate($date, new DateTime())) {
                $this->errorJson(self::errorMessage($error), 400);
            }

            $result = self::apply($relation, $date);

            self::blame($result);

            $this->json($result);
        });
    }

    /**
     * Grava a nova data na relação de selo e recalcula a validade a partir dela.
     *
     * @return array{relation:int,organization:int,seal:int,previous:string,date:string}
     */
    public static function apply(AgentSealRelation $relation, DateTime $date): array
    {
        $app = App::i();

        $previous = $relation->createTimestamp?->format('d/m/Y') ?? '';

        $app->disableAccessControl();
        try {
            $relation->createTimestamp = clone $date;
            $relation->validateDate = self::expirationFor($date, (int) $relation->seal->validPeriod);
            $relation->save(true);
        } finally {
            $app->enableAccessControl();
        }

        return [
            'relation' => (int) $relation->id,
            'organization' => (int) $relation->owner->id,
            'seal' => (int) $relation->seal->id,
            'previous' => $previous,
            'date' => $date->format('d/m/Y'),
        ];
    }

    /**
     * Converte o texto em data (dd/mm/aaaa ou aaaa-mm-dd), sem hora. Datas inexistentes
     * como 31/02 voltam null: o createFromFormat as aceita, mas não reproduz o texto.
     */
    public static function parseDate(string $input): ?DateTime
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        foreach (self::FORMATS as $format) {
            $date = DateTime::createFromFormat("!{$format}", $input);

            if ($date && $date->format($format) === $input) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Retorna o código do problema, ou null quando a data serve.
     */
    public static function validate(?DateTime $date, DateTime $today): ?string
    {
        if (!$date) {
            return 'invalid';
        }

        if ((int) $date->format('Y') < self::MIN_YEAR) {
            return 'too_old';
        }

        if ($date->format('Y-m-d') > $today->format('Y-m-d')) {
            return 'future';
        }

        return null;
    }

    public static function errorMessage(string $error): string
    {
        return match ($error) {
            'too_old' => sprintf(i::__('A data da certificação não pode ser anterior a %d.'), self::MIN_YEAR),
            'future' => i::__('A data da certificação não pode estar no futuro.'),
            default => i::__('Informe uma data válida no formato dd/mm/aaaa.'),
        };
    }

    // o core calcula a validade no setSeal como criação + validPeriod em meses
    public static function expirationFor(DateTime $date, int $valid_period_months): DateTime
    {
        $expiration = clone $date;
        $expiration->add(new \DateInterval('P' . max(0, $valid_period_months) . 'M'));

        return $expiration;
    }

    public static function isVerificationSeal($seal_id, array $verification_seals): bool
    {
        $ids = [(int) ($verification_seals['ponto'] ?? 0), (int) ($verification_seals['pontao'] ?? 0)];

        return $seal_id !== null && in_array((int) $seal_id, $ids, true);
    }

    public static function isVerificationRelation(AgentSealRelation $relation, array $verification_seals): bool
    {
        return self::isVerificationSeal($relation->seal?->id, $verification_seals);
    }

    // UserInterface porque o visitante é um GuestUser, não uma entidade User
    public static function canManage(?UserInterface $user): bool
    {
        return $user && !$user->is('guest') && $user->is(self::ROLE);
    }

    public static function requireManager($controller): void
    {
        $controller->requireAuthentication();

        if (!self::canManage(App::i()->user)) {
            $controller->errorJson(i::__('Acesso restrito.'), 403);
        }
    }

    // confirma a senha local de quem altera a data
    public static function requirePassword($controller): void
    {
        $user = App::i()->user;
        $hash = $user instanceof User ? $user->getMetadata(self::passwordMeta()) : null;

        $check = self::checkPassword($hash, (string) ($controller->data['password'] ?? ''));

        if ($check === 'no_password') {
            $controller->errorJson(i::__('Sua conta não tem senha cadastrada, por isso não é possível confirmar esta ação.'), 403);
        }

        if ($check !== 'ok') {
            $controller->errorJson(i::__('Senha incorreta.'), 403);
        }
    }

    // nome do metadado da senha local, lido do plugin quando ele está carregado
    public static function passwordMeta(): string
    {
        return class_exists(LocalAuth::class) ? LocalAuth::$passMetaName : self::PASSWORD_META;
    }

    public static function checkPassword($hash, string $password): string
    {
        if (!is_string($hash) || $hash === '') {
            return 'no_password';
        }

        return $password !== '' && password_verify($password, $hash) ? 'ok' : 'invalid';
    }

    // registra no MapasBlame a relação, a organização, a data anterior e a nova (a senha nunca vai)
    public static function blame(array $result): void
    {
        if (!class_exists(\MapasBlame\Request::class)) {
            return;
        }

        try {
            (new \MapasBlame\Request())->log(self::BLAME_ACTION, $result);
        } catch (\Throwable $e) {
            App::i()->log->error("CulturaViva: falha ao registrar alteração da data de certificação no MapasBlame: {$e->getMessage()}");
        }
    }
}
