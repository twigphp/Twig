<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_SetTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $names = new \Twig\Node\Node([new \Twig\Node\Expression\AssignNameExpression('foo', 1)], [], 1);
        $values = new \Twig\Node\Node([new \Twig\Node\Expression\ConstantExpression('foo', 1)], [], 1);
        $node = new \Twig\Node\SetNode(false, $names, $values, 1);

        $this->assertEquals($names, $node->getNode('names'));
        $this->assertEquals($values, $node->getNode('values'));
        $this->assertFalse($node->getAttribute('capture'));
    }

    public function getTests()
    {
        $tests = [];

        $names = new \Twig\Node\Node([new \Twig\Node\Expression\AssignNameExpression('foo', 1)], [], 1);
        $values = new \Twig\Node\Node([new \Twig\Node\Expression\ConstantExpression('foo', 1)], [], 1);
        $node = new \Twig\Node\SetNode(false, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = "foo";
EOF
        ];

        $names = new \Twig\Node\Node([new \Twig\Node\Expression\AssignNameExpression('foo', 1)], [], 1);
        $values = new \Twig\Node\Node([new \Twig\Node\PrintNode(new \Twig\Node\Expression\ConstantExpression('foo', 1), 1)], [], 1);
        $node = new \Twig\Node\SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
ob_start();
echo "foo";
\$context["foo"] = ('' === \$tmp = ob_get_clean()) ? '' : new \Twig\Markup(\$tmp, \$this->env->getCharset());
EOF
        ];

        $names = new \Twig\Node\Node([new \Twig\Node\Expression\AssignNameExpression('foo', 1)], [], 1);
        $values = new \Twig\Node\TextNode('foo', 1);
        $node = new \Twig\Node\SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = ('' === \$tmp = "foo") ? '' : new \Twig\Markup(\$tmp, \$this->env->getCharset());
EOF
        ];

        $names = new \Twig\Node\Node([new \Twig\Node\Expression\AssignNameExpression('foo', 1), new \Twig\Node\Expression\AssignNameExpression('bar', 1)], [], 1);
        $values = new \Twig\Node\Node([new \Twig\Node\Expression\ConstantExpression('foo', 1), new \Twig\Node\Expression\NameExpression('bar', 1)], [], 1);
        $node = new \Twig\Node\SetNode(false, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
list(\$context["foo"], \$context["bar"]) = ["foo", {$this->getVariableGetter('bar')}];
EOF
        ];

        return $tests;
    }
}
