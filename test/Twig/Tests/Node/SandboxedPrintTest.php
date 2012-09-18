<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


class Twig_Tests_Node_SandboxedPrintTest extends Twig_Test_NodeTestCase
{
    /**
     * @covers Twig_Node_SandboxedPrint::__construct
     */
    public function testConstructor()
    {
        $node = new Twig_Node_SandboxedPrint($expr = new Twig_Node_Expression_Constant('foo', 0), 0);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    /**
     * @covers Twig_Node_SandboxedPrint::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $tests[] = array(new Twig_Node_SandboxedPrint(new Twig_Node_Expression_Constant('foo', 0), 0), <<<EOF
echo \$this->env->getExtension('sandbox')->ensureToStringAllowed("foo");
EOF
        );

        return $tests;
    }
}
