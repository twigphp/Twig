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
use Twig\Loader\LoaderInterface;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;
use Twig\Test\NodeTestCase;
use Twig\TwigFilter;

class FilterTest extends NodeTestCase
{
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

    public function getTests()
    {
        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addFilter(new TwigFilter('bar', 'twig_tests_filter_dummy', ['needs_environment' => true]));
        $environment->addFilter(new TwigFilter('bar_closure', \Closure::fromCallable(twig_tests_filter_dummy::class), ['needs_environment' => true]));
        $environment->addFilter(new TwigFilter('barbar', 'Twig\Tests\Node\Expression\twig_tests_filter_barbar', ['needs_context' => true, 'is_variadic' => true]));
        $environment->addFilter(new TwigFilter('magic_static', __NAMESPACE__.'\ChildMagicCallStub::magicStaticCall'));

        $extension = new class() extends AbstractExtension {
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
        $environment->addExtension($extension);

        $tests = [];

        $expr = new ConstantExpression('foo', 1);
        $node = $this->createFilter($expr, 'upper');
        $node = $this->createFilter($node, 'number_format', [new ConstantExpression(2, 1), new ConstantExpression('.', 1), new ConstantExpression(',', 1)]);

        $tests[] = [$node, 'twig_number_format_filter($this->env, twig_upper_filter($this->env, "foo"), 2, ".", ",")'];

        // named arguments
        $date = new ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
            'format' => new ConstantExpression('d/m/Y H:i:s P', 1),
        ]);
        $tests[] = [$node, 'twig_date_format_filter($this->env, 0, "d/m/Y H:i:s P", "America/Chicago")'];

        // skip an optional argument
        $date = new ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
        ]);
        $tests[] = [$node, 'twig_date_format_filter($this->env, 0, null, "America/Chicago")'];

        // underscores vs camelCase for named arguments
        $string = new ConstantExpression('abc', 1);
        $node = $this->createFilter($string, 'reverse', [
            'preserve_keys' => new ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'twig_reverse_filter($this->env, "abc", true)'];
        $node = $this->createFilter($string, 'reverse', [
            'preserveKeys' => new ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'twig_reverse_filter($this->env, "abc", true)'];

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

        $node = $this->createFilter($string, 'barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_filter_barbar($context, "abc", "1", "2", ["3", "foo" => "bar"])', $environment];

        // from extension
        $node = $this->createFilter($string, 'foo');
        $tests[] = [$node, sprintf('$this->extensions[\'%s\']->foo("abc")', \get_class($extension)), $environment];

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
        $env = new Environment(new ArrayLoader([]));
        $env->addFilter(new TwigFilter('anonymous', function () {}));

        return $env;
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
