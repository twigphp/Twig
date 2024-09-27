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
use Twig\Node\EmptyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Test\NullTest;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Nodes;
use Twig\Test\NodeTestCase;
use Twig\TwigTest;

class TestTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new ConstantExpression('foo', 1);
        $name = 'test_name';
        $args = new EmptyNode();
        $node = new TestExpression($expr, new TwigTest($name), $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals($name, $node->getAttribute('name'));
    }

    public static function provideTests(): iterable
    {
        $environment = static::createEnvironment();

        $tests = [];

        $expr = new ConstantExpression('foo', 1);
        $node = new NullTest($expr, $environment->getTest('null'), new EmptyNode(), 1);
        $tests[] = [$node, '(null === "foo")'];

        // test as an anonymous function
        $node = self::createTest($environment, new ConstantExpression('foo', 1), 'anonymous', [new ConstantExpression('foo', 1)]);
        $tests[] = [$node, '$this->env->getTest(\'anonymous\')->getCallable()("foo", "foo")'];

        // arbitrary named arguments
        $string = new ConstantExpression('abc', 1);
        $node = self::createTest($environment, $string, 'barbar');
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_test_barbar("abc")', $environment];

        $node = self::createTest($environment, $string, 'barbar', ['foo' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_test_barbar("abc", null, null, ["foo" => "bar"])', $environment];

        $node = self::createTest($environment, $string, 'barbar', ['arg2' => new ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_test_barbar("abc", null, "bar")', $environment];

        $node = self::createTest($environment, $string, 'barbar', [
            new ConstantExpression('1', 1),
            new ConstantExpression('2', 1),
            new ConstantExpression('3', 1),
            'foo' => new ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'Twig\Tests\Node\Expression\twig_tests_test_barbar("abc", "1", "2", ["3", "foo" => "bar"])', $environment];

        return $tests;
    }

    private static function createTest(Environment $env, $node, $name, array $arguments = []): TestExpression
    {
        return new TestExpression($node, $env->getTest($name), new Nodes($arguments), 1);
    }

    protected static function createEnvironment(): Environment
    {
        $env = new Environment(new ArrayLoader());
        $env->addTest(new TwigTest('anonymous', function () {}));
        $env->addTest(new TwigTest('barbar', 'Twig\Tests\Node\Expression\twig_tests_test_barbar', ['is_variadic' => true, 'need_context' => true]));

        return $env;
    }
}

function twig_tests_test_barbar($string, $arg1 = null, $arg2 = null, array $args = [])
{
}
