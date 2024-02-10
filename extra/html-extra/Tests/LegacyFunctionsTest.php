<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Html\Tests;

use PHPUnit\Framework\TestCase;
use Twig\Extra\Html\HtmlExtension;

/**
 * @group legacy
 */
class LegacyFunctionsTest extends TestCase
{
    public function testHtmlToMarkdown()
    {
        $this->assertSame(HtmlExtension::htmlClasses(['charset' => 'utf-8']), twig_html_classes(['charset' => 'utf-8']));
    }
}
