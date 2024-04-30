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

use Twig\Node\TypeHintNode;
use Twig\Test\NodeTestCase;

class TypeHintTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new TypeHintNode('interval', '\DateInterval|string', 1);

        $this->assertEquals('interval', $node->getAttribute('name'));
        $this->assertEquals('\DateInterval|string', $node->getAttribute('type'));
    }

    public function getTests()
    {
        $tests = [];

        $node = new TypeHintNode('interval', '\DateInterval|string', 1);
        $tests[] = [$node, "// line 1"];

        return $tests;
    }
}
