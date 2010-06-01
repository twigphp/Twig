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
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, 0, '[');

        $this->assertEquals($expr, $node->node);
        $this->assertEquals($attr, $node->attribute);
        $this->assertEquals($args, $node->arguments);
        $this->assertEquals('[', $node['token_value']);
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
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, 0);
        $tests[] = array($node, '$this->getAttribute($this->getContext($context, \'foo\'), "bar", array())');

        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, 0, '[');
        $tests[] = array($node, '$this->getAttribute($this->getContext($context, \'foo\'), "bar", array(), true)');

        $args = new Twig_Node(array(
            new Twig_Node_Expression_Name('foo', 0),
            new Twig_Node_Expression_Constant('bar', 0),
        ));
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, 0);
        $tests[] = array($node, '$this->getAttribute($this->getContext($context, \'foo\'), "bar", array($this->getContext($context, \'foo\'), "bar", ))');

        return $tests;
    }
}
