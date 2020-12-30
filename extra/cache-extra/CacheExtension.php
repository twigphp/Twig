<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Extra\Cache;

use Twig\Extension\AbstractExtension;
use Twig\Extra\Cache\TokenParser\CacheTokenParser;

final class CacheExtension extends AbstractExtension
{
    public function getTokenParsers()
    {
        return [
            new CacheTokenParser(),
        ];
    }
}
