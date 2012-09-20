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
        $loader = new Twig_Loader_String('{{ d1.date }}{{ d2.date }}');

        $twig = new Twig_Environment($loader, array(
            'debug'      => true,
            'cache'      => false,
            'autoescape' => array($this, 'escapingStrategyCallback'),
        ));

		// If it fails, PHP will crash.
    }
}
