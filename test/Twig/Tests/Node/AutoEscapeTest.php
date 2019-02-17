<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Test\NodeTestCase;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Node\AutoEscapeNode;

class Twig_Tests_Node_AutoEscapeTest extends NodeTestCase
{
    public function testConstructor()
    {
        $body = new Node([new TextNode('foo', 1)]);
        $node = new AutoEscapeNode(true, $body, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertTrue($node->getAttribute('value'));
    }

    public function getTests()
    {
        $body = new Node([new TextNode('foo', 1)]);
        $node = new AutoEscapeNode(true, $body, 1);

        return [
            [$node, "// line 1\necho \"foo\";"],
        ];
    }
}
