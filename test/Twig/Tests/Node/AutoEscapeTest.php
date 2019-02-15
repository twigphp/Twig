<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Tests_Node_AutoEscapeTest extends \Twig\Test\NodeTestCase
{
    public function testConstructor()
    {
        $body = new \Twig\Node\Node([new \Twig\Node\TextNode('foo', 1)]);
        $node = new \Twig\Node\AutoEscapeNode(true, $body, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertTrue($node->getAttribute('value'));
    }

    public function getTests()
    {
        $body = new \Twig\Node\Node([new \Twig\Node\TextNode('foo', 1)]);
        $node = new \Twig\Node\AutoEscapeNode(true, $body, 1);

        return [
            [$node, "// line 1\necho \"foo\";"],
        ];
    }
}
