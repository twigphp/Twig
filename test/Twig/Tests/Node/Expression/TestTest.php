<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_TestTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\ConstantExpression('foo', 1);
        $name = new \Twig\Node\Expression\ConstantExpression('null', 1);
        $args = new \Twig\Node\Node();
        $node = new \Twig\Node\Expression\TestExpression($expr, $name, $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals($name, $node->getAttribute('name'));
    }

    public function getTests()
    {
        $environment = new \Twig\Environment($this->getMockBuilder('\Twig\Loader\LoaderInterface')->getMock());
        $environment->addTest(new \Twig\TwigTest('barbar', 'twig_tests_test_barbar', ['is_variadic' => true, 'need_context' => true]));

        $tests = [];

        $expr = new \Twig\Node\Expression\ConstantExpression('foo', 1);
        $node = new \Twig\Node\Expression\Test\NullTest($expr, 'null', new \Twig\Node\Node([]), 1);
        $tests[] = [$node, '(null === "foo")'];

        // test as an anonymous function
        if (PHP_VERSION_ID >= 50300) {
            $node = $this->createTest(new \Twig\Node\Expression\ConstantExpression('foo', 1), 'anonymous', [new \Twig\Node\Expression\ConstantExpression('foo', 1)]);
            $tests[] = [$node, 'call_user_func_array($this->env->getTest(\'anonymous\')->getCallable(), ["foo", "foo"])'];
        }

        // arbitrary named arguments
        $string = new \Twig\Node\Expression\ConstantExpression('abc', 1);
        $node = $this->createTest($string, 'barbar');
        $tests[] = [$node, 'twig_tests_test_barbar("abc")', $environment];

        $node = $this->createTest($string, 'barbar', ['foo' => new \Twig\Node\Expression\ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_test_barbar("abc", null, null, ["foo" => "bar"])', $environment];

        $node = $this->createTest($string, 'barbar', ['arg2' => new \Twig\Node\Expression\ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_test_barbar("abc", null, "bar")', $environment];

        $node = $this->createTest($string, 'barbar', [
            new \Twig\Node\Expression\ConstantExpression('1', 1),
            new \Twig\Node\Expression\ConstantExpression('2', 1),
            new \Twig\Node\Expression\ConstantExpression('3', 1),
            'foo' => new \Twig\Node\Expression\ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'twig_tests_test_barbar("abc", "1", "2", [0 => "3", "foo" => "bar"])', $environment];

        return $tests;
    }

    protected function createTest($node, $name, array $arguments = [])
    {
        return new \Twig\Node\Expression\TestExpression($node, $name, new \Twig\Node\Node($arguments), 1);
    }

    protected function getEnvironment()
    {
        if (PHP_VERSION_ID >= 50300) {
            return include 'PHP53/TestInclude.php';
        }

        return parent::getEnvironment();
    }
}

function twig_tests_test_barbar($string, $arg1 = null, $arg2 = null, array $args = [])
{
}
