<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Loader_ChainTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @group legacy
     */
    public function testGetSource()
    {
        $loader = new \Twig\Loader\ChainLoader([
            new \Twig\Loader\ArrayLoader(['foo' => 'bar']),
            new \Twig\Loader\ArrayLoader(['foo' => 'foobar', 'bar' => 'foo']),
        ]);

        $this->assertEquals('bar', $loader->getSource('foo'));
        $this->assertEquals('foo', $loader->getSource('bar'));
    }

    public function testGetSourceContext()
    {
        $path = __DIR__.'/../Fixtures';
        $loader = new \Twig\Loader\ChainLoader([
            new \Twig\Loader\ArrayLoader(['foo' => 'bar']),
            new \Twig\Loader\ArrayLoader(['errors/index.html' => 'baz']),
            new \Twig\Loader\FilesystemLoader([$path]),
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

    /**
     * @expectedException \Twig\Error\LoaderError
     */
    public function testGetSourceContextWhenTemplateDoesNotExist()
    {
        $loader = new \Twig\Loader\ChainLoader([]);

        $loader->getSourceContext('foo');
    }

    /**
     * @group legacy
     * @expectedException \Twig\Error\LoaderError
     */
    public function testGetSourceWhenTemplateDoesNotExist()
    {
        $loader = new \Twig\Loader\ChainLoader([]);

        $loader->getSource('foo');
    }

    public function testGetCacheKey()
    {
        $loader = new \Twig\Loader\ChainLoader([
            new \Twig\Loader\ArrayLoader(['foo' => 'bar']),
            new \Twig\Loader\ArrayLoader(['foo' => 'foobar', 'bar' => 'foo']),
        ]);

        $this->assertEquals('foo:bar', $loader->getCacheKey('foo'));
        $this->assertEquals('bar:foo', $loader->getCacheKey('bar'));
    }

    /**
     * @expectedException \Twig\Error\LoaderError
     */
    public function testGetCacheKeyWhenTemplateDoesNotExist()
    {
        $loader = new \Twig\Loader\ChainLoader([]);

        $loader->getCacheKey('foo');
    }

    public function testAddLoader()
    {
        $loader = new \Twig\Loader\ChainLoader();
        $loader->addLoader(new \Twig\Loader\ArrayLoader(['foo' => 'bar']));

        $this->assertEquals('bar', $loader->getSourceContext('foo')->getCode());
    }

    public function testExists()
    {
        $loader1 = $this->getMockBuilder('Twig_ChainTestLoaderWithExistsInterface')->getMock();
        $loader1->expects($this->once())->method('exists')->will($this->returnValue(false));
        $loader1->expects($this->never())->method('getSourceContext');

        // can be removed in 2.0
        $loader2 = $this->getMockBuilder('Twig_ChainTestLoaderInterface')->getMock();
        //$loader2 = $this->getMockBuilder(['\Twig\Loader\LoaderInterface', '\Twig\Loader\SourceContextLoaderInterface'])->getMock();
        $loader2->expects($this->once())->method('getSourceContext')->will($this->returnValue(new \Twig\Source('content', 'index')));

        $loader = new \Twig\Loader\ChainLoader();
        $loader->addLoader($loader1);
        $loader->addLoader($loader2);

        $this->assertTrue($loader->exists('foo'));
    }
}

interface Twig_ChainTestLoaderInterface extends \Twig\Loader\LoaderInterface, Twig_SourceContextLoaderInterface
{
}

interface Twig_ChainTestLoaderWithExistsInterface extends \Twig\Loader\LoaderInterface, Twig_ExistsLoaderInterface, Twig_SourceContextLoaderInterface
{
}
