<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_TestTest extends Twig_Test_NodeTestCase
{
    /**
     * @covers Twig_Node_Expression_Test::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 1);
        $name = new Twig_Node_Expression_Constant('null', 1);
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_Test($expr, $name, $args, 1);

        $this->assertEquals($expr, $node->getNode('node'));
        $this->assertEquals($args, $node->getNode('arguments'));
        $this->assertEquals($name, $node->getAttribute('name'));
    }

    /**
     * @covers Twig_Node_Expression_Test::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $expr = new Twig_Node_Expression_Constant('foo', 1);
        $node = new Twig_Node_Expression_Test_Null($expr, 'null', new Twig_Node(array()), 1);

        $tests[] = array($node, '(null === "foo")');

        return $tests;
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage The test "nul" does not exist. Did you mean "null" at line 1
     */
    public function testUnknownTest()
    {
        $node = $this->createTest(new Twig_Node_Expression_Constant('foo', 1), 'nul');
        $node->compile($this->getCompiler());
    }

    protected function createTest($node, $name, array $arguments = array())
    {
        return new Twig_Node_Expression_Test($node, $name, new Twig_Node($arguments), 1);
    }
}
