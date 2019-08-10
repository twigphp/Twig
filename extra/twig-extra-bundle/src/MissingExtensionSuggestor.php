<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\TwigExtraBundle;

use Twig\Error\SyntaxError;

final class MissingExtensionSuggestor
{
    private const FILTERS = [
        'data_uri' => ['HtmlExtension', 'twig/html-extra'],
        'markdown_to_html' => ['MarkdownExtension', 'twig/markdown-extra'],
        'html_to_markdown' => ['MarkdownExtension', 'twig/markdown-extra'],
    ];

    private const FUNCTIONS = [
        'html_classes' => ['HtmlExtension', 'twig/html-extra'],
    ];

    public function suggestFilter(string $name): bool
    {
        if (isset(self::FILTERS[$name])) {
            throw new SyntaxError(sprintf('The "%s" filter is part of the %s, which is not installed/enabled; try running "composer require %s twig/extra-bundle".', $name, self::FILTERS[$name][0], self::FILTERS[$name][1]));
        }

        return false;
    }

    public function suggestFunction(string $name): bool
    {
        if (isset(self::FUNCTIONS[$name])) {
            throw new SyntaxError(sprintf('The "%s" function is part of the %s, which is not installed/enabled; try running "composer require %s twig/extra-bundle".', $name, self::FILTERS[$name][0], self::FILTERS[$name][1]));
        }

        return false;
    }
}
