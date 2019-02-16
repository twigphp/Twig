<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    public function testAutoescapeOption()
    {
        $loader = new \Twig\Loader\ArrayLoader([
            'html' => '{{ foo }} {{ foo }}',
            'js' => '{{ bar }} {{ bar }}',
        ]);

        $twig = new \Twig\Environment($loader, [
            'debug' => true,
            'cache' => false,
            'autoescape' => [$this, 'escapingStrategyCallback'],
        ]);

        $this->assertEquals('foo&lt;br/ &gt; foo&lt;br/ &gt;', $twig->render('html', ['foo' => 'foo<br/ >']));
        $this->assertEquals('foo\u003Cbr\/\u0020\u003E foo\u003Cbr\/\u0020\u003E', $twig->render('js', ['bar' => 'foo<br/ >']));
    }

    public function escapingStrategyCallback($name)
    {
        return $name;
    }

    public function testGlobals()
    {
        $loader = $this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock();
        $loader->expects($this->any())->method('getSourceContext')->will($this->returnValue(new \Twig\Source('', '')));

        // globals can be added after calling getGlobals
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after a template has been loaded
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->loadTemplate('index');
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions init
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions and a template has been loaded
        $arrayLoader = new \Twig\Loader\ArrayLoader(['index' => '{{foo}}']);
        $twig = new \Twig\Environment($arrayLoader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->loadTemplate('index');
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        $twig = new \Twig\Environment($arrayLoader);
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $template = $twig->loadTemplate('index');
        $this->assertEquals('bar', $template->render([]));

        // globals cannot be added after a template has been loaded
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->loadTemplate('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }

        // globals cannot be added after extensions init
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }

        // globals cannot be added after extensions and a template has been loaded
        $twig = new \Twig\Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->loadTemplate('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }

        // test adding globals after a template has been loaded without call to getGlobals
        $twig = new \Twig\Environment($loader);
        $twig->loadTemplate('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }
    }

    public function testExtensionsAreNotInitializedWhenRenderingACompiledTemplate()
    {
        $cache = new \Twig\Cache\FilesystemCache($dir = sys_get_temp_dir().'/twig');
        $options = ['cache' => $cache, 'auto_reload' => false, 'debug' => false];

        // force compilation
        $twig = new \Twig\Environment($loader = new \Twig\Loader\ArrayLoader(['index' => '{{ foo }}']), $options);

        $key = $cache->generateKey('index', $twig->getTemplateClass('index'));
        $cache->write($key, $twig->compileSource(new \Twig\Source('{{ foo }}', 'index')));

        // check that extensions won't be initialized when rendering a template that is already in the cache
        $twig = $this
            ->getMockBuilder('\Twig\Environment')
            ->setConstructorArgs([$loader, $options])
            ->setMethods(['initExtensions'])
            ->getMock()
        ;

        $twig->expects($this->never())->method('initExtensions');

        // render template
        $output = $twig->render('index', ['foo' => 'bar']);
        $this->assertEquals('bar', $output);

        Twig_Tests_FilesystemHelper::removeDir($dir);
    }

    public function testAutoReloadCacheMiss()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->getMockBuilder('\Twig\Cache\CacheInterface')->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new \Twig\Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

        // Cache miss: getTimestamp returns 0 and as a result the load() is
        // skipped.
        $cache->expects($this->once())
            ->method('generateKey')
            ->will($this->returnValue('key'));
        $cache->expects($this->once())
            ->method('getTimestamp')
            ->will($this->returnValue(0));
        $loader->expects($this->never())
            ->method('isFresh');
        $cache->expects($this->once())
            ->method('write');
        $cache->expects($this->once())
            ->method('load');

        $twig->loadTemplate($templateName);
    }

    public function testAutoReloadCacheHit()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->getMockBuilder('\Twig\Cache\CacheInterface')->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new \Twig\Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

        $now = time();

        // Cache hit: getTimestamp returns something > extension timestamps and
        // the loader returns true for isFresh().
        $cache->expects($this->once())
            ->method('generateKey')
            ->will($this->returnValue('key'));
        $cache->expects($this->once())
            ->method('getTimestamp')
            ->will($this->returnValue($now));
        $loader->expects($this->once())
            ->method('isFresh')
            ->will($this->returnValue(true));
        $cache->expects($this->atLeastOnce())
            ->method('load');

        $twig->loadTemplate($templateName);
    }

    public function testAutoReloadOutdatedCacheHit()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->getMockBuilder('\Twig\Cache\CacheInterface')->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new \Twig\Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

        $now = time();

        $cache->expects($this->once())
            ->method('generateKey')
            ->will($this->returnValue('key'));
        $cache->expects($this->once())
            ->method('getTimestamp')
            ->will($this->returnValue($now));
        $loader->expects($this->once())
            ->method('isFresh')
            ->will($this->returnValue(false));
        $cache->expects($this->once())
            ->method('write');
        $cache->expects($this->once())
            ->method('load');

        $twig->loadTemplate($templateName);
    }

    public function testHasGetExtensionByClassName()
    {
        $twig = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension($ext = new Twig_Tests_EnvironmentTest_Extension());
        $this->assertTrue($twig->hasExtension('Twig_Tests_EnvironmentTest_Extension'));
        $this->assertTrue($twig->hasExtension('\Twig_Tests_EnvironmentTest_Extension'));

        $this->assertSame($ext, $twig->getExtension('Twig_Tests_EnvironmentTest_Extension'));
        $this->assertSame($ext, $twig->getExtension('\Twig_Tests_EnvironmentTest_Extension'));

        $this->assertTrue($twig->hasExtension('Twig\Tests\EnvironmentTest\Extension'));
        $this->assertSame($ext, $twig->getExtension('Twig\Tests\EnvironmentTest\Extension'));
    }

    public function testAddExtension()
    {
        $twig = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension());

        $this->assertArrayHasKey('test', $twig->getTags());
        $this->assertArrayHasKey('foo_filter', $twig->getFilters());
        $this->assertArrayHasKey('foo_function', $twig->getFunctions());
        $this->assertArrayHasKey('foo_test', $twig->getTests());
        $this->assertArrayHasKey('foo_unary', $twig->getUnaryOperators());
        $this->assertArrayHasKey('foo_binary', $twig->getBinaryOperators());
        $this->assertArrayHasKey('foo_global', $twig->getGlobals());
        $visitors = $twig->getNodeVisitors();
        $found = false;
        foreach ($visitors as $visitor) {
            if ($visitor instanceof Twig_Tests_EnvironmentTest_NodeVisitor) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testAddMockExtension()
    {
        $extension = $this->getMockBuilder('\Twig\Extension\ExtensionInterface')->getMock();

        $loader = new \Twig\Loader\ArrayLoader(['page' => 'hey']);

        $twig = new \Twig\Environment($loader);
        $twig->addExtension($extension);

        $this->assertInstanceOf('\Twig\Extension\ExtensionInterface', $twig->getExtension(\get_class($extension)));
        $this->assertTrue($twig->isTemplateFresh('page', time()));
    }

    public function testInitRuntimeWithAnExtensionUsingInitRuntimeNoDeprecation()
    {
        $loader = $this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock();
        $twig = new \Twig\Environment($loader);
        $loader->expects($this->once())->method('getSourceContext')->will($this->returnValue(new \Twig\Source('', '')));
        $twig->addExtension(new Twig_Tests_EnvironmentTest_ExtensionWithoutDeprecationInitRuntime());
        $twig->loadTemplate('');

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any deprecations
        $this->addToAssertionCount(1);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to register extension "Twig_Tests_EnvironmentTest_Extension" as it is already registered.
     */
    public function testOverrideExtension()
    {
        $twig = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());

        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension());
    }

    public function testAddRuntimeLoader()
    {
        $runtimeLoader = $this->getMockBuilder('\Twig\RuntimeLoader\RuntimeLoaderInterface')->getMock();
        $runtimeLoader->expects($this->any())->method('load')->will($this->returnValue(new Twig_Tests_EnvironmentTest_Runtime()));

        $loader = new \Twig\Loader\ArrayLoader([
            'func_array' => '{{ from_runtime_array("foo") }}',
            'func_array_default' => '{{ from_runtime_array() }}',
            'func_array_named_args' => '{{ from_runtime_array(name="foo") }}',
            'func_string' => '{{ from_runtime_string("foo") }}',
            'func_string_default' => '{{ from_runtime_string() }}',
            'func_string_named_args' => '{{ from_runtime_string(name="foo") }}',
        ]);

        $twig = new \Twig\Environment($loader);
        $twig->addExtension(new Twig_Tests_EnvironmentTest_ExtensionWithoutRuntime());
        $twig->addRuntimeLoader($runtimeLoader);

        $this->assertEquals('foo', $twig->render('func_array'));
        $this->assertEquals('bar', $twig->render('func_array_default'));
        $this->assertEquals('foo', $twig->render('func_array_named_args'));
        $this->assertEquals('foo', $twig->render('func_string'));
        $this->assertEquals('bar', $twig->render('func_string_default'));
        $this->assertEquals('foo', $twig->render('func_string_named_args'));
    }

    /**
     * @expectedException \Twig\Error\RuntimeError
     * @expectedExceptionMessage Failed to load Twig template "testFailLoadTemplate.twig", index "abc": cache is corrupted in "testFailLoadTemplate.twig".
     */
    public function testFailLoadTemplate()
    {
        $template = 'testFailLoadTemplate.twig';
        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader([$template => false]));
        //$twig->setCache(new CorruptCache());
        $twig->loadTemplate($template, 'abc');
    }

    /**
     * @expectedException \Twig\Error\RuntimeError
     * @expectedExceptionMessage Circular reference detected for Twig template "base.html.twig", path: base.html.twig -> base.html.twig in "base.html.twig" at line 1
     */
    public function testFailLoadTemplateOnCircularReference()
    {
        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader([
            'base.html.twig' => '{% extends "base.html.twig" %}',
        ]));

        $twig->loadTemplate('base.html.twig');
    }

    /**
     * @expectedException \Twig\Error\RuntimeError
     * @expectedExceptionMessage Circular reference detected for Twig template "base1.html.twig", path: base1.html.twig -> base2.html.twig -> base1.html.twig in "base1.html.twig" at line 1
     */
    public function testFailLoadTemplateOnComplexCircularReference()
    {
        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader([
            'base1.html.twig' => '{% extends "base2.html.twig" %}',
            'base2.html.twig' => '{% extends "base1.html.twig" %}',
        ]));

        $twig->loadTemplate('base1.html.twig');
    }

    protected function getMockLoader($templateName, $templateContent)
    {
        $loader = $this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock();
        $loader->expects($this->any())
          ->method('getSourceContext')
          ->with($templateName)
          ->will($this->returnValue(new \Twig\Source($templateContent, $templateName)));
        $loader->expects($this->any())
          ->method('getCacheKey')
          ->with($templateName)
          ->will($this->returnValue($templateName));

        return $loader;
    }
}

class CorruptCache implements \Twig\Cache\CacheInterface
{
    public function generateKey($name, $className)
    {
        return $name.':'.$className;
    }

    public function write($key, $content)
    {
    }

    public function load($key)
    {
    }

    public function getTimestamp($key)
    {
        time();
    }
}

class Twig_Tests_EnvironmentTest_Extension_WithGlobals extends \Twig\Extension\AbstractExtension
{
    public function getGlobals()
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}

class Twig_Tests_EnvironmentTest_Extension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    public function getTokenParsers()
    {
        return [
            new Twig_Tests_EnvironmentTest_TokenParser(),
        ];
    }

    public function getNodeVisitors()
    {
        return [
            new Twig_Tests_EnvironmentTest_NodeVisitor(),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('foo_filter'),
        ];
    }

    public function getTests()
    {
        return [
            new \Twig\TwigTest('foo_test'),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('foo_function'),
        ];
    }

    public function getOperators()
    {
        return [
            ['foo_unary' => []],
            ['foo_binary' => []],
        ];
    }

    public function getGlobals()
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}
class_alias('Twig_Tests_EnvironmentTest_Extension', 'Twig\Tests\EnvironmentTest\Extension', false);

class Twig_Tests_EnvironmentTest_TokenParser extends \Twig\TokenParser\AbstractTokenParser
{
    public function parse(\Twig\Token $token)
    {
    }

    public function getTag()
    {
        return 'test';
    }
}

class Twig_Tests_EnvironmentTest_NodeVisitor implements \Twig\NodeVisitor\NodeVisitorInterface
{
    public function enterNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        return $node;
    }

    public function leaveNode(\Twig\Node\Node $node, \Twig\Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}

class Twig_Tests_EnvironmentTest_ExtensionWithDeprecationInitRuntime extends \Twig\Extension\AbstractExtension
{
    public function initRuntime(\Twig\Environment $env)
    {
    }
}

class Twig_Tests_EnvironmentTest_ExtensionWithoutDeprecationInitRuntime extends \Twig\Extension\AbstractExtension implements \Twig\Extension\InitRuntimeInterface
{
    public function initRuntime(\Twig\Environment $env)
    {
    }
}

class Twig_Tests_EnvironmentTest_ExtensionWithoutRuntime extends \Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('from_runtime_array', ['Twig_Tests_EnvironmentTest_Runtime', 'fromRuntime']),
            new \Twig\TwigFunction('from_runtime_string', 'Twig_Tests_EnvironmentTest_Runtime::fromRuntime'),
        ];
    }

    public function getName()
    {
        return 'from_runtime';
    }
}

class Twig_Tests_EnvironmentTest_Runtime
{
    public function fromRuntime($name = 'bar')
    {
        return $name;
    }
}
