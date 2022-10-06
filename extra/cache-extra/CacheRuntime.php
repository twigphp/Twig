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

use Symfony\Contracts\Cache\CacheInterface;

class CacheRuntime
{
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
