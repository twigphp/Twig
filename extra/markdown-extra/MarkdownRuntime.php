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
        $body = $this->commonWhitespace($body);
        $body = $this->removeIndentation($body);

        return $this->converter->convert($body);
    }

    private function commonWhitespace(string $body): string
    {
        return str_replace(["\t", "\0", "\x0B"], ['    ', '', ''], $body);
    }

    private function removeIndentation(string $body): string
    {
        $indent = $this->minIndentations($body);
        if ($indent > 0) {
            $body = preg_replace("{^ {{$indent}}}m", '', $body);
        }

        return $body;
    }

    private function minIndentations(string $body): int
    {
        $non_empty_lines = preg_split('%(\r|\n)%', $body, -1, PREG_SPLIT_NO_EMPTY);

        $list = [];
        foreach ($non_empty_lines as $line)
        {
            $len = strspn($line, " ");
            if ($len === 0)
                return 0;

            $list[] = $len;
        }

        return min($list);
    }
}
