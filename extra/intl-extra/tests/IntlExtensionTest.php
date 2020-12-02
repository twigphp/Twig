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
use Twig\Extra\Intl\IntlExtension;

class IntlExtensionTest extends TestCase
{
    public function testFormatterProto()
    {
        $dateFormatterProto = new \IntlDateFormatter('fr', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
        $numberFormatterProto = new \NumberFormatter('fr', \NumberFormatter::DECIMAL);
        $numberFormatterProto->setTextAttribute(\NumberFormatter::POSITIVE_PREFIX, '++');
        $numberFormatterProto->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $ext = new IntlExtension($dateFormatterProto, $numberFormatterProto);
        $this->assertSame('++12,3', $ext->formatNumber('12.3456'));
    }

    /**
     * @dataProvider getCurrencyData
     */
    public function testFormatterProtoWithCurrency(string $locale, string $expectedCurrency)
    {
        $numberFormatterProto = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $numberFormatterProto->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $ext = new IntlExtension(null, $numberFormatterProto);
        $this->assertSame($expectedCurrency, $ext->formatCurrency('12.3456', 'EUR'));
    }

    public function getCurrencyData()
    {
        return [
            ['en', '€12.3'],
            ['fr', '12,3 €'],
        ];
    }
}
