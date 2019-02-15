<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_PrintTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $expr = new \Twig\Node\Expression\ConstantExpression('foo', 1);
        $node = new \Twig\Node\PrintNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public function getTests()
    {
        $tests = [];
        $tests[] = [new \Twig\Node\PrintNode(new \Twig\Node\Expression\ConstantExpression('foo', 1), 1), "// line 1\necho \"foo\";"];

        return $tests;
    }
}
