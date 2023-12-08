<?php

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\Extension\AttributeExtension;
use Twig\Tests\Extension\Fixtures\ExtensionWithAttributes;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @requires PHP >= 8.0
 */
class AttributeExtensionTest extends TestCase
{
    /**
     * @dataProvider provideFilters
     */
    public function testFilter(string $name, string $method, array $options)
    {
        $object = new ExtensionWithAttributes();
        $extension = new AttributeExtension([$object]);
        foreach ($extension->getFilters() as $filter) {
            if ($filter->getName() === $name) {
                $this->assertEquals(new TwigFilter($name, [$object, $method], $options), $filter);

                return;
            }
        }

        $this->fail(sprintf('Filter "%s" is not registered.', $name));
    }

    public static function provideFilters()
    {
        yield 'with name' => ['foo', 'fooFilter', ['is_safe' => ['html']]];
        yield 'with env' => ['with_env_filter', 'withEnvFilter', ['needs_environment' => true]];
        yield 'with context' => ['with_context_filter', 'withContextFilter', ['needs_context' => true]];
        yield 'with env and context' => ['with_env_and_context_filter', 'withEnvAndContextFilter', ['needs_environment' => true, 'needs_context' => true]];
        yield 'no argument' => ['no_arg_filter', 'noArgFilter', []];
        yield 'variadic' => ['variadic_filter', 'variadicFilter', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecated_filter', 'deprecatedFilter', ['deprecated' => true, 'alternative' => 'bar']];
        yield 'pattern' => ['pattern_*_filter', 'patternFilter', []];
    }

    /**
     * @dataProvider provideFunctions
     */
    public function testFunction(string $name, string $method, array $options)
    {
        $object = new ExtensionWithAttributes();
        $extension = new AttributeExtension([$object]);
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                $this->assertEquals(new TwigFunction($name, [$object, $method], $options), $function);

                return;
            }
        }

        $this->fail(sprintf('Function "%s" is not registered.', $name));
    }

    public static function provideFunctions()
    {
        yield 'with name' => ['foo', 'fooFunction', ['is_safe' => ['html']]];
        yield 'with env' => ['with_env_function', 'withEnvFunction', ['needs_environment' => true]];
        yield 'with context' => ['with_context_function', 'withContextFunction', ['needs_context' => true]];
        yield 'with env and context' => ['with_env_and_context_function', 'withEnvAndContextFunction', ['needs_environment' => true, 'needs_context' => true]];
        yield 'no argument' => ['no_arg_function', 'noArgFunction', []];
        yield 'variadic' => ['variadic_function', 'variadicFunction', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecated_function', 'deprecatedFunction', ['deprecated' => true, 'alternative' => 'bar']];
    }

    /**
     * @dataProvider provideTests
     */
    public function testTest(string $name, string $method, array $options)
    {
        $object = new ExtensionWithAttributes();
        $extension = new AttributeExtension([$object]);
        foreach ($extension->getTests() as $test) {
            if ($test->getName() === $name) {
                $this->assertEquals(new TwigTest($name, [$object, $method], $options), $test);

                return;
            }
        }

        $this->fail(sprintf('Function "%s" is not registered.', $name));
    }

    public static function provideTests()
    {
        yield 'with name' => ['foo', 'fooTest', []];
        yield 'variadic' => ['variadic_test', 'variadicTest', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecated_test', 'deprecatedTest', ['deprecated' => true, 'alternative' => 'bar']];
    }

    public function testRuntimeExtension()
    {
        $class = ExtensionWithAttributes::class;
        $extension = new AttributeExtension([$class]);

        $this->assertSame([$class, 'fooFilter'], $extension->getFilters()['foo']->getCallable());
        $this->assertSame([$class, 'fooFunction'], $extension->getFunctions()['foo']->getCallable());
        $this->assertSame([$class, 'fooTest'], $extension->getTests()['foo']->getCallable());
    }

    public function testLastModified()
    {
        $extension = new AttributeExtension([ExtensionWithAttributes::class]);
        $this->assertSame(filemtime(__DIR__ . '/Fixtures/ExtensionWithAttributes.php'), $extension->getLastModified());
    }
}
