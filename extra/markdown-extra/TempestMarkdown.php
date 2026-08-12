<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Markdown;

use Tempest\Markdown\Markdown;

class TempestMarkdown implements MarkdownInterface
{
    private $converter;

    public function __construct(?Markdown $converter = null)
    {
        $this->converter = $converter ?: new Markdown();
    }

    public function convert(string $body): string
    {
        return $this->converter->parse($body)->html;
    }
}
