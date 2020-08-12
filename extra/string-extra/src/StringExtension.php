<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\String;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class StringExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('u', [$this, 'createUnicodeString']),
            new TwigFilter('slug', [$this, 'createSlug']),
        ];
    }

    public function createUnicodeString(?string $text): UnicodeString
    {
        return new UnicodeString($text ?? '');
    }

    public function createSlug(?string $string, ?string $separator = '-', string $locale = null): string
    {
        return (new AsciiSlugger())->slug($string, $separator, $locale);
    }
}
