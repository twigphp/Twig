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

class Twig_Tests_Node_Expression_GetAttrTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_GetAttr::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Name('foo', 0);
        $attr = new Twig_Node_Expression_Constant('bar', 0);
        $args = new Twig_Node(array(
            new Twig_Node_Expression_Name('foo', 0),
            new Twig_Node_Expression_Constant('bar', 0),
        ));
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_Node_Expression_GetAttr::TYPE_ARRAY, 0);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($attr, $node->getNode('attribute'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals(Twig_Node_Expression_GetAttr::TYPE_ARRAY, $node->getAttribute('type'));
    }

    /**
     * @covers Twig_Node_Expression_GetAttr::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $expr = new Twig_Node_Expression_Name('foo', 0);
        $attr = new Twig_Node_Expression_Constant('bar', 0);
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_Node_Expression_GetAttr::TYPE_ANY, 0);
        $tests[] = array($node, '$this->getAttribute((isset($context[\'foo\']) ? $context[\'foo\'] : null), "bar", array(), "any", false, 0)');

        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_Node_Expression_GetAttr::TYPE_ARRAY, 0);
        $tests[] = array($node, '$this->getAttribute((isset($context[\'foo\']) ? $context[\'foo\'] : null), "bar", array(), "array", false, 0)');


        $args = new Twig_Node(array(
            new Twig_Node_Expression_Name('foo', 0),
            new Twig_Node_Expression_Constant('bar', 0),
        ));
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_Node_Expression_GetAttr::TYPE_METHOD, 0);
        $tests[] = array($node, '$this->getAttribute((isset($context[\'foo\']) ? $context[\'foo\'] : null), "bar", array((isset($context[\'foo\']) ? $context[\'foo\'] : null), "bar", ), "method", false, 0)');

        return $tests;
    }
}
