<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_Unary_PosTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\ConstantExpression(1, 1);
        $node = new \Twig\Node\Expression\Unary\PosUnary($expr, 1);

        $this->assertEquals($expr, $node->getNode('node'));
    }

    public function getTests()
    {
        $node = new \Twig\Node\Expression\ConstantExpression(1, 1);
        $node = new \Twig\Node\Expression\Unary\PosUnary($node, 1);

        return [
            [$node, '+1'],
        ];
    }
}
