<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Markdown\Tests;

use PHPUnit\Framework\TestCase;

use function Twig\Extra\Markdown\html_to_markdown;

use Twig\Extra\Markdown\MarkdownExtension;

/**
 * @group legacy
 */
class LegacyFunctionsTest extends TestCase
{
    public function testHtmlToMarkdown()
    {
        $this->assertSame(MarkdownExtension::htmlToMarkdown('<p>foo</p>'), html_to_markdown('<p>foo</p>'));
    }
}
