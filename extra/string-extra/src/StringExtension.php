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

use Symfony\Component\String\UnicodeString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class StringExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('u', [$this, 'createUnicodeString']),
        ];
    }

    public function createUnicodeString(?string $text): UnicodeString
    {
        return new UnicodeString($text ?? '');
    }
}
