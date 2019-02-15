<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_Expression_ParentTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $node = new \Twig\Node\Expression\ParentExpression('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public function getTests()
    {
        $tests = [];
        $tests[] = [new \Twig\Node\Expression\ParentExpression('foo', 1), '$this->renderParentBlock("foo", $context, $blocks)'];

        return $tests;
    }
}
