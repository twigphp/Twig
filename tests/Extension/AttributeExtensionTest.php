<?php

namespace Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Twig\DeprecatedCallableInfo;
use Twig\Error\RuntimeError;
use Twig\Extension\AttributeExtension;
use Twig\ExtensionSet;
use Twig\Tests\Extension\Fixtures\FilterWithoutValue;
use Twig\Tests\Extension\Fixtures\TestWithoutValue;
use Twig\Tests\Extension\Fixtures\ExtensionWithAttributes;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class AttributeExtensionTest extends TestCase
{
    /**
     * @dataProvider provideFilters
     */
    public function testFilter(string $name, string $method, array $options)
    {
        $extension = new AttributeExtension(ExtensionWithAttributes::class);
        foreach ($extension->getFilters() as $filter) {
            if ($filter->getName() === $name) {
                $this->assertEquals(new TwigFilter($name, [ExtensionWithAttributes::class, $method], $options), $filter);

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
        yield 'variadic' => ['variadic_filter', 'variadicFilter', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecated_filter', 'deprecatedFilter', ['deprecation_info' => new DeprecatedCallableInfo('foo/bar', '1.2')]];
        yield 'pattern' => ['pattern_*_filter', 'patternFilter', []];
    }

    /**
     * @dataProvider provideFunctions
     */
    public function testFunction(string $name, string $method, array $options)
    {
        $extension = new AttributeExtension(ExtensionWithAttributes::class);
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                $this->assertEquals(new TwigFunction($name, [ExtensionWithAttributes::class, $method], $options), $function);

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
        yield 'deprecated' => ['deprecated_function', 'deprecatedFunction', ['deprecation_info' => new DeprecatedCallableInfo('foo/bar', '1.2')]];
    }

    /**
     * @dataProvider provideTests
     */
    public function testTest(string $name, string $method, array $options)
    {
        $extension = new AttributeExtension(ExtensionWithAttributes::class);
        foreach ($extension->getTests() as $test) {
            if ($test->getName() === $name) {
                $this->assertEquals(new TwigTest($name, [ExtensionWithAttributes::class, $method], $options), $test);

                return;
            }
        }

        $this->fail(sprintf('Test "%s" is not registered.', $name));
    }

    public static function provideTests()
    {
        yield 'with name' => ['foo', 'fooTest', []];
        yield 'with env' => ['with_env_test', 'withEnvTest', ['needs_environment' => true]];
        yield 'with context' => ['with_context_test', 'withContextTest', ['needs_context' => true]];
        yield 'with env and context' => ['with_env_and_context_test', 'withEnvAndContextTest', ['needs_environment' => true, 'needs_context' => true]];
        yield 'variadic' => ['variadic_test', 'variadicTest', ['is_variadic' => true]];
        yield 'deprecated' => ['deprecated_test', 'deprecatedTest', ['deprecation_info' => new DeprecatedCallableInfo('foo/bar', '1.2')]];
    }

    public function testFilterRequireOneArgument()
    {
        $extension = new AttributeExtension(FilterWithoutValue::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"'.FilterWithoutValue::class.'::myFilter()" needs at least 1 arguments to be used AsTwigFilter, but only 0 defined.');

        $extension->getTests();
    }

    public function testTestRequireOneArgument()
    {
        $extension = new AttributeExtension(TestWithoutValue::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"'.TestWithoutValue::class.'::myTest()" needs at least 1 arguments to be used AsTwigTest, but only 0 defined.');

        $extension->getTests();
    }

    public function testLastModifiedWithObject()
    {
        $extension = new AttributeExtension(\stdClass::class);

        $this->assertSame(filemtime((new \ReflectionClass(AttributeExtension::class))->getFileName()), $extension->getLastModified());
    }

    public function testLastModifiedWithClass()
    {
        $extension = new AttributeExtension('__CLASS_FOR_TEST_LAST_MODIFIED__');

        $filename = tempnam(sys_get_temp_dir(), 'twig');
        try {
            file_put_contents($filename, '<?php class __CLASS_FOR_TEST_LAST_MODIFIED__ {}');
            require $filename;

            $this->assertSame(filemtime($filename), $extension->getLastModified());
        } finally {
            unlink($filename);
        }
    }

    public function testMultipleRegistrations()
    {
        $extensionSet = new ExtensionSet();
        $extensionSet->addExtension($extension1 = new AttributeExtension(ExtensionWithAttributes::class));
        $extensionSet->addExtension($extension2 = new AttributeExtension(\stdClass::class));

        $this->assertCount(2, $extensionSet->getExtensions());
        $this->assertNotNull($extensionSet->getFilter('foo'));

        $this->assertSame($extension1, $extensionSet->getExtension(ExtensionWithAttributes::class));
        $this->assertSame($extension2, $extensionSet->getExtension(\stdClass::class));

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "Twig\Extension\AttributeExtension" extension is not enabled.');
        $extensionSet->getExtension(AttributeExtension::class);
    }
}
