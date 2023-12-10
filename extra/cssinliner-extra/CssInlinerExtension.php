<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\CssInliner;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CssInlinerExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('inline_css', [self::class, 'inlineCss'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @internal
     */
    public static function inlineCss(string $body, string ...$css): string
    {
        static $inliner;
        if (null === $inliner) {
            $inliner = new CssToInlineStyles();
        }

        return $inliner->convert($body, implode("\n", $css));
    }
}
