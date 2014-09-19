<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Loader_PreprocessorTest extends PHPUnit_Framework_TestCase
{
    public function testProcessing()
    {
        $realLoader = new Twig_Loader_String();
        $loader = new Twig_Loader_Preprocessor($realLoader, 'strtoupper');
        $this->assertEquals('TEST', $loader->getSource('test'));
    }
}
