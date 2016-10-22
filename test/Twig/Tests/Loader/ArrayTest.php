<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Loader_ArrayTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Twig_Error_Loader
     */
    public function testGetSourceContextWhenTemplateDoesNotExist()
    {
        $loader = new Twig_Loader_Array(array());

        $loader->getSourceContext('foo');
    }

    public function testGetCacheKey()
    {
        $loader = new Twig_Loader_Array(array('foo' => 'bar'));

        $this->assertEquals('bar', $loader->getCacheKey('foo'));
    }

    /**
     * @expectedException Twig_Error_Loader
     */
    public function testGetCacheKeyWhenTemplateDoesNotExist()
    {
        $loader = new Twig_Loader_Array(array());

        $loader->getCacheKey('foo');
    }

    public function testSetTemplate()
    {
        $loader = new Twig_Loader_Array(array());
        $loader->setTemplate('foo', 'bar');

        $this->assertEquals('bar', $loader->getSourceContext('foo')->getCode());
    }

    public function testIsFresh()
    {
        $loader = new Twig_Loader_Array(array('foo' => 'bar'));
        $this->assertTrue($loader->isFresh('foo', time()));
    }

    /**
     * @expectedException Twig_Error_Loader
     */
    public function testIsFreshWhenTemplateDoesNotExist()
    {
        $loader = new Twig_Loader_Array(array());

        $loader->isFresh('foo', time());
    }
}
