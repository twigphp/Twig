<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/TestCase.php';

class Twig_Tests_Node_PrintTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Print::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $node = new Twig_Node_Print($expr, 0);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    /**
     * @covers Twig_Node_Print::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();
        $tests[] = array(new Twig_Node_Print(new Twig_Node_Expression_Constant('foo', 0), 0), 'echo "foo";');

        return $tests;
    }
}
