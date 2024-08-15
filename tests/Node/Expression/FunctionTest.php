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
use Twig\Test\NodeTestCase;
use Twig\TwigFunction;

class FunctionTest extends NodeTestCase
{
    public function testConstructor()
    {
        $name = 'function';
        $args = new Node();
        $node = new FunctionExpression(new TwigFunction($name), $args, 1);

        $this->assertEquals($name, $node->getAttribute('name'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    public function getTests()
    {
        $environment = $this->getEnvironment();

        $tests = [];

        $node = $this->createFunction($environment, 'foo');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy()', $environment];

        $node = $this->createFunction($environment, 'foo_closure');
        $tests[] = [$node, twig_tests_function_dummy::class.'()', $environment];

        $node = $this->createFunction($environment, 'foo', [new ConstantExpression('bar', 1), new ConstantExpression('foobar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy("bar", "foobar")', $environment];

        $node = $this->createFunction($environment, 'bar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy($this->env)', $environment];

        $node = $this->createFunction($environment, 'bar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy($this->env, "bar")', $environment];

        $node = $this->createFunction($environment, 'foofoo');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy($context)', $environment];

        $node = $this->createFunction($environment, 'foofoo', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy($context, "bar")', $environment];

        $node = $this->createFunction($environment, 'foobar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy($this->env, $context)', $environment];

        $node = $this->createFunction($environment, 'foobar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_dummy($this->env, $context, "bar")', $environment];

        // named arguments
        $node = $this->createFunction($environment, 'date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
            'date' => new ConstantExpression(0, 1),
        ]);
        $tests[] = [$node, '$this->extensions[\'Twig\Extension\CoreExtension\']->convertDate(0, "America/Chicago")'];

        // arbitrary named arguments
        $node = $this->createFunction($environment, 'barbar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar()', $environment];

        $node = $this->createFunction($environment, 'barbar', ['foo' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar(null, null, ["foo" => "bar"])', $environment];

        $node = $this->createFunction($environment, 'barbar', ['arg2' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar(null, "bar")', $environment];

        $node = $this->createFunction($environment, 'barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar("1", "2", ["3", "foo" => "bar"])', $environment];

        // function as an anonymous function
        $node = $this->createFunction($environment, 'anonymous', [new ConstantExpression('foo', 1)]);
        $tests[] = [$node, '$this->env->getFunction(\'anonymous\')->getCallable()("foo")'];

        return $tests;
    }

    protected function createFunction(Environment $env, $name, array $arguments = [])
    {
        return new FunctionExpression($env->getFunction($name), new Node($arguments), 1);
    }

    protected function getEnvironment()
    {
        $env = new Environment(new ArrayLoader());
        $env->addFunction(new TwigFunction('anonymous', function () {}));
        $env->addFunction(new TwigFunction('foo', 'Twig\Tests\Node\Expression\twig_tests_function_dummy', []));
        $env->addFunction(new TwigFunction('foo_closure', \Closure::fromCallable(twig_tests_function_dummy::class), []));
        $env->addFunction(new TwigFunction('bar', 'Twig\Tests\Node\Expression\twig_tests_function_dummy', ['needs_environment' => true]));
        $env->addFunction(new TwigFunction('foofoo', 'Twig\Tests\Node\Expression\twig_tests_function_dummy', ['needs_context' => true]));
        $env->addFunction(new TwigFunction('foobar', 'Twig\Tests\Node\Expression\twig_tests_function_dummy', ['needs_environment' => true, 'needs_context' => true]));
        $env->addFunction(new TwigFunction('barbar', 'Twig\Tests\Node\Expression\twig_tests_function_barbar', ['is_variadic' => true]));

        return $env;
    }
}

function twig_tests_function_dummy()
{
}

function twig_tests_function_barbar($arg1 = null, $arg2 = null, array $args = [])
{
}
