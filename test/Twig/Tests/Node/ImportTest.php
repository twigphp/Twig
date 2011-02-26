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

class Twig_Tests_Node_ImportTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Import::__construct
     */
    public function testConstructor()
    {
        $macro = new Twig_Node_Expression_Constant('foo.twig', 0);
        $var = new Twig_Node_Expression_AssignName('macro', 0);
        $node = new Twig_Node_Import($macro, $var, 0);

        $this->assertEquals($macro, $node->getNode('expr'));
        $this->assertEquals($var, $node->getNode('var'));
    }

    /**
     * @covers Twig_Node_Import::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $macro = new Twig_Node_Expression_Constant('foo.twig', 0);
        $var = new Twig_Node_Expression_AssignName('macro', 0);
        $node = new Twig_Node_Import($macro, $var, 0);

        $tests[] = array($node, '$context[\'macro\'] = $this->env->loadTemplate("foo.twig");');

        return $tests;
    }
}
