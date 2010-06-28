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

class Twig_Tests_Node_DebugTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Debug::__construct
     */
    public function testConstructor()
    {
        $expr = new Twig_Node_Expression_Name('foo', 0);
        $node = new Twig_Node_Debug($expr, 0);
        $this->assertEquals($expr, $node->expr);

        $node = new Twig_Node_Debug(null, 0);
        $this->assertEquals(null, $node->expr);
    }

    /**
     * @covers Twig_Node_Debug::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $tests[] = array(new Twig_Node_Debug(null, 0), <<<EOF
if (\$this->env->isDebug()) {
    \$vars = array();
    foreach (\$context as \$key => \$value) {
        if (!\$value instanceof Twig_Template) {
            \$vars[\$key] = \$value;
        }
    }
    print_r(\$vars);
}
EOF
        );

        $expr = new Twig_Node_Expression_Name('foo', 0);
        $node = new Twig_Node_Debug($expr, 0);

        $tests[] = array($node, <<<EOF
if (\$this->env->isDebug()) {
    print_r((isset(\$context['foo']) ? \$context['foo'] : null));
}
EOF
        );

        return $tests;
    }
}
