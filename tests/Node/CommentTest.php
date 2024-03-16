<?php

namespace Twig\Tests\Node;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\CommentNode;
use Twig\Test\NodeTestCase;

class CommentTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new CommentNode('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('text'));
    }

    public function getTests()
    {
        return [
            [new CommentNode('foo', 1), ""],
        ];
    }
}
