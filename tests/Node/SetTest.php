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

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class SetTest extends NodeTestCase
{
    public function testConstructor()
    {
        $names = new Nodes([new AssignContextVariable('foo', 1)], 1);
        $values = new Nodes([new ConstantExpression('foo', 1)], 1);
        $node = new SetNode(false, $names, $values, 1);

        $this->assertEquals($names, $node->getNode('names'));
        $this->assertEquals($values, $node->getNode('values'));
        $this->assertFalse($node->getAttribute('capture'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $names = new Nodes([new AssignContextVariable('foo', 1)], 1);
        $values = new Nodes([new ConstantExpression('foo', 1)], 1);
        $node = new SetNode(false, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = "foo";
EOF
        ];

        $names = new Nodes([new AssignContextVariable('foo', 1)], 1);
        $values = new Nodes([new PrintNode(new ConstantExpression('foo', 1), 1)], 1);
        $node = new SetNode(true, $names, $values, 1);

        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = ('' === \$tmp = implode('', iterator_to_array((function () use (&\$context, \$macros, \$blocks) {
    yield "foo";
    yield from [];
})(), false))) ? '' : new Markup(\$tmp, \$this->env->getCharset());
EOF
            , new Environment(new ArrayLoader(), ['use_yield' => true]),
        ];

        $tests[] = [$node, <<<'EOF'
// line 1
$context["foo"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
    yield "foo";
    yield from [];
})())) ? '' : new Markup($tmp, $this->env->getCharset());
EOF
            , new Environment(new ArrayLoader(), ['use_yield' => false]),
        ];

        $names = new Nodes([new AssignContextVariable('foo', 1)], 1);
        $values = new TextNode('foo', 1);
        $node = new SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = new Markup("foo", \$this->env->getCharset());
EOF
        ];

        $names = new Nodes([new AssignContextVariable('foo', 1)], 1);
        $values = new TextNode('', 1);
        $node = new SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = "";
EOF
        ];

        $names = new Nodes([new AssignContextVariable('foo', 1)], 1);
        $values = new PrintNode(new ConstantExpression('foo', 1), 1);
        $node = new SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = new Markup("foo", \$this->env->getCharset());
EOF
        ];

        $names = new Nodes([new AssignContextVariable('foo', 1), new AssignContextVariable('bar', 1)], 1);
        $values = new Nodes([new ConstantExpression('foo', 1), new ContextVariable('bar', 1)], 1);
        $node = new SetNode(false, $names, $values, 1);
        $tests[] = [$node, <<<'EOF'
// line 1
[$context["foo"], $context["bar"]] = ["foo", ($context["bar"] ?? null)];
EOF
        ];

        return $tests;
    }
}
