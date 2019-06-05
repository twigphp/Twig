<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\InitRuntimeInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

require_once __DIR__.'/FilesystemHelper.php';

class Twig_Tests_EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    private $deprecations = [];

    /**
     * @group legacy
     */
    public function testLegacyTokenizeSignature()
    {
        $env = new Environment();
        $stream = $env->tokenize('{{ foo }}', 'foo');
        $this->assertEquals('{{ foo }}', $stream->getSource());
        $this->assertEquals('foo', $stream->getFilename());
    }

    /**
     * @group legacy
     */
    public function testLegacyCompileSourceSignature()
    {
        $loader = new ArrayLoader(['foo' => '{{ foo }}']);
        $env = new Environment($loader);
        $this->assertContains('getTemplateName', $env->compileSource('{{ foo }}', 'foo'));
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage You must set a loader first.
     * @group legacy
     */
    public function testRenderNoLoader()
    {
        $env = new Environment();
        $env->render('test');
    }

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
        // to be removed in 2.0
        $loader = $this->getMockBuilder('Twig_EnvironmentTestLoaderInterface')->getMock();
        //$loader = $this->getMockBuilder(['\Twig\Loader\LoaderInterface', '\Twig\Loader\SourceContextLoaderInterface'])->getMock();
        $loader->expects($this->any())->method('getSourceContext')->willReturn(new Source('', ''));

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

        /* to be uncomment in Twig 2.0
        // globals cannot be added after a template has been loaded
        $twig = new Environment($loader);
        $twig->addGlobal('foo', 'foo');
        $twig->getGlobals();
        $twig->load('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertFalse(array_key_exists('bar', $twig->getGlobals()));
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
            $this->assertFalse(array_key_exists('bar', $twig->getGlobals()));
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
            $this->assertFalse(array_key_exists('bar', $twig->getGlobals()));
        }

        // test adding globals after a template has been loaded without call to getGlobals
        $twig = new Environment($loader);
        $twig->load('index');
        try {
            $twig->addGlobal('bar', 'bar');
            $this->fail();
        } catch (\LogicException $e) {
            $this->assertFalse(array_key_exists('bar', $twig->getGlobals()));
        }
        */
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
        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

        // Cache miss: getTimestamp returns 0 and as a result the load() is
        // skipped.
        $cache->expects($this->once())
            ->method('generateKey')
            ->willReturn('key');
        $cache->expects($this->once())
            ->method('getTimestamp')
            ->willReturn(0);
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

        $cache = $this->getMockBuilder('\Twig\Cache\CacheInterface')->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

        $now = time();

        // Cache hit: getTimestamp returns something > extension timestamps and
        // the loader returns true for isFresh().
        $cache->expects($this->once())
            ->method('generateKey')
            ->willReturn('key');
        $cache->expects($this->once())
            ->method('getTimestamp')
            ->willReturn($now);
        $loader->expects($this->once())
            ->method('isFresh')
            ->willReturn(true);
        $cache->expects($this->atLeastOnce())
            ->method('load');

        $twig->load($templateName);
    }

    public function testAutoReloadOutdatedCacheHit()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->getMockBuilder('\Twig\Cache\CacheInterface')->getMock();
        $loader = $this->getMockLoader($templateName, $templateContent);
        $twig = new Environment($loader, ['cache' => $cache, 'auto_reload' => true, 'debug' => false]);

        $now = time();

        $cache->expects($this->once())
            ->method('generateKey')
            ->willReturn('key');
        $cache->expects($this->once())
            ->method('getTimestamp')
            ->willReturn($now);
        $loader->expects($this->once())
            ->method('isFresh')
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('write');
        $cache->expects($this->once())
            ->method('load');

        $twig->load($templateName);
    }

    /**
     * @group legacy
     */
    public function testHasGetExtensionWithDynamicName()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());

        $ext1 = new Twig_Tests_EnvironmentTest_Extension_DynamicWithDeprecatedName('ext1');
        $ext2 = new Twig_Tests_EnvironmentTest_Extension_DynamicWithDeprecatedName('ext2');
        $twig->addExtension($ext1);
        $twig->addExtension($ext2);

        $this->assertTrue($twig->hasExtension('ext1'));
        $this->assertTrue($twig->hasExtension('ext2'));

        $this->assertTrue($twig->hasExtension('Twig_Tests_EnvironmentTest_Extension_DynamicWithDeprecatedName'));

        $this->assertSame($ext1, $twig->getExtension('ext1'));
        $this->assertSame($ext2, $twig->getExtension('ext2'));
    }

    public function testHasGetExtensionByClassName()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
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
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
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

    /**
     * @requires PHP 5.3
     */
    public function testAddExtensionWithDeprecatedGetGlobals()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension_WithGlobals());

        $this->deprecations = [];
        set_error_handler([$this, 'handleError']);

        $this->assertArrayHasKey('foo_global', $twig->getGlobals());

        $this->assertCount(1, $this->deprecations);
        $this->assertContains('Defining the getGlobals() method in the "Twig_Tests_EnvironmentTest_Extension_WithGlobals" extension ', $this->deprecations[0]);

        restore_error_handler();
    }

    /**
     * @group legacy
     */
    public function testRemoveExtension()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension_WithDeprecatedName());
        $twig->removeExtension('environment_test');

        $this->assertArrayNotHasKey('test', $twig->getTags());
        $this->assertArrayNotHasKey('foo_filter', $twig->getFilters());
        $this->assertArrayNotHasKey('foo_function', $twig->getFunctions());
        $this->assertArrayNotHasKey('foo_test', $twig->getTests());
        $this->assertArrayNotHasKey('foo_unary', $twig->getUnaryOperators());
        $this->assertArrayNotHasKey('foo_binary', $twig->getBinaryOperators());
        $this->assertArrayNotHasKey('foo_global', $twig->getGlobals());
        $this->assertCount(2, $twig->getNodeVisitors());
    }

    public function testAddMockExtension()
    {
        // should be replaced by the following in 2.0 (this current code is just to avoid a dep notice)
        // $extension = $this->getMockBuilder('\Twig\Extension\AbstractExtension')->getMock();
        $extension = eval(<<<EOF
use Twig\Extension\AbstractExtension;

class Twig_Tests_EnvironmentTest_ExtensionInEval extends AbstractExtension
{
}
EOF
        );
        $extension = new Twig_Tests_EnvironmentTest_ExtensionInEval();

        $loader = new ArrayLoader(['page' => 'hey']);

        $twig = new Environment($loader);
        $twig->addExtension($extension);

        $this->assertInstanceOf('\Twig\Extension\ExtensionInterface', $twig->getExtension(\get_class($extension)));
        $this->assertTrue($twig->isTemplateFresh('page', time()));
    }

    public function testInitRuntimeWithAnExtensionUsingInitRuntimeNoDeprecation()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_ExtensionWithoutDeprecationInitRuntime());
        $twig->initRuntime();

        // add a dummy assertion here to satisfy PHPUnit, the only thing we want to test is that the code above
        // can be executed without throwing any deprecations
        $this->addToAssertionCount(1);
    }

    /**
     * @requires PHP 5.3
     */
    public function testInitRuntimeWithAnExtensionUsingInitRuntimeDeprecation()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_ExtensionWithDeprecationInitRuntime());

        $this->deprecations = [];
        set_error_handler([$this, 'handleError']);

        $twig->initRuntime();

        $this->assertCount(1, $this->deprecations);
        $this->assertContains('Defining the initRuntime() method in the "Twig_Tests_EnvironmentTest_ExtensionWithDeprecationInitRuntime" extension is deprecated since version 1.23.', $this->deprecations[0]);

        restore_error_handler();
    }

    public function handleError($type, $msg)
    {
        if (E_USER_DEPRECATED === $type) {
            $this->deprecations[] = $msg;
        }
    }

    /**
     * @requires PHP 5.3
     */
    public function testOverrideExtension()
    {
        $twig = new Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_ExtensionWithDeprecationInitRuntime());

        $this->deprecations = [];
        set_error_handler([$this, 'handleError']);

        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension_WithDeprecatedName());
        $twig->addExtension(new Twig_Tests_EnvironmentTest_Extension_WithDeprecatedName());

        $this->assertCount(1, $this->deprecations);
        $this->assertContains('The possibility to register the same extension twice', $this->deprecations[0]);

        restore_error_handler();
    }

    public function testAddRuntimeLoader()
    {
        $runtimeLoader = $this->getMockBuilder('\Twig\RuntimeLoader\RuntimeLoaderInterface')->getMock();
        $runtimeLoader->expects($this->any())->method('load')->willReturn(new Twig_Tests_EnvironmentTest_Runtime());

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

    protected function getMockLoader($templateName, $templateContent)
    {
        // to be removed in 2.0
        $loader = $this->getMockBuilder('Twig_EnvironmentTestLoaderInterface')->getMock();
        //$loader = $this->getMockBuilder(['\Twig\Loader\LoaderInterface', '\Twig\Loader\SourceContextLoaderInterface'])->getMock();
        $loader->expects($this->any())
          ->method('getSourceContext')
          ->with($templateName)
          ->willReturn(new Source($templateContent, $templateName));
        $loader->expects($this->any())
          ->method('getCacheKey')
          ->with($templateName)
          ->willReturn($templateName);

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
            new TwigFilter('foo_filter', 'foo_filter'),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('foo_test', 'foo_test'),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('foo_function', 'foo_function'),
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

class Twig_Tests_EnvironmentTest_Extension_WithDeprecatedName extends AbstractExtension
{
    public function getName()
    {
        return 'environment_test';
    }
}

class Twig_Tests_EnvironmentTest_Extension_DynamicWithDeprecatedName extends AbstractExtension
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
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
    public function enterNode(Twig_NodeInterface $node, Environment $env)
    {
        return $node;
    }

    public function leaveNode(Twig_NodeInterface $node, Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}

class Twig_Tests_EnvironmentTest_ExtensionWithDeprecationInitRuntime extends AbstractExtension
{
    public function initRuntime(Environment $env)
    {
    }
}

class Twig_Tests_EnvironmentTest_ExtensionWithoutDeprecationInitRuntime extends AbstractExtension implements InitRuntimeInterface
{
    public function initRuntime(Environment $env)
    {
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

// to be removed in 2.0
interface Twig_EnvironmentTestLoaderInterface extends LoaderInterface, SourceContextLoaderInterface
{
}
