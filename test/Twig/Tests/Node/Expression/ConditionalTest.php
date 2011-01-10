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

class Twig_Tests_Node_Expression_ConditionalTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Conditional::__construct
     */
    public function testConstructor()
    {
        $expr1 = new Twig_Node_Expression_Constant(1, 0);
        $expr2 = new Twig_Node_Expression_Constant(2, 0);
        $expr3 = new Twig_Node_Expression_Constant(3, 0);
        $node = new Twig_Node_Expression_Conditional($expr1, $expr2, $expr3, 0);

        $this->assertEquals($expr1, $node->getNode('expr1'));
        $this->assertEquals($expr2, $node->getNode('expr2'));
        $this->assertEquals($expr3, $node->getNode('expr3'));
    }

    /**
     * @covers Twig_Node_Expression_Conditional::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $expr1 = new Twig_Node_Expression_Constant(1, 0);
        $expr2 = new Twig_Node_Expression_Constant(2, 0);
        $expr3 = new Twig_Node_Expression_Constant(3, 0);
        $node = new Twig_Node_Expression_Conditional($expr1, $expr2, $expr3, 0);
        $tests[] = array($node, '((1) ? (2) : (3))');

        return $tests;
    }
}
