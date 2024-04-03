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
    public function testFormatterWithoutProto()
    {
        $ext = new IntlExtension();
        $env = new Environment(new ArrayLoader());

        $this->assertSame('12.346', $ext->formatNumber('12.3456'));
        $this->assertStringStartsWith(
            'Feb 20, 2020, 1:37:00',
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00'))
        );
    }

    public function testFormatterWithoutProtoFallsBackToCoreExtensionTimezone()
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

    public function testFormatterWithoutProtoSkipTimezoneConverter()
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

    public function testFormatterProto()
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

    public function testFormatterOverridenProto()
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
}
