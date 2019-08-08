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
use Twig\Loader\ChainLoader;
use Twig\Loader\ExistsLoaderInterface;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;
use Twig\Source;

class ChainTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @group legacy
     */
    public function testGetSource()
    {
        $loader = new ChainLoader([
            new ArrayLoader(['foo' => 'bar']),
            new ArrayLoader(['foo' => 'foobar', 'bar' => 'foo']),
        ]);

        $this->assertEquals('bar', $loader->getSource('foo'));
        $this->assertEquals('foo', $loader->getSource('bar'));
    }

    public function testGetSourceContext()
    {
        $path = __DIR__.'/../Fixtures';
        $loader = new ChainLoader([
            new ArrayLoader(['foo' => 'bar']),
            new ArrayLoader(['errors/index.html' => 'baz']),
            new FilesystemLoader([$path]),
        ]);

        $this->assertEquals('foo', $loader->getSourceContext('foo')->getName());
        $this->assertSame('', $loader->getSourceContext('foo')->getPath());

        $this->assertEquals('errors/index.html', $loader->getSourceContext('errors/index.html')->getName());
        $this->assertSame('', $loader->getSourceContext('errors/index.html')->getPath());
        $this->assertEquals('baz', $loader->getSourceContext('errors/index.html')->getCode());

        $this->assertEquals('errors/base.html', $loader->getSourceContext('errors/base.html')->getName());
        $this->assertEquals(realpath($path.'/errors/base.html'), realpath($loader->getSourceContext('errors/base.html')->getPath()));
        $this->assertNotEquals('baz', $loader->getSourceContext('errors/base.html')->getCode());
    }

    public function testGetSourceContextWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ChainLoader([]);

        $loader->getSourceContext('foo');
    }

    /**
     * @group legacy
     */
    public function testGetSourceWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ChainLoader([]);

        $loader->getSource('foo');
    }

    public function testGetCacheKey()
    {
        $loader = new ChainLoader([
            new ArrayLoader(['foo' => 'bar']),
            new ArrayLoader(['foo' => 'foobar', 'bar' => 'foo']),
        ]);

        $this->assertEquals('foo:bar', $loader->getCacheKey('foo'));
        $this->assertEquals('bar:foo', $loader->getCacheKey('bar'));
    }

    public function testGetCacheKeyWhenTemplateDoesNotExist()
    {
        $this->expectException('\Twig\Error\LoaderError');

        $loader = new ChainLoader([]);

        $loader->getCacheKey('foo');
    }

    public function testAddLoader()
    {
        $loader = new ChainLoader();
        $loader->addLoader(new ArrayLoader(['foo' => 'bar']));

        $this->assertEquals('bar', $loader->getSourceContext('foo')->getCode());
    }

    public function testExists()
    {
        $loader1 = $this->createMock('Twig\Tests\Loader\ChainTestLoaderWithExistsInterface');
        $loader1->expects($this->once())->method('exists')->willReturn(false);
        $loader1->expects($this->never())->method('getSourceContext');

        // can be removed in 2.0
        $loader2 = $this->createMock('Twig\Tests\Loader\ChainTestLoaderInterface');
        //$loader2 = $this->createMock(['\Twig\Loader\LoaderInterface', '\Twig\Loader\SourceContextLoaderInterface']);
        $loader2->expects($this->once())->method('getSourceContext')->willReturn(new Source('content', 'index'));

        $loader = new ChainLoader();
        $loader->addLoader($loader1);
        $loader->addLoader($loader2);

        $this->assertTrue($loader->exists('foo'));
    }
}

interface ChainTestLoaderInterface extends LoaderInterface, SourceContextLoaderInterface
{
}

interface ChainTestLoaderWithExistsInterface extends LoaderInterface, ExistsLoaderInterface, SourceContextLoaderInterface
{
}
