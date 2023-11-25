<?php

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\Tests\Extension\Fixtures\AttributeExtension;
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
        $extension = new AttributeExtension();
        foreach ($extension->getFilters() as $filter) {
            if ($filter->getName() === $name) {
                $this->assertEquals(new TwigFilter($name, [$extension, $method], $options), $filter);

                return;
            }
        }

        $this->fail(sprintf('Filter "%s" is not registered.', $name));
    }

    public static function provideFilters()
    {
        yield 'basic' => ['fooFilter', 'fooFilter', []];
        yield 'with name' => ['bar', 'barFilter', []];
        yield 'with env' => ['withEnvFilter', 'withEnvFilter', ['needs_environment' => true]];
        yield 'with context' => ['withContextFilter', 'withContextFilter', ['needs_context' => true]];
        yield 'with env and context' => ['withEnvAndContextFilter', 'withEnvAndContextFilter', ['needs_environment' => true, 'needs_context' => true]];
        yield 'variadic' => ['variadicFilter', 'variadicFilter', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecatedFilter', 'deprecatedFilter', ['deprecated' => true, 'alternative' => 'bar']];
    }

    /**
     * @dataProvider provideFunctions
     */
    public function testFunction(string $name, string $method, array $options)
    {
        $extension = new AttributeExtension();
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                $this->assertEquals(new TwigFunction($name, [$extension, $method], $options), $function);

                return;
            }
        }

        $this->fail(sprintf('Function "%s" is not registered.', $name));
    }

    public static function provideFunctions()
    {
        yield 'basic' => ['fooFunction', 'fooFunction', []];
        yield 'with name' => ['bar', 'barFunction', []];
        yield 'with env' => ['withEnvFunction', 'withEnvFunction', ['needs_environment' => true]];
        yield 'with context' => ['withContextFunction', 'withContextFunction', ['needs_context' => true]];
        yield 'with env and context' => ['withEnvAndContextFunction', 'withEnvAndContextFunction', ['needs_environment' => true, 'needs_context' => true]];
        yield 'variadic' => ['variadicFunction', 'variadicFunction', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecatedFunction', 'deprecatedFunction', ['deprecated' => true, 'alternative' => 'bar']];
    }

    /**
     * @dataProvider provideTests
     */
    public function testTest(string $name, string $method, array $options)
    {
        $extension = new AttributeExtension();
        foreach ($extension->getTests() as $test) {
            if ($test->getName() === $name) {
                $this->assertEquals(new TwigTest($name, [$extension, $method], $options), $test);

                return;
            }
        }

        $this->fail(sprintf('Function "%s" is not registered.', $name));
    }

    public static function provideTests()
    {
        yield 'basic' => ['fooTest', 'fooTest', []];
        yield 'with name' => ['bar', 'barTest', []];
        yield 'variadic' => ['variadicTest', 'variadicTest', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecatedTest', 'deprecatedTest', ['deprecated' => true, 'alternative' => 'bar']];
    }
}
