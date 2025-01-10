<?php

namespace Twig\Tests;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Twig\Extension\EscaperExtension;

class EscaperTest extends TestCase
{
    public function testLastModified()
    {
        $this->assertGreaterThan(1000000000, (new EscaperExtension())->getLastModified());
    }
}
