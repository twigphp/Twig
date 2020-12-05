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
    public function testFormatterProtoWithCurrency(string $locale, string $currency, string $expectedCurrency)
    {
        $numberFormatterProto = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $numberFormatterProto->setAttribute(\NumberFormatter::FRACTION_DIGITS, 1);
        $ext = new IntlExtension(null, $numberFormatterProto);
        $this->assertSame($expectedCurrency, $this->fixSpace($ext->formatCurrency('12.3456', $currency, [], $locale)));
    }

    public function getCurrencyData()
    {
        return [
            ['en', 'EUR', '€12.3'],
            ['fr', 'EUR', '12,3 €'],
            ['jp', 'YEN', 'YEN 12.3'],
        ];
    }

    private function fixSpace(string $string): string
    {
        $string = mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');
        $stringWithoutSpaces = str_replace('&nbsp;', ' ', $string);

        return html_entity_decode($stringWithoutSpaces);
    }
}
