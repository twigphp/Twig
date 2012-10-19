<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_FilterTest extends Twig_Test_NodeTestCase
{
    /**
     * @covers Twig_Node_Expression_Filter::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 1);
        $name = new Twig_Node_Expression_Constant('upper', 1);
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_Filter($expr, $name, $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($name, $node->getNode('filter'));
        $this->assertEquals($args, $node->getNode('arguments'));
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @expectedException Twig_Error_Syntax
     * @expectedExceptionMessage The filter "lowe" does not exist. Did you mean "lower" at line 1
     */
    public function testCompileUnknownFilter()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 1);
        $node = $this->createFilter($expr, 'lowe', array(new Twig_Node_Expression_Constant('bar', 1), new Twig_Node_Expression_Constant('foobar', 1)));

        $node->compile($this->getCompiler());
    }

    public function getTests()
    {
        $tests = array();

        $expr = new Twig_Node_Expression_Constant('foo', 1);
        $node = $this->createFilter($expr, 'upper');
        $node = $this->createFilter($node, 'number_format', array(new Twig_Node_Expression_Constant(2, 1), new Twig_Node_Expression_Constant('.', 1), new Twig_Node_Expression_Constant(',', 1)));

        if (function_exists('mb_get_info')) {
            $tests[] = array($node, 'twig_number_format_filter($this->env, twig_upper_filter($this->env, "foo"), 2, ".", ",")');
        } else {
            $tests[] = array($node, 'twig_number_format_filter($this->env, strtoupper("foo"), 2, ".", ",")');
        }

        return $tests;
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage The filter "uppe" does not exist. Did you mean "upper" at line 1
     */
    public function testUnknownFilter()
    {
        $node = $this->createFilter(new Twig_Node_Expression_Constant('foo', 1), 'uppe');
        $node->compile($this->getCompiler());
    }

    protected function createFilter($node, $name, array $arguments = array())
    {
        $name = new Twig_Node_Expression_Constant($name, 1);
        $arguments = new Twig_Node($arguments);

        return new Twig_Node_Expression_Filter($node, $name, $arguments, 1);
    }
}
