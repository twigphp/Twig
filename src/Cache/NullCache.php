<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Cache;

/**
 * Implements a no-cache strategy.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class NullCache implements CacheInterface
{
    /**
     * @param string $name
     * @param string $className
     * @return string
     */
    public function generateKey(string $name, string $className): string
    {
        return '';
    }

    /**
     * @param string $key
     * @param string $content
     */
    public function write(string $key, string $content): void
    {
    }

    /**
     * @param string $key
     */
    public function load(string $key): void
    {
    }

    /**
     * @param string $key
     * @return int
     */
    public function getTimestamp(string $key): int
    {
        return 0;
    }
}
