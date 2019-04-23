<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Cache\CacheInterface;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class Twig_Tests_EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    public function testAutoescapeOption()
    {
        $loader = new ArrayLoader([
            'html' => '{{ foo }} {{ foo }}',
            'js' => '{{ bar }} {{ bar }}',
        ]);

        $twig = new Environment($loader, [
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
        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $loader->expects($this->any())->method('getSourceContext')->will($this->returnValue(new Source('', '')));

        // globals can be added after calling getGlobals
        $twig = new Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after a template has been loaded
        $twig = new Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->load('index');
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions init
        $twig = new Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        // globals can be modified after extensions and a template has been loaded
        $arrayLoader = new ArrayLoader(['index' => '{{foo}}']);
        $twig = new Environment($arrayLoader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->load('index');
        $twig->addGlobal('foo', 'bar');
        $globals = $twig->getGlobals();
        $this->assertEquals('bar', $globals['foo']);

        $twig = new Environment($arrayLoader);
        $twig->getGlobals();
        $twig->addGlobal('foo', 'bar');
        $template = $twig->load('index');
        $this->assertEquals('bar', $template->render([]));

        // globals cannot be added after a template has been loaded
        $twig = new Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->load('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }

        // globals cannot be added after extensions init
        $twig = new Environment($loader);
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
        $twig = new Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->getFunctions();
        $twig->load('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }

        // test adding globals after a template has been loaded without call to getGlobals
        $twig = new Environment($loader);
        $twig->load('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertArrayNotHasKey('bar', $twig->getGlobals());
        }
    }

    public function testExtensionsAreNotInitializedWhenRenderingACompiledTemplate()
    {
        $cache = new FilesystemCache($dir = sys_get_temp_dir().'/twig');
        $options = ['cache' => $cache, 'auto_reload' => false, 'debug' => false];

        // force compilation
        $twig = new Environment($loader = new ArrayLoader(['index' => '{{ foo }}']), $options);

        $key = $cache->generateKey('index', $twig->getTemplateClass('index'));
        $cache->write($key, $twig->compileSource(new Source('{{ foo }}', 'index')));

        // check that extensions won't be initialized when rendering a template that is already in the cache
        $twig = $this
            ->getMockBuilder(Environment::class)
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

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

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

        $twig->load($templateName);
    }

    public function testAutoReloadCacheHit()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

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

        $twig->load($templateName);
    }

    public function testAutoReloadOutdatedCacheHit()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

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

        $twig->load($templateName);
    }

    public function testHasGetExtensionByClassName()
    {
        $twig = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
        $twig->addExtension($ext = new Twig_Tests_EnvironmentTest_Extension());
        $this->assertTrue($twig->hasExtension('Twig_Tests_EnvironmentTest_Extension'));
        $this->assertTrue($twig->hasExtension('\Twig_Tests_EnvironmentTest_Extension'));

        $this->assertSame($ext, $twig->getExtension('Twig_Tests_EnvironmentTest_Extension'));
        $this->assertSame($ext, $twig->getExtension('\Twig_Tests_EnvironmentTest_Extension'));
    }

    public function testAddExtension()
    {
        $twig = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());
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
        $extension = $this->getMockBuilder(ExtensionInterface::class)->getMock();

        $loader = new ArrayLoader(['page' => 'hey']);

        $twig = new Environment($loader);
        $twig->addExtension($extension);

        $this->assertInstanceOf(ExtensionInterface::class, $twig->getExtension(\get_class($extension)));
        $this->assertTrue($twig->isTemplateFresh('page', time()));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unable to register extension "Twig_Tests_EnvironmentTest_Extension" as it is already registered.
     */
    public function testOverrideExtension()
    {
        $twig = new Environment($this->getMockBuilder(LoaderInterface::class)->getMock());

        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension());
    }

    public function testAddRuntimeLoader()
    {
        $runtimeLoader = $this->getMockBuilder(RuntimeLoaderInterface::class)->getMock();
        $runtimeLoader->expects($this->any())->method('load')->will($this->returnValue(new Twig_Tests_EnvironmentTest_Runtime()));

        $loader = new ArrayLoader([
            'func_array' => '{{ from_runtime_array("foo") }}',
            'func_array_default' => '{{ from_runtime_array() }}',
            'func_array_named_args' => '{{ from_runtime_array(name="foo") }}',
            'func_string' => '{{ from_runtime_string("foo") }}',
            'func_string_default' => '{{ from_runtime_string() }}',
            'func_string_named_args' => '{{ from_runtime_string(name="foo") }}',
        ]);

        $twig = new Environment($loader);
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
     * @expectedExceptionMessage Failed to load Twig template "testFailLoadTemplate.twig", index "112233": cache might be corrupted in "testFailLoadTemplate.twig".
     */
    public function testFailLoadTemplate()
    {
        $template = 'testFailLoadTemplate.twig';
        $twig = new Environment(new ArrayLoader([$template => false]));
        $twig->loadTemplate($twig->getTemplateClass($template), $template, 112233);
    }

    protected function getMockLoader($templateName, $templateContent)
    {
        $loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $loader->expects($this->any())
          ->method('getSourceContext')
          ->with($templateName)
          ->will($this->returnValue(new Source($templateContent, $templateName)));
        $loader->expects($this->any())
          ->method('getCacheKey')
          ->with($templateName)
          ->will($this->returnValue($templateName));

        return $loader;
    }
}

class Twig_Tests_EnvironmentTest_Extension_WithGlobals extends AbstractExtension
{
    public function getGlobals()
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}

class Twig_Tests_EnvironmentTest_Extension extends AbstractExtension implements GlobalsInterface
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
            new TwigFilter('foo_filter'),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('foo_test'),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('foo_function'),
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

class Twig_Tests_EnvironmentTest_TokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
    }

    public function getTag()
    {
        return 'test';
    }
}

class Twig_Tests_EnvironmentTest_NodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env)
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}

class Twig_Tests_EnvironmentTest_ExtensionWithoutRuntime extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('from_runtime_array', ['Twig_Tests_EnvironmentTest_Runtime', 'fromRuntime']),
            new TwigFunction('from_runtime_string', 'Twig_Tests_EnvironmentTest_Runtime::fromRuntime'),
        ];
    }
}

class Twig_Tests_EnvironmentTest_Runtime
{
    public function fromRuntime($name = 'bar')
    {
        return $name;
    }
}
