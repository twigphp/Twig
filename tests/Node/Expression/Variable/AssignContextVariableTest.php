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

use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Test\NodeTestCase;

class AssignContextVariableTest extends NodeTestCase
{
    public function testConstructor()
    {
        $node = new AssignContextVariable('foo', 1);

        $this->assertEquals('foo', $node->getAttribute('name'));
    }

    public static function provideTests(): iterable
    {
        $node = new AssignContextVariable('foo', 1);

        return [
            [$node, '$context["foo"]'],
        ];
    }
}
