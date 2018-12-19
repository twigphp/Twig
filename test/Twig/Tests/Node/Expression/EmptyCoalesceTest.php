<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_EmptyCoalesceTest extends Twig_Test_NodeTestCase
{
    public function getTests()
    {
        $left = new Twig_Node_Expression_Name('foo', 1);
        $right = new Twig_Node_Expression_Constant(2, 1);
        $node = new Twig_Node_Expression_EmptyCoalesce($left, $right, 1);

        return array(array($node, "((empty(// line 1\n(\$context[\"foo\"] ?? null)) ? null : (\$context[\"foo\"] ?? null)) ?? (empty(2) ? null : 2))"));
    }
}
