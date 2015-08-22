<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_FunctionTest extends Twig_Test_NodeTestCase
{
    public function testConstructor()
    {
        $name = 'function';
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_Function($name, $args, 1);

        $this->assertEquals($name, $node->getAttribute('name'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    public function getTests()
    {
        $environment = new Twig_Environment($this->getMock('Twig_LoaderInterface'));
        $environment->addFunction(new Twig_Function('foo', 'twig_tests_function_dummy', array()));
        $environment->addFunction(new Twig_Function('bar', 'twig_tests_function_dummy', array('needs_environment' => true)));
        $environment->addFunction(new Twig_Function('foofoo', 'twig_tests_function_dummy', array('needs_context' => true)));
        $environment->addFunction(new Twig_Function('foobar', 'twig_tests_function_dummy', array('needs_environment' => true, 'needs_context' => true)));
        $environment->addFunction(new Twig_Function('barbar', 'twig_tests_function_barbar', array('is_variadic' => true)));

        $tests = array();

        $node = $this->createFunction('foo');
        $tests[] = array($node, 'twig_tests_function_dummy()', $environment);

        $node = $this->createFunction('foo', array(new Twig_Node_Expression_Constant('bar', 1), new Twig_Node_Expression_Constant('foobar', 1)));
        $tests[] = array($node, 'twig_tests_function_dummy("bar", "foobar")', $environment);

        $node = $this->createFunction('bar');
        $tests[] = array($node, 'twig_tests_function_dummy($this->env)', $environment);

        $node = $this->createFunction('bar', array(new Twig_Node_Expression_Constant('bar', 1)));
        $tests[] = array($node, 'twig_tests_function_dummy($this->env, "bar")', $environment);

        $node = $this->createFunction('foofoo');
        $tests[] = array($node, 'twig_tests_function_dummy($context)', $environment);

        $node = $this->createFunction('foofoo', array(new Twig_Node_Expression_Constant('bar', 1)));
        $tests[] = array($node, 'twig_tests_function_dummy($context, "bar")', $environment);

        $node = $this->createFunction('foobar');
        $tests[] = array($node, 'twig_tests_function_dummy($this->env, $context)', $environment);

        $node = $this->createFunction('foobar', array(new Twig_Node_Expression_Constant('bar', 1)));
        $tests[] = array($node, 'twig_tests_function_dummy($this->env, $context, "bar")', $environment);

        // named arguments
        $node = $this->createFunction('date', array(
            'timezone' => new Twig_Node_Expression_Constant('America/Chicago', 1),
            'date' => new Twig_Node_Expression_Constant(0, 1),
        ));
        $tests[] = array($node, 'twig_date_converter($this->env, 0, "America/Chicago")');

        // arbitrary named arguments
        $node = $this->createFunction('barbar');
        $tests[] = array($node, 'twig_tests_function_barbar()', $environment);

        $node = $this->createFunction('barbar', array('foo' => new Twig_Node_Expression_Constant('bar', 1)));
        $tests[] = array($node, 'twig_tests_function_barbar(null, null, array("foo" => "bar"))', $environment);

        $node = $this->createFunction('barbar', array('arg2' => new Twig_Node_Expression_Constant('bar', 1)));
        $tests[] = array($node, 'twig_tests_function_barbar(null, "bar")', $environment);

        $node = $this->createFunction('barbar', array(
            new Twig_Node_Expression_Constant('1', 1),
            new Twig_Node_Expression_Constant('2', 1),
            new Twig_Node_Expression_Constant('3', 1),
            'foo' => new Twig_Node_Expression_Constant('bar', 1),
        ));
        $tests[] = array($node, 'twig_tests_function_barbar("1", "2", array(0 => "3", "foo" => "bar"))', $environment);

        // function as an anonymous function
        $node = $this->createFunction('anonymous', array(new Twig_Node_Expression_Constant('foo', 1)));
        $tests[] = array($node, 'call_user_func_array($this->env->getFunction(\'anonymous\')->getCallable(), array("foo"))');

        return $tests;
    }

    protected function createFunction($name, array $arguments = array())
    {
        return new Twig_Node_Expression_Function($name, new Twig_Node($arguments), 1);
    }

    protected function getEnvironment()
    {
        $env = new Twig_Environment(new Twig_Loader_Array(array()));
        $env->addFunction(new Twig_Function('anonymous', function () {}));

        return $env;
    }
}

function twig_tests_function_dummy()
{
}

function twig_tests_function_barbar($arg1 = null, $arg2 = null, array $args = array())
{
}
