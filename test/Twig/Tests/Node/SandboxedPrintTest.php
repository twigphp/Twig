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

class Twig_Tests_Node_SandboxedPrintTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_SandboxedPrint::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Constant('foo', 0);
        $node = new Twig_Node_Print($expr, 0);
        $node = new Twig_Node_SandboxedPrint($node);

        $this->assertEquals($expr, $node->expr);
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

        $node = new Twig_Node_Print(new Twig_Node_Expression_Constant('foo', 0), 0);
        $tests[] = array(new Twig_Node_SandboxedPrint($node), <<<EOF
if (\$this->env->hasExtension('sandbox') && is_object("foo")) {
    \$this->env->getExtension('sandbox')->checkMethodAllowed("foo", '__toString');
}
echo "foo";
EOF
        );

        return $tests;
    }
}
