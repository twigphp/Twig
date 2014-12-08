<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Loader_ChainTest extends PHPUnit_Framework_TestCase
{
    public function testGetSource()
    {
        $loader = new Twig_Loader_Chain(array(
            new Twig_Loader_Array(array('foo' => 'bar')),
            new Twig_Loader_Array(array('foo' => 'foobar', 'bar' => 'foo')),
        ));

        $this->assertEquals('bar', $loader->getSource('foo'));
        $this->assertEquals('foo', $loader->getSource('bar'));
    }

    /**
     * @expectedException Twig_Error_Loader
     */
    public function testGetSourceWhenTemplateDoesNotExist()
    {
        $loader = new Twig_Loader_Chain(array());

        $loader->getSource('foo');
    }

    public function testGetCacheKey()
    {
        $loader = new Twig_Loader_Chain(array(
            new Twig_Loader_Array(array('foo' => 'bar')),
            new Twig_Loader_Array(array('foo' => 'foobar', 'bar' => 'foo')),
        ));

        $this->assertEquals('bar', $loader->getCacheKey('foo'));
        $this->assertEquals('foo', $loader->getCacheKey('bar'));
    }

    /**
     * @expectedException Twig_Error_Loader
     */
    public function testGetCacheKeyWhenTemplateDoesNotExist()
    {
        $loader = new Twig_Loader_Chain(array());

        $loader->getCacheKey('foo');
    }

    public function testAddLoader()
    {
        $loader = new Twig_Loader_Chain();
        $loader->addLoader(new Twig_Loader_Array(array('foo' => 'bar')));

        $this->assertEquals('bar', $loader->getSource('foo'));
    }

    public function testExists()
    {
        $loader1 = $this->getMock('Twig_Loader_Array', array('exists', 'getSource'), array(), '', false);
        $loader1->expects($this->once())->method('exists')->will($this->returnValue(false));
        $loader1->expects($this->never())->method('getSource');

        $loader2 = $this->getMock('Twig_LoaderInterface');
        $loader2->expects($this->once())->method('getSource')->will($this->returnValue('content'));

        $loader = new Twig_Loader_Chain();
        $loader->addLoader($loader1);
        $loader->addLoader($loader2);

        $this->assertTrue($loader->exists('foo'));
    }

    public function testGetLastLoadedTemplateName()
    {
        $loader1 = $this->getMock('Twig_Loader_Array', array('getLastLoadedTemplateName', 'exists', 'getSource'), array(), '', false);
        $loader1->expects($this->at(0))
            ->method('exists')
            ->with($this->equalTo('bar.twig'))
            ->will($this->returnValue(false));
        $loader1->expects($this->at(1))
            ->method('exists')
            ->with($this->equalTo('foo.twig'))
            ->will($this->returnValue(true));
        $loader1->expects($this->once())->method('getSource')->will($this->returnValue('foo'));
        $loader1->expects($this->once())->method('getLastLoadedTemplateName')->will($this->returnValue('foo.twig'));

        $loader2 = $this->getMock('Twig_Loader_Array', array('getLastLoadedTemplateName', 'exists', 'getSource'), array(), '', false);
        $loader2->expects($this->at(0))
            ->method('exists')
            ->with($this->equalTo('bar.twig'))
            ->will($this->returnValue(true));
        $loader2->expects($this->once())->method('getSource')->will($this->returnValue('bar'));
        $loader2->expects($this->once())->method('getLastLoadedTemplateName')->will($this->returnValue('bar.twig'));

        $loader = new Twig_Loader_Chain(array($loader1, $loader2));

        $loader->getSource('bar.twig');
        $this->assertEquals('bar.twig', $loader->getLastLoadedTemplateName());

        $loader->getSource('foo.twig');
        $this->assertEquals('foo.twig', $loader->getLastLoadedTemplateName());
    }
}
