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

use League\HTMLToMarkdown\HtmlConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MarkdownExtension extends AbstractExtension
{
    private const MAX_CACHED_CONVERTERS = 100;

    /**
     * @var array<string, HtmlConverter>
     */
    private static array $converters = [];

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown_to_html', ['Twig\\Extra\\Markdown\\MarkdownRuntime', 'convert'], ['is_safe' => ['html']]),
            new TwigFilter('html_to_markdown', self::htmlToMarkdown(...)),
        ];
    }

    /**
     * @internal
     */
    public static function htmlToMarkdown(string $body, array $options = []): string
    {
        if (!class_exists(HtmlConverter::class)) {
            throw new \LogicException('You cannot use the "html_to_markdown" filter as league/html-to-markdown is not installed; try running "composer require league/html-to-markdown".');
        }

        $options += [
            'hard_break' => true,
            'strip_tags' => true,
            'remove_nodes' => 'head style',
        ];

        if (!isset(self::$converters[$key = serialize($options)])) {
            if (\count(self::$converters) >= self::MAX_CACHED_CONVERTERS) {
                array_shift(self::$converters);
            }
            self::$converters[$key] = new HtmlConverter($options);
        }

        return self::$converters[$key]->convert($body);
    }
}
