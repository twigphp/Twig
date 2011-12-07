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

class Twig_Tests_Node_Expression_TestTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Test::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $name = new Twig_Node_Expression_Constant('null', 0);
        $args = new Twig_Node();
        $node = new Twig_Node_Expression_Test($expr, $name, $args, 0);

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

        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $node = new Twig_Node_Expression_Test_Null($expr, 'null', new Twig_Node(array()), 0);

        $tests[] = array($node, '(null === "foo")');

        return $tests;
    }

    /**
     * @covers Twig_Node_Expression_Filter::compile
     * @expectedException        Twig_Error_Syntax
     * @expectedExceptionMessage The test "nul" does not exist. Did you mean "null" at line 0
     */
    public function testUnknownTest()
    {
        $node = $this->createTest(new Twig_Node_Expression_Constant('foo', 0), 'nul');
        $node->compile($this->getCompiler());
    }

    protected function createTest($node, $name, array $arguments = array())
    {
        return new Twig_Node_Expression_Test($node, $name, new Twig_Node($arguments), 0);
    }
}
