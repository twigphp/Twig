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
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_TemplateInterface::ARRAY_CALL, 0);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($attr, $node->getNode('attribute'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals(Twig_TemplateInterface::ARRAY_CALL, $node->getAttribute('type'));
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
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_TemplateInterface::ANY_CALL, 0);
        $tests[] = array($node, '$this->getAttribute($this->getContext($context, \'foo\'), "bar", array(), "any", false)');

        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_TemplateInterface::ARRAY_CALL, 0);
        $tests[] = array($node, '$this->getAttribute($this->getContext($context, \'foo\'), "bar", array(), "array", false)');


        $args = new Twig_Node(array(
            new Twig_Node_Expression_Name('foo', 0),
            new Twig_Node_Expression_Constant('bar', 0),
        ));
        $node = new Twig_Node_Expression_GetAttr($expr, $attr, $args, Twig_TemplateInterface::METHOD_CALL, 0);
        $tests[] = array($node, '$this->getAttribute($this->getContext($context, \'foo\'), "bar", array($this->getContext($context, \'foo\'), "bar", ), "method", false)');

        return $tests;
    }
}
