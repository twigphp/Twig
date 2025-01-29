<?php

namespace Twig\Tests\Node\Expression\Binary;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\Binary\NullCoalesceBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Test\NodeTestCase;

class NullCoalesceTest extends NodeTestCase
{
    public static function provideTests(): iterable
    {
        $left = new ContextVariable('foo', 1);
        $right = new ConstantExpression(2, 1);
        $node = new NullCoalesceBinary($left, $right, 1);

        return [[$node, "(((// line 1\narray_key_exists(\"foo\", \$context) &&  !(null === \$context[\"foo\"]))) ? (\$context[\"foo\"]) : (2))"]];
    }
}
