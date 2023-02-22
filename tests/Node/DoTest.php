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

use Twig\Node\DoNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Test\ASTNodeTestCase;

class DoTest extends ASTNodeTestCase
{
    public function testConstructor()
    {
        $expr = new ConstantExpression('foo', 1);
        $node = new DoNode($expr, 1);

        $this->assertEquals($expr, $node->getNode('expr'));
    }

    public static function getTests()
    {
        $tests = [];

        $expr = new ConstantExpression('foo', 1);
        $node = new DoNode($expr, 1);
        $tests[] = [$node, "// line 1\n\"foo\";"];

        return $tests;
    }
}
