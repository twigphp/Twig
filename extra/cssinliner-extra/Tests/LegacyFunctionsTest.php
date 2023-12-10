<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\CssInliner\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Extra\CssInliner\CssInlinerExtension;

use function Twig\Extra\CssInliner\twig_inline_css;

/**
 * @group legacy
 */
class LegacyFunctionsTest extends TestCase
{
    public function testInlineCss()
    {
        $this->assertSame(CssInlinerExtension::inlineCss('<p>body</p>', 'p { color: red }'), twig_inline_css('<p>body</p>', 'p { color: red }'));
    }
}
