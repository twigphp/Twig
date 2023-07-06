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
use Twig\Node\Expression\NameExpression;
use Twig\Node\IfNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Test\ASTNodeTestCase;

class IfTest extends ASTNodeTestCase
{
    public function testConstructor()
    {
        $t = new Node([
            new ConstantExpression(true, 1),
            new PrintNode(new NameExpression('foo', 1), 1),
        ], [], 1);
        $else = null;
        $node = new IfNode($t, $else, 1);

        $this->assertEquals($t, $node->getNode('tests'));
        $this->assertFalse($node->hasNode('else'));

        $else = new PrintNode(new NameExpression('bar', 1), 1);
        $node = new IfNode($t, $else, 1);
        $this->assertEquals($else, $node->getNode('else'));
    }

    public static function getTests()
    {
        $tests = [];

        $t = new Node([
            new ConstantExpression(true, 1),
            new PrintNode(new NameExpression('foo', 1), 1),
        ], [], 1);
        $else = null;
        $node = new IfNode($t, $else, 1);

        $variableGetterFoo = self::getVariableGetter('foo');
        $variableGetterBar = self::getVariableGetter('bar');
        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    echo {$variableGetterFoo};
}
EOF
        ];

        $t = new Node([
            new ConstantExpression(true, 1),
            new PrintNode(new NameExpression('foo', 1), 1),
            new ConstantExpression(false, 1),
            new PrintNode(new NameExpression('bar', 1), 1),
        ], [], 1);
        $else = null;
        $node = new IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    echo {$variableGetterFoo};
} elseif (false) {
    echo {$variableGetterBar};
}
EOF
        ];

        $t = new Node([
            new ConstantExpression(true, 1),
            new PrintNode(new NameExpression('foo', 1), 1),
        ], [], 1);
        $else = new PrintNode(new NameExpression('bar', 1), 1);
        $node = new IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    echo {$variableGetterFoo};
} else {
    echo {$variableGetterBar};
}
EOF
        ];

        return $tests;
    }
}
