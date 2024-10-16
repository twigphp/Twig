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

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\IfNode;
use Twig\Node\Nodes;
use Twig\Node\PrintNode;
use Twig\Test\NodeTestCase;

class IfTest extends NodeTestCase
{
    public function testConstructor()
    {
        $t = new Nodes([
            new ConstantExpression(true, 1),
            new PrintNode(new ContextVariable('foo', 1), 1),
        ], 1);
        $else = null;
        $node = new IfNode($t, $else, 1);

        $this->assertEquals($t, $node->getNode('tests'));
        $this->assertFalse($node->hasNode('else'));

        $else = new PrintNode(new ContextVariable('bar', 1), 1);
        $node = new IfNode($t, $else, 1);
        $this->assertEquals($else, $node->getNode('else'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $t = new Nodes([
            new ConstantExpression(true, 1),
            new PrintNode(new ContextVariable('foo', 1), 1),
        ], 1);
        $else = null;
        $node = new IfNode($t, $else, 1);

        $fooGetter = self::createVariableGetter('foo');
        $barGetter = self::createVariableGetter('bar');

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    yield $fooGetter;
}
EOF
        ];

        $t = new Nodes([
            new ConstantExpression(true, 1),
            new PrintNode(new ContextVariable('foo', 1), 1),
            new ConstantExpression(false, 1),
            new PrintNode(new ContextVariable('bar', 1), 1),
        ], 1);
        $else = null;
        $node = new IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    yield $fooGetter;
} elseif (false) {
    yield $barGetter;
}
EOF
        ];

        $t = new Nodes([
            new ConstantExpression(true, 1),
            new PrintNode(new ContextVariable('foo', 1), 1),
        ], 1);
        $else = new PrintNode(new ContextVariable('bar', 1), 1);
        $node = new IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    yield $fooGetter;
} else {
    yield $barGetter;
}
EOF
        ];

        return $tests;
    }
}
