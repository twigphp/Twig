<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_NullCoalesceTest extends \Twig\Test\NodeTestCase
{
    public function getTests()
    {
        $left = new \Twig\Node\Expression\NameExpression('foo', 1);
        $right = new \Twig\Node\Expression\ConstantExpression(2, 1);
        $node = new \Twig\Node\Expression\NullCoalesceExpression($left, $right, 1);

        return [[$node, "((// line 1\n\$context[\"foo\"]) ?? (2))"]];
    }
}
