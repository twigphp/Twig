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

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\BlockNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class BlockTest extends NodeTestCase
{
    public function testConstructor()
    {
        $body = new TextNode('foo', 1);
        $node = new BlockNode('foo', $body, 1);

        $this->assertEquals($body, $node->getNode('body'));
        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];
        $tests[] = [new BlockNode('foo', new TextNode('foo', 1), 1), <<<EOF
// line 1
/**
 * @return iterable<null|scalar|\Stringable>
 */
public function block_foo(array \$context, array \$blocks = []): iterable
{
    \$macros = \$this->macros;
    yield "foo";
    yield from [];
}
EOF
            , new Environment(new ArrayLoader()),
        ];

        return $tests;
    }
}
