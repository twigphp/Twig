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

class Twig_Tests_Node_IfTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_If::__construct
     */
    public function testConstructor()
    {
        $t = new Twig_Node(array(
            new Twig_Node_Expression_Constant(true, 0),
            new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0),
        ), array(), 0);
        $else = null;
        $node = new Twig_Node_If($t, $else, 0);

        $this->assertEquals($t, $node->getNode('tests'));
        $this->assertEquals(null, $node->getNode('else'));

        $else = new Twig_Node_Print(new Twig_Node_Expression_Name('bar', 0), 0);
        $node = new Twig_Node_If($t, $else, 0);
        $this->assertEquals($else, $node->getNode('else'));
    }

    /**
     * @covers Twig_Node_If::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $t = new Twig_Node(array(
            new Twig_Node_Expression_Constant(true, 0),
            new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0),
        ), array(), 0);
        $else = null;
        $node = new Twig_Node_If($t, $else, 0);

        $tests[] = array($node, <<<EOF
if (true) {
    echo {$this->getVariableGetter('foo')};
}
EOF
        );

        $t = new Twig_Node(array(
            new Twig_Node_Expression_Constant(true, 0),
            new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0),
            new Twig_Node_Expression_Constant(false, 0),
            new Twig_Node_Print(new Twig_Node_Expression_Name('bar', 0), 0),
        ), array(), 0);
        $else = null;
        $node = new Twig_Node_If($t, $else, 0);

        $tests[] = array($node, <<<EOF
if (true) {
    echo {$this->getVariableGetter('foo')};
} elseif (false) {
    echo {$this->getVariableGetter('bar')};
}
EOF
        );

        $t = new Twig_Node(array(
            new Twig_Node_Expression_Constant(true, 0),
            new Twig_Node_Print(new Twig_Node_Expression_Name('foo', 0), 0),
        ), array(), 0);
        $else = new Twig_Node_Print(new Twig_Node_Expression_Name('bar', 0), 0);
        $node = new Twig_Node_If($t, $else, 0);

        $tests[] = array($node, <<<EOF
if (true) {
    echo {$this->getVariableGetter('foo')};
} else {
    echo {$this->getVariableGetter('bar')};
}
EOF
        );

        return $tests;
    }
}
