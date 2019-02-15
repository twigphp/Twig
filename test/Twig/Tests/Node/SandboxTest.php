<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_SandboxTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $body = new \Twig\Node\TextNode('foo', 1);
        $node = new \Twig\Node\SandboxNode($body, 1);

        $this->assertEquals($body, $node->getNode('body'));
    }

    public function getTests()
    {
        $tests = [];

        $body = new \Twig\Node\TextNode('foo', 1);
        $node = new \Twig\Node\SandboxNode($body, 1);

        $tests[] = [$node, <<<EOF
// line 1
\$sandbox = \$this->extensions['Twig_Extension_Sandbox'];
if (!\$alreadySandboxed = \$sandbox->isSandboxed()) {
    \$sandbox->enableSandbox();
}
echo "foo";
if (!\$alreadySandboxed) {
    \$sandbox->disableSandbox();
}
EOF
        ];

        return $tests;
    }
}
