<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_Binary_DivTest extends Twig_Test_NodeTestCase
{
    /**
     * @covers Twig_Node_Expression_Binary_Div::__construct
     */
    public function testConstructor()
    {
        $left = new Twig_Node_Expression_Constant(1, 0);
        $right = new Twig_Node_Expression_Constant(2, 0);
        $node = new Twig_Node_Expression_Binary_Div($left, $right, 0);

        $this->assertEquals($left, $node->getNode('left'));
        $this->assertEquals($right, $node->getNode('right'));
    }

    /**
     * @covers Twig_Node_Expression_Binary_Div::compile
     * @covers Twig_Node_Expression_Binary_Div::operator
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $left = new Twig_Node_Expression_Constant(1, 0);
        $right = new Twig_Node_Expression_Constant(2, 0);
        $node = new Twig_Node_Expression_Binary_Div($left, $right, 0);

        return array(
            array($node, '(1 / 2)'),
        );
    }
}
