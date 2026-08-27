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
use Twig\Error\RuntimeError;
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
                'jeudi 20 février 2020 à 13:37:00 heure normale d’Europe centrale', // codespell:ignore normale
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

    public function testFormatterCalendarPrecedence(): void
    {
        $env = new Environment(new ArrayLoader());
        $date = new \DateTime('2020-02-20T00:00:00+00:00');
        $gregorianProto = new \IntlDateFormatter('th_TH', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::GREGORIAN);
        $traditionalProto = new \IntlDateFormatter('th_TH', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::TRADITIONAL);
        $hebrewProto = new \IntlDateFormatter('en_US', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', \IntlCalendar::createInstance('UTC', 'en_US@calendar=hebrew'));
        $failedCalendarProto = new class('th_TH', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC') extends \IntlDateFormatter {
            public function getCalendar(): int|false
            {
                return false;
            }

            public function getCalendarObject(): \IntlCalendar|false|null
            {
                return false;
            }
        };

        $this->assertSame('2563', (new IntlExtension($gregorianProto))->formatDate($env, $date, pattern: 'yyyy', timezone: 'UTC', calendar: 'traditional', locale: 'th_TH'));
        $this->assertSame('2020', (new IntlExtension($traditionalProto))->formatDate($env, $date, pattern: 'yyyy', timezone: 'UTC', calendar: 'gregorian', locale: 'th_TH'));
        $this->assertSame('2563', (new IntlExtension($traditionalProto))->formatDate($env, $date, pattern: 'yyyy', timezone: 'UTC', locale: 'th_TH'));
        $this->assertSame('5780', (new IntlExtension($hebrewProto))->formatDate($env, $date, pattern: 'yyyy', timezone: 'UTC', locale: 'en_US'));
        $this->assertSame('2020', (new IntlExtension($failedCalendarProto))->formatDate($env, $date, pattern: 'yyyy', timezone: 'UTC', locale: 'th_TH'));
        $this->assertSame('2020', (new IntlExtension())->formatDate($env, $date, pattern: 'yyyy', timezone: 'UTC', locale: 'th_TH'));
    }

    public function testFormatterObjectCalendarChangesAreApplied(): void
    {
        $env = new Environment(new ArrayLoader());
        $date = new \DateTime('2021-01-01T00:00:00+00:00');
        $calendar = \IntlCalendar::createInstance('UTC', 'en_US@calendar=gregorian');
        $calendar->setFirstDayOfWeek(\IntlCalendar::DOW_MONDAY);
        $calendar->setMinimalDaysInFirstWeek(4);
        $proto = new \IntlDateFormatter('en_US', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', $calendar);
        $ext = new IntlExtension($proto);

        $this->assertSame('2020-53', $ext->formatDate($env, $date, pattern: 'Y-ww', timezone: 'UTC', locale: 'en_US'));

        $calendar = \IntlCalendar::createInstance('UTC', 'en_US@calendar=gregorian');
        $calendar->setFirstDayOfWeek(\IntlCalendar::DOW_SUNDAY);
        $calendar->setMinimalDaysInFirstWeek(1);
        $proto->setCalendar($calendar);

        $this->assertSame('2021-01', $ext->formatDate($env, $date, pattern: 'Y-ww', timezone: 'UTC', locale: 'en_US'));
    }

    public function testFormatterProtoFormatFailuresFallBackToMedium(): void
    {
        $env = new Environment(new ArrayLoader());
        $date = new \DateTime('2020-02-20T00:00:00+00:00');
        $proto = new class('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'UTC') extends \IntlDateFormatter {
            public function getDateType(): int|false
            {
                return false;
            }

            public function getTimeType(): int|false
            {
                return false;
            }
        };
        $ext = new IntlExtension($proto);
        $expectedDate = (new \IntlDateFormatter('en_US', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, 'UTC', \IntlDateFormatter::GREGORIAN))->format($date);
        $expectedTime = (new \IntlDateFormatter('en_US', \IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM, 'UTC', \IntlDateFormatter::GREGORIAN))->format($date);

        $this->assertSame($expectedDate, $ext->formatDate($env, $date, timezone: 'UTC', locale: 'en_US'));
        $this->assertSame($expectedTime, $ext->formatTime($env, $date, timezone: 'UTC', locale: 'en_US'));
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

    public function testFormatListThrowsOnFailure(): void
    {
        if (!class_exists('IntlListFormatter')) {
            $this->markTestSkipped('IntlListFormatter is not available.');
        }

        $strings = ['Alice', "\xB1\x31"];
        $formatter = new \IntlListFormatter('en', \IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE);
        if (false !== $formatter->format($strings)) {
            $this->markTestSkipped('IntlListFormatter accepts the malformed UTF-8 input.');
        }

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Unable to format the given list: '.$formatter->getErrorMessage());

        (new IntlExtension())->formatList($strings, locale: 'en');
    }
}
