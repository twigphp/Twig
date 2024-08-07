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
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;
use Twig\Test\NodeTestCase;
use Twig\TwigFilter;

class FilterTest extends NodeTestCase
{
    private $extension = null;

    public function testConstructor()
    {
        $expr = new ConstantExpression('foo', 1);
        $name = new ConstantExpression('upper', 1);
        $args = new Node();
        $node = new FilterExpression($expr, $name, $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($name, $node->getNode('filter'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    protected function tearDown(): void
    {
        $this->extension = null;
    }

    public function getTests()
    {
        $environment = $this->getEnvironment();

        $tests = [];

        $expr = new ConstantExpression('foo', 1);
        $node = $this->createFilter($expr, 'upper');
        $node = $this->createFilter($node, 'number_format', [new ConstantExpression(2, 1), new ConstantExpression('.', 1), new ConstantExpression(',', 1)]);

        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->formatNumber(Twig\Extension\CoreExtension::upper($this->env->getCharset(), "foo"), 2, ".", ",")'];

        // named arguments
        $date = new ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
            'format' => new ConstantExpression('d/m/Y H:i:s P', 1),
        ]);
        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->formatDate(0, "d/m/Y H:i:s P", "America/Chicago")'];

        // skip an optional argument
        $date = new ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
        ]);
        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->formatDate(0, null, "America/Chicago")'];

        // underscores vs camelCase for named arguments
        $string = new ConstantExpression('abc', 1);
        $node = $this->createFilter($string, 'reverse', [
            'preserve_keys' => new ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'Twig\Extension\CoreExtension::reverse($this->env->getCharset(), "abc", true)'];
        $node = $this->createFilter($string, 'reverse', [
            'preserveKeys' => new ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'Twig\Extension\CoreExtension::reverse($this->env->getCharset(), "abc", true)'];

        // filter as an anonymous function
        $node = $this->createFilter(new ConstantExpression('foo', 1), 'anonymous');
        $tests[] = [$node, '$this->env->getFilter(\'anonymous\')->getCallable()("foo")'];

        // needs environment
        $node = $this->createFilter($string, 'bar');
        $tests[] = [$node, 'twig_tests_filter_dummy($this->env, "abc")', $environment];

        $node = $this->createFilter($string, 'bar_closure');
        $tests[] = [$node, twig_tests_filter_dummy::class.'($this->env, "abc")', $environment];

        $node = $this->createFilter($string, 'bar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_filter_dummy($this->env, "abc", "bar")', $environment];

        // arbitrary named arguments
        $node = $this->createFilter($string, 'barbar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc")', $environment];

        $node = $this->createFilter($string, 'barbar', ['foo' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", null, null, ["foo" => "bar"])', $environment];

        $node = $this->createFilter($string, 'barbar', ['arg2' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", null, "bar")', $environment];

        if (\PHP_VERSION_ID >= 80111) {
            $node = $this->createFilter($string, 'first_class_callable_static');
            $tests[] = [$node, 'Twig\Tests\Node\Expression\FilterTestExtension::staticMethod("abc")', $environment];

            $node = $this->createFilter($string, 'first_class_callable_object');
            $tests[] = [$node, '$this->extensions[\'Twig\Tests\Node\Expression\FilterTestExtension\']->objectMethod("abc")', $environment];
        }

        $node = $this->createFilter($string, 'barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", "1", "2", ["3", "foo" => "bar"])', $environment];

        // from extension
        $node = $this->createFilter($string, 'foo');
        $tests[] = [$node, \sprintf('$this->extensions[\'%s\']->foo("abc")', \get_class($this->getExtension())), $environment];

        $node = $this->createFilter($string, 'foobar');
        $tests[] = [$node, '$this->env->getFilter(\'foobar\')->getCallable()("abc")', $environment];

        $node = $this->createFilter($string, 'magic_static');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\ChildMagicCallStub::magicStaticCall("abc")', $environment];

        return $tests;
    }

    public function testCompileWithWrongNamedArgumentName()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown argument "foobar" for filter "date(format, timezone)" at line 1.');

        $date = new ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'foobar' => new ConstantExpression('America/Chicago', 1),
        ]);

        $compiler = $this->getCompiler();
        $compiler->compile($node);
    }

    public function testCompileWithMissingNamedArgument()
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Value for argument "from" is required for filter "replace" at line 1.');

        $value = new ConstantExpression(0, 1);
        $node = $this->createFilter($value, 'replace', [
            'to' => new ConstantExpression('foo', 1),
        ]);

        $compiler = $this->getCompiler();
        $compiler->compile($node);
    }

    protected function createFilter($node, $name, array $arguments = [])
    {
        $name = new ConstantExpression($name, 1);
        $arguments = new Node($arguments);

        return new FilterExpression($node, $name, $arguments, 1);
    }

    protected function getEnvironment()
    {
        $env = new Environment(new ArrayLoader());
        $env->addFilter(new TwigFilter('anonymous', function () {}));
        $env->addFilter(new TwigFilter('bar', 'twig_tests_filter_dummy', ['needs_environment' => true]));
        $env->addFilter(new TwigFilter('bar_closure', \Closure::fromCallable(twig_tests_filter_dummy::class), ['needs_environment' => true]));
        $env->addFilter(new TwigFilter('barbar', 'Twig\Tests\Node\Expression\twig_tests_filter_barbar', ['needs_context' => true, 'is_variadic' => true]));
        $env->addFilter(new TwigFilter('magic_static', __NAMESPACE__.'\ChildMagicCallStub::magicStaticCall'));
        if (\PHP_VERSION_ID >= 80111) {
            $env->addExtension(new FilterTestExtension());
        }
        $env->addExtension($this->getExtension());

        return $env;
    }

    private function getExtension()
    {
        if ($this->extension) {
            return $this->extension;
        }

        return $this->extension = new class() extends AbstractExtension {
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
