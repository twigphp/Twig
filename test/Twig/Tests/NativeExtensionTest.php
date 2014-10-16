<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_NativeExtensionTest extends PHPUnit_Framework_TestCase
{
    public function testGetProperties()
    {
        $twig = new Twig_Environment(new Twig_Loader_String(), array(
            'debug'      => true,
            'cache'      => false,
            'autoescape' => false,
        ));

        $s1 = new stdClass();
        $s2 = new stdClass();

        $s1->foo = 'foo';
        $s2->bar = 'bar';

        $output = $twig->render('{{ s1.foo }}{{ s2.bar }}', compact('s1', 's2'));

        $this->assertEquals($output, $s1->foo.$s2->bar);
    }
}
