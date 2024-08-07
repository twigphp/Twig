<?php

namespace Twig\Tests\Node\Expression\Filter;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\RawFilter;
use Twig\Test\NodeTestCase;

class RawTest extends NodeTestCase
{
    public function testConstructor()
    {
        $filter = new RawFilter($node = new ConstantExpression('foo', 12));

        $this->assertSame(12, $filter->getTemplateLine());
        $this->assertSame('raw', $filter->getNode('filter')->getAttribute('value'));
        $this->assertSame($node, $filter->getNode('node'));
        $this->assertCount(0, $filter->getNode('arguments'));
    }

    public function getTests()
    {
        $node = new RawFilter(new ConstantExpression('foo', 12));

        return [
            [$node, '"foo"'],
        ];
    }
}
