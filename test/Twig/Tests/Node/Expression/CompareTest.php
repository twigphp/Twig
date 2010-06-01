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

class Twig_Tests_Node_Expression_CompareTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Compare::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant(1, 0);
        $ops = new Twig_Node(array(
            new Twig_Node_Expression_Constant('>', 0),
            new Twig_Node_Expression_Constant(2, 0),
        ), array(), 0);
        $node = new Twig_Node_Expression_Compare($expr, $ops, 0);

        $this->assertEquals($expr, $node->expr);
        $this->assertEquals($ops, $node->ops);
    }

    /**
     * @covers Twig_Node_Expression_Compare::compile
     * @covers Twig_Node_Expression_Compare::compileIn
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $expr = new Twig_Node_Expression_Constant(1, 0);
        $ops = new Twig_Node(array(
            new Twig_Node_Expression_Constant('>', 0),
            new Twig_Node_Expression_Constant(2, 0),
        ), array(), 0);
        $node = new Twig_Node_Expression_Compare($expr, $ops, 0);
        $tests[] = array($node, '1 > 2');

        $ops = new Twig_Node(array(
            new Twig_Node_Expression_Constant('>', 0),
            new Twig_Node_Expression_Constant(2, 0),
            new Twig_Node_Expression_Constant('<', 0),
            new Twig_Node_Expression_Constant(4, 0),
        ), array(), 0);
        $node = new Twig_Node_Expression_Compare($expr, $ops, 0);
        $tests[] = array($node, '1 > ($tmp1 = 2) && ($tmp1 < 4)');

        $ops = new Twig_Node(array(
            new Twig_Node_Expression_Constant('in', 0),
            new Twig_Node_Expression_Constant(2, 0),
        ), array(), 0);
        $node = new Twig_Node_Expression_Compare($expr, $ops, 0);
        $tests[] = array($node, 'twig_in_filter(1, 2)');

        return $tests;
    }
}
