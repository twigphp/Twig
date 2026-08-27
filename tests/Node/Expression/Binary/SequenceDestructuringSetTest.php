<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node\Expression\Binary;

use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\SequenceDestructuringSetBinary;
use Twig\Node\Expression\EmptyExpression;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Test\NodeTestCase;

class SequenceDestructuringSetTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        $left = new ArrayExpression([], 1);
        $left->addElement(new AssignContextVariable('first', 1));
        $left->addElement(new EmptyExpression(1));
        $left->addElement(new AssignContextVariable('third', 1));
        $node = new SequenceDestructuringSetBinary($left, new ContextVariable('values', 1), 1);

        return [
            [$node, <<<'EOF'
// line 1
[(($_v0 = ($context["values"] ?? null)) instanceof \Traversable ? CoreExtension::destructureSequence($context, [0 => "first", 1 => null, 2 => "third"], $_v0) : ([$context["first"], , $context["third"]] = array_pad($_v0, 3, null))), $_v0 = null][0]
EOF
            ],
        ];
    }
}
