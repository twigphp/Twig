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

class Twig_Tests_Node_SandboxTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Sandbox::__construct
     */
    public function testConstructor()
    {
        $body = new Twig_Node_Text('foo', 0);
        $node = new Twig_Node_Sandbox($body, 0);

        $this->assertEquals($body, $node->getNode('body'));
    }

    /**
     * @covers Twig_Node_Sandbox::compile
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $tests = array();

        $body = new Twig_Node_Text('foo', 0);
        $node = new Twig_Node_Sandbox($body, 0);

        $tests[] = array($node, <<<EOF
\$sandbox = \$this->env->getExtension('sandbox');
if (!\$alreadySandboxed = \$sandbox->isSandboxed()) {
    \$sandbox->enableSandbox();
}
echo "foo";
if (!\$alreadySandboxed) {
    \$sandbox->disableSandbox();
}
EOF
        );

        return $tests;
    }
}
