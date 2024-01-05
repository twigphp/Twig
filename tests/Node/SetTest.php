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

use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class SetTest extends NodeTestCase
{
    public function testConstructor()
    {
        $names = new Node([new AssignNameExpression('foo', 1)], [], 1);
        $values = new Node([new ConstantExpression('foo', 1)], [], 1);
        $node = new SetNode(false, $names, $values, 1);

        $this->assertEquals($names, $node->getNode('names'));
        $this->assertEquals($values, $node->getNode('values'));
        $this->assertFalse($node->getAttribute('capture'));
    }

    public function getTests()
    {
        $tests = [];

        $names = new Node([new AssignNameExpression('foo', 1)], [], 1);
        $values = new Node([new ConstantExpression('foo', 1)], [], 1);
        $node = new SetNode(false, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = "foo";
EOF
        ];

        $names = new Node([new AssignNameExpression('foo', 1)], [], 1);
        $values = new Node([new PrintNode(new ConstantExpression('foo', 1), 1)], [], 1);
        $node = new SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = (function () use (&\$context, \$macros, \$blocks) {
    ob_start(function () { return ''; });
    try {
        echo "foo";

        return ('' === \$tmp = ob_get_contents()) ? '' : new Markup(\$tmp, \$this->env->getCharset());
    } finally {
        ob_end_clean();
    }
})();
EOF
        ];

        $names = new Node([new AssignNameExpression('foo', 1)], [], 1);
        $values = new TextNode('foo', 1);
        $node = new SetNode(true, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
\$context["foo"] = ('' === \$tmp = "foo") ? '' : new Markup(\$tmp, \$this->env->getCharset());
EOF
        ];

        $names = new Node([new AssignNameExpression('foo', 1), new AssignNameExpression('bar', 1)], [], 1);
        $values = new Node([new ConstantExpression('foo', 1), new NameExpression('bar', 1)], [], 1);
        $node = new SetNode(false, $names, $values, 1);
        $tests[] = [$node, <<<EOF
// line 1
[\$context["foo"], \$context["bar"]] = ["foo", {$this->getVariableGetter('bar')}];
EOF
        ];

        return $tests;
    }
}
