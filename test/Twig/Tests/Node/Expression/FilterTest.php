<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_FilterTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\ConstantExpression('foo', 1);
        $name = new \Twig\Node\Expression\ConstantExpression('upper', 1);
        $args = new \Twig\Node\Node();
        $node = new \Twig\Node\Expression\FilterExpression($expr, $name, $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($name, $node->getNode('filter'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    public function getTests()
    {
        $environment = new \Twig\Environment($this->getMockBuilder(\Twig\Loader\LoaderInterface::class)->getMock());
        $environment->addFilter(new \Twig\TwigFilter('bar', 'twig_tests_filter_dummy', ['needs_environment' => true]));
        $environment->addFilter(new \Twig\TwigFilter('barbar', 'twig_tests_filter_barbar', ['needs_context' => true, 'is_variadic' => true]));

        $tests = [];

        $expr = new \Twig\Node\Expression\ConstantExpression('foo', 1);
        $node = $this->createFilter($expr, 'upper');
        $node = $this->createFilter($node, 'number_format', [new \Twig\Node\Expression\ConstantExpression(2, 1), new \Twig\Node\Expression\ConstantExpression('.', 1), new \Twig\Node\Expression\ConstantExpression(',', 1)]);

        $tests[] = [$node, 'twig_number_format_filter($this->env, twig_upper_filter($this->env, "foo"), 2, ".", ",")'];

        // named arguments
        $date = new \Twig\Node\Expression\ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'timezone' => new \Twig\Node\Expression\ConstantExpression('America/Chicago', 1),
            'format' => new \Twig\Node\Expression\ConstantExpression('d/m/Y H:i:s P', 1),
        ]);
        $tests[] = [$node, 'twig_date_format_filter($this->env, 0, "d/m/Y H:i:s P", "America/Chicago")'];

        // skip an optional argument
        $date = new \Twig\Node\Expression\ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'timezone' => new \Twig\Node\Expression\ConstantExpression('America/Chicago', 1),
        ]);
        $tests[] = [$node, 'twig_date_format_filter($this->env, 0, null, "America/Chicago")'];

        // underscores vs camelCase for named arguments
        $string = new \Twig\Node\Expression\ConstantExpression('abc', 1);
        $node = $this->createFilter($string, 'reverse', [
            'preserve_keys' => new \Twig\Node\Expression\ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'twig_reverse_filter($this->env, "abc", true)'];
        $node = $this->createFilter($string, 'reverse', [
            'preserveKeys' => new \Twig\Node\Expression\ConstantExpression(true, 1),
        ]);
        $tests[] = [$node, 'twig_reverse_filter($this->env, "abc", true)'];

        // filter as an anonymous function
        $node = $this->createFilter(new \Twig\Node\Expression\ConstantExpression('foo', 1), 'anonymous');
        $tests[] = [$node, 'call_user_func_array($this->env->getFilter(\'anonymous\')->getCallable(), ["foo"])'];

        // needs environment
        $node = $this->createFilter($string, 'bar');
        $tests[] = [$node, 'twig_tests_filter_dummy($this->env, "abc")', $environment];

        $node = $this->createFilter($string, 'bar', [new \Twig\Node\Expression\ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_filter_dummy($this->env, "abc", "bar")', $environment];

        // arbitrary named arguments
        $node = $this->createFilter($string, 'barbar');
        $tests[] = [$node, 'twig_tests_filter_barbar($context, "abc")', $environment];

        $node = $this->createFilter($string, 'barbar', ['foo' => new \Twig\Node\Expression\ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_filter_barbar($context, "abc", null, null, ["foo" => "bar"])', $environment];

        $node = $this->createFilter($string, 'barbar', ['arg2' => new \Twig\Node\Expression\ConstantExpression('bar', 1)]);
        $tests[] = [$node, 'twig_tests_filter_barbar($context, "abc", null, "bar")', $environment];

        $node = $this->createFilter($string, 'barbar', [
            new \Twig\Node\Expression\ConstantExpression('1', 1),
            new \Twig\Node\Expression\ConstantExpression('2', 1),
            new \Twig\Node\Expression\ConstantExpression('3', 1),
            'foo' => new \Twig\Node\Expression\ConstantExpression('bar', 1),
        ]);
        $tests[] = [$node, 'twig_tests_filter_barbar($context, "abc", "1", "2", [0 => "3", "foo" => "bar"])', $environment];

        return $tests;
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Unknown argument "foobar" for filter "date(format, timezone)" at line 1.
     */
    public function testCompileWithWrongNamedArgumentName()
    {
        $date = new \Twig\Node\Expression\ConstantExpression(0, 1);
        $node = $this->createFilter($date, 'date', [
            'foobar' => new \Twig\Node\Expression\ConstantExpression('America/Chicago', 1),
        ]);

        $compiler = $this->getCompiler();
        $compiler->compile($node);
    }

    /**
     * @expectedException        \Twig\Error\SyntaxError
     * @expectedExceptionMessage Value for argument "from" is required for filter "replace" at line 1.
     */
    public function testCompileWithMissingNamedArgument()
    {
        $value = new \Twig\Node\Expression\ConstantExpression(0, 1);
        $node = $this->createFilter($value, 'replace', [
            'to' => new \Twig\Node\Expression\ConstantExpression('foo', 1),
        ]);

        $compiler = $this->getCompiler();
        $compiler->compile($node);
    }

    protected function createFilter($node, $name, array $arguments = [])
    {
        $name = new \Twig\Node\Expression\ConstantExpression($name, 1);
        $arguments = new \Twig\Node\Node($arguments);

        return new \Twig\Node\Expression\FilterExpression($node, $name, $arguments, 1);
    }

    protected function getEnvironment()
    {
        $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader([]));
        $env->addFilter(new \Twig\TwigFilter('anonymous', function () {}));

        return $env;
    }
}

function twig_tests_filter_dummy()
{
}

function twig_tests_filter_barbar($context, $string, $arg1 = null, $arg2 = null, array $args = [])
{
}
