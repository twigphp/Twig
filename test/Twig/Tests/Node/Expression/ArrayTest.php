<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_ArrayTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $elements = [new \Twig\Node\Expression\ConstantExpression('foo', 1), $foo = new \Twig\Node\Expression\ConstantExpression('bar', 1)];
        $node = new \Twig\Node\Expression\ArrayExpression($elements, 1);

        $this->assertEquals($foo, $node->getNode(1));
    }

    public function getTests()
    {
        $elements = [
            new \Twig\Node\Expression\ConstantExpression('foo', 1),
            new \Twig\Node\Expression\ConstantExpression('bar', 1),

            new \Twig\Node\Expression\ConstantExpression('bar', 1),
            new \Twig\Node\Expression\ConstantExpression('foo', 1),
        ];
        $node = new \Twig\Node\Expression\ArrayExpression($elements, 1);

        return [
            [$node, '["foo" => "bar", "bar" => "foo"]'],
        ];
    }
}
