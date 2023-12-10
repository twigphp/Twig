<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Inky\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Extra\Inky\InkyExtension;

use function Twig\Extra\Inky\twig_inky;

/**
 * @group legacy
 */
class LegacyFunctionsTest extends TestCase
{
    public function testInlineCss()
    {
        $this->assertSame(InkyExtension::inky('<p>Foo</p>'), twig_inky('<p>Foo</p>'));
    }
}
