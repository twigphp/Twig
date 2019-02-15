<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_SandboxedPrintTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $node = new \Twig\Node\SandboxedPrintNode($expr = new \Twig\Node\Expression\ConstantExpression('foo', 1), 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public function getTests()
    {
        $tests = [];

        $tests[] = [new \Twig\Node\SandboxedPrintNode(new \Twig\Node\Expression\ConstantExpression('foo', 1), 1), <<<EOF
// line 1
echo \$this->env->getExtension('\Twig\Extension\SandboxExtension')->ensureToStringAllowed("foo");
EOF
        ];

        return $tests;
    }
}
