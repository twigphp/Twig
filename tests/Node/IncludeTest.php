<?php

namespace Twig\Tests\Node;

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
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Node\IncludeNode;
use Twig\Test\NodeTestCase;

class IncludeTest extends NodeTestCase
{
    public function testConstructor()
    {
        $expr = new ConstantExpression('foo.twig', 1);
        $node = new IncludeNode($expr, null, false, false, 1);

        $this->assertFalse($node->hasNode('variables'));
        $this->assertEquals($expr, $node->getNode('expr'));
        $this->assertFalse($node->getAttribute('only'));

        $vars = new ArrayExpression([new ConstantExpression('foo', 1), new ConstantExpression(true, 1)], 1);
        $node = new IncludeNode($expr, $vars, true, false, 1);
        $this->assertEquals($vars, $node->getNode('variables'));
        $this->assertTrue($node->getAttribute('only'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $expr = new ConstantExpression('foo.twig', 1);
        $node = new IncludeNode($expr, null, false, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1)->unwrap()->yield($context);
EOF
        ];

        $expr = new ConditionalTernary(
            new ConstantExpression(true, 1),
            new ConstantExpression('foo', 1),
            new ConstantExpression('foo', 1),
            0
        );
        $node = new IncludeNode($expr, null, false, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load(((true) ? ("foo") : ("foo")), 1)->unwrap()->yield($context);
EOF
        ];

        $expr = new ConstantExpression('foo.twig', 1);
        $vars = new ArrayExpression([new ConstantExpression('foo', 1), new ConstantExpression(true, 1)], 1);
        $node = new IncludeNode($expr, $vars, false, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1)->unwrap()->yield(CoreExtension::merge($context, ["foo" => true]));
EOF
        ];

        $node = new IncludeNode($expr, $vars, true, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1)->unwrap()->yield(CoreExtension::toArray(["foo" => true]));
EOF
        ];

        $node = new IncludeNode($expr, $vars, true, true, 1);
        $tests[] = [$node, <<<EOF
// line 1
try {
    \$_v%s = \$this->load("foo.twig", 1);
} catch (LoaderError \$e) {
    // ignore missing template
    \$_v%s = null;
}
if (\$_v%s) {
    yield from \$_v%s->unwrap()->yield(CoreExtension::toArray(["foo" => true]));
}
EOF
            , null, true];

        return $tests;
    }
}
