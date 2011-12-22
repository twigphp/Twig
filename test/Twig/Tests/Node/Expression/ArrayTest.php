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

class Twig_Tests_Node_Expression_ArrayTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Array::__construct
     */
    public function testConstructor()
    {
        $elements = array(new Twig_Node_Expression_Constant('foo', 0), $foo = new Twig_Node_Expression_Constant('bar', 0));
        $node = new Twig_Node_Expression_Array($elements, 0);

        $this->assertEquals($foo, $node->getNode(1));
    }

    /**
     * @covers Twig_Node_Expression_Array::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $elements = array(
            new Twig_Node_Expression_Constant('foo', 0),
            new Twig_Node_Expression_Constant('bar', 0),

            new Twig_Node_Expression_Constant('bar', 0),
            new Twig_Node_Expression_Constant('foo', 0),
        );
        $node = new Twig_Node_Expression_Array($elements, 0);

        return array(
            array($node, 'array("foo" => "bar", "bar" => "foo")'),
        );
    }
}
