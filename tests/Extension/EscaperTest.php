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
use Twig\Loader\LoaderInterface;
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
        $twig = new Environment($this->createMock(LoaderInterface::class));
        $escaperExt = $twig->getExtension(EscaperExtension::class);
        $escaperExt->setEnvironment($twig);
        $escaperExt->setEscaper('foo', 'Twig\Tests\legacy_escaper');
        $this->assertSame($expected, $twig->getRuntime(EscaperRuntime::class)->escape($string, $strategy));
    }

    public function provideCustomEscaperCases()
    {
        return [
            ['fooUTF-8', 'foo', 'foo'],
            ['UTF-8', null, 'foo'],
            ['42UTF-8', 42, 'foo'],
        ];
    }

    /**
     * @group legacy
     */
    public function testCustomEscapersOnMultipleEnvs()
    {
        $env1 = new Environment($this->createMock(LoaderInterface::class));
        $escaperExt1 = $env1->getExtension(EscaperExtension::class);
        $escaperExt1->setEnvironment($env1);
        $escaperExt1->setEscaper('foo', 'Twig\Tests\legacy_escaper');

        $env2 = new Environment($this->createMock(LoaderInterface::class));
        $escaperExt2 = $env2->getExtension(EscaperExtension::class);
        $escaperExt2->setEnvironment($env2);
        $escaperExt2->setEscaper('foo', 'Twig\Tests\legacy_escaper_again');

        $this->assertSame('fooUTF-8', $env1->getRuntime(EscaperRuntime::class)->escape('foo', 'foo'));
        $this->assertSame('fooUTF-81', $env2->getRuntime(EscaperRuntime::class)->escape('foo', 'foo'));
    }
}

function legacy_escaper(Environment $twig, $string)
{
    return $string.$twig->getCharset();
}

function legacy_escaper_again(Environment $twig, $string)
{
    return $string.$twig->getCharset().'1';
}
