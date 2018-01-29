<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Twig_Tests_NodeVisitor_WhiteSpaceCutterTest test for Twig_NodeVisitor_WhiteSpaceCutter
 *
 * @author Konstantin Kuklin <konstantin.kuklin@gmail.com>
 */
class Twig_Tests_NodeVisitor_WhiteSpaceCutterTest extends \PHPUnit\Framework\TestCase
{
    public function testDisabledWhiteSpaceCutter()
    {
        $code = 'f o      o ';
        $env = new Twig_Environment(
            $this->getMockBuilder('Twig_LoaderInterface')->getMock(),
            ['cache' => false, 'skip_whitespaces' => true]
        );

        $parsed = $env->parse($env->tokenize(new Twig_Source($code, 'index')));
        $result = $this->getData($parsed);
        self::assertEquals($code, $result);
    }

    public function dataProviderEnabledWhiteSpaceCutter()
    {
        return [
            [' b a        r', ' b a r'],
            [' b a        r ', ' b a r '],
            ['b      a r', 'b a r'],
            ['b      a r ', 'b a r '],
            ["b\nar  ", 'b ar '],
        ];
    }

    /**
     * @dataProvider dataProviderEnabledWhiteSpaceCutter
     *
     * @throws Twig_Error_Syntax
     */
    public function testEnabledWhiteSpaceCutter($input, $expected)
    {
        $env = new Twig_Environment(
            $this->getMockBuilder('Twig_LoaderInterface')->getMock(),
            ['cache' => false, 'skip_whitespaces' => false]
        );

        $parsed = $env->parse($env->tokenize(new Twig_Source($input, 'index')));
        $result = $this->getData($parsed);

        self::assertEquals($expected, $result);
    }

    private function getData($parsed)
    {
        $bodyNode = $parsed->getNode('body');
        $textNode = $bodyNode->getNode(0);

        return $textNode->getAttribute('data');
    }
}
