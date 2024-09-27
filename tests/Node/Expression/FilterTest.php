<?php

namespace Twig\Tests\Node\Expression;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Nodes;
use Twig\Test\NodeTestCase;
use Twig\TwigFilter;

class FilterTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new ConstantExpression('foo', 1);
        $filter = new TwigFilter($name = 'upper');
        $args = new EmptyNode();
        $node = new FilterExpression($expr, $filter, $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($name, $node->getAttribute('name'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    public static function provideTests(): iterable
    {
        $environment = static::createEnvironment();

        $tests = [];

        $expr = new ConstantExpression('foo', 1);
        $node = self::createFilter($environment, $expr, 'upper');
        $node = self::createFilter($environment, $node, 'number_format', [new ConstantExpression(2, 1), new ConstantExpression('.', 1), new ConstantExpression(',', 1)]);

        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->formatNumber(Twig\Extension\CoreExtension::upper($this->env->getCharset(), "foo"), 2, ".", ",")'];

        // named arguments
        $date = new ConstantExpression(0, 1);
        $node = self::createFilter($environment, $date, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
            'format' => new ConstantExpression('d/m/Y H:i:s P', 1),
        ]);
        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->formatDate(0, "d/m/Y H:i:s P", "America/Chicago")'];

        // skip an optional argument
        $date = new ConstantExpression(0, 1);
        $node = self::createFilter($environment, $date, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
        ]);
        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->formatDate(0, null, "America/Chicago")'];

        // underscores vs camelCase for named arguments
        $string = new ConstantExpression('abc', 1);
        $node = self::createFilter($environment, $string, 'reverse', [
            'preserve_keys' => new ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'Twig\Extension\CoreExtension::reverse($this->env->getCharset(), "abc", true)'];
        $node = self::createFilter($environment, $string, 'reverse', [
            'preserveKeys' => new ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'Twig\Extension\CoreExtension::reverse($this->env->getCharset(), "abc", true)'];

        // filter as an anonymous function
        $node = self::createFilter($environment, new ConstantExpression('foo', 1), 'anonymous');
        $tests[] = [$node, '$this->env->getFilter(\'anonymous\')->getCallable()("foo")'];

        // needs environment
        $node = self::createFilter($environment, $string, 'bar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_dummy($this->env, "abc")', $environment];

        $node = self::createFilter($environment, $string, 'bar_closure');
        $tests[] = [$node, twig_tests_filter_dummy::class.'($this->env, "abc")', $environment];

        $node = self::createFilter($environment, $string, 'bar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_dummy($this->env, "abc", "bar")', $environment];

        // arbitrary named arguments
        $node = self::createFilter($environment, $string, 'barbar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc")', $environment];

        $node = self::createFilter($environment, $string, 'barbar', ['foo' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", null, null, ["foo" => "bar"])', $environment];

        $node = self::createFilter($environment, $string, 'barbar', ['arg2' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", null, "bar")', $environment];

        if (\PHP_VERSION_ID >= 80111) {
            $node = self::createFilter($environment, $string, 'first_class_callable_static');
            $tests[] = [$node, 'Twig\Tests\Node\Expression\FilterTestExtension::staticMethod("abc")', $environment];

            $node = self::createFilter($environment, $string, 'first_class_callable_object');
            $tests[] = [$node, '$this->extensions[\'Twig\Tests\Node\Expression\FilterTestExtension\']->objectMethod("abc")', $environment];
        }

        $node = self::createFilter($environment, $string, 'barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", "1", "2", ["3", "foo" => "bar"])', $environment];

        // from extension
        $node = self::createFilter($environment, $string, 'foo');
        $tests[] = [$node, \sprintf('$this->extensions[\'%s\']->foo("abc")', \get_class(self::createExtension())), $environment];

        $node = self::createFilter($environment, $string, 'foobar');
        $tests[] = [$node, '$this->env->getFilter(\'foobar\')->getCallable()("abc")', $environment];

        $node = self::createFilter($environment, $string, 'magic_static');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\ChildMagicCallStub::magicStaticCall("abc")', $environment];

        return $tests;
    }

    public function testCompileWithWrongNamedArgumentName()
    {
        $date = new ConstantExpression(0, 1);
        $node = $this->createFilter($this->getEnvironment(), $date, 'date', [
            'foobar' => new ConstantExpression('America/Chicago', 1),
        ]);

        $compiler = $this->getCompiler();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown argument "foobar" for filter "date(format, timezone)" at line 1.');

        $compiler->compile($node);
    }

    public function testCompileWithMissingNamedArgument()
    {
        $value = new ConstantExpression(0, 1);
        $node = $this->createFilter($this->getEnvironment(), $value, 'replace', [
            'to' => new ConstantExpression('foo', 1),
        ]);

        $compiler = $this->getCompiler();

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Value for argument "from" is required for filter "replace" at line 1.');

        $compiler->compile($node);
    }

    private static function createFilter(Environment $env, $node, $name, array $arguments = []): FilterExpression
    {
        return new FilterExpression($node, $env->getFilter($name), new Nodes($arguments), 1);
    }

    protected static function createEnvironment(): Environment
    {
        $env = new Environment(new ArrayLoader());
        $env->addFilter(new TwigFilter('anonymous', function () {}));
        $env->addFilter(new TwigFilter('bar', 'Twig\Tests\Node\Expression\twig_tests_filter_dummy', ['needs_environment' => true]));
        $env->addFilter(new TwigFilter('bar_closure', \Closure::fromCallable(twig_tests_filter_dummy::class), ['needs_environment' => true]));
        $env->addFilter(new TwigFilter('barbar', 'Twig\Tests\Node\Expression\twig_tests_filter_barbar', ['needs_context' => true, 'is_variadic' => true]));
        $env->addFilter(new TwigFilter('magic_static', __NAMESPACE__.'\ChildMagicCallStub::magicStaticCall'));
        if (\PHP_VERSION_ID >= 80111) {
            $env->addExtension(new FilterTestExtension());
        }
        $env->addExtension(self::createExtension());

        return $env;
    }

    private static function createExtension(): AbstractExtension
    {
        return new class() extends AbstractExtension {
            public function getFilters(): array
            {
                return [
                    new TwigFilter('foo', \Closure::fromCallable([$this, 'foo'])),
                    new TwigFilter('foobar', \Closure::fromCallable([$this, 'foobar'])),
                ];
            }

            public function foo()
            {
            }

            protected function foobar()
            {
            }
        };
    }
}

function twig_tests_filter_dummy()
{
}

function twig_tests_filter_barbar($context, $string, $arg1 = null, $arg2 = null, array $args = [])
{
}

class ChildMagicCallStub extends ParentMagicCallStub
{
    public static function identifier()
    {
        return 'child';
    }
}

class ParentMagicCallStub
{
    public static function identifier()
    {
        throw new \Exception('Identifier has not been defined.');
    }

    public static function __callStatic($method, $arguments)
    {
        if ('magicStaticCall' !== $method) {
            throw new \BadMethodCallException('Unexpected call to __callStatic.');
        }

        return 'inherited_static_magic_'.static::identifier().'_'.$arguments[0];
    }
}
