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
        return $this->converter->convert(self::stripCommonIndentation($body));
    }

    /**
     * Removes the indentation shared by all non-blank lines.
     *
     * This lets authors indent a `{% apply markdown_to_html %}` block to match
     * the surrounding template without that indentation leaking into Markdown
     * (where leading whitespace is significant, e.g. code blocks).
     */
    private static function stripCommonIndentation(string $body): string
    {
        $lines = explode("\n", $body);

        $indent = null;
        foreach ($lines as $line) {
            if ('' === trim($line)) {
                continue;
            }

            $lineIndent = substr($line, 0, strspn($line, " \t"));
            if (null === $indent) {
                $indent = $lineIndent;
                continue;
            }

            $max = min(\strlen($indent), \strlen($lineIndent));
            $common = 0;
            while ($common < $max && $indent[$common] === $lineIndent[$common]) {
                ++$common;
            }
            $indent = substr($indent, 0, $common);

            if ('' === $indent) {
                return $body;
            }
        }

        if (null === $indent || '' === $indent) {
            return $body;
        }

        $length = \strlen($indent);
        foreach ($lines as $i => $line) {
            if (str_starts_with($line, $indent)) {
                $lines[$i] = substr($line, $length);
            }
        }

        return implode("\n", $lines);
    }
}
