<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Inky;

use Pinky;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class InkyExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('inky_to_html', [self::class, 'inky'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @internal
     */
    public static function inky(string $body): string
    {
        return false === ($html = Pinky\transformString($body)->saveHTML()) ? '' : $html;
    }
}
