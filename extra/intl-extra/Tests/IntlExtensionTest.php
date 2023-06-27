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
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;

class IntlExtensionTest extends TestCase
{
    public function testFormatterProto()
    {
        $dateFormatterProto = new \IntlDateFormatter('fr', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, new \DateTimeZone('Europe/Paris'));
        $numberFormatterProto = new \NumberFormatter('fr', \NumberFormatter::DECIMAL);
        $numberFormatterProto->setTextAttribute(\NumberFormatter::POSITIVE_PREFIX, '++');
        $numberFormatterProto->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $ext = new IntlExtension($dateFormatterProto, $numberFormatterProto);
        $env = new Environment(new ArrayLoader());

        $this->assertSame('++12,3', $ext->formatNumber('12.3456'));
        $this->assertSame(
            'jeudi 20 février 2020 à 14:37:00 heure normale d’Europe centrale',
            $ext->formatDateTime($env, new \DateTime('2020-02-20T13:37:00+00:00'))
        );
    }
}
