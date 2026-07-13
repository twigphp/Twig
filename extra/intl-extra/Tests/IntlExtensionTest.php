<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Intl\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;

class IntlExtensionTest extends TestCase
{
    public function testFormatterWithoutProto(): void
    {
        $ext = new IntlExtension();
        $env = new Environment(new ArrayLoader());

        $this->assertSame('12.346', $ext->formatNumber('12.3456'));
        $this->assertStringStartsWith(
            'Feb 20, 2020, 1:37:00',
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00'))
        );
    }

    public function testFormatterWithoutProtoFallsBackToCoreExtensionTimezone(): void
    {
        $ext = new IntlExtension();
        $env = new Environment(new ArrayLoader());
        // EET is always +2 without changes for daylight saving time
        // so it has a fixed difference to UTC
        $env->getExtension(CoreExtension::class)->setTimezone('EET');

        $this->assertStringStartsWith(
            'Feb 20, 2020, 3:37:00',
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00', new \DateTimeZone('UTC')))
        );
    }

    public function testFormatterWithoutProtoSkipTimezoneConverter(): void
    {
        $ext = new IntlExtension();
        $env = new Environment(new ArrayLoader());
        // EET is always +2 without changes for daylight saving time
        // so it has a fixed difference to UTC
        $env->getExtension(CoreExtension::class)->setTimezone('EET');

        $this->assertStringStartsWith(
            'Feb 20, 2020, 1:37:00',
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00', new \DateTimeZone('UTC')), 'medium', 'medium', '', false)
        );
    }

    public function testFormatterProto(): void
    {
        $dateFormatterProto = new \IntlDateFormatter('fr', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, new \DateTimeZone('Europe/Paris'));
        $numberFormatterProto = new \NumberFormatter('fr', \NumberFormatter::DECIMAL);
        $numberFormatterProto->setTextAttribute(\NumberFormatter::POSITIVE_PREFIX, '++');
        $numberFormatterProto->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $ext = new IntlExtension($dateFormatterProto, $numberFormatterProto);
        $env = new Environment(new ArrayLoader());

        $this->assertSame('++12,3', $ext->formatNumber('12.3456'));
        $this->assertContains(
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00', new \DateTimeZone('Europe/Paris'))),
            [
                'jeudi 20 février 2020 à 13:37:00 heure normale d’Europe centrale',
                'jeudi 20 février 2020 à 13:37:00 temps universel coordonné',
            ]
        );
    }

    public function testFormatterOverridenProto(): void
    {
        $dateFormatterProto = new \IntlDateFormatter('fr', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, new \DateTimeZone('Europe/Paris'));
        $numberFormatterProto = new \NumberFormatter('fr', \NumberFormatter::DECIMAL);
        $numberFormatterProto->setTextAttribute(\NumberFormatter::POSITIVE_PREFIX, '++');
        $numberFormatterProto->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $ext = new IntlExtension($dateFormatterProto, $numberFormatterProto);
        $env = new Environment(new ArrayLoader());

        $this->assertSame(
            'twelve point three',
            $ext->formatNumber('12.3456', [], 'spellout', 'default', 'en_US')
        );
        $this->assertSame(
            '2020-02-20 13:37:00',
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00'), 'short', 'short', 'yyyy-MM-dd HH:mm:ss', 'UTC', 'gregorian', 'en_US')
        );
    }

    public function testFormatterProtoDoesNotOverrideExplicitFormats(): void
    {
        $dateFormatterProto = new \IntlDateFormatter('nl_NL', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM, new \DateTimeZone('Europe/Amsterdam'));
        $ext = new IntlExtension($dateFormatterProto);
        $env = new Environment(new ArrayLoader());
        $date = new \DateTime('2020-02-20T22:22:00+00:00', new \DateTimeZone('UTC'));

        $this->assertSame('20 feb 2020', $ext->formatDate($env, $date));
        $this->assertSame('22:22:00', $ext->formatTime($env, $date));
        $this->assertSame('donderdag 20 februari 2020', $ext->formatDateTime($env, $date, 'full', 'none'));
    }

    public function testFormatterProtoWithCustomPatternIsUsedByDefault(): void
    {
        $dateFormatterProto = new \IntlDateFormatter('nl_NL', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM, new \DateTimeZone('Europe/Amsterdam'), \IntlDateFormatter::GREGORIAN, 'yyyy-MM-dd');
        $ext = new IntlExtension($dateFormatterProto);
        $env = new Environment(new ArrayLoader());
        $date = new \DateTime('2020-02-20T22:22:00+00:00', new \DateTimeZone('UTC'));

        $this->assertSame('2020-02-20', $ext->formatDateTime($env, $date));
        $this->assertSame('20 feb 2020', $ext->formatDate($env, $date, 'medium'));
        // the prototype pattern describes a full datetime rendering, so format_date/format_time ignore it
        $this->assertSame('20 feb 2020', $ext->formatDate($env, $date));
        $this->assertSame('22:22:00', $ext->formatTime($env, $date));
    }

    public function testDateFormatterCacheIsBounded(): void
    {
        $ext = new IntlExtension();
        $env = new Environment(new ArrayLoader());
        $date = new \DateTime('2020-02-20T13:37:00+00:00');

        for ($i = 0; $i < 250; ++$i) {
            $ext->formatDateTime($env, $date, 'medium', 'medium', 'yyyy-MM-dd-'.$i, 'UTC', 'gregorian', 'en_US');
        }

        $cache = (new \ReflectionProperty(IntlExtension::class, 'dateFormatters'))->getValue($ext);
        $this->assertLessThanOrEqual(100, \count($cache));
        $this->assertSame(
            '2020-02-20-249',
            $ext->formatDateTime($env, $date, 'medium', 'medium', 'yyyy-MM-dd-249', 'UTC', 'gregorian', 'en_US')
        );
    }

    public function testNumberFormatterCacheIsBounded(): void
    {
        $ext = new IntlExtension();

        for ($i = 0; $i < 250; ++$i) {
            $ext->formatNumber(1, ['multiplier' => $i + 1], 'decimal', 'default', 'en_US');
        }

        $cache = (new \ReflectionProperty(IntlExtension::class, 'numberFormatters'))->getValue($ext);
        $this->assertLessThanOrEqual(100, \count($cache));
        $this->assertGreaterThan(1, \count($cache));
    }
}
