<?php

namespace Twig\Tests\Node\Expression;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Test\NodeTestCase;

class ArrayTest extends NodeTestCase
{
    public function testConstructor()
    {
        $elements = [new ConstantExpression('foo', 1), $foo = new ConstantExpression('bar', 1)];
        $node = new ArrayExpression($elements, 1);

        $this->assertEquals($foo, $node->getNode('1'));
    }

    public static function provideTests(): iterable
    {
        $elements = [
            new ConstantExpression('foo', 1),
            new ConstantExpression('bar', 1),

            new ConstantExpression('bar', 1),
            new ConstantExpression('foo', 1),
        ];
        $node = new ArrayExpression($elements, 1);

        return [
            [$node, '["foo" => "bar", "bar" => "foo"]'],
        ];
    }
}
