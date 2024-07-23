<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\EscaperRuntime;

class EscaperTest extends TestCase
{
    /**
     * @dataProvider provideCustomEscaperCases
     *
     * @group legacy
     */
    public function testCustomEscaper($expected, $string, $strategy)
    {
        $twig = new Environment(new ArrayLoader());
        $escaperExt = $twig->getExtension(EscaperExtension::class);
        $escaperExt->setEscaper('foo', 'Twig\Tests\legacy_escaper');
        $this->assertSame($expected, $twig->getRuntime(EscaperRuntime::class)->escape($string, $strategy, 'ISO-8859-1'));
    }

    public function provideCustomEscaperCases()
    {
        return [
            ['foo**ISO-8859-1**UTF-8', 'foo', 'foo'],
            ['**ISO-8859-1**UTF-8', null, 'foo'],
            ['42**ISO-8859-1**UTF-8', 42, 'foo'],
        ];
    }

    /**
     * @dataProvider provideCustomEscaperCases
     *
     * @group legacy
     */
    public function testCustomEscaperWithoutCallingSetEscaperRuntime($expected, $string, $strategy)
    {
        $twig = new Environment(new ArrayLoader());
        $escaperExt = $twig->getExtension(EscaperExtension::class);
        $escaperExt->setEscaper('foo', 'Twig\Tests\legacy_escaper');
        $this->assertSame($expected, $twig->getRuntime(EscaperRuntime::class)->escape($string, $strategy, 'ISO-8859-1'));
    }

    /**
     * @group legacy
     */
    public function testCustomEscapersOnMultipleEnvs()
    {
        $env1 = new Environment(new ArrayLoader());
        $escaperExt1 = $env1->getExtension(EscaperExtension::class);
        $escaperExt1->setEscaper('foo', 'Twig\Tests\legacy_escaper');

        $env2 = new Environment(new ArrayLoader());
        $escaperExt2 = $env2->getExtension(EscaperExtension::class);
        $escaperExt2->setEscaper('foo', 'Twig\Tests\legacy_escaper_again');

        $this->assertSame('foo**ISO-8859-1**UTF-8', $env1->getRuntime(EscaperRuntime::class)->escape('foo', 'foo', 'ISO-8859-1'));
        $this->assertSame('foo**ISO-8859-1**UTF-8**again', $env2->getRuntime(EscaperRuntime::class)->escape('foo', 'foo', 'ISO-8859-1'));
    }
}

function legacy_escaper(Environment $twig, $string, $charset)
{
    return $string.'**'.$charset.'**'.$twig->getCharset();
}

function legacy_escaper_again(Environment $twig, $string, $charset)
{
    return $string.'**'.$charset.'**'.$twig->getCharset().'**again';
}
