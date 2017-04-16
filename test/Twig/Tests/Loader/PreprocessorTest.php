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

    public function testExists()
    {
        $realLoader = $this->getMock('Twig_Loader_Array', array('exists', 'getSource'), array(), '', false);
        $realLoader->expects($this->once())->method('exists')->will($this->returnValue(false));
        $realLoader->expects($this->never())->method('getSource');

        $loader = new Twig_Loader_Preprocessor($realLoader, 'trim');
        $this->assertFalse($loader->exists('foo'));

        $realLoader = $this->getMock('Twig_LoaderInterface');
        $realLoader->expects($this->once())->method('getSource')->will($this->returnValue('content'));

        $loader = new Twig_Loader_Preprocessor($realLoader, 'trim');
        $this->assertTrue($loader->exists('foo'));
    }
}
