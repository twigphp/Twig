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

use Michelf\MarkdownExtra;

class MichelfMarkdown implements MarkdownInterface
{
    private $converter;

    public function __construct(?MarkdownExtra $converter = null)
    {
        if (null === $converter) {
            $converter = new MarkdownExtra();
            $converter->hard_wrap = true;
        }

        $this->converter = $converter;
    }

    public function convert(string $body): string
    {
        return $this->converter->transform($body);
    }
}
