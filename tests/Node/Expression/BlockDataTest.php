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

use Twig\Node\Expression\BlockDataExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Test\NodeTestCase;

class BlockDataTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        yield [new BlockDataExpression(new ConstantExpression('foo', 1), null, 1), '$this->renderBlockData("foo", $context, $blocks)'];
        yield [new BlockDataExpression(new ConstantExpression('foo', 1), new ConstantExpression('bar.twig', 1), 1), '$this->load("bar.twig", 1)->renderBlockData("foo", $context)'];
    }
}
