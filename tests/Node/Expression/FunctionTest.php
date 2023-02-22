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
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Node;
use Twig\Test\ASTNodeTestCase;
use Twig\TwigFunction;

class FunctionTest extends ASTNodeTestCase
{
    public function testConstructor()
    {
        $name = 'function';
        $args = new Node();
        $node = new FunctionExpression($name, $args, 1);

        $this->assertEquals($name, $node->getAttribute('name'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    public static function getTests()
    {
        $environment = new Environment(new ArrayLoader());
        $environment->addFunction(new TwigFunction('foo', 'twig_tests_function_dummy', []));
        $environment->addFunction(new TwigFunction('foo_closure', \Closure::fromCallable(twig_tests_function_dummy::class), []));
        $environment->addFunction(new TwigFunction('bar', 'twig_tests_function_dummy', ['needs_environment' => true]));
        $environment->addFunction(new TwigFunction('foofoo', 'twig_tests_function_dummy', ['needs_context' => true]));
        $environment->addFunction(new TwigFunction('foobar', 'twig_tests_function_dummy', ['needs_environment' => true, 'needs_context' => true]));
        $environment->addFunction(new TwigFunction('barbar', 'Twig\Tests\Node\Expression\twig_tests_function_barbar', ['is_variadic' => true]));

        $tests = [];

        $node = self::createFunction('foo');
        $tests[] = [$node, 'twig_tests_function_dummy()', $environment];

        $node = self::createFunction('foo_closure');
        $tests[] = [$node, twig_tests_function_dummy::class.'()', $environment];

        $node = self::createFunction('foo', [new ConstantExpression('bar', 1), new ConstantExpression('foobar', 1)]);
        $tests[] = [$node, 'twig_tests_function_dummy("bar", "foobar")', $environment];

        $node = self::createFunction('bar');
        $tests[] = [$node, 'twig_tests_function_dummy($this->env)', $environment];

        $node = self::createFunction('bar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_function_dummy($this->env, "bar")', $environment];

        $node = self::createFunction('foofoo');
        $tests[] = [$node, 'twig_tests_function_dummy($context)', $environment];

        $node = self::createFunction('foofoo', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_function_dummy($context, "bar")', $environment];

        $node = self::createFunction('foobar');
        $tests[] = [$node, 'twig_tests_function_dummy($this->env, $context)', $environment];

        $node = self::createFunction('foobar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_function_dummy($this->env, $context, "bar")', $environment];

        // named arguments
        $node = self::createFunction('date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
            'date' => new ConstantExpression(0, 1),
        ]);
        $tests[] = [$node, 'twig_date_converter($this->env, 0, "America/Chicago")'];

        // arbitrary named arguments
        $node = self::createFunction('barbar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar()', $environment];

        $node = self::createFunction('barbar', ['foo' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar(null, null, ["foo" => "bar"])', $environment];

        $node = self::createFunction('barbar', ['arg2' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar(null, "bar")', $environment];

        $node = self::createFunction('barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar("1", "2", [0 => "3", "foo" => "bar"])', $environment];

        // function as an anonymous function
        $node = self::createFunction('anonymous', [new ConstantExpression('foo', 1)]);
        $tests[] = [$node, '$this->env->getFunction(\'anonymous\')->getCallable()("foo")'];

        return $tests;
    }

    protected static function createFunction($name, array $arguments = [])
    {
        return new FunctionExpression($name, new Node($arguments), 1);
    }

    protected static function getEnvironment()
    {
        $env = new Environment(new ArrayLoader([]));
        $env->addFunction(new TwigFunction('anonymous', function () {}));

        return $env;
    }
}

function twig_tests_function_dummy()
{
}

function twig_tests_function_barbar($arg1 = null, $arg2 = null, array $args = [])
{
}
