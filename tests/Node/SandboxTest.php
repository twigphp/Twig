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

use Twig\Node\SandboxNode;
use Twig\Node\TextNode;
use Twig\Test\NodeTestCase;

class SandboxTest extends NodeTestCase
{
    public function testConstructor()
    {
        $body = new TextNode('foo', 1);
        $node = new SandboxNode($body, 1);

        $this->assertEquals($body, $node->getNode('body'));
    }

    public function getTests()
    {
        $tests = [];

        $body = new TextNode('foo', 1);
        $node = new SandboxNode($body, 1);

        $tests[] = [$node, <<<EOF
// line 1
if (!\$alreadySandboxed = \$this->sandbox->isSandboxed()) {
    \$this->sandbox->enableSandbox();
}
try {
    yield "foo";
} finally {
    if (!\$alreadySandboxed) {
        \$this->sandbox->disableSandbox();
    }
}
EOF
        ];

        return $tests;
    }
}
