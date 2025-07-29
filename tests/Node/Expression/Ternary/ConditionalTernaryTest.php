<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests\Node\Expression;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Test\NodeTestCase;

class ConditionalTernaryTest extends NodeTestCase
{
    public function testConstructor()
    {
        $test = new ConstantExpression(1, 1);
        $left = new ConstantExpression(2, 1);
        $right = new ConstantExpression(3, 1);
        $node = new ConditionalTernary($test, $left, $right, 1);

        $this->assertEquals($test, $node->getNode('test'));
        $this->assertEquals($left, $node->getNode('left'));
        $this->assertEquals($right, $node->getNode('right'));
    }

    public static function provideTests(): iterable
    {
        $tests = [];

        $test = new ConstantExpression(1, 1);
        $left = new ConstantExpression(2, 1);
        $right = new ConstantExpression(3, 1);
        $node = new ConditionalTernary($test, $left, $right, 1);
        $tests[] = [$node, '((1) ? (2) : (3))'];

        return $tests;
    }
}
