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

class MarkdownRuntime
{
    private $converter;

    public function __construct(MarkdownInterface $converter)
    {
        $this->converter = $converter;
    }

    public function convert(string $body): string
    {
        // remove indentation
        if ($white = substr($body, 0, strspn($body, " \t\r\n\0\x0B"))) {
            $body = preg_replace("{^$white}m", '', $body);
        }

        return $this->converter->convert($body);
    }
}
