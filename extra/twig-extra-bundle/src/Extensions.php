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

final class Extensions
{
    private const EXTENSIONS = [
        'html' => [
            'name' => 'html',
            'class' => HtmlExtension::class,
            'class_name' => 'HtmlExtension',
            'package' => 'twig/html-extra',
            'filters' => ['data_uri'],
            'functions' => ['html_classes'],
        ],
        'markdown' => [
            'name' => 'markdown',
            'class' => MarkdownExtension::class,
            'class_name' => 'MarkdownExtension',
            'package' => 'twig/markdown-extra',
            'filters' => ['html_to_markdown', 'markdown_to_html'],
        ],
        'intl' => [
            'name' => 'intl',
            'class' => IntlExtension::class,
            'class_name' => 'IntlExtension',
            'package' => 'twig/intl-extra',
            'filters' => ['country_name', 'currency_name', 'currency_symbol', 'language_name', 'country_timezones',
                'format_currency', 'format_number', 'format_decimal_number', 'format_currency_number',
                'format_percent_number', 'format_scientific_number', 'format_spellout_number', 'format_ordinal_number',
                'format_duration_number', 'format_date', 'format_datetime', 'format_time'
            ],
        ],
        'cssinliner' => [
            'name' => 'cssinliner',
            'class' => CssInlinerExtension::class,
            'class_name' => 'CssInlinerExtension',
            'package' => 'twig/cssinliner-extra',
            'filters' => ['inline_css'],
        ],
        'inky' => [
            'name' => 'inky',
            'class' => InkyExtension::class,
            'class_name' => 'InkyExtension',
            'package' => 'twig/inky-extra',
            'filters' => ['inky'],
        ],
    ];

    public static function getClasses(): array
    {
        return array_column(self::EXTENSIONS, 'class', 'name');
    }

    public static function getFilters(string $name): array
    {
        foreach (self::EXTENSIONS as $extension) {
            if (in_array($name, $extension['filters'])) {
                return [$extension['class_name'], $extension['package']];
            }
        }

        return [];
    }

    public static function getFunctions(string $name): array
    {
        foreach (self::EXTENSIONS as $extension) {
            if (in_array($name, $extension['functions'])) {
                return [$extension['class_name'], $extension['package']];
            }
        }

        return [];
    }
}
