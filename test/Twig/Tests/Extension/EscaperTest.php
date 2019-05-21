<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Loader\LoaderInterface;

class Twig_Tests_Extension_EscaperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideCustomEscaperCases
     */
    public function testCustomEscaper($expected, $string, $strategy)
    {
        $twig = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $twig->getExtension(EscaperExtension::class)->setEscaper('foo', 'foo_escaper_for_test');

        $this->assertSame($expected, twig_escape_filter($twig, $string, $strategy));
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
     * @expectedException \Twig\Error\RuntimeError
     */
    public function testUnknownCustomEscaper()
    {
        twig_escape_filter(new Environment($this->getMockBuilder(LoaderInterface::class)->getMock()), 'foo', 'bar');
    }
}

function foo_escaper_for_test(Environment $env, $string, $charset)
{
    return $string.$charset;
}
