<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node;

use Twig\Node\EmbedNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Test\NodeTestCase;

class EmbedTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new EmbedNode('foo.twig', 0, null, false, false, 1);

        $this->assertFalse($node->hasNode('variables'));
        $this->assertEquals('foo.twig', $node->getAttribute('name'));
        $this->assertEquals(0, $node->getAttribute('index'));
        $this->assertFalse($node->getAttribute('only'));
        $this->assertFalse($node->getAttribute('ignore_missing'));

        $vars = new ArrayExpression([new ConstantExpression('foo', 1), new ConstantExpression(true, 1)], 1);
        $node = new EmbedNode('bar.twig', 1, $vars, true, false, 1);
        $this->assertEquals($vars, $node->getNode('variables'));
        $this->assertTrue($node->getAttribute('only'));
        $this->assertEquals('bar.twig', $node->getAttribute('name'));
        $this->assertEquals(1, $node->getAttribute('index'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $node = new EmbedNode('foo.twig', 0, null, false, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1, 0)->unwrap()->yield($context);
EOF
        ];

        $node = new EmbedNode('foo.twig', 1, null, false, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1, 1)->unwrap()->yield($context);
EOF
        ];

        $vars = new ArrayExpression([new ConstantExpression('foo', 1), new ConstantExpression(true, 1)], 1);
        $node = new EmbedNode('foo.twig', 0, $vars, false, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1, 0)->unwrap()->yield(CoreExtension::merge($context, ["foo" => true]));
EOF
        ];

        $node = new EmbedNode('foo.twig', 0, $vars, true, false, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
yield from $this->load("foo.twig", 1, 0)->unwrap()->yield(CoreExtension::toArray(["foo" => true]));
EOF
        ];

        $node = new EmbedNode('foo.twig', 2, $vars, true, true, 1);
        $tests[] = [$node, <<<EOF
// line 1
try {
    \$_v0 = \$this->load("foo.twig", 1, 2);
    \$_v0->getParent(\$context);
;
} catch (LoaderError \$e) {
    // ignore missing template
    \$_v0 = null;
}
if (\$_v0) {
    yield from \$_v0->unwrap()->yield(CoreExtension::toArray(["foo" => true]));
}
EOF
        ];

        return $tests;
    }
}
