<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\EmptyCoalesceExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Test\NodeTestCase;

class Twig_Tests_Node_Expression_EmptyCoalesceTest extends NodeTestCase
{
    public function getTests()
    {
        $left = new NameExpression('foo', 1);
        $right = new ConstantExpression(2, 1);
        $node = new EmptyCoalesceExpression($left, $right, 1);

        return array(array($node, "((".EmptyCoalesceExpression::class."::empty(// line 1\n(\$context[\"foo\"] ?? null)) ? null : (\$context[\"foo\"] ?? null)) ?? (".EmptyCoalesceExpression::class."::empty(2) ? null : 2))"));
    }
}
