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

use Parsedown;

class ErusevMarkdown implements MarkdownInterface
{
    private $converter;

    public function __construct(Parsedown $converter = null)
    {
        $this->converter = $converter ?: new Parsedown();
    }

    public function convert(string $body): string
    {
        return $this->converter->text($body);
    }
}
