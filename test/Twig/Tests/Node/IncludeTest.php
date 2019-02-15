<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_IncludeTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\ConstantExpression('foo.twig', 1);
        $node = new \Twig\Node\IncludeNode($expr, null, false, false, 1);

        $this->assertFalse($node->hasNode('variables'));
        $this->assertEquals($expr, $node->getNode('expr'));
        $this->assertFalse($node->getAttribute('only'));

        $vars = new \Twig\Node\Expression\ArrayExpression([new \Twig\Node\Expression\ConstantExpression('foo', 1), new \Twig\Node\Expression\ConstantExpression(true, 1)], 1);
        $node = new \Twig\Node\IncludeNode($expr, $vars, true, false, 1);
        $this->assertEquals($vars, $node->getNode('variables'));
        $this->assertTrue($node->getAttribute('only'));
    }

    public function getTests()
    {
        $tests = [];

        $expr = new \Twig\Node\Expression\ConstantExpression('foo.twig', 1);
        $node = new \Twig\Node\IncludeNode($expr, null, false, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$this->loadTemplate("foo.twig", null, 1)->display(\$context);
EOF
        ];

        $expr = new \Twig\Node\Expression\ConditionalExpression(
                        new \Twig\Node\Expression\ConstantExpression(true, 1),
                        new \Twig\Node\Expression\ConstantExpression('foo', 1),
                        new \Twig\Node\Expression\ConstantExpression('foo', 1),
                        0
                    );
        $node = new \Twig\Node\IncludeNode($expr, null, false, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$this->loadTemplate(((true) ? ("foo") : ("foo")), null, 1)->display(\$context);
EOF
        ];

        $expr = new \Twig\Node\Expression\ConstantExpression('foo.twig', 1);
        $vars = new \Twig\Node\Expression\ArrayExpression([new \Twig\Node\Expression\ConstantExpression('foo', 1), new \Twig\Node\Expression\ConstantExpression(true, 1)], 1);
        $node = new \Twig\Node\IncludeNode($expr, $vars, false, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$this->loadTemplate("foo.twig", null, 1)->display(array_merge(\$context, ["foo" => true]));
EOF
        ];

        $node = new \Twig\Node\IncludeNode($expr, $vars, true, false, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$this->loadTemplate("foo.twig", null, 1)->display(["foo" => true]);
EOF
        ];

        $node = new \Twig\Node\IncludeNode($expr, $vars, true, true, 1);
        $tests[] = [$node, <<<EOF
// line 1
try {
    \$this->loadTemplate("foo.twig", null, 1)->display(["foo" => true]);
} catch (\Twig\Error\LoaderError \$e) {
    // ignore missing template
}
EOF
        ];

        return $tests;
    }
}
