<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/../TestCase.php';

class Twig_Tests_Node_Expression_FunctionTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Function::__construct
     */
    public function testConstructor()
    {
        $name = 'function';
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_Function($name, $args, 0);

        $this->assertEquals($name, $node->getAttribute('name'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    /**
     * @covers Twig_Node_Expression_Function::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage The function "cycl" does not exist. Did you mean "cycle" at line 0
     */
    public function testUnknownFunction()
    {
        $node = $this->createFunction('cycl', array());
        $node->compile($this->getCompiler());
    }

    public function getTests()
    {
        $environment = new Twig_Environment();
        $environment->addFunction('foo', new Twig_Function_Function('foo', array()));
        $environment->addFunction('bar', new Twig_Function_Function('bar', array('needs_environment' => true)));
        $environment->addFunction('foofoo', new Twig_Function_Function('foofoo', array('needs_context' => true)));
        $environment->addFunction('foobar', new Twig_Function_Function('foobar', array('needs_environment' => true, 'needs_context' => true)));

        $tests = array();

        $node = $this->createFunction('foo');
        $tests[] = array($node, 'foo()', $environment);

        $node = $this->createFunction('foo', array(new Twig_Node_Expression_Constant('bar', 0), new Twig_Node_Expression_Constant('foobar', 0)));
        $tests[] = array($node, 'foo("bar", "foobar")', $environment);

        $node = $this->createFunction('bar');
        $tests[] = array($node, 'bar($this->env)', $environment);

        $node = $this->createFunction('bar', array(new Twig_Node_Expression_Constant('bar', 0)));
        $tests[] = array($node, 'bar($this->env, "bar")', $environment);

        $node = $this->createFunction('foofoo');
        $tests[] = array($node, 'foofoo($context)', $environment);

        $node = $this->createFunction('foofoo', array(new Twig_Node_Expression_Constant('bar', 0)));
        $tests[] = array($node, 'foofoo($context, "bar")', $environment);

        $node = $this->createFunction('foobar');
        $tests[] = array($node, 'foobar($this->env, $context)', $environment);

        $node = $this->createFunction('foobar', array(new Twig_Node_Expression_Constant('bar', 0)));
        $tests[] = array($node, 'foobar($this->env, $context, "bar")', $environment);

        return $tests;
    }

    protected function createFunction($name, array $arguments = array())
    {
        return new Twig_Node_Expression_Function($name, new Twig_Node($arguments), 0);
    }
}
