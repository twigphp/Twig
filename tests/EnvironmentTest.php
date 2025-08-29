<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Cache\CacheInterface;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\ExpressionParser\Infix\BinaryOperatorExpressionParser;
use Twig\ExpressionParser\InfixExpressionParserInterface;
use Twig\ExpressionParser\Prefix\UnaryOperatorExpressionParser;
use Twig\ExpressionParser\PrefixExpressionParserInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Source;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class EnvironmentTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testVersionConstants()
    {
        $version = Environment::VERSION;
        $exploded = explode('-', $version);
        $this->assertEquals(Environment::EXTRA_VERSION, $exploded[1] ?? '');

        $version = $exploded[0];
        $exploded = explode('.', $version);
        $this->assertEquals(Environment::MAJOR_VERSION, $exploded[0]);
        $this->assertEquals(Environment::MINOR_VERSION, $exploded[1]);
        $this->assertEquals(Environment::RELEASE_VERSION, $exploded[2]);

        $this->assertEquals(Environment::VERSION_ID, Environment::MAJOR_VERSION * 10000 + Environment::MINOR_VERSION * 100 + Environment::RELEASE_VERSION);
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
        $loader = $this->createMock(LoaderInterface::class);
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
        $twig->addExtension($extension = new class extends AbstractExtension {
            public bool $throw = false;

            public function getFilters(): array
            {
                if ($this->throw) {
                    throw new \RuntimeException('Extension are not supposed to be initialized.');
                }

                return parent::getFilters();
            }
        });

        $key = $cache->generateKey('index', $twig->getTemplateClass('index'));
        $cache->write($key, $twig->compileSource(new Source('{{ foo }}', 'index')));

        // check that extensions won't be initialized when rendering a template that is already in the cache
        $twig = new Environment($loader, $options);
        $extension->throw = true;
        $twig->addExtension($extension);

        // render template
        $output = $twig->render('index', ['foo' => 'bar']);
        $this->assertEquals('bar', $output);

        FilesystemHelper::removeDir($dir);
    }

    public function testAutoReloadCacheMiss()
    {
        $templateName = __FUNCTION__;
        $templateContent = __FUNCTION__;

        $cache = $this->createMock(CacheInterface::class);
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

        $cache = $this->createMock(CacheInterface::class);
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

        $cache = $this->createMock(CacheInterface::class);
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

    public function testHasGetExtensionByClassName()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension($ext = new EnvironmentTest_Extension());
        $this->assertSame($ext, $twig->getExtension(EnvironmentTest_Extension::class));
        $this->assertSame($ext, $twig->getExtension(EnvironmentTest_Extension::class));
    }

    public function testAddExtension()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new EnvironmentTest_Extension());

        $this->assertArrayHasKey('test', $twig->getTokenParsers());
        $this->assertArrayHasKey('foo_filter', $twig->getFilters());
        $this->assertArrayHasKey('foo_function', $twig->getFunctions());
        $this->assertArrayHasKey('foo_test', $twig->getTests());
        $this->assertNotNull($twig->getExpressionParsers()->getByName(PrefixExpressionParserInterface::class, 'foo_unary'));
        $this->assertNotNull($twig->getExpressionParsers()->getByName(InfixExpressionParserInterface::class, 'foo_binary'));
        $this->assertArrayHasKey('foo_global', $twig->getGlobals());
        $visitors = $twig->getNodeVisitors();
        $found = false;
        foreach ($visitors as $visitor) {
            if ($visitor instanceof EnvironmentTest_NodeVisitor) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testAddMockExtension()
    {
        $extension = $this->createMock(ExtensionInterface::class);
        $loader = new ArrayLoader(['page' => 'hey']);

        $twig = new Environment($loader);
        $twig->addExtension($extension);

        $this->assertInstanceOf(ExtensionInterface::class, $twig->getExtension($extension::class));
        $this->assertTrue($twig->isTemplateFresh('page', time()));
    }

    public function testOverrideExtension()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new EnvironmentTest_Extension());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to register extension "Twig\Tests\EnvironmentTest_Extension" as it is already registered.');

        $twig->addExtension(new EnvironmentTest_Extension());
    }

    public function testAddRuntimeLoader()
    {
        $runtimeLoader = $this->createMock(RuntimeLoaderInterface::class);
        $runtimeLoader->expects($this->any())->method('load')->willReturn(new EnvironmentTest_Runtime());

        $loader = new ArrayLoader([
            'func_array' => '{{ from_runtime_array("foo") }}',
            'func_array_default' => '{{ from_runtime_array() }}',
            'func_array_named_args' => '{{ from_runtime_array(name="foo") }}',
            'func_string' => '{{ from_runtime_string("foo") }}',
            'func_string_default' => '{{ from_runtime_string() }}',
            'func_string_named_args' => '{{ from_runtime_string(name="foo") }}',
        ]);

        $twig = new Environment($loader, ['autoescape' => false]);
        $twig->addExtension(new EnvironmentTest_ExtensionWithoutRuntime());
        $twig->addRuntimeLoader($runtimeLoader);

        $this->assertEquals('foo', $twig->render('func_array'));
        $this->assertEquals('bar', $twig->render('func_array_default'));
        $this->assertEquals('foo', $twig->render('func_array_named_args'));
        $this->assertEquals('foo', $twig->render('func_string'));
        $this->assertEquals('bar', $twig->render('func_string_default'));
        $this->assertEquals('foo', $twig->render('func_string_named_args'));
    }

    public function testFailLoadTemplate()
    {
        $template = 'testFailLoadTemplate.twig';
        $twig = new Environment(new ArrayLoader([$template => false]));

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Failed to load Twig template "testFailLoadTemplate.twig", index "112233": cache might be corrupted in "testFailLoadTemplate.twig".');

        $twig->loadTemplate($twig->getTemplateClass($template), $template, 112233);
    }

    public function testUndefinedFunctionCallback()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->registerUndefinedFunctionCallback(function (string $name) {
            if ('dynamic' === $name) {
                return new TwigFunction('dynamic', function () { return 'dynamic'; });
            }

            return false;
        });

        $this->assertNull($twig->getFunction('does_not_exist'));
        $this->assertInstanceOf(TwigFunction::class, $function = $twig->getFunction('dynamic'));
        $this->assertSame('dynamic', $function->getName());
    }

    public function testUndefinedFilterCallback()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->registerUndefinedFilterCallback(function (string $name) {
            if ('dynamic' === $name) {
                return new TwigFilter('dynamic', function () { return 'dynamic'; });
            }

            return false;
        });

        $this->assertNull($twig->getFilter('does_not_exist'));
        $this->assertInstanceOf(TwigFilter::class, $filter = $twig->getFilter('dynamic'));
        $this->assertSame('dynamic', $filter->getName());
    }

    public function testUndefinedTestCallback()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->registerUndefinedTestCallback(function (string $name) {
            if ('dynamic' === $name) {
                return new TwigTest('dynamic', function () { return 'dynamic'; });
            }

            return false;
        });

        $this->assertNull($twig->getTest('does_not_exist'));
        $this->assertInstanceOf(TwigTest::class, $test = $twig->getTest('dynamic'));
        $this->assertSame('dynamic', $test->getName());
    }

    public function testUndefinedTokenParserCallback()
    {
        $twig = new Environment(new ArrayLoader());
        $twig->registerUndefinedTokenParserCallback(function (string $name) {
            if ('dynamic' === $name) {
                $parser = $this->createMock(TokenParserInterface::class);
                $parser->expects($this->once())->method('getTag')->willReturn('dynamic');

                return $parser;
            }

            return false;
        });

        $this->assertNull($twig->getTokenParser('does_not_exist'));
        $this->assertInstanceOf(TokenParserInterface::class, $parser = $twig->getTokenParser('dynamic'));
        $this->assertSame('dynamic', $parser->getTag());
    }

    /**
     * @group legacy
     *
     * @requires PHP 8
     */
    public function testLegacyEchoingNode()
    {
        $loader = new ArrayLoader(['echo_bar' => 'A{% set v %}B{% test %}C{% endset %}D{% test %}E{{ v }}F{% set w %}{% test %}{% endset %}G{{ w }}H']);

        $twig = new Environment($loader);
        $twig->addExtension(new EnvironmentTest_Extension());

        if ($twig->useYield()) {
            $this->expectException(SyntaxError::class);
            $this->expectExceptionMessage('An exception has been thrown during the compilation of a template ("You cannot enable the "use_yield" option of Twig as node "Twig\Tests\EnvironmentTest_LegacyEchoingNode" is not marked as ready for it; please make it ready and then flag it with the #[\Twig\Attribute\YieldReady] attribute.") in "echo_bar".');
        } else {
            $this->expectDeprecation(<<<'EOF'
Since twig/twig 3.9: Twig node "Twig\Tests\EnvironmentTest_LegacyEchoingNode" is not marked as ready for using "yield" instead of "echo"; please make it ready and then flag it with the #[\Twig\Attribute\YieldReady] attribute.
  Since twig/twig 3.9: Using "echo" is deprecated, use "yield" instead in "Twig\Tests\EnvironmentTest_LegacyEchoingNode", then flag the class with #[\Twig\Attribute\YieldReady].
EOF
            );
        }

        $this->assertSame('ADbarEBbarCFGbarH', $twig->render('echo_bar'));
    }

    protected function getMockLoader($templateName, $templateContent)
    {
        $loader = $this->createMock(LoaderInterface::class);
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

    public function testResettingGlobals()
    {
        $twig = new Environment(new ArrayLoader(['index' => '']));
        $twig->addExtension(new class extends AbstractExtension implements GlobalsInterface {
            public function getGlobals(): array
            {
                return [
                    'global_ext' => bin2hex(random_bytes(16)),
                ];
            }
        });

        // Force extensions initialization
        $twig->load('index');

        // Simulate request
        $g1 = $twig->getGlobals();
        // Simulate another call from request 1 (the globals are cached)
        $g2 = $twig->getGlobals();
        $this->assertSame($g1['global_ext'], $g2['global_ext']);

        // Simulate request 2
        $twig->resetGlobals();
        $g3 = $twig->getGlobals();
        $this->assertNotSame($g3['global_ext'], $g2['global_ext']);
    }

    public function testHotCache()
    {
        $dir = sys_get_temp_dir().'/twig-hot-cache-test';
        if (is_dir($dir)) {
            FilesystemHelper::removeDir($dir);
        }
        mkdir($dir);
        file_put_contents($dir.'/index.twig', 'x');
        try {
            $twig = new Environment(new FilesystemLoader($dir), [
                'debug' => false,
                'auto_reload' => false,
                'cache' => $dir.'/cache',
            ]);

            // prime the cache
            $this->assertSame('x', $twig->load('index.twig')->render([]));

            // update the template
            file_put_contents($dir.'/index.twig', 'y');

            // re-render, should use the cached version
            $this->assertSame('x', $twig->load('index.twig')->render([]));

            // clear the cache
            $twig->removeCache('index.twig');

            // re-render, should use the updated template
            $this->assertSame('y', $twig->load('index.twig')->render([]));

            // the new template should not be cached
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir.'/cache', \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            $count = 0;
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isDir()) {
                    ++$count;
                }
            }
            $this->assertSame(0, $count);

            // re-render, should use the updated template
            $this->assertSame('y', $twig->load('index.twig')->render([]));
        } finally {
            FilesystemHelper::removeDir($dir);
        }
    }
}

class EnvironmentTest_Extension_WithGlobals extends AbstractExtension
{
    public function getGlobals()
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}

class EnvironmentTest_Extension extends AbstractExtension implements GlobalsInterface
{
    public function getTokenParsers(): array
    {
        return [
            new EnvironmentTest_TokenParser(),
        ];
    }

    public function getNodeVisitors(): array
    {
        return [
            new EnvironmentTest_NodeVisitor(),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('foo_filter'),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('foo_test'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('foo_function'),
        ];
    }

    public function getExpressionParsers(): array
    {
        return [
            new UnaryOperatorExpressionParser('', 'foo_unary', 0),
            new BinaryOperatorExpressionParser('', 'foo_binary', 0),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}

class EnvironmentTest_TokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new EnvironmentTest_LegacyEchoingNode([], [], 1);
    }

    public function getTag(): string
    {
        return 'test';
    }
}

class EnvironmentTest_NodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}

class EnvironmentTest_ExtensionWithoutRuntime extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('from_runtime_array', ['Twig\Tests\EnvironmentTest_Runtime', 'fromRuntime']),
            new TwigFunction('from_runtime_string', 'Twig\Tests\EnvironmentTest_Runtime::fromRuntime'),
        ];
    }
}

class EnvironmentTest_Runtime
{
    public function fromRuntime($name = 'bar')
    {
        return $name;
    }
}

class EnvironmentTest_LegacyEchoingNode extends Node
{
    public function compile($compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo "bar";')
        ;
    }
}
