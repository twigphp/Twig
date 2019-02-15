<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_IfTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $t = new \Twig\Node\Node([
            new \Twig\Node\Expression\ConstantExpression(true, 1),
            new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('foo', 1), 1),
        ], [], 1);
        $else = null;
        $node = new \Twig\Node\IfNode($t, $else, 1);

        $this->assertEquals($t, $node->getNode('tests'));
        $this->assertFalse($node->hasNode('else'));

        $else = new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('bar', 1), 1);
        $node = new \Twig\Node\IfNode($t, $else, 1);
        $this->assertEquals($else, $node->getNode('else'));
    }

    public function getTests()
    {
        $tests = [];

        $t = new \Twig\Node\Node([
            new \Twig\Node\Expression\ConstantExpression(true, 1),
            new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('foo', 1), 1),
        ], [], 1);
        $else = null;
        $node = new \Twig\Node\IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    echo {$this->getVariableGetter('foo')};
}
EOF
        ];

        $t = new \Twig\Node\Node([
            new \Twig\Node\Expression\ConstantExpression(true, 1),
            new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('foo', 1), 1),
            new \Twig\Node\Expression\ConstantExpression(false, 1),
            new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('bar', 1), 1),
        ], [], 1);
        $else = null;
        $node = new \Twig\Node\IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    echo {$this->getVariableGetter('foo')};
} elseif (false) {
    echo {$this->getVariableGetter('bar')};
}
EOF
        ];

        $t = new \Twig\Node\Node([
            new \Twig\Node\Expression\ConstantExpression(true, 1),
            new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('foo', 1), 1),
        ], [], 1);
        $else = new \Twig\Node\PrintNode(new \Twig\Node\Expression\NameExpression('bar', 1), 1);
        $node = new \Twig\Node\IfNode($t, $else, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (true) {
    echo {$this->getVariableGetter('foo')};
} else {
    echo {$this->getVariableGetter('bar')};
}
EOF
        ];

        return $tests;
    }
}
