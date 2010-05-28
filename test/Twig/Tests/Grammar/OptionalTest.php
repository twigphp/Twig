<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Grammar_OptionalTest extends PHPUnit_Framework_TestCase
{
    public function testMagicToString()
    {
        $grammar = new Twig_Grammar_Optional(new Twig_Grammar_Constant('foo'), new Twig_Grammar_Number('bar'));
        $this->assertEquals('[foo <bar:number>]', (string) $grammar);
    }
}
