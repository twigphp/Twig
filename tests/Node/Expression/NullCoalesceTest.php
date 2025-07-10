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
use Twig\Node\Expression\NullCoalesceExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Test\NodeTestCase;

/**
 * @group legacy
 */
class NullCoalesceTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        $left = new ContextVariable('foo', 1);
        $right = new ConstantExpression(2, 1);
        $node = new NullCoalesceExpression($left, $right, 1);

        return [[$node, "((// line 1\n\$context[\"foo\"]) ?? (2))"]];
    }
}
