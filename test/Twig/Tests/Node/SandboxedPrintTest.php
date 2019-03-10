<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\SandboxedPrintNode;
use Twig\Template;
use Twig\Test\NodeTestCase;

class Twig_Tests_Node_SandboxedPrintTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new SandboxedPrintNode($expr = new ConstantExpression('foo', 1), 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public function getTests()
    {
        $tests[] = [new SandboxedPrintNode(new ConstantExpression('foo', 1), 1), <<<EOF
// line 1
echo "foo";
EOF
        ];

        $tests[] = [new SandboxedPrintNode(new NameExpression('foo', 1), 1), <<<EOF
// line 1
echo \$this->extensions[SandboxExtension::class]->ensureToStringAllowed({$this->getVariableGetter('foo', false)});
EOF
        ];

        return $tests;
    }
}
