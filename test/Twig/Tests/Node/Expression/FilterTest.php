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

class Twig_Tests_Node_Expression_FilterTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Filter::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        $this->assertEquals($expr, $node->node);
        $this->assertEquals($filters, $node->filters);
    }

    /**
     * @covers Twig_Node_Expression_Filter::hasFilter
     */
    public function testHasFilter()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        $this->assertTrue($node->hasFilter('upper'));
        $this->assertFalse($node->hasFilter('lower'));
    }

    /**
     * @covers Twig_Node_Expression_Filter::prependFilter
     */
    public function testPrependFilter()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        $a = new Twig_Node_Expression_Constant('lower', 0);
        $b = new Twig_Node_Expression_Constant('foobar', 0);
        $node->prependFilter($a, $b);

        $filters = new Twig_Node(array(
            $a,
            $b,
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
        ), array(), 0);

        $this->assertEquals($filters, $node->filters);
    }

    /**
     * @covers Twig_Node_Expression_Filter::appendFilter
     */
    public function testAppendFilter()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        $a = new Twig_Node_Expression_Constant('lower', 0);
        $b = new Twig_Node_Expression_Constant('foobar', 0);
        $node->appendFilter($a, $b);

        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
            $a,
            $b,
        ), array(), 0);

        $this->assertEquals($filters, $node->filters);
    }

    /**
     * @covers Twig_Node_Expression_Filter::appendFilters
     */
    public function testAppendFilters()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        $others = new Twig_Node(array(
            $a = new Twig_Node_Expression_Constant('lower', 0),
            $b = new Twig_Node_Expression_Constant('foobar', 0),
        ), array(), 0);
        $node->appendFilters($others);

        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
            $a,
            $b,
        ), array(), 0);

        $this->assertEquals($filters, $node->filters);
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);

        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('foobar', 0),
            new Twig_Node(array(new Twig_Node_Expression_Constant('bar', 0), new Twig_Node_Expression_Constant('foobar', 0))),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        $tests[] = array($node, '$this->resolveMissingFilter("foobar", array("foo", "bar", "foobar"))');

        try {
            $node->compile($this->getCompiler());
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals('Twig_SyntaxError', get_class($e));
        }
    }

    public function getTests()
    {
        $tests = array();

        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $filters = new Twig_Node(array(
            new Twig_Node_Expression_Constant('upper', 0),
            new Twig_Node(),
            new Twig_Node_Expression_Constant('lower', 0),
            new Twig_Node(array(new Twig_Node_Expression_Constant('bar', 0), new Twig_Node_Expression_Constant('foobar', 0))),
        ), array(), 0);
        $node = new Twig_Node_Expression_Filter($expr, $filters, 0);

        if (function_exists('mb_get_info')) {
            $tests[] = array($node, 'twig_lower_filter($this->env, twig_upper_filter($this->env, "foo"), "bar", "foobar")');
        } else {
            $tests[] = array($node, 'strtolower(strtoupper("foo"), "bar", "foobar")');
        }

        return $tests;
    }
}
