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
            new TwigFilter('inky_to_html', 'Twig\\Extra\\Inky\\twig_inky', ['is_safe' => ['html']]),
        ];
    }
}

function twig_inky(string $body): string
{
    return false === ($html = Pinky\transformString($body)->saveHTML()) ? '' : $html;
}
