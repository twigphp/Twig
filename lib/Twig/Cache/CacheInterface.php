<?php

/*
 * This file is part of Twig.
 *
 * (c) 2014 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interface Twig_Cache_CacheInterface needs to be implemented by cache classes
 *
 * @author Andrew Tch <andrew@noop.lv>
 */
interface Twig_Cache_CacheInterface
{
    /**
     * Returns cache key depending on current implementation.
     *
     * @param string $className Template class name
     * @param string $prefix    Global template class prefix
     * @return string
     */
    public function getCacheKey($className, $prefix);

    /**
     * Clears all cached templates
     */
    public function clear();

    /**
     * Checks if given cache entity exists.
     *
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * Writes compiled template to cache.
     *
     * @param $key
     * @param $content
     */
    public function write($key, $content);

    /**
     * Loads cache file. This function returns nothing, it should require or eval() the compiled template.
     *
     * @param $key
     */
    public function load($key);

    /**
     * Returns timestamp of cached template..
     *
     * @param $key
     *
     * @return int
     */
    public function getTimestamp($key);
}
