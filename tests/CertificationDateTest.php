<?php

namespace CulturaViva\Tests;

use CulturaViva\CertificationDate;
use DateTime;
use MapasCulturais\GuestUser;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/CertificationDate.php';

class CertificationDateTest extends TestCase
{
    const SEALS = ['ponto' => '6', 'pontao' => '101', 'desativado' => '116'];

    function testAcceptsBrazilianAndIsoDates(): void
    {
        $this->assertSame('2015-03-10', CertificationDate::parseDate('10/03/2015')->format('Y-m-d'));
        $this->assertSame('2015-03-10', CertificationDate::parseDate('2015-03-10')->format('Y-m-d'));
        $this->assertSame('2015-03-10', CertificationDate::parseDate(' 10/03/2015 ')->format('Y-m-d'));
    }

    function testParsedDateHasNoTime(): void
    {
        $this->assertSame('00:00:00', CertificationDate::parseDate('10/03/2015')->format('H:i:s'));
    }

    function testRejectsInvalidDates(): void
    {
        foreach (['', '31/02/2015', '2015-02-31', '10-03-2015', '10/3/2015', '2015/03/10', 'abc', '10/03/15', '2015-03-10 10:00'] as $input) {
            $this->assertNull(CertificationDate::parseDate($input), "entrada '{$input}'");
        }
    }

    function testValidatesDateRange(): void
    {
        $today = new DateTime('2026-10-16');

        $this->assertSame('invalid', CertificationDate::validate(null, $today));
        $this->assertSame('too_old', CertificationDate::validate(new DateTime('2003-12-31'), $today));
        $this->assertSame('future', CertificationDate::validate(new DateTime('2026-10-17'), $today));

        $this->assertNull(CertificationDate::validate(new DateTime('2004-01-01'), $today));
        $this->assertNull(CertificationDate::validate(new DateTime('2026-10-16'), $today));
        $this->assertNull(CertificationDate::validate(new DateTime('2015-03-10'), $today));
    }

    function testTodayCountsAsTodayRegardlessOfTime(): void
    {
        $this->assertNull(CertificationDate::validate(new DateTime('2026-10-16 00:00:00'), new DateTime('2026-10-16 23:59:59')));
    }

    function testCalculatesExpirationLikeTheCore(): void
    {
        $date = new DateTime('2015-03-10');

        $this->assertSame('2015-03-10', CertificationDate::expirationFor($date, 0)->format('Y-m-d'));
        $this->assertSame('2016-03-10', CertificationDate::expirationFor($date, 12)->format('Y-m-d'));
        $this->assertSame('2015-03-10', CertificationDate::expirationFor($date, -5)->format('Y-m-d'));

        // não altera a data recebida
        $this->assertSame('2015-03-10', $date->format('Y-m-d'));
    }

    function testRecognizesOnlyVerificationSeals(): void
    {
        $this->assertTrue(CertificationDate::isVerificationSeal(6, self::SEALS));
        $this->assertTrue(CertificationDate::isVerificationSeal('101', self::SEALS));

        foreach ([null, 0, 105, 116, 117] as $seal_id) {
            $this->assertFalse(CertificationDate::isVerificationSeal($seal_id, self::SEALS), "selo {$seal_id}");
        }
    }

    function testChecksLocalPassword(): void
    {
        $hash = password_hash('segredo', PASSWORD_DEFAULT);

        $this->assertSame('ok', CertificationDate::checkPassword($hash, 'segredo'));
        $this->assertSame('invalid', CertificationDate::checkPassword($hash, 'outra'));
        $this->assertSame('invalid', CertificationDate::checkPassword($hash, ''));
        $this->assertSame('no_password', CertificationDate::checkPassword(null, 'segredo'));
        $this->assertSame('no_password', CertificationDate::checkPassword('', 'segredo'));
    }

    function testPasswordMetadataNameWithoutThePlugin(): void
    {
        $this->assertSame('localAuthenticationPassword', CertificationDate::passwordMeta());
    }

    function testGuestCannotManage(): void
    {
        $this->assertFalse(CertificationDate::canManage(null));
        $this->assertFalse(CertificationDate::canManage(new GuestUser()));
    }
}
