<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Source;

class SourceTest extends TestCase
{
    public function testGetColumn(): void
    {
        $source = new Source("foo\nbarbaz\nqux", 'index');

        // first line: column is the 1-based offset
        $this->assertSame(1, $source->getColumn(0));
        $this->assertSame(3, $source->getColumn(2));

        // a "\n" closes the line; the next character starts a new line at column 1
        $this->assertSame(1, $source->getColumn(4));
        $this->assertSame(4, $source->getColumn(7));

        // "\r\n" and "\r" also close the line
        $this->assertSame(1, (new Source("foo\r\nbar", 'index'))->getColumn(5));
        $this->assertSame(1, (new Source("foo\rbar", 'index'))->getColumn(4));

        // an unknown offset yields null
        $this->assertNull($source->getColumn(-1));
    }
}
