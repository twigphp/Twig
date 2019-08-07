<?php

namespace Twig\Tests\Loader;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Loader\ArrayLoader;

class ArrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @group legacy
     */
    public function testGetSource()
    {
        $loader = new ArrayLoader(['foo' => 'bar']);

        $this->assertEquals('bar', $loader->getSource('foo'));
    }

    /**
     * @group legacy
     */
    public function testGetSourceWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ArrayLoader([]);

        $loader->getSource('foo');
    }

    public function testGetSourceContextWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ArrayLoader([]);

        $loader->getSourceContext('foo');
    }

    public function testGetCacheKey()
    {
        $loader = new ArrayLoader(['foo' => 'bar']);

        $this->assertEquals('foo:bar', $loader->getCacheKey('foo'));
    }

    public function testGetCacheKeyWhenTemplateHasDuplicateContent()
    {
        $loader = new ArrayLoader([
            'foo' => 'bar',
            'baz' => 'bar',
        ]);

        $this->assertEquals('foo:bar', $loader->getCacheKey('foo'));
        $this->assertEquals('baz:bar', $loader->getCacheKey('baz'));
    }

    public function testGetCacheKeyIsProtectedFromEdgeCollisions()
    {
        $loader = new ArrayLoader([
            'foo__' => 'bar',
            'foo' => '__bar',
        ]);

        $this->assertEquals('foo__:bar', $loader->getCacheKey('foo__'));
        $this->assertEquals('foo:__bar', $loader->getCacheKey('foo'));
    }

    public function testGetCacheKeyWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ArrayLoader([]);

        $loader->getCacheKey('foo');
    }

    public function testSetTemplate()
    {
        $loader = new ArrayLoader([]);
        $loader->setTemplate('foo', 'bar');

        $this->assertEquals('bar', $loader->getSourceContext('foo')->getCode());
    }

    public function testIsFresh()
    {
        $loader = new ArrayLoader(['foo' => 'bar']);
        $this->assertTrue($loader->isFresh('foo', time()));
    }

    public function testIsFreshWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ArrayLoader([]);

        $loader->isFresh('foo', time());
    }

    public function testTemplateReference()
    {
        $name = new Twig_Test_Loader_TemplateReference('foo');
        $loader = new ArrayLoader(['foo' => 'bar']);

        $loader->getCacheKey($name);
        $loader->getSourceContext($name);
        $loader->isFresh($name, time());
        $loader->setTemplate($name, 'foo:bar');

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without crashing PHP
        $this->addToAssertionCount(1);
    }
}

class Twig_Test_Loader_TemplateReference
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return $this->name;
    }
}
