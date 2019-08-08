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
        $node = new FunctionExpression($name, $args, 1);

        $this->assertEquals($name, $node->getAttribute('name'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    public function getTests()
    {
        $environment = new Environment($this->createMock('\Twig\Loader\LoaderInterface'));
        $environment->addFunction(new TwigFunction('foo', 'foo', []));
        $environment->addFunction(new TwigFunction('bar', 'bar', ['needs_environment' => true]));
        $environment->addFunction(new TwigFunction('foofoo', 'foofoo', ['needs_context' => true]));
        $environment->addFunction(new TwigFunction('foobar', 'foobar', ['needs_environment' => true, 'needs_context' => true]));
        $environment->addFunction(new TwigFunction('barbar', 'Twig\Tests\Node\Expression\twig_tests_function_barbar', ['is_variadic' => true]));

        $tests = [];

        $node = $this->createFunction('foo');
        $tests[] = [$node, 'foo()', $environment];

        $node = $this->createFunction('foo', [new ConstantExpression('bar', 1), new ConstantExpression('foobar', 1)]);
        $tests[] = [$node, 'foo("bar", "foobar")', $environment];

        $node = $this->createFunction('bar');
        $tests[] = [$node, 'bar($this->env)', $environment];

        $node = $this->createFunction('bar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'bar($this->env, "bar")', $environment];

        $node = $this->createFunction('foofoo');
        $tests[] = [$node, 'foofoo($context)', $environment];

        $node = $this->createFunction('foofoo', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'foofoo($context, "bar")', $environment];

        $node = $this->createFunction('foobar');
        $tests[] = [$node, 'foobar($this->env, $context)', $environment];

        $node = $this->createFunction('foobar', [new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'foobar($this->env, $context, "bar")', $environment];

        // named arguments
        $node = $this->createFunction('date', [
            'timezone' => new ConstantExpression('America/Chicago', 1),
            'date' => new ConstantExpression(0, 1),
        ]);
        $tests[] = [$node, 'twig_date_converter($this->env, 0, "America/Chicago")'];

        // arbitrary named arguments
        $node = $this->createFunction('barbar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar()', $environment];

        $node = $this->createFunction('barbar', ['foo' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar(null, null, ["foo" => "bar"])', $environment];

        $node = $this->createFunction('barbar', ['arg2' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar(null, "bar")', $environment];

        $node = $this->createFunction('barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_function_barbar("1", "2", [0 => "3", "foo" => "bar"])', $environment];

        // function as an anonymous function
        if (\PHP_VERSION_ID >= 50300) {
            $node = $this->createFunction('anonymous', [new ConstantExpression('foo', 1)]);
            $tests[] = [$node, 'call_user_func_array($this->env->getFunction(\'anonymous\')->getCallable(), ["foo"])'];
        }

        return $tests;
    }

    protected function createFunction($name, array $arguments = [])
    {
        return new FunctionExpression($name, new Node($arguments), 1);
    }

    protected function getEnvironment()
    {
        if (\PHP_VERSION_ID >= 50300) {
            return include 'PHP53/FunctionInclude.php';
        }

        return parent::getEnvironment();
    }
}

function twig_tests_function_barbar($arg1 = null, $arg2 = null, array $args = [])
{
}
